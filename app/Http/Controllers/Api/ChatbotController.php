
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMagic;
use App\Models\DanhMucDichVu;
use App\Services\Chat\AssistantSoulConfigService;
use App\Services\Chat\ChatContextBuilderService;
use App\Services\Chat\ChatMemoryService;
use App\Services\Chat\OpenAiChatService;
use App\Services\Chat\ServiceSearchIntentService;
use App\Services\Chat\SimilarIssueSearchService;
use App\Services\Chat\TechnicianRecommendationService;
use App\Services\TravelFeeConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly SimilarIssueSearchService $similarIssueSearchService,
        private readonly TechnicianRecommendationService $technicianRecommendationService,
        private readonly AssistantSoulConfigService $assistantSoulConfigService,
        private readonly ChatContextBuilderService $chatContextBuilderService,
        private readonly ChatMemoryService $chatMemoryService,
        private readonly OpenAiChatService $openAiChatService,
        private readonly ServiceSearchIntentService $serviceSearchIntentService,
        private readonly TravelFeeConfigService $travelFeeConfigService
    ) {
    }

    public function history(Request $request)
    {
        [$userId, $guestToken] = $this->resolveActor($request);

        $messages = $this->conversationQuery($userId, $guestToken)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChatMagic $message) => [
                'id' => $message->id,
                'sender' => $message->sender,
                'text' => $message->text,
                'meta' => $message->meta ?? new \stdClass(),
                'created_at' => optional($message->created_at)->toISOString(),
            ]);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        [$userId, $guestToken] = $this->resolveActor($request);
        $userText = trim((string) $request->input('text', ''));

        $userMessage = $this->createMessage($userId, $guestToken, 'user', $userText);
        $this->chatMemoryService->rememberFromMessage($userText, $userId, $guestToken, $userMessage->id);

        $historyMessages = $this->conversationQuery($userId, $guestToken)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChatMagic $message) => [
                'sender' => $message->sender,
                'text' => $message->text,
            ])->all();

        $assistantPayload = $this->buildAssistantPayload($userText, $historyMessages, $userId, $guestToken);

        $assistantMessage = $this->createMessage(
            $userId,
            $guestToken,
            'assistant',
            $assistantPayload['assistant_text'],
            [
                'cases' => $assistantPayload['cases'],
                'technicians' => $assistantPayload['technicians'],
                'services' => $assistantPayload['services'] ?? [],
                'services_more_url' => $assistantPayload['services_more_url'] ?? null,
                'youtube_links' => $assistantPayload['youtube_links'],
                'model' => $assistantPayload['model'],
                'ai' => $assistantPayload['ai'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'OK',
            'data' => array_merge($assistantPayload, [
                'assistant_message_id' => $assistantMessage->id,
            ]),
        ]);
    }

    public function syncGuest(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $guestToken = (string) $request->attributes->get('chat_guest_token', $request->cookie('guest_token', ''));
        if ($guestToken === '') {
            return response()->json(['message' => 'Guest token missing'], 400);
        }

        $updatedRows = ChatMagic::query()
            ->where('guest_token', $guestToken)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);

        $this->chatMemoryService->syncGuestToUser($guestToken, $user->id);

        return response()->json([
            'message' => 'Synced',
            'synced_count' => $updatedRows,
        ]);
    }

    public function aiResponse(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $text = trim((string) $request->input('text', ''));
        $historyMessages = [['sender' => 'user', 'text' => $text]];
        $assistantPayload = $this->buildAssistantPayload($text, $historyMessages, $user->id, null);

        return response()->json([
            'assistant_text' => $assistantPayload['assistant_text'] ?? '',
            'cases' => $assistantPayload['cases'] ?? [],
            'technicians' => $assistantPayload['technicians'] ?? [],
            'services' => $assistantPayload['services'] ?? [],
            'services_more_url' => $assistantPayload['services_more_url'] ?? null,
            'youtube_links' => $assistantPayload['youtube_links'] ?? [],
            'model' => $assistantPayload['model'] ?? null,
            'ai' => $assistantPayload['ai'] ?? null,
            'debug' => [
                'message_count' => count($historyMessages) + 2,
            ],
        ]);
    }

    private function createMessage(
        ?int $userId,
        ?string $guestToken,
        string $sender,
        string $text,
        ?array $meta = null
    ): ChatMagic {
        return ChatMagic::query()->create([
            'user_id' => $userId,
            'guest_token' => $guestToken,
            'sender' => $sender,
            'text' => $text,
            'meta' => $meta,
        ]);
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function resolveActor(Request $request): array
    {
        $userId = $request->user()?->id;
        $guestToken = (string) $request->attributes->get('chat_guest_token', $request->cookie('guest_token', ''));
        $guestToken = $guestToken !== '' ? $guestToken : null;

        return [$userId, $guestToken];
    }

    private function conversationQuery(?int $userId, ?string $guestToken)
    {
        return ChatMagic::query()->where(function ($query) use ($userId, $guestToken): void {
            if ($userId !== null) {
                $query->where('user_id', $userId);
                if ($guestToken !== null) {
                    $query->orWhere(function ($subQuery) use ($guestToken): void {
                        $subQuery->where('guest_token', $guestToken)->whereNull('user_id');
                    });
                }
                return;
            }

            if ($guestToken !== null) {
                $query->where('guest_token', $guestToken);
                return;
            }

            // Defensive fallback.
            $query->whereRaw('1 = 0');
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $historyMessages
     * @return array<string, mixed>
     */
    private function buildAssistantPayload(string $text, array $historyMessages, ?int $userId, ?string $guestToken): array
    {
        if ($this->containsEmergencyKeyword($text)) {
            $technicians = $this->technicianRecommendationService->recommend($text, [], 3);

            return $this->finalizeAssistantPayload($text, [
                'assistant_text' => $this->buildEmergencyAssistantText($technicians),
                'cases' => [],
                'technicians' => $technicians,
                'youtube_links' => [],
                'model' => null,
                'ai' => $this->deterministicAiMeta('emergency_rule'),
            ]);
        }

        if ($this->isStoreHotlineQuestion($text)) {
            return $this->finalizeAssistantPayload(
                $text,
                $this->buildStoreInfoPayload('store_hotline_rule', $this->buildStoreHotlineAssistantText())
            );
        }

        if ($this->isStoreOpeningHoursQuestion($text)) {
            return $this->finalizeAssistantPayload(
                $text,
                $this->buildStoreInfoPayload('store_hours_rule', $this->buildStoreOpeningHoursAssistantText())
            );
        }

        if ($this->isStoreTransportFeeQuestion($text)) {
            return $this->finalizeAssistantPayload(
                $text,
                $this->buildStoreInfoPayload('store_transport_fee_rule', $this->buildStoreTransportFeeAssistantText())
            );
        }

        if ($this->isStoreMapQuestion($text)) {
            return $this->finalizeAssistantPayload(
                $text,
                $this->buildStoreInfoPayload('store_map_rule', $this->buildStoreMapAssistantText())
            );
        }

        if ($this->isStoreAddressQuestion($text)) {
            return $this->finalizeAssistantPayload(
                $text,
                $this->buildStoreInfoPayload('store_address_rule', $this->buildStoreAddressAssistantText())
            );
        }

        if ($this->isServiceCatalogQuestion($text)) {
            return $this->finalizeAssistantPayload($text, [
                'assistant_text' => $this->buildServiceCatalogAssistantText(),
                'cases' => [],
                'technicians' => [],
                'services' => $this->buildServiceCatalogCards(),
                'services_more_url' => route('customer.search'),
                'youtube_links' => [],
                'model' => null,
                'ai' => $this->deterministicAiMeta('service_catalog_rule'),
            ]);
        }

        $serviceSearchIntent = $this->serviceSearchIntentService->detect($text);
        if ($serviceSearchIntent['is_unsupported_service_search']) {
            return $this->finalizeAssistantPayload($text, [
                'assistant_text' => $this->buildUnsupportedServiceAssistantText(
                    (string) ($serviceSearchIntent['requested_service_name'] ?? '')
                ),
                'cases' => [],
                'technicians' => [],
                'youtube_links' => [],
                'model' => null,
                'ai' => $this->deterministicAiMeta('unsupported_service_rule'),
            ]);
        }

        if ($serviceSearchIntent['is_service_search']) {
            $technicians = $this->technicianRecommendationService->recommend(
                $text,
                [],
                3,
                $serviceSearchIntent['service_id']
            );

            return $this->finalizeAssistantPayload($text, [
                'assistant_text' => $this->buildServiceSearchAssistantText(
                    (string) $serviceSearchIntent['service_name'],
                    $technicians
                ),
                'cases' => [],
                'technicians' => $technicians,
                'youtube_links' => [],
                'model' => null,
                'ai' => $this->deterministicAiMeta('service_search_rule'),
            ]);
        }

        $cases = $this->similarIssueSearchService->search($text, 3);
        $technicians = $this->technicianRecommendationService->recommend($text, $cases, 3);
        $memories = $this->chatMemoryService->recallForPrompt($text, $userId, $guestToken, 5);
        $storeInfo = $this->storeInfo();
        $youtubeLinks = [];

        $context = $this->chatContextBuilderService->build(
            $historyMessages,
            $cases,
            $technicians,
            $memories,
            $storeInfo,
            $youtubeLinks
        );

        $aiPayload = $this->openAiChatService->generateResponse($context['messages']);

        return $this->finalizeAssistantPayload($text, [
            'assistant_text' => (string) ($aiPayload['assistant_text'] ?? 'Tôi đã ghi nhận vấn đề. Bạn xem thông tin gợi ý bên dưới để chọn thợ phù hợp.'),
            'cases' => $cases,
            'technicians' => $technicians,
            'youtube_links' => $youtubeLinks,
            'model' => $aiPayload['model'] ?? null,
            'ai' => $aiPayload['ai'] ?? $this->deterministicAiMeta('system_data_only'),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $technicians
     */
    private function buildServiceSearchAssistantText(string $serviceName, array $technicians): string
    {
        if ($serviceName === '') {
            return $technicians === []
                ? 'Hiện chưa có thợ phù hợp.'
                : 'Đây là các thợ phù hợp.';
        }

        return $technicians === []
            ? 'Hiện chưa có thợ ' . mb_strtolower($serviceName, 'UTF-8') . ' phù hợp.'
            : 'Đây là các thợ ' . mb_strtolower($serviceName, 'UTF-8') . ' phù hợp.';
    }

    private function buildUnsupportedServiceAssistantText(string $requestedServiceName): string
    {
        $requestedServiceName = trim($requestedServiceName);

        if ($requestedServiceName === '') {
            return 'Hiện tại dịch vụ này chưa có trong cửa hàng. Bạn vui lòng chọn dịch vụ khác.';
        }

        return 'Hiện tại dịch vụ sửa ' . mb_strtolower($requestedServiceName, 'UTF-8') . ' chưa có trong cửa hàng. Bạn vui lòng chọn dịch vụ khác.';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function finalizeAssistantPayload(string $userText, array $payload): array
    {
        $assistantText = trim((string) ($payload['assistant_text'] ?? ''));
        $assistantText = $this->trimUnrequestedAssistantLines($assistantText, $userText);
        $assistantText = $this->appendRelatedFollowUpQuestion(
            $assistantText,
            $userText,
            (array) ($payload['cases'] ?? []),
            (array) ($payload['technicians'] ?? []),
            (string) data_get($payload, 'ai.status', '')
        );

        $payload['assistant_text'] = $assistantText;

        return $payload;
    }

    private function trimUnrequestedAssistantLines(string $assistantText, string $userText): string
    {
        if ($assistantText === '') {
            return '';
        }

        $keepProcess = $this->isProcessQuestion($userText);
        $keepHotline = $this->isStoreHotlineQuestion($userText);
        $keepAddress = $this->isStoreAddressQuestion($userText);
        $keepOpeningHours = $this->isStoreOpeningHoursQuestion($userText);
        $keepTransportFee = $this->isStoreTransportFeeQuestion($userText);
        $keepStoreMap = $this->isStoreMapQuestion($userText);
        $keepPrice = $this->isPriceQuestion($userText) || $this->serviceSearchIntentService->detect($userText)['is_service_search'];

        $lines = preg_split('/\R+/u', $assistantText) ?: [];
        $filteredLines = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            $normalizedLine = \App\Services\Chat\TextNormalizer::normalize($trimmedLine);

            if (str_starts_with($normalizedLine, 'quy trinh tiep nhan') && !$keepProcess) {
                continue;
            }

            if ((str_starts_with($normalizedLine, 'hotline') || str_contains($normalizedLine, 'goi truc tiep')) && !$keepHotline) {
                continue;
            }

            if (str_starts_with($normalizedLine, 'dia chi cua hang') && !$keepAddress) {
                continue;
            }

            if (str_starts_with($normalizedLine, 'gio mo cua') && !$keepOpeningHours) {
                continue;
            }

            if ((str_starts_with($normalizedLine, 'phi mang den cua hang') || str_starts_with($normalizedLine, 'neu ban can cua hang ho tro cho')) && !$keepTransportFee) {
                continue;
            }

            if ((str_starts_with($normalizedLine, 'ban do cua hang') || str_starts_with($normalizedLine, 'cua hang o')) && !$keepStoreMap) {
                continue;
            }

            if (str_starts_with($normalizedLine, 'gia tham khao') && !$keepPrice) {
                continue;
            }

            $filteredLines[] = $trimmedLine;
        }

        return $filteredLines === []
            ? $assistantText
            : implode("\n", $filteredLines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cases
     * @param  array<int, array<string, mixed>>  $technicians
     */
    private function appendRelatedFollowUpQuestion(
        string $assistantText,
        string $userText,
        array $cases,
        array $technicians,
        string $aiStatus
    ): string {
        $assistantText = trim($assistantText);
        if ($assistantText === '' || preg_match('/\?\s*$/u', $assistantText) === 1) {
            return $assistantText;
        }

        $followUpQuestion = $this->buildRelatedFollowUpQuestion($userText, $cases, $technicians, $aiStatus);
        if ($followUpQuestion === null || $followUpQuestion === '') {
            return $assistantText;
        }

        return $assistantText . "\n\n" . $followUpQuestion;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cases
     * @param  array<int, array<string, mixed>>  $technicians
     */
    private function buildRelatedFollowUpQuestion(string $userText, array $cases, array $technicians, string $aiStatus): ?string
    {
        if ($this->containsEmergencyKeyword($userText)) {
            return 'Bạn có muốn tôi tìm thợ điện phù hợp ngay bây giờ không?';
        }

        if ($aiStatus === 'unsupported_service_rule') {
            return 'Bạn có muốn tôi gợi ý dịch vụ khác đang có trong cửa hàng không?';
        }

        if ($aiStatus === 'service_catalog_rule') {
            return 'Bạn đang cần hỗ trợ về dịch vụ nào trong số này?';
        }

        if ($this->isStoreHotlineQuestion($userText)) {
            return 'Bạn có muốn tôi tìm thợ phù hợp cho trường hợp này không?';
        }

        if ($this->isStoreAddressQuestion($userText) || $this->isStoreMapQuestion($userText)) {
            return 'Bạn có muốn tôi tìm thợ đến tận nơi thay vì mang thiết bị tới cửa hàng không?';
        }

        if ($this->isStoreOpeningHoursQuestion($userText) || $this->isStoreTransportFeeQuestion($userText)) {
            return 'Bạn có muốn tôi gợi ý nên sửa tại nhà hay mang tới cửa hàng không?';
        }

        $serviceSearchIntent = $this->serviceSearchIntentService->detect($userText);
        if ($serviceSearchIntent['is_service_search']) {
            return $technicians === []
                ? 'Bạn có muốn tôi tìm thêm thợ phù hợp ở dịch vụ này không?'
                : 'Bạn có muốn tôi mở hồ sơ hoặc đặt lịch với một thợ phù hợp không?';
        }

        $serviceName = $this->resolveRelevantServiceName($userText, $cases);
        if ($serviceName !== null) {
            return 'Bạn có muốn tôi tìm thợ ' . mb_strtolower($serviceName, 'UTF-8') . ' phù hợp không?';
        }

        if ($technicians !== [] || $cases !== [] || $aiStatus === 'system_fallback_overloaded') {
            return 'Bạn có muốn tôi tìm thợ phù hợp cho trường hợp này không?';
        }

        return 'Bạn có muốn tôi hỏi thêm vài chi tiết để khoanh vùng lỗi chính xác hơn không?';
    }

    /**
     * @param  array<int, array<string, mixed>>  $cases
     */
    private function resolveRelevantServiceName(string $userText, array $cases): ?string
    {
        $serviceSearchIntent = $this->serviceSearchIntentService->detect($userText);
        $serviceName = trim((string) ($serviceSearchIntent['service_name'] ?? ''));
        if ($serviceName !== '') {
            return $serviceName;
        }

        $caseServiceName = trim((string) ($cases[0]['service_type'] ?? $cases[0]['service_name'] ?? ''));
        if ($caseServiceName !== '') {
            return $caseServiceName;
        }

        return $this->detectMentionedServiceName($userText);
    }

    private function detectMentionedServiceName(string $userText): ?string
    {
        $normalizedMessage = \App\Services\Chat\TextNormalizer::normalize($userText);
        if ($normalizedMessage === '') {
            return null;
        }

        $messageTokens = \App\Services\Chat\TextNormalizer::tokens($userText);
        $bestName = null;
        $bestScore = 0.0;

        foreach (DanhMucDichVu::query()->select('ten_dich_vu')->get() as $service) {
            $serviceName = trim((string) $service->ten_dich_vu);
            if ($serviceName === '') {
                continue;
            }

            $normalizedService = \App\Services\Chat\TextNormalizer::normalize($serviceName);
            $serviceTokens = \App\Services\Chat\TextNormalizer::tokens($serviceName);
            $phraseScore = str_contains($normalizedMessage, $normalizedService) ? 1.0 : 0.0;
            $tokenScore = \App\Services\Chat\TextNormalizer::overlapScore($serviceTokens, $messageTokens);
            $score = max($phraseScore, $tokenScore);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestName = $serviceName;
            }
        }

        return $bestScore >= 0.6 ? $bestName : null;
    }

    private function isProcessQuestion(string $text): bool
    {
        return $this->matchesNormalizedMarkers($text, [
            'quy trinh tiep nhan',
            'quy trinh sua chua',
            'quy trinh dat lich',
            'quy trinh lam viec',
            'lam nhu the nao',
            'tiep nhan nhu the nao',
        ]);
    }

    private function isPriceQuestion(string $text): bool
    {
        return $this->matchesNormalizedMarkers($text, [
            'gia bao nhieu',
            'chi phi bao nhieu',
            'ton bao nhieu',
            'gia tham khao',
            'bao nhieu tien',
            'gia sua',
            'phi sua',
            'bao gia',
        ]);
    }

    private function containsEmergencyKeyword(string $text): bool
    {
        $normalized = mb_strtolower($text, 'UTF-8');

        foreach ($this->assistantSoulEmergencyKeywords() as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $technicians
     */
    private function buildEmergencyAssistantText(array $technicians): string
    {
        $emergencyConfig = (array) ($this->assistantSoulConfigService->getConfig()['emergency_response'] ?? []);
        $priceLine = (string) ($emergencyConfig['fallback_price_line'] ?? 'Gia tham khao: Gia cu the chi duoc xac nhan sau khi tho kiem tra an toan hien truong.');
        $priceLineTemplate = (string) ($emergencyConfig['price_line_template'] ?? 'Gia tham khao: %s (chi la muc du kien tiep nhan/kiem tra, khong phai bao gia cuoi cung).');

        foreach ($technicians as $technician) {
            $referencePrice = trim((string) ($technician['reference_price'] ?? ''));
            if ($referencePrice === '') {
                continue;
            }

            $priceLine = sprintf($priceLineTemplate, $referencePrice);
            break;
        }

        $lines = config('assistant_soul.emergency_response.lines', []);
        if (!is_array($lines)) {
            $lines = [];
        }

        $lines = array_values(array_filter(array_map(static function ($line): string {
            return trim((string) $line);
        }, $lines), static fn (string $line): bool => $line !== ''));
        $lines[] = $priceLine;

        return implode("\n", $lines);
    }

    /**
     * @return array<int, string>
     */
    private function assistantSoulEmergencyKeywords(): array
    {
        $keywords = $this->assistantSoulConfigService->getConfig()['emergency_keywords'] ?? [];

        if (!is_array($keywords)) {
            return ['khet', 'boc khoi', 'no', 'giat dien'];
        }

        return array_values(array_filter(array_map(static function ($keyword): string {
            return mb_strtolower(trim((string) $keyword), 'UTF-8');
        }, $keywords), static fn (string $keyword): bool => $keyword !== ''));
    }

    private function isStoreAddressQuestion(string $text): bool
    {
        $normalized = \App\Services\Chat\TextNormalizer::normalize($text);
        if ($normalized === '') {
            return false;
        }

        $markers = [
            'dia chi cua hang',
            'dia chi shop',
            'dia chi tiem',
            'cua hang o dau',
            'shop o dau',
            'tiem o dau',
            'mang den cua hang o dau',
            'dia chi mang den',
            'cua hang nam o dau',
        ];

        foreach ($markers as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function buildStoreAddressAssistantText(): string
    {
        $storeAddress = trim($this->travelFeeConfigService->resolveStoreAddress());

        return $storeAddress !== ''
            ? 'Địa chỉ cửa hàng: ' . $storeAddress . '.'
            : 'Hiện hệ thống chưa cấu hình địa chỉ cửa hàng.';
    }

    private function isStoreHotlineQuestion(string $text): bool
    {
        return $this->matchesNormalizedMarkers($text, [
            'hotline',
            'so dien thoai cua hang',
            'so dt cua hang',
            'sdt cua hang',
            'so lien he cua hang',
            'lien he cua hang',
            'goi cua hang',
        ]);
    }

    private function buildStoreHotlineAssistantText(): string
    {
        $hotline = trim($this->travelFeeConfigService->resolveStoreHotline());

        return $hotline !== ''
            ? 'Hotline cửa hàng: ' . $hotline . '.'
            : 'Hiện hệ thống chưa cấu hình hotline cửa hàng.';
    }

    private function isStoreOpeningHoursQuestion(string $text): bool
    {
        return $this->matchesNormalizedMarkers($text, [
            'gio mo cua',
            'gio dong cua',
            'mo cua luc nao',
            'dong cua luc nao',
            'gio lam viec cua hang',
            'cua hang mo den may gio',
            'cua hang dong luc may gio',
        ]);
    }

    private function buildStoreOpeningHoursAssistantText(): string
    {
        $openingHours = trim($this->travelFeeConfigService->resolveStoreOpeningHours());

        return $openingHours !== ''
            ? 'Giờ mở cửa cửa hàng: ' . $openingHours . '.'
            : 'Hiện hệ thống chưa cấu hình giờ mở cửa.';
    }

    private function isStoreTransportFeeQuestion(string $text): bool
    {
        return $this->matchesNormalizedMarkers($text, [
            'phi mang den cua hang',
            'phi cho den cua hang',
            'phi van chuyen den cua hang',
            'phi mang toi cua hang',
            'phi dua den cua hang',
            'phi cua hang ho tro van chuyen',
        ]);
    }

    private function buildStoreTransportFeeAssistantText(): string
    {
        $transportFee = $this->travelFeeConfigService->resolveStoreTransportFee();

        if ($transportFee <= 0) {
            return 'Phí mang đến cửa hàng: 0 đồng nếu bạn tự mang thiết bị tới.';
        }

        return 'Phí hỗ trợ vận chuyển đến cửa hàng tham khảo: '
            . $this->formatCurrencyVnd($transportFee)
            . '.';
    }

    private function isStoreMapQuestion(string $text): bool
    {
        return $this->matchesNormalizedMarkers($text, [
            'ban do cua hang',
            'map cua hang',
            'chi duong den cua hang',
            'duong di den cua hang',
            'cua hang o dau tren ban do',
            'mo ban do cua hang',
        ]);
    }

    private function buildStoreMapAssistantText(): string
    {
        $storeAddress = trim($this->travelFeeConfigService->resolveStoreAddress());
        $mapUrl = trim($this->travelFeeConfigService->resolveStoreMapUrl());

        if ($storeAddress === '') {
            return 'Hiện hệ thống chưa có địa chỉ cửa hàng nên chưa thể tạo liên kết bản đồ.';
        }

        if ($mapUrl === '') {
            return 'Địa chỉ cửa hàng: ' . $storeAddress . '.';
        }

        return 'Bản đồ cửa hàng: ' . $mapUrl;
    }

    private function isServiceCatalogQuestion(string $text): bool
    {
        return $this->matchesNormalizedMarkers($text, [
            'liet ke dich vu',
            'liet ke cac dich vu',
            'danh sach dich vu',
            'cac dich vu co trong cua hang',
            'dich vu co trong cua hang',
            'cua hang co nhung dich vu nao',
            'cua hang co dich vu nao',
            'hien co nhung dich vu nao',
            'co nhung dich vu nao',
        ]);
    }

    private function buildServiceCatalogAssistantText(): string
    {
        if ($this->buildServiceCatalogCards() === []) {
            return 'Hiện tại cửa hàng chưa cấu hình dịch vụ nào.';
        }

        return 'Đây là các dịch vụ hiện có trong cửa hàng.';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildServiceCatalogCards(): array
    {
        $query = DanhMucDichVu::query()->orderBy('ten_dich_vu');

        if (\Illuminate\Support\Facades\Schema::hasColumn('danh_muc_dich_vu', 'trang_thai')) {
            $query->where('trang_thai', true);
        }

        return $query
            ->limit(5)
            ->get(['id', 'ten_dich_vu', 'hinh_anh'])
            ->filter(static fn (DanhMucDichVu $service) => trim((string) $service->ten_dich_vu) !== '')
            ->map(static fn (DanhMucDichVu $service): array => [
                'id' => (int) $service->id,
                'name' => (string) $service->ten_dich_vu,
                'image' => $service->hinh_anh ?: asset('assets/images/logontu.png'),
                'url' => route('customer.search'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function storeInfo(): array
    {
        $storeAddress = trim($this->travelFeeConfigService->resolveStoreAddress());
        $storeHotline = trim($this->travelFeeConfigService->resolveStoreHotline());
        $storeOpeningHours = trim($this->travelFeeConfigService->resolveStoreOpeningHours());
        $storeMapUrl = trim($this->travelFeeConfigService->resolveStoreMapUrl());
        $storeTransportFee = $this->travelFeeConfigService->resolveStoreTransportFee();

        return [
            'address' => $storeAddress,
            'hotline' => $storeHotline,
            'opening_hours' => $storeOpeningHours,
            'map_url' => $storeMapUrl,
            'transport_fee' => $storeTransportFee,
            'transport_fee_label' => $storeTransportFee > 0 ? $this->formatCurrencyVnd($storeTransportFee) : '0 đồng',
            'mode_label' => 'Tại cửa hàng',
            'address_available' => $storeAddress !== '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStoreInfoPayload(string $status, string $assistantText): array
    {
        return [
            'assistant_text' => $assistantText,
            'cases' => [],
            'technicians' => [],
            'youtube_links' => [],
            'model' => null,
            'ai' => $this->deterministicAiMeta($status),
        ];
    }

    /**
     * @param  array<int, string>  $markers
     */
    private function matchesNormalizedMarkers(string $text, array $markers): bool
    {
        $normalized = \App\Services\Chat\TextNormalizer::normalize($text);
        if ($normalized === '') {
            return false;
        }

        foreach ($markers as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function formatCurrencyVnd(float $amount): string
    {
        return number_format(round(max(0, $amount)), 0, ',', '.') . ' đồng';
    }

    /**
     * @return array<string, mixed>
     */
    private function deterministicAiMeta(string $status): array
    {
        return [
            'status' => $status,
            'degraded' => false,
            'used_system_data' => true,
            'primary_model' => null,
            'model' => null,
            'notice' => null,
            'badge' => null,
        ];
    }
}

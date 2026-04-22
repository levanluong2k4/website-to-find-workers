<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMagic;
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

            return [
                'assistant_text' => $this->buildEmergencyAssistantText($technicians),
                'cases' => [],
                'technicians' => $technicians,
                'youtube_links' => [],
                'model' => null,
                'ai' => $this->deterministicAiMeta('emergency_rule'),
            ];
        }

        if ($this->isStoreHotlineQuestion($text)) {
            return $this->buildStoreInfoPayload('store_hotline_rule', $this->buildStoreHotlineAssistantText());
        }

        if ($this->isStoreOpeningHoursQuestion($text)) {
            return $this->buildStoreInfoPayload('store_hours_rule', $this->buildStoreOpeningHoursAssistantText());
        }

        if ($this->isStoreTransportFeeQuestion($text)) {
            return $this->buildStoreInfoPayload('store_transport_fee_rule', $this->buildStoreTransportFeeAssistantText());
        }

        if ($this->isStoreMapQuestion($text)) {
            return $this->buildStoreInfoPayload('store_map_rule', $this->buildStoreMapAssistantText());
        }

        if ($this->isStoreAddressQuestion($text)) {
            return $this->buildStoreInfoPayload('store_address_rule', $this->buildStoreAddressAssistantText());
        }

        $serviceSearchIntent = $this->serviceSearchIntentService->detect($text);
        if ($serviceSearchIntent['is_service_search']) {
            $technicians = $this->technicianRecommendationService->recommend(
                $text,
                [],
                3,
                $serviceSearchIntent['service_id']
            );

            return [
                'assistant_text' => $this->buildServiceSearchAssistantText(
                    (string) $serviceSearchIntent['service_name'],
                    $technicians
                ),
                'cases' => [],
                'technicians' => $technicians,
                'youtube_links' => [],
                'model' => null,
                'ai' => $this->deterministicAiMeta('service_search_rule'),
            ];
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

        return [
            'assistant_text' => (string) ($aiPayload['assistant_text'] ?? 'Tôi đã ghi nhận vấn đề. Bạn xem thông tin gợi ý bên dưới để chọn thợ phù hợp.'),
            'cases' => $cases,
            'technicians' => $technicians,
            'youtube_links' => $youtubeLinks,
            'model' => $aiPayload['model'] ?? null,
            'ai' => $aiPayload['ai'] ?? $this->deterministicAiMeta('system_data_only'),
        ];
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
            ? 'Địa chỉ cửa hàng là: ' . $storeAddress . '. Bạn có thể mang thiết bị đến cửa hàng hoặc đặt lịch để hệ thống gợi ý thợ phù hợp.'
            : 'Hiện hệ thống chưa cấu hình địa chỉ cửa hàng. Bạn vui lòng liên hệ admin để cập nhật thông tin này.';
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
            ? 'Hotline cửa hàng hiện tại là: ' . $hotline . '. Nếu cần tư vấn nhanh hoặc xác nhận lịch, bạn có thể gọi trực tiếp số này.'
            : 'Hiện hệ thống chưa cấu hình hotline cửa hàng. Bạn vui lòng liên hệ admin để cập nhật số điện thoại hỗ trợ.';
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
            ? 'Giờ mở cửa hiện tại của cửa hàng là: ' . $openingHours . '. Bạn nên gọi trước nếu muốn xác nhận kỹ thuật viên hoặc lịch nhận máy.'
            : 'Hiện hệ thống chưa cấu hình giờ mở cửa. Bạn vui lòng liên hệ hotline cửa hàng để xác nhận khung giờ làm việc.';
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
            return 'Hiện phí mang đến cửa hàng là 0 đồng nếu bạn tự mang thiết bị đến. Nếu cần cửa hàng hỗ trợ vận chuyển, bạn nên liên hệ trước để xác nhận chi phí thực tế.';
        }

        return 'Nếu bạn cần cửa hàng hỗ trợ chở thiết bị đến cửa hàng, phí tham khảo hiện tại là '
            . $this->formatCurrencyVnd($transportFee)
            . '. Nếu bạn tự mang thiết bị đến thì không phát sinh khoản phí này.';
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
            return 'Địa chỉ cửa hàng là: ' . $storeAddress . '.';
        }

        return 'Cửa hàng ở: ' . $storeAddress . '. Bạn có thể mở bản đồ tại đây: ' . $mapUrl;
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMagic;
use App\Services\Chat\AssistantSoulConfigService;
use App\Services\Chat\ChatContextBuilderService;
use App\Services\Chat\OpenAiChatService;
use App\Services\Chat\ServiceSearchIntentService;
use App\Services\Chat\SimilarIssueSearchService;
use App\Services\Chat\TechnicianRecommendationService;
use App\Services\Chat\YoutubeSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly SimilarIssueSearchService $similarIssueSearchService,
        private readonly TechnicianRecommendationService $technicianRecommendationService,
        private readonly YoutubeSuggestionService $youtubeSuggestionService,
        private readonly AssistantSoulConfigService $assistantSoulConfigService,
        private readonly ChatContextBuilderService $chatContextBuilderService,
        private readonly OpenAiChatService $openAiChatService,
        private readonly ServiceSearchIntentService $serviceSearchIntentService
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

        $this->createMessage($userId, $guestToken, 'user', $userText);

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

        $assistantPayload = $this->buildAssistantPayload($userText, $historyMessages);

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
        $assistantPayload = $this->buildAssistantPayload($text, $historyMessages);

        return response()->json([
            'assistant_text' => $assistantPayload['assistant_text'] ?? '',
            'cases' => $assistantPayload['cases'] ?? [],
            'technicians' => $assistantPayload['technicians'] ?? [],
            'youtube_links' => $assistantPayload['youtube_links'] ?? [],
            'model' => $assistantPayload['model'] ?? null,
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
    private function buildAssistantPayload(string $text, array $historyMessages): array
    {
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
            ];
        }

        $cases = $this->similarIssueSearchService->search($text, 3);
        $technicians = $this->technicianRecommendationService->recommend($text, $cases, 3);
        $youtubeLinks = $this->youtubeSuggestionService->suggest($text, $cases, 3);

        if ($this->containsEmergencyKeyword($text)) {
            return [
                'assistant_text' => $this->buildEmergencyAssistantText($technicians),
                'cases' => $cases,
                'technicians' => $technicians,
                'youtube_links' => $youtubeLinks,
                'model' => null,
            ];
        }

        $context = $this->chatContextBuilderService->build(
            $historyMessages,
            $cases,
            $technicians,
            $youtubeLinks
        );

        $aiPayload = $this->openAiChatService->generateResponse($context['messages']);

        return [
            'assistant_text' => (string) ($aiPayload['assistant_text'] ?? 'Toi da ghi nhan van de. Ban xem thong tin goi y ben duoi de chon tho phu hop.'),
            'cases' => $cases,
            'technicians' => $technicians,
            'youtube_links' => $youtubeLinks,
            'model' => $aiPayload['model'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $technicians
     */
    private function buildServiceSearchAssistantText(string $serviceName, array $technicians): string
    {
        if ($serviceName === '') {
            return $technicians === []
                ? 'Hien chua co tho phu hop.'
                : 'Day la cac tho phu hop.';
        }

        return $technicians === []
            ? 'Hien chua co tho ' . mb_strtolower($serviceName, 'UTF-8') . ' phu hop.'
            : 'Day la cac tho ' . mb_strtolower($serviceName, 'UTF-8') . ' phu hop.';
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
}

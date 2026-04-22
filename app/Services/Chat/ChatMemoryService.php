<?php

namespace App\Services\Chat;

use App\Models\ChatMemory;
use App\Models\DanhMucDichVu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ChatMemoryService
{
    private const MAX_MEMORIES_PER_ACTOR = 20;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recallForPrompt(string $message, ?int $userId, ?string $guestToken, int $limit = 5): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $actor = $this->resolveActor($userId, $guestToken);
        if ($actor === null) {
            return [];
        }

        $items = ChatMemory::query()
            ->where('actor_key', $actor['actor_key'])
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->limit(self::MAX_MEMORIES_PER_ACTOR)
            ->get();

        if ($items->isEmpty()) {
            return [];
        }

        $messageTokens = TextNormalizer::tokens($message);
        $normalizedMessage = TextNormalizer::normalize($message);

        $selected = $items->map(function (ChatMemory $memory) use ($messageTokens, $normalizedMessage): array {
            return [
                'memory' => $memory,
                'score' => $this->memoryScore($memory, $messageTokens, $normalizedMessage),
            ];
        })
            ->filter(fn (array $entry): bool => $entry['score'] >= 0.08 || in_array($entry['memory']->memory_type, ['service_interest', 'preferred_time', 'preferred_location'], true))
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        if ($selected->isEmpty()) {
            return [];
        }

        ChatMemory::query()
            ->whereIn('id', $selected->pluck('memory.id')->all())
            ->update(['last_used_at' => now()]);

        return $selected->map(function (array $entry): array {
            /** @var ChatMemory $memory */
            $memory = $entry['memory'];

            return [
                'type' => $memory->memory_type,
                'label' => $memory->label,
                'value' => $memory->value,
                'summary' => $memory->summary,
                'confidence' => (float) $memory->confidence,
            ];
        })->all();
    }

    public function rememberFromMessage(string $message, ?int $userId, ?string $guestToken, ?int $sourceMessageId = null): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $actor = $this->resolveActor($userId, $guestToken);
        if ($actor === null) {
            return 0;
        }

        $memories = $this->extractMemories($message);
        if ($memories === []) {
            return 0;
        }

        $remembered = 0;

        foreach ($memories as $memory) {
            $record = ChatMemory::query()->updateOrCreate(
                [
                    'actor_key' => $actor['actor_key'],
                    'memory_type' => $memory['memory_type'],
                    'memory_key' => $memory['memory_key'],
                ],
                [
                    'user_id' => $actor['user_id'],
                    'guest_token' => $actor['guest_token'],
                    'actor_type' => $actor['actor_type'],
                    'label' => $memory['label'],
                    'value' => $memory['value'],
                    'summary' => $memory['summary'],
                    'confidence' => $memory['confidence'],
                    'source_message_id' => $sourceMessageId,
                    'is_active' => true,
                    'last_used_at' => now(),
                    'meta' => $memory['meta'] ?? [],
                ]
            );

            if ($record->wasRecentlyCreated || $record->wasChanged()) {
                $remembered++;
            }
        }

        $this->pruneActorMemories($actor['actor_key']);

        return $remembered;
    }

    public function syncGuestToUser(string $guestToken, int $userId): void
    {
        if (!$this->tableExists() || trim($guestToken) === '') {
            return;
        }

        $guestActorKey = $this->actorKey(null, $guestToken);
        $userActorKey = $this->actorKey($userId, null);

        $guestMemories = ChatMemory::query()
            ->where('actor_key', $guestActorKey)
            ->get();

        foreach ($guestMemories as $memory) {
            ChatMemory::query()->updateOrCreate(
                [
                    'actor_key' => $userActorKey,
                    'memory_type' => $memory->memory_type,
                    'memory_key' => $memory->memory_key,
                ],
                [
                    'user_id' => $userId,
                    'guest_token' => null,
                    'actor_type' => 'user',
                    'label' => $memory->label,
                    'value' => $memory->value,
                    'summary' => $memory->summary,
                    'confidence' => $memory->confidence,
                    'source_message_id' => $memory->source_message_id,
                    'is_active' => $memory->is_active,
                    'last_used_at' => $memory->last_used_at ?? now(),
                    'meta' => $memory->meta ?? [],
                ]
            );
        }

        ChatMemory::query()->where('actor_key', $guestActorKey)->delete();
        $this->pruneActorMemories($userActorKey);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractMemories(string $message): array
    {
        $text = trim($message);
        if ($text === '') {
            return [];
        }

        $lower = mb_strtolower($text, 'UTF-8');
        $normalized = TextNormalizer::normalize($text);
        $memories = [];

        foreach ($this->matchServices($normalized) as $service) {
            $serviceName = trim((string) ($service->ten_dich_vu ?? ''));
            $memories[] = [
                'memory_type' => 'service_interest',
                'memory_key' => 'service:' . $service->id,
                'label' => 'Dich vu tung quan tam',
                'value' => $serviceName,
                'summary' => 'Khach tung hoi ve dich vu ' . $serviceName,
                'confidence' => 0.92,
                'meta' => ['service_id' => (int) $service->id],
            ];
        }

        foreach ($this->extractTimePhrases($lower) as $phrase) {
            $normalizedPhrase = TextNormalizer::normalize($phrase);
            $memories[] = [
                'memory_type' => 'preferred_time',
                'memory_key' => 'time:' . md5($normalizedPhrase),
                'label' => 'Khung gio uu tien',
                'value' => trim($phrase),
                'summary' => 'Khach thuong muon dat lich vao ' . trim($phrase),
                'confidence' => 0.84,
            ];
        }

        $location = $this->extractLocationSnippet($text);
        if ($location !== null) {
            $normalizedLocation = TextNormalizer::normalize($location);
            $memories[] = [
                'memory_type' => 'preferred_location',
                'memory_key' => 'location:' . md5($normalizedLocation),
                'label' => 'Khu vuc thuong nhac toi',
                'value' => trim($location),
                'summary' => 'Khach thuong nhac toi dia diem: ' . trim($location),
                'confidence' => 0.82,
            ];
        }

        $issue = $this->extractIssueMemory($text, $normalized);
        if ($issue !== null) {
            $memories[] = $issue;
        }

        return collect($memories)
            ->unique(fn (array $memory) => $memory['memory_type'] . ':' . $memory['memory_key'])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, DanhMucDichVu>
     */
    private function matchServices(string $normalizedMessage): Collection
    {
        if ($normalizedMessage === '' || !Schema::hasTable((new DanhMucDichVu())->getTable())) {
            return collect();
        }

        $services = DanhMucDichVu::query()
            ->select('id', 'ten_dich_vu')
            ->get();

        return $services->filter(function (DanhMucDichVu $service) use ($normalizedMessage): bool {
            $serviceName = TextNormalizer::normalize((string) $service->ten_dich_vu);
            if ($serviceName === '') {
                return false;
            }

            if (str_contains($normalizedMessage, $serviceName)) {
                return true;
            }

            $serviceTokens = TextNormalizer::tokens((string) $service->ten_dich_vu);
            if ($serviceTokens === []) {
                return false;
            }

            return TextNormalizer::overlapScore(TextNormalizer::tokens($normalizedMessage), $serviceTokens) >= 0.75;
        })->values();
    }

    /**
     * @return array<int, string>
     */
    private function extractTimePhrases(string $lowerText): array
    {
        $patterns = [
            '/\b(buoi sang|buoi chieu|buoi toi|sang|chieu|toi)\b/iu',
            '/\b(cuoi tuan|thu 7|chu nhat)\b/iu',
            '/\b(sau\s+\d{1,2}(?:h|:\d{2})?|truoc\s+\d{1,2}(?:h|:\d{2})?)\b/iu',
            '/\b(khoang\s+\d{1,2}(?:h|:\d{2})?(?:\s*-\s*\d{1,2}(?:h|:\d{2})?)?)\b/iu',
        ];

        $matches = [];
        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $lowerText, $found)) {
                continue;
            }

            foreach (($found[1] ?? []) as $phrase) {
                $phrase = trim((string) $phrase);
                if ($phrase !== '') {
                    $matches[] = $phrase;
                }
            }
        }

        return array_values(array_unique($matches));
    }

    private function extractLocationSnippet(string $text): ?string
    {
        if (preg_match('/((?:so\s*\d+[^\n,.;]{0,50})?(?:duong|pho|hem|quan|q\.|phuong|p\.)[^\n,.;]{0,80})/iu', $text, $matches)) {
            $snippet = trim((string) ($matches[1] ?? ''));

            return $snippet !== '' ? $snippet : null;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractIssueMemory(string $rawText, string $normalizedText): ?array
    {
        $tokens = TextNormalizer::tokens($rawText);
        $issueMarkers = ['khong', 'loi', 'bi', 'chay', 'ro', 'nong', 'keu', 'mat', 'hu', 'liet'];
        $containsIssueMarker = collect($issueMarkers)->contains(
            fn (string $marker): bool => str_contains($normalizedText, $marker)
        );

        if (!$containsIssueMarker || count($tokens) < 3) {
            return null;
        }

        $value = trim(mb_substr($rawText, 0, 180, 'UTF-8'));
        if ($value === '') {
            return null;
        }

        return [
            'memory_type' => 'issue_context',
            'memory_key' => 'issue:' . md5(TextNormalizer::normalize($value)),
            'label' => 'Van de tung mo ta',
            'value' => $value,
            'summary' => 'Khach tung mo ta van de: ' . $value,
            'confidence' => 0.76,
        ];
    }

    private function memoryScore(ChatMemory $memory, array $messageTokens, string $normalizedMessage): float
    {
        $memoryText = implode(' ', array_filter([
            (string) $memory->label,
            (string) $memory->value,
            (string) $memory->summary,
        ]));

        $overlap = TextNormalizer::overlapScore($messageTokens, TextNormalizer::tokens($memoryText));
        $phrase = $normalizedMessage !== '' && str_contains(TextNormalizer::normalize($memoryText), $normalizedMessage) ? 1.0 : 0.0;
        $recency = $memory->last_used_at?->diffInDays(now()) ?? 30;
        $recencyScore = $recency <= 7 ? 1.0 : ($recency <= 30 ? 0.7 : 0.4);
        $typeBoost = in_array($memory->memory_type, ['service_interest', 'preferred_time', 'preferred_location'], true) ? 0.18 : 0.0;

        if ($messageTokens === []) {
            return min(1.0, $typeBoost + (0.25 * $recencyScore));
        }

        return min(1.0, (0.55 * $overlap) + (0.10 * $phrase) + (0.15 * $recencyScore) + $typeBoost);
    }

    private function pruneActorMemories(string $actorKey): void
    {
        $idsToKeep = ChatMemory::query()
            ->where('actor_key', $actorKey)
            ->orderByDesc('updated_at')
            ->limit(self::MAX_MEMORIES_PER_ACTOR)
            ->pluck('id')
            ->all();

        ChatMemory::query()
            ->where('actor_key', $actorKey)
            ->when($idsToKeep !== [], fn ($query) => $query->whereNotIn('id', $idsToKeep))
            ->delete();
    }

    /**
     * @return array{actor_type: string, actor_key: string, user_id: int|null, guest_token: string|null}|null
     */
    private function resolveActor(?int $userId, ?string $guestToken): ?array
    {
        if ($userId !== null) {
            return [
                'actor_type' => 'user',
                'actor_key' => $this->actorKey($userId, null),
                'user_id' => $userId,
                'guest_token' => null,
            ];
        }

        $guestToken = trim((string) $guestToken);
        if ($guestToken === '') {
            return null;
        }

        return [
            'actor_type' => 'guest',
            'actor_key' => $this->actorKey(null, $guestToken),
            'user_id' => null,
            'guest_token' => $guestToken,
        ];
    }

    private function actorKey(?int $userId, ?string $guestToken): string
    {
        return $userId !== null ? 'user:' . $userId : 'guest:' . trim((string) $guestToken);
    }

    private function tableExists(): bool
    {
        return Schema::hasTable((new ChatMemory())->getTable());
    }
}

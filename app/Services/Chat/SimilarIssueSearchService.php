<?php

namespace App\Services\Chat;

use App\Models\AiKnowledgeItem;
use App\Models\DanhMucDichVu;
use App\Models\DonDatLich;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SimilarIssueSearchService
{
    public function __construct(
        private readonly GeminiEmbeddingService $embeddingService,
        private readonly QdrantClientService $qdrantClient,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $message, int $limit = 3): array
    {
        $normalizedMessage = TextNormalizer::normalize($message);
        $tokens = TextNormalizer::tokens($message);
        $serviceHint = $this->detectServiceHint($normalizedMessage, $this->normalizedWords($message));

        $knowledgeResults = $this->searchKnowledgeLibrary(
            $message,
            $limit,
            $normalizedMessage,
            $tokens,
            $serviceHint
        );
        if ($knowledgeResults !== []) {
            return $knowledgeResults;
        }

        return $this->searchLegacyBookings($message, $limit, $serviceHint['service_id']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchKnowledgeLibrary(
        string $message,
        int $limit,
        string $normalizedMessage,
        array $tokens,
        array $serviceHint
    ): array
    {
        $semanticResults = $this->searchKnowledgeLibrarySemantic(
            $limit,
            $normalizedMessage,
            $tokens,
            $serviceHint
        );
        if ($semanticResults !== []) {
            return $semanticResults;
        }

        return $this->searchKnowledgeLibraryLexicalFallback(
            $message,
            $limit,
            $normalizedMessage,
            $tokens,
            $serviceHint['service_id']
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchKnowledgeLibrarySemantic(
        int $limit,
        string $normalizedMessage,
        array $tokens,
        array $serviceHint
    ): array
    {
        if ($normalizedMessage === '') {
            return [];
        }

        try {
            $queryVector = $this->embeddingService->embedQuery($normalizedMessage);
            $hits = $this->searchQdrantKnowledgeHits(
                $queryVector,
                $this->buildQdrantFilter($serviceHint['service_id']),
                max(12, $limit * 4),
            );
        } catch (Throwable $exception) {
            Log::warning('Qdrant semantic search failed, using lexical fallback.', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        $scoredHits = collect($hits)
            ->map(function (array $hit): ?array {
                $knowledgeItemId = data_get($hit, 'payload.knowledge_item_id');
                if (!is_numeric($knowledgeItemId)) {
                    return null;
                }

                return [
                    'knowledge_item_id' => (int) $knowledgeItemId,
                    'semantic_score' => $this->normalizeSemanticScore((float) ($hit['score'] ?? 0.0)),
                ];
            })
            ->filter()
            ->values();

        if ($scoredHits->isEmpty()) {
            return [];
        }

        $knowledgeItems = AiKnowledgeItem::query()
            ->whereIn('id', $scoredHits->pluck('knowledge_item_id')->all())
            ->whereIn('source_type', ['booking_case', 'repair_catalog'])
            ->where('is_active', true)
            ->when(($serviceHint['service_id'] ?? null) !== null, function ($query) use ($serviceHint): void {
                $query->where('primary_service_id', $serviceHint['service_id']);
            })
            ->get()
            ->keyBy('id');

        $minimumScore = ($serviceHint['service_id'] ?? null) !== null ? 0.34 : 0.36;

        $ranked = $scoredHits
            ->map(function (array $hit) use ($knowledgeItems, $tokens, $normalizedMessage): ?array {
                /** @var AiKnowledgeItem|null $item */
                $item = $knowledgeItems->get($hit['knowledge_item_id']);
                if ($item === null) {
                    return null;
                }

                return $this->buildKnowledgeResult(
                    $item,
                    $tokens,
                    $normalizedMessage,
                    $hit['semantic_score']
                );
            })
            ->filter(fn (?array $case): bool => $case !== null && $case['score'] >= $minimumScore)
            ->sortByDesc('score')
            ->values();

        return $ranked->take($limit)->map(function (array $case): array {
            unset($case['score']);

            return $case;
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchKnowledgeLibraryLexicalFallback(
        string $message,
        int $limit,
        string $normalizedMessage,
        array $tokens,
        ?int $serviceId = null
    ): array
    {
        $dbDriver = DB::connection()->getDriverName();
        $useMysqlFullText = $dbDriver === 'mysql' && $normalizedMessage !== '';
        $fullTextBooleanQuery = $tokens !== []
            ? $this->toBooleanFullTextQuery($tokens)
            : $normalizedMessage;

        if ($tokens === [] && !$useMysqlFullText) {
            return [];
        }

        $query = AiKnowledgeItem::query()
            ->where('source_type', 'booking_case')
            ->where('is_active', true)
            ->when($serviceId !== null, function ($builder) use ($serviceId): void {
                $builder->where('primary_service_id', $serviceId);
            });

        if ($useMysqlFullText) {
            $query->selectRaw(
                "ai_knowledge_items.*, MATCH(title, service_name, symptom_text, cause_text, solution_text, content) AGAINST (? IN NATURAL LANGUAGE MODE) AS fulltext_score",
                [$normalizedMessage]
            );
        }

        $query->where(function (Builder $builder) use ($tokens, $fullTextBooleanQuery, $useMysqlFullText): void {
            if ($useMysqlFullText && $fullTextBooleanQuery !== '') {
                $builder->orWhereRaw(
                    "MATCH(title, service_name, symptom_text, cause_text, solution_text, content) AGAINST (? IN BOOLEAN MODE)",
                    [$fullTextBooleanQuery]
                );
            }

            foreach ($tokens as $token) {
                $builder->orWhere('normalized_content', 'like', "%{$token}%")
                    ->orWhere('service_name', 'like', "%{$token}%")
                    ->orWhere('title', 'like', "%{$token}%")
                    ->orWhere('symptom_text', 'like', "%{$token}%")
                    ->orWhere('cause_text', 'like', "%{$token}%")
                    ->orWhere('solution_text', 'like', "%{$token}%");
            }
        });

        if ($useMysqlFullText) {
            $query->orderByDesc('fulltext_score');
        }

        $candidates = $query
            ->orderByDesc('quality_score')
            ->orderByDesc('published_at')
            ->limit(120)
            ->get();

        $minimumScore = $tokens === [] ? 0.25 : 0.12;

        $ranked = $candidates
            ->map(fn (AiKnowledgeItem $item): array => $this->buildKnowledgeResult($item, $tokens, $normalizedMessage))
            ->filter(fn (array $case): bool => $case['score'] >= $minimumScore)
            ->sortByDesc('score')
            ->values();

        return $ranked->take($limit)->map(function (array $case): array {
            unset($case['score']);

            return $case;
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchLegacyBookings(string $message, int $limit, ?int $serviceId = null): array
    {
        $tokens = TextNormalizer::tokens($message);
        $normalizedMessage = TextNormalizer::normalize($message);
        $dbDriver = DB::connection()->getDriverName();
        $useMysqlFullText = $dbDriver === 'mysql' && $normalizedMessage !== '';
        $fullTextBooleanQuery = $tokens !== []
            ? $this->toBooleanFullTextQuery($tokens)
            : $normalizedMessage;

        if ($tokens === [] && !$useMysqlFullText) {
            return [];
        }

        $query = DonDatLich::query()
            ->with(['dichVus:id,ten_dich_vu'])
            ->withAvg('danhGias as avg_review_rating', 'so_sao')
            ->where('trang_thai', 'da_xong')
            ->whereNotNull('mo_ta_van_de')
            ->when($serviceId !== null, function ($builder) use ($serviceId): void {
                $builder->whereHas('dichVus', function ($serviceQuery) use ($serviceId): void {
                    $serviceQuery->where('danh_muc_dich_vu.id', $serviceId);
                });
            });

        if ($useMysqlFullText) {
            $query->selectRaw(
                "don_dat_lich.*, MATCH(mo_ta_van_de, giai_phap) AGAINST (? IN NATURAL LANGUAGE MODE) AS fulltext_score",
                [$normalizedMessage]
            );
        }

        $query->where(function (Builder $builder) use ($tokens, $fullTextBooleanQuery, $useMysqlFullText): void {
            if ($useMysqlFullText && $fullTextBooleanQuery !== '') {
                $builder->orWhereRaw(
                    "MATCH(mo_ta_van_de, giai_phap) AGAINST (? IN BOOLEAN MODE)",
                    [$fullTextBooleanQuery]
                );
            }

            foreach ($tokens as $token) {
                $builder->orWhere('mo_ta_van_de', 'like', "%{$token}%")
                    ->orWhere('giai_phap', 'like', "%{$token}%");
            }
        });

        if ($useMysqlFullText) {
            $query->orderByDesc('fulltext_score');
        }

        $candidates = $query
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $ranked = $candidates
            ->map(fn (DonDatLich $job): array => $this->buildLegacyResult($job, $tokens, $normalizedMessage))
            ->filter(fn (array $case): bool => $case['score'] >= 0.2)
            ->sortByDesc('score')
            ->values();

        return $ranked->take($limit)->map(function (array $case): array {
            unset($case['score']);

            return $case;
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildKnowledgeResult(
        AiKnowledgeItem $item,
        array $tokens,
        string $normalizedMessage,
        float $semanticScore = 0.0
    ): array {
        $serviceType = (string) ($item->service_name ?: 'Dich vu sua chua');
        $title = (string) ($item->title ?? '');
        $symptom = (string) ($item->symptom_text ?? '');
        $cause = (string) ($item->cause_text ?? '');
        $solution = (string) ($item->solution_text ?? '');
        $combined = implode(' ', array_filter([
            $title,
            (string) ($item->content ?? ''),
            $symptom,
            $cause,
            $solution,
            $serviceType,
        ]));

        $serviceScore = $this->serviceScore($tokens, $serviceType);
        $titleScore = $this->fieldScore($tokens, $normalizedMessage, $title);
        $symptomScore = $this->fieldScore($tokens, $normalizedMessage, $symptom);
        $causeScore = $this->fieldScore($tokens, $normalizedMessage, $cause);
        $solutionScore = $this->fieldScore($tokens, $normalizedMessage, $solution);
        $contentScore = $this->contentScore($tokens, $normalizedMessage, $combined, (float) ($item->fulltext_score ?? 0));
        $phraseBoost = max(
            $this->phraseScore($normalizedMessage, $title),
            $this->phraseScore($normalizedMessage, $symptom),
            $this->phraseScore($normalizedMessage, $solution)
        );
        $semanticScore = min(1.0, max(0.0, $semanticScore));
        $ratingNorm = $this->normalizeRating((float) ($item->rating_avg ?? 0));
        $qualityNorm = min(1.0, max(0.0, (float) ($item->quality_score ?? 0)));
        $recencyScore = $this->recencyScore($item->published_at ?? $item->updated_at);

        $score = (0.35 * $semanticScore)
            + (0.10 * $serviceScore)
            + (0.18 * $symptomScore)
            + (0.07 * $causeScore)
            + (0.10 * $solutionScore)
            + (0.05 * $titleScore)
            + (0.05 * $contentScore)
            + (0.04 * $phraseBoost)
            + (0.03 * $qualityNorm)
            + (0.02 * $ratingNorm)
            + (0.01 * $recencyScore);

        $metadata = (array) ($item->metadata ?? []);
        $beforeImage = $metadata['before_image'] ?? null;
        $afterImage = $metadata['after_image'] ?? null;

        return [
            'id' => (int) ($item->source_id ?? $item->id),
            'service_type' => $serviceType,
            'problem_description' => $symptom,
            'cause' => $cause,
            'solution' => $solution,
            'before_image' => is_string($beforeImage) ? $beforeImage : null,
            'after_image' => is_string($afterImage) ? $afterImage : null,
            'rating' => round((float) ($item->rating_avg ?? 0), 2),
            'score' => round($score, 4),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLegacyResult(DonDatLich $job, array $tokens, string $normalizedMessage): array
    {
        $serviceType = (string) (
            $job->dichVus->pluck('ten_dich_vu')->filter()->implode(', ')
            ?: 'Dich vu sua chua'
        );
        $symptom = (string) ($job->mo_ta_van_de ?? '');
        $solution = (string) ($job->giai_phap ?? '');
        $combined = implode(' ', array_filter([
            $symptom,
            $solution,
            $serviceType,
        ]));

        $serviceScore = $this->serviceScore($tokens, $serviceType);
        $symptomScore = $this->fieldScore($tokens, $normalizedMessage, $symptom);
        $solutionScore = $this->fieldScore($tokens, $normalizedMessage, $solution);
        $contentScore = $this->contentScore($tokens, $normalizedMessage, $combined, (float) ($job->fulltext_score ?? 0));
        $phraseBoost = max(
            $this->phraseScore($normalizedMessage, $symptom),
            $this->phraseScore($normalizedMessage, $solution)
        );
        $ratingNorm = $this->normalizeRating((float) ($job->avg_review_rating ?? 0));
        $recencyScore = $this->recencyScore($job->updated_at ?? $job->created_at);

        $score = (0.18 * $serviceScore)
            + (0.32 * $symptomScore)
            + (0.16 * $solutionScore)
            + (0.10 * $contentScore)
            + (0.05 * $phraseBoost)
            + (0.05 * $ratingNorm)
            + (0.04 * $recencyScore);

        $beforeImages = is_array($job->hinh_anh_mo_ta) ? $job->hinh_anh_mo_ta : [];
        $afterImages = is_array($job->hinh_anh_ket_qua) ? $job->hinh_anh_ket_qua : [];

        return [
            'id' => $job->id,
            'service_type' => $serviceType,
            'problem_description' => $symptom,
            'cause' => '',
            'solution' => $solution,
            'before_image' => $beforeImages[0] ?? null,
            'after_image' => $afterImages[0] ?? null,
            'rating' => round((float) ($job->avg_review_rating ?? 0), 2),
            'score' => round($score, 4),
        ];
    }

    private function fieldScore(array $tokens, string $normalizedMessage, string $text): float
    {
        if ($text === '') {
            return 0.0;
        }

        $tokenScore = TextNormalizer::overlapScore($tokens, TextNormalizer::tokens($text));
        $phraseScore = $this->phraseScore($normalizedMessage, $text);

        if ($tokens === []) {
            return $phraseScore;
        }

        return min(1.0, (0.72 * $tokenScore) + (0.28 * $phraseScore));
    }

    private function contentScore(array $tokens, string $normalizedMessage, string $text, float $fulltextScore): float
    {
        $textScore = $this->textScore($tokens, $text, $fulltextScore);
        $phraseScore = $this->phraseScore($normalizedMessage, $text);

        return min(1.0, (0.8 * $textScore) + (0.2 * $phraseScore));
    }

    private function textScore(array $tokens, string $text, float $fulltextScore): float
    {
        $haystackTokens = TextNormalizer::tokens($text);
        $tokenOverlap = TextNormalizer::overlapScore($tokens, $haystackTokens);
        $fulltextNorm = min(1.0, max(0.0, $fulltextScore / 8));

        if ($tokens === []) {
            return $fulltextNorm;
        }

        return min(1.0, (0.55 * $tokenOverlap) + (0.45 * $fulltextNorm));
    }

    private function serviceScore(array $tokens, string $serviceType): float
    {
        if ($tokens === []) {
            return 0.0;
        }

        return TextNormalizer::overlapScore($tokens, TextNormalizer::tokens($serviceType));
    }

    private function phraseScore(string $normalizedMessage, string $text): float
    {
        $needle = trim($normalizedMessage);
        $haystack = TextNormalizer::normalize($text);

        if ($needle === '' || $haystack === '') {
            return 0.0;
        }

        if (str_contains($haystack, $needle)) {
            return 1.0;
        }

        if (mb_strlen($haystack, 'UTF-8') >= 12 && str_contains($needle, $haystack)) {
            return 0.9;
        }

        $parts = preg_split('/\s+/u', $needle) ?: [];
        $partCount = count($parts);
        if ($partCount < 2) {
            return 0.0;
        }

        $bestPhraseLength = 0;
        for ($size = min(4, $partCount); $size >= 2; $size--) {
            for ($index = 0; $index <= ($partCount - $size); $index++) {
                $phrase = implode(' ', array_slice($parts, $index, $size));
                if ($phrase !== '' && str_contains($haystack, $phrase)) {
                    $bestPhraseLength = $size;
                    break 2;
                }
            }
        }

        if ($bestPhraseLength === 0) {
            return 0.0;
        }

        return min(0.85, $bestPhraseLength / max($partCount, 1));
    }

    private function normalizeRating(float $rating): float
    {
        if ($rating <= 0) {
            return 0.0;
        }

        return min(1.0, max(0.0, $rating / 5));
    }

    private function recencyScore(mixed $publishedAt): float
    {
        if (!$publishedAt instanceof \DateTimeInterface) {
            return 0.2;
        }

        $days = (int) now()->diffInDays($publishedAt);

        return match (true) {
            $days <= 7 => 1.0,
            $days <= 30 => 0.85,
            $days <= 90 => 0.65,
            $days <= 180 => 0.45,
            $days <= 365 => 0.3,
            default => 0.15,
        };
    }

    private function toBooleanFullTextQuery(array $tokens): string
    {
        if ($tokens === []) {
            return '';
        }

        $parts = [];
        foreach ($tokens as $token) {
            $parts[] = '+' . $token . '*';
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<int, string>  $messageWords
     * @return array{service_id: int|null, service_name: string|null}
     */
    private function detectServiceHint(string $normalizedMessage, array $messageWords): array
    {
        if ($normalizedMessage === '') {
            return ['service_id' => null, 'service_name' => null];
        }

        $bestMatch = null;
        $bestScore = 0.0;

        $services = DanhMucDichVu::query()
            ->select('id', 'ten_dich_vu')
            ->where('trang_thai', true)
            ->get();

        foreach ($services as $service) {
            $serviceName = trim((string) $service->ten_dich_vu);
            if ($serviceName === '') {
                continue;
            }

            $normalizedService = TextNormalizer::normalize($serviceName);
            $serviceAlias = $this->serviceAlias($normalizedService);
            $serviceWords = $this->normalizedWords($serviceAlias);
            if ($serviceWords === []) {
                continue;
            }

            $phraseScore = 0.0;
            if (str_contains($normalizedMessage, $normalizedService)) {
                $phraseScore = 1.0;
            } elseif ($serviceAlias !== '' && str_contains($normalizedMessage, $serviceAlias)) {
                $phraseScore = 0.95;
            }

            $tokenScore = TextNormalizer::overlapScore($serviceWords, $messageWords);
            $matchCount = count(array_intersect($serviceWords, $messageWords));
            $score = max($phraseScore, $tokenScore);

            if ($matchCount === 0) {
                continue;
            }

            if ($score < $bestScore) {
                continue;
            }

            $candidate = [
                'service_id' => (int) $service->id,
                'service_name' => $serviceName,
                'match_count' => $matchCount,
                'service_word_count' => count($serviceWords),
                'score' => $score,
            ];

            if (
                $bestMatch !== null
                && $score === $bestScore
                && (
                    $candidate['match_count'] < ($bestMatch['match_count'] ?? 0)
                    || (
                        $candidate['match_count'] === ($bestMatch['match_count'] ?? 0)
                        && $candidate['service_word_count'] <= ($bestMatch['service_word_count'] ?? 0)
                    )
                )
            ) {
                continue;
            }

            $bestScore = $score;
            $bestMatch = $candidate;
        }

        if ($bestMatch === null || $bestScore < 0.72) {
            return ['service_id' => null, 'service_name' => null];
        }

        return [
            'service_id' => $bestMatch['service_id'],
            'service_name' => $bestMatch['service_name'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQdrantFilter(?int $serviceId): array
    {
        $must = [
            [
                'key' => 'is_active',
                'match' => [
                    'value' => true,
                ],
            ],
        ];

        if ($serviceId !== null) {
            $must[] = [
                'key' => 'primary_service_id',
                'match' => [
                    'value' => $serviceId,
                ],
            ];
        }

        return ['must' => $must];
    }

    private function normalizeSemanticScore(float $score): float
    {
        if ($score < 0.0) {
            return 0.0;
        }

        return min(1.0, $score);
    }

    private function serviceAlias(string $normalizedService): string
    {
        return preg_replace('/^(sua|ve sinh|lap dat)\s+/u', '', $normalizedService) ?? $normalizedService;
    }

    /**
     * @return array<int, string>
     */
    private function normalizedWords(string $text): array
    {
        $normalized = TextNormalizer::normalize($text);
        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $normalized) ?: [];
        $words = [];

        foreach ($parts as $part) {
            if (mb_strlen($part, 'UTF-8') < 2) {
                continue;
            }

            $words[] = $part;
        }

        return array_values(array_unique($words));
    }

    /**
     * @param  array<int, float>  $queryVector
     * @param  array<string, mixed>|null  $filter
     * @return array<int, array<string, mixed>>
     */
    private function searchQdrantKnowledgeHits(array $queryVector, ?array $filter, int $limit): array
    {
        $collection = (string) config('services.qdrant.collection', 'ai_knowledge_items_v1');

        if ($filter === null) {
            return $this->qdrantClient->search($collection, $queryVector, null, $limit);
        }

        try {
            return $this->qdrantClient->search($collection, $queryVector, $filter, $limit);
        } catch (Throwable $exception) {
            Log::warning('Qdrant filtered search failed, retrying without filter.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->qdrantClient->search($collection, $queryVector, null, max($limit, 20));
        }
    }
}

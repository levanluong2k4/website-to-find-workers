<?php

namespace App\Services\Chat;

use App\Models\AiKnowledgeItem;
use App\Models\DonDatLich;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SimilarIssueSearchService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $message, int $limit = 3): array
    {
        $knowledgeResults = $this->searchKnowledgeLibrary($message, $limit);
        if ($knowledgeResults !== []) {
            return $knowledgeResults;
        }

        return $this->searchLegacyBookings($message, $limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchKnowledgeLibrary(string $message, int $limit): array
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

        $query = AiKnowledgeItem::query()
            ->where('source_type', 'booking_case')
            ->where('is_active', true);

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

        $minimumScore = $tokens === [] ? 0.25 : 0.22;

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
    private function searchLegacyBookings(string $message, int $limit): array
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
            ->whereNotNull('mo_ta_van_de');

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
    private function buildKnowledgeResult(AiKnowledgeItem $item, array $tokens, string $normalizedMessage): array
    {
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
        $ratingNorm = $this->normalizeRating((float) ($item->rating_avg ?? 0));
        $qualityNorm = min(1.0, max(0.0, (float) ($item->quality_score ?? 0)));
        $recencyScore = $this->recencyScore($item->published_at ?? $item->updated_at);

        $score = (0.16 * $serviceScore)
            + (0.28 * $symptomScore)
            + (0.08 * $causeScore)
            + (0.12 * $solutionScore)
            + (0.07 * $titleScore)
            + (0.08 * $contentScore)
            + (0.07 * $phraseBoost)
            + (0.06 * $qualityNorm)
            + (0.04 * $ratingNorm)
            + (0.04 * $recencyScore);

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
}

<?php

namespace App\Services\Chat;

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
        $tokens = TextNormalizer::tokens($message);
        $normalizedMessage = TextNormalizer::normalize($message);
        $dbDriver = DB::connection()->getDriverName();
        $useMysqlFullText = $dbDriver === 'mysql' && $normalizedMessage !== '';

        $query = DonDatLich::query()
            ->with(['dichVu:id,ten_dich_vu'])
            ->withAvg('danhGias as avg_review_rating', 'so_sao')
            ->where('trang_thai', 'da_xong')
            ->whereNotNull('mo_ta_van_de')
            ->latest('created_at')
            ->limit(60);

        if ($useMysqlFullText) {
            $query->selectRaw(
                "don_dat_lich.*, MATCH(mo_ta_van_de, nguyen_nhan, giai_phap) AGAINST (? IN NATURAL LANGUAGE MODE) AS fulltext_score",
                [$normalizedMessage]
            );
        }

        if ($tokens !== [] || $useMysqlFullText) {
            $query->where(function (Builder $builder) use ($tokens, $useMysqlFullText): void {
                if ($useMysqlFullText) {
                    $builder->orWhereRaw(
                        "MATCH(mo_ta_van_de, nguyen_nhan, giai_phap) AGAINST (? IN BOOLEAN MODE)",
                        [$this->toBooleanFullTextQuery($tokens)]
                    );
                }

                foreach ($tokens as $token) {
                    $builder->orWhere('mo_ta_van_de', 'like', "%{$token}%")
                        ->orWhere('nguyen_nhan', 'like', "%{$token}%")
                        ->orWhere('giai_phap', 'like', "%{$token}%");
                }
            });
        }

        $candidates = $query->get();

        $ranked = $candidates->map(function (DonDatLich $job) use ($tokens): array {
            $serviceType = (string) ($job->dichVu?->ten_dich_vu ?? 'Dich vu sua chua');
            $combined = implode(' ', array_filter([
                (string) $job->mo_ta_van_de,
                (string) $job->nguyen_nhan,
                (string) $job->giai_phap,
                $serviceType,
            ]));

            $textScore = $this->textScore($tokens, $combined, (float) ($job->fulltext_score ?? 0));
            $serviceScore = $this->serviceScore($tokens, $serviceType);
            $ratingNorm = $this->normalizeRating((float) ($job->avg_review_rating ?? 0));

            $score = (0.6 * $textScore) + (0.2 * $serviceScore) + (0.2 * $ratingNorm);

            $beforeImages = is_array($job->hinh_anh_mo_ta) ? $job->hinh_anh_mo_ta : [];
            $afterImages = is_array($job->hinh_anh_ket_qua) ? $job->hinh_anh_ket_qua : [];

            return [
                'id' => $job->id,
                'service_type' => $serviceType,
                'problem_description' => (string) $job->mo_ta_van_de,
                'cause' => (string) ($job->nguyen_nhan ?? ''),
                'solution' => (string) ($job->giai_phap ?? ''),
                'before_image' => $beforeImages[0] ?? null,
                'after_image' => $afterImages[0] ?? null,
                'rating' => round((float) ($job->avg_review_rating ?? 0), 2),
                'score' => round($score, 4),
            ];
        })->sortByDesc('score')->values();

        return $ranked->take($limit)->map(function (array $case): array {
            unset($case['score']);

            return $case;
        })->all();
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

    private function normalizeRating(float $rating): float
    {
        if ($rating <= 0) {
            return 0.0;
        }

        return min(1.0, max(0.0, $rating / 5));
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


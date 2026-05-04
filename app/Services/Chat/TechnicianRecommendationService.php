<?php

namespace App\Services\Chat;

use App\Models\DonDatLich;
use App\Models\HoSoTho;

class TechnicianRecommendationService
{
    /**
     * @param  array<int, array<string, mixed>>  $cases
     * @return array<int, array<string, mixed>>
     */
    public function recommend(string $message, array $cases, int $limit = 3, ?int $serviceId = null): array
    {
        $queryTokens = TextNormalizer::tokens($message);
        $caseServiceTokens = [];
        foreach ($cases as $case) {
            $caseServiceTokens = array_merge(
                $caseServiceTokens,
                TextNormalizer::tokens((string) ($case['service_type'] ?? ''))
            );
        }
        $skillIntentTokens = array_values(array_unique(array_merge($queryTokens, $caseServiceTokens)));

        $workers = HoSoTho::query()
            ->with(['user:id,name,avatar', 'user.dichVus:id,ten_dich_vu'])
            ->where('trang_thai_duyet', 'da_duyet')
            ->where('dang_hoat_dong', true)
            ->when($serviceId !== null, function ($query) use ($serviceId): void {
                $query->whereHas('user.dichVus', function ($serviceQuery) use ($serviceId): void {
                    $serviceQuery->where('danh_muc_dich_vu.id', $serviceId);
                });
            })
            ->get();

        if ($workers->isEmpty()) {
            return [];
        }

        $completedJobsMap = DonDatLich::query()
            ->selectRaw('tho_id, COUNT(*) AS total')
            ->where('trang_thai', 'da_xong')
            ->whereNotNull('tho_id')
            ->groupBy('tho_id')
            ->pluck('total', 'tho_id');

        $maxCompleted = (int) max(1, (int) $completedJobsMap->max());

        $ranked = $workers->map(function (HoSoTho $worker) use ($skillIntentTokens, $completedJobsMap, $maxCompleted, $serviceId): array {
            $serviceNames = $worker->user?->dichVus?->pluck('ten_dich_vu')->all() ?? [];
            $skills = implode(', ', $serviceNames);
            $skillCorpus = trim($skills . ' ' . (string) $worker->kinh_nghiem);
            $skillScore = TextNormalizer::overlapScore($skillIntentTokens, TextNormalizer::tokens($skillCorpus));

            $ratingNorm = min(1.0, max(0.0, ((float) $worker->danh_gia_trung_binh) / 5));
            $completedJobs = (int) ($completedJobsMap[$worker->user_id] ?? 0);
            $completedNorm = min(1.0, $completedJobs / $maxCompleted);

            $score = (0.45 * $skillScore) + (0.35 * $ratingNorm) + (0.20 * $completedNorm);

            $bookingUrl = '/customer/booking?worker_id=' . $worker->user_id;
            if ($serviceId !== null) {
                $bookingUrl .= '&dich_vu_id=' . $serviceId;
            }

            return [
                'id' => $worker->user_id,
                'name' => (string) ($worker->user?->name ?? 'Tho sua chua'),
                'skills' => $skills,
                'rating' => round((float) $worker->danh_gia_trung_binh, 2),
                'completed_jobs_count' => $completedJobs,
                'avatar' => $worker->user?->avatar,
                'reference_price' => (string) ($worker->bang_gia_tham_khao ?? ''),
                'profile_url' => '/customer/worker-profile/' . $worker->user_id,
                'booking_url' => $bookingUrl,
                'score' => round($score, 4),
            ];
        })->sortByDesc('score')->values();

        return $ranked->take($limit)->map(function (array $tech): array {
            unset($tech['score']);

            return $tech;
        })->all();
    }
}

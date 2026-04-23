<?php

namespace App\Http\Controllers;

use App\Models\DanhMucDichVu;
use App\Models\DanhGia;
use App\Models\DonDatLich;
use App\Models\HoSoTho;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerHomeController extends Controller
{
    public function __invoke(): View
    {
        $reviewBaseQuery = DanhGia::query()
            ->whereNotNull('don_dat_lich_id')
            ->whereHas('donDatLich');

        $fiveStarReviews = (clone $reviewBaseQuery)
            ->with([
                'nguoiDanhGia:id,name,avatar',
                'donDatLich:id,tho_id,dia_chi,loai_dat_lich,ngay_hen,khung_gio_hen',
                'donDatLich.tho:id,name',
                'donDatLich.dichVus:id,ten_dich_vu',
            ])
            ->where('so_sao', 5)
            ->latest()
            ->take(24)
            ->get()
            ->sortByDesc(static fn (DanhGia $review) => trim((string) ($review->nhan_xet ?? '')) !== '')
            ->take(12)
            ->map(fn (DanhGia $review) => $this->transformHighlightReview($review))
            ->values();

        $featuredWorkerProfiles = HoSoTho::query()
            ->with([
                'user:id,name,avatar,is_active',
                'user.dichVus:id,ten_dich_vu',
            ])
            ->where('trang_thai_duyet', 'da_duyet')
            ->where('dang_hoat_dong', true)
            ->whereHas('user', function ($query) {
                $query
                    ->where('role', 'worker')
                    ->where('is_active', true);
            })
            ->orderByDesc('tong_so_danh_gia')
            ->orderByDesc('danh_gia_trung_binh')
            ->take(3)
            ->get();

        $completedJobsByWorker = DonDatLich::query()
            ->whereIn('tho_id', $featuredWorkerProfiles->pluck('user_id'))
            ->where('trang_thai', 'da_xong')
            ->selectRaw('tho_id, COUNT(*) as total')
            ->groupBy('tho_id')
            ->pluck('total', 'tho_id');

        $pricingHighlights = $this->buildPricingHighlights();

        return view('customer.home', [
            'featuredWorkers' => $featuredWorkerProfiles
                ->map(fn (HoSoTho $profile) => $this->transformFeaturedWorker(
                    $profile,
                    (int) ($completedJobsByWorker[$profile->user_id] ?? 0)
                ))
                ->values(),
            'heroReviewStats' => [
                'five_star_total' => (clone $reviewBaseQuery)->where('so_sao', 5)->count(),
                'review_total' => (clone $reviewBaseQuery)->count(),
            ],
            'highlightReviews' => $fiveStarReviews,
            'pricingHighlights' => $pricingHighlights,
        ]);
    }

    /**
     * @return Collection<int, array<string, float|int|string|null>>
     */
    private function buildPricingHighlights(): Collection
    {
        $serviceConfigs = collect([
            [
                'icon' => 'tv',
                'label' => 'Sửa Tivi',
                'keywords' => ['tivi'],
            ],
            [
                'icon' => 'local_laundry_service',
                'label' => 'Máy Giặt',
                'keywords' => ['may giat'],
            ],
            [
                'icon' => 'kitchen',
                'label' => 'Tủ lạnh',
                'keywords' => ['tu lanh'],
            ],
            [
                'icon' => 'ac_unit',
                'label' => 'Điều Hòa',
                'keywords' => ['dieu hoa', 'may lanh'],
            ],
        ]);

        $services = DanhMucDichVu::query()
            ->select('id', 'ten_dich_vu')
            ->where('trang_thai', 1)
            ->get();

        $usedServiceIds = [];

        return $serviceConfigs
            ->map(function (array $config) use ($services, &$usedServiceIds): array {
                $matchedService = $services->first(function (DanhMucDichVu $service) use ($config, $usedServiceIds): bool {
                    if (in_array((int) $service->id, $usedServiceIds, true)) {
                        return false;
                    }

                    $normalizedName = $this->normalizeSearchText($service->ten_dich_vu);

                    foreach ($config['keywords'] as $keyword) {
                        if (str_contains($normalizedName, $keyword)) {
                            return true;
                        }
                    }

                    return false;
                });

                if ($matchedService !== null) {
                    $usedServiceIds[] = (int) $matchedService->id;
                }

                $minimumPrice = $matchedService !== null
                    ? $this->resolveServiceMinimumPrice((int) $matchedService->id)
                    : null;

                return [
                    'icon' => $config['icon'],
                    'name' => $config['label'],
                    'booking_name' => $matchedService?->ten_dich_vu ?? $config['label'],
                    'badge_label' => $minimumPrice === null ? 'Báo giá' : 'Từ',
                    'price_value' => $minimumPrice,
                    'price_label' => $minimumPrice === null
                        ? 'Liên hệ'
                        : number_format($minimumPrice, 0, ',', '.'),
                ];
            })
            ->values();
    }

    /**
     * @return array<string, int|string>
     */
    private function transformFeaturedWorker(HoSoTho $profile, int $completedJobs): array
    {
        $user = $profile->user;
        $serviceNames = $user?->dichVus?->pluck('ten_dich_vu')->filter()->values()->all() ?? [];

        return [
            'id' => (int) $profile->user_id,
            'name' => trim((string) ($user?->name ?? 'Kỹ thuật viên')),
            'avatar_url' => $this->normalizeAvatar($user?->avatar) ?? '/assets/images/worker2.png',
            'specialty_label' => $this->buildFeaturedWorkerSpecialty($serviceNames),
            'experience_label' => $this->resolveExperienceLabel($profile->kinh_nghiem),
            'completed_jobs_label' => $this->resolveCompletedJobsLabel($completedJobs),
            'rating_label' => number_format((float) ($profile->danh_gia_trung_binh ?? 0), 1),
            'profile_url' => '/customer/worker-profile/' . (int) $profile->user_id,
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function transformHighlightReview(DanhGia $review): array
    {
        $booking = $review->donDatLich;
        $reviewerName = trim((string) ($review->nguoiDanhGia?->name ?? 'Khách hàng'));
        $comment = trim((string) ($review->nhan_xet ?? ''));
        $serviceNames = $booking ? $booking->dichVus->pluck('ten_dich_vu')->filter()->values()->all() : [];

        return [
            'id' => (int) $review->id,
            'reviewer_name' => $reviewerName !== '' ? $reviewerName : 'Khách hàng',
            'reviewer_avatar' => $this->normalizeAvatar($review->nguoiDanhGia?->avatar),
            'reviewer_initials' => $this->buildInitials($reviewerName !== '' ? $reviewerName : 'Khách hàng'),
            'rating' => 5,
            'comment' => $comment !== ''
                ? Str::limit($comment, 145)
                : 'Đúng hẹn, báo giá rõ ràng và sửa xong gọn trong một lần hẹn.',
            'service_label' => $this->buildServiceLabel($serviceNames),
            'booking_code' => $booking ? $this->formatBookingCode((int) $booking->id) : null,
            'mode_label' => $booking?->loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Tại cửa hàng',
            'worker_name' => trim((string) ($booking?->tho?->name ?? 'Kỹ thuật viên cửa hàng')),
            'date_label' => $review->created_at?->format('d/m/Y') ?? 'Gần đây',
        ];
    }

    private function buildServiceLabel(array $serviceNames): string
    {
        if ($serviceNames === []) {
            return 'Dịch vụ điện máy';
        }

        $visibleNames = array_slice($serviceNames, 0, 2);
        $remainingCount = max(0, count($serviceNames) - count($visibleNames));
        $label = implode(' • ', $visibleNames);

        if ($remainingCount > 0) {
            $label .= ' +' . $remainingCount;
        }

        return $label;
    }

    private function buildFeaturedWorkerSpecialty(array $serviceNames): string
    {
        if ($serviceNames === []) {
            return 'Dịch vụ tổng hợp';
        }

        return implode(' - ', array_slice($serviceNames, 0, 2));
    }

    private function buildInitials(string $name): string
    {
        $parts = collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->filter();

        if ($parts->isEmpty()) {
            return 'KH';
        }

        return $parts
            ->slice(-2)
            ->map(static fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    private function formatBookingCode(int $bookingId): string
    {
        return 'DD-' . str_pad((string) $bookingId, 4, '0', STR_PAD_LEFT);
    }

    private function resolveExperienceLabel(?string $experience): string
    {
        $value = trim((string) $experience);

        if ($value === '') {
            return 'Chưa cập nhật kinh nghiệm';
        }

        if (preg_match('/(\d+)/u', $value, $matches) === 1) {
            return $matches[1] . ' năm kinh nghiệm';
        }

        return Str::limit($value, 42);
    }

    private function resolveCompletedJobsLabel(int $completedJobs): string
    {
        if ($completedJobs <= 0) {
            return 'Chưa có đơn hoàn tất';
        }

        return 'Đã hoàn thành ' . $completedJobs . ' công việc';
    }

    private function resolveServiceMinimumPrice(int $serviceId): ?float
    {
        $minimumPrice = DB::table('trieu_chung')
            ->join('trieu_chung_nguyen_nhan', 'trieu_chung.id', '=', 'trieu_chung_nguyen_nhan.trieu_chung_id')
            ->join('huong_xu_ly', 'trieu_chung_nguyen_nhan.nguyen_nhan_id', '=', 'huong_xu_ly.nguyen_nhan_id')
            ->where('trieu_chung.dich_vu_id', $serviceId)
            ->whereNotNull('huong_xu_ly.gia_tham_khao')
            ->where('huong_xu_ly.gia_tham_khao', '>', 0)
            ->min('huong_xu_ly.gia_tham_khao');

        if ($minimumPrice === null) {
            return null;
        }

        return (float) $minimumPrice;
    }

    private function normalizeSearchText(?string $value): string
    {
        return (string) Str::of((string) $value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim();
    }

    private function normalizeAvatar(?string $avatar): ?string
    {
        $value = trim((string) $avatar);

        if ($value === '') {
            return null;
        }

        if (Str::contains($value, 'user-default.png')) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '/'])) {
            return $value;
        }

        return '/storage/' . ltrim($value, '/');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DanhGia;
use Illuminate\Support\Str;
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

        return view('customer.home', [
            'heroReviewStats' => [
                'five_star_total' => (clone $reviewBaseQuery)->where('so_sao', 5)->count(),
                'review_total' => (clone $reviewBaseQuery)->count(),
            ],
            'highlightReviews' => $fiveStarReviews,
        ]);
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

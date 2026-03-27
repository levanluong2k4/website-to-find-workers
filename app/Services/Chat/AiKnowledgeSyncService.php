<?php

namespace App\Services\Chat;

use App\Models\AiKnowledgeItem;
use App\Models\DanhMucDichVu;
use App\Models\DonDatLich;

class AiKnowledgeSyncService
{
    public function sync(string $source = 'all', ?int $sourceId = null): array
    {
        $result = [
            'booking_case' => 0,
            'service_catalog' => 0,
        ];

        if (in_array($source, ['all', 'booking_case'], true)) {
            $result['booking_case'] = $this->syncBookingCases($source === 'booking_case' ? $sourceId : null);
        }

        if (in_array($source, ['all', 'service_catalog'], true)) {
            $result['service_catalog'] = $this->syncServiceCatalog($source === 'service_catalog' ? $sourceId : null);
        }

        return $result;
    }

    public function syncBookingCases(?int $bookingId = null): int
    {
        $bookings = DonDatLich::query()
            ->with(['dichVus:id,ten_dich_vu', 'danhGias:id,don_dat_lich_id,so_sao,nhan_xet'])
            ->where('trang_thai', 'da_xong')
            ->when($bookingId !== null, fn ($query) => $query->whereKey($bookingId))
            ->latest('updated_at')
            ->get();

        $synced = 0;

        foreach ($bookings as $booking) {
            $payload = $this->buildBookingCasePayload($booking);
            if ($payload === null) {
                continue;
            }

            AiKnowledgeItem::query()->updateOrCreate(
                ['source_key' => $payload['source_key']],
                $payload
            );

            $synced++;
        }

        return $synced;
    }

    public function syncServiceCatalog(?int $serviceId = null): int
    {
        $services = DanhMucDichVu::query()
            ->when($serviceId !== null, fn ($query) => $query->whereKey($serviceId))
            ->where('trang_thai', true)
            ->get();

        $synced = 0;

        foreach ($services as $service) {
            $contentLines = array_filter([
                'Danh mục dịch vụ: ' . $service->ten_dich_vu,
                $service->mo_ta ? 'Mô tả: ' . $service->mo_ta : null,
                'Nguồn dữ liệu: danh mục dịch vụ đang hoạt động trên hệ thống.',
            ]);
            $content = implode("\n", $contentLines);

            $payload = [
                'source_type' => 'service_catalog',
                'source_id' => $service->id,
                'source_key' => 'service_catalog:' . $service->id,
                'primary_service_id' => $service->id,
                'service_name' => $service->ten_dich_vu,
                'title' => 'Danh mục: ' . $service->ten_dich_vu,
                'content' => $content,
                'normalized_content' => TextNormalizer::normalize($content . ' ' . $service->ten_dich_vu),
                'symptom_text' => $service->mo_ta,
                'cause_text' => null,
                'solution_text' => null,
                'price_context' => null,
                'rating_avg' => null,
                'quality_score' => 0.4500,
                'metadata' => [
                    'service_id' => $service->id,
                    'image' => $service->hinh_anh,
                ],
                'is_active' => true,
                'published_at' => now(),
            ];

            AiKnowledgeItem::query()->updateOrCreate(
                ['source_key' => $payload['source_key']],
                $payload
            );

            $synced++;
        }

        return $synced;
    }

    private function buildBookingCasePayload(DonDatLich $booking): ?array
    {
        $symptom = trim((string) $booking->mo_ta_van_de);
        $cause = trim((string) ($booking->nguyen_nhan ?? ''));
        $solution = trim((string) ($booking->giai_phap ?? ''));
        $serviceNames = $booking->dichVus->pluck('ten_dich_vu')->filter()->values();
        $serviceName = $serviceNames->implode(', ');
        $hasUsableContent = $symptom !== '' || $cause !== '' || $solution !== '' || $serviceName !== '';

        if (!$hasUsableContent) {
            return null;
        }

        $reviewRatings = $booking->danhGias->pluck('so_sao')->filter(fn ($value) => is_numeric($value));
        $ratingAvg = $reviewRatings->isNotEmpty()
            ? round($reviewRatings->avg(), 2)
            : null;
        $reviewComments = $booking->danhGias
            ->pluck('nhan_xet')
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->values()
            ->all();

        $priceContext = $this->buildPriceContext($booking);
        $content = implode("\n", array_filter([
            $serviceName !== '' ? 'Dịch vụ: ' . $serviceName : null,
            $symptom !== '' ? 'Triệu chứng: ' . $symptom : null,
            $cause !== '' ? 'Nguyên nhân: ' . $cause : null,
            $solution !== '' ? 'Giải pháp: ' . $solution : null,
            $priceContext !== '' ? 'Chi phí: ' . $priceContext : null,
            $ratingAvg !== null ? 'Đánh giá trung bình: ' . $ratingAvg . '/5' : null,
            $reviewComments !== [] ? 'Nhận xét khách hàng: ' . implode(' | ', $reviewComments) : null,
        ]));

        $qualityScore = $this->calculateBookingQualityScore(
            $symptom,
            $cause,
            $solution,
            $serviceName,
            $ratingAvg,
            $booking->gia_da_cap_nhat,
            !empty($booking->hinh_anh_ket_qua)
        );

        return [
            'source_type' => 'booking_case',
            'source_id' => $booking->id,
            'source_key' => 'booking_case:' . $booking->id,
            'primary_service_id' => $booking->dichVus->first()?->id,
            'service_name' => $serviceName !== '' ? $serviceName : null,
            'title' => $serviceName !== '' ? 'Ca sửa chữa: ' . $serviceName : 'Ca sửa chữa #' . $booking->id,
            'content' => $content,
            'normalized_content' => TextNormalizer::normalize($content),
            'symptom_text' => $symptom !== '' ? $symptom : null,
            'cause_text' => $cause !== '' ? $cause : null,
            'solution_text' => $solution !== '' ? $solution : null,
            'price_context' => $priceContext !== '' ? $priceContext : null,
            'rating_avg' => $ratingAvg,
            'quality_score' => $qualityScore,
            'metadata' => [
                'booking_id' => $booking->id,
                'service_ids' => $booking->dichVus->pluck('id')->values()->all(),
                'before_image' => is_array($booking->hinh_anh_mo_ta) ? ($booking->hinh_anh_mo_ta[0] ?? null) : null,
                'after_image' => is_array($booking->hinh_anh_ket_qua) ? ($booking->hinh_anh_ket_qua[0] ?? null) : null,
                'review_comments' => $reviewComments,
                'cost_breakdown' => [
                    'phi_di_lai' => (float) ($booking->phi_di_lai ?? 0),
                    'tien_cong' => (float) ($booking->tien_cong ?? 0),
                    'phi_linh_kien' => (float) ($booking->phi_linh_kien ?? 0),
                    'tien_thue_xe' => (float) ($booking->tien_thue_xe ?? 0),
                    'tong_tien' => (float) ($booking->tong_tien ?? 0),
                    'chi_tiet_tien_cong' => is_array($booking->chi_tiet_tien_cong) ? $booking->chi_tiet_tien_cong : [],
                    'chi_tiet_linh_kien' => is_array($booking->chi_tiet_linh_kien) ? $booking->chi_tiet_linh_kien : [],
                ],
            ],
            'is_active' => true,
            'published_at' => $booking->updated_at ?? $booking->created_at ?? now(),
        ];
    }

    private function buildPriceContext(DonDatLich $booking): string
    {
        $parts = [];

        if ((float) ($booking->phi_di_lai ?? 0) > 0) {
            $parts[] = 'phí đi lại ' . $this->formatCurrency((float) $booking->phi_di_lai);
        }

        if ((float) ($booking->tien_cong ?? 0) > 0) {
            $parts[] = 'tiền công ' . $this->formatCurrency((float) $booking->tien_cong);
        }

        if ((float) ($booking->phi_linh_kien ?? 0) > 0) {
            $parts[] = 'linh kiện ' . $this->formatCurrency((float) $booking->phi_linh_kien);
        }

        if ((float) ($booking->tien_thue_xe ?? 0) > 0) {
            $parts[] = 'xe chở ' . $this->formatCurrency((float) $booking->tien_thue_xe);
        }

        if ((float) ($booking->tong_tien ?? 0) > 0) {
            $parts[] = 'tổng ' . $this->formatCurrency((float) $booking->tong_tien);
        }

        return implode(', ', $parts);
    }

    private function calculateBookingQualityScore(
        string $symptom,
        string $cause,
        string $solution,
        string $serviceName,
        ?float $ratingAvg,
        bool $hasUpdatedPricing,
        bool $hasAfterImage
    ): float {
        $score = 0.0;
        $score += $symptom !== '' ? 0.28 : 0.0;
        $score += $cause !== '' ? 0.20 : 0.0;
        $score += $solution !== '' ? 0.24 : 0.0;
        $score += $serviceName !== '' ? 0.08 : 0.0;
        $score += $hasUpdatedPricing ? 0.08 : 0.0;
        $score += $hasAfterImage ? 0.04 : 0.0;

        if ($ratingAvg !== null && $ratingAvg > 0) {
            $score += min(0.08, max(0.0, ($ratingAvg / 5) * 0.08));
        }

        return round(min(1.0, $score), 4);
    }

    private function formatCurrency(float $amount): string
    {
        return number_format($amount, 0, '.', ',') . ' VND';
    }
}

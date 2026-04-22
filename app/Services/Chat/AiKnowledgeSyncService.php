<?php

namespace App\Services\Chat;

use App\Models\AiKnowledgeItem;
use App\Models\CustomerFeedbackCase;
use App\Models\DanhMucDichVu;
use App\Models\DonDatLich;
use App\Models\HoSoTho;
use App\Models\NguyenNhan;
use App\Models\TrieuChung;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AiKnowledgeSyncService
{
    public function sync(string $source = 'all', ?int $sourceId = null): array
    {
        $result = [
            'booking_case' => 0,
            'service_catalog' => 0,
            'worker_profile' => 0,
            'customer_feedback_case' => 0,
            'repair_catalog' => 0,
        ];

        if (in_array($source, ['all', 'booking_case'], true)) {
            $result['booking_case'] = $this->syncBookingCases($source === 'booking_case' ? $sourceId : null);
        }

        if (in_array($source, ['all', 'service_catalog'], true)) {
            $result['service_catalog'] = $this->syncServiceCatalog($source === 'service_catalog' ? $sourceId : null);
        }

        if (in_array($source, ['all', 'worker_profile'], true)) {
            $result['worker_profile'] = $this->syncWorkerProfiles($source === 'worker_profile' ? $sourceId : null);
        }

        if (in_array($source, ['all', 'customer_feedback_case'], true)) {
            $result['customer_feedback_case'] = $this->syncCustomerFeedbackCases($source === 'customer_feedback_case' ? $sourceId : null);
        }

        if (in_array($source, ['all', 'repair_catalog'], true)) {
            $result['repair_catalog'] = $this->syncRepairCatalog($source === 'repair_catalog' ? $sourceId : null);
        }

        return $result;
    }

    public function syncSourceRecord(string $source, int $sourceId): void
    {
        match ($source) {
            'booking_case' => $this->syncBookingCaseRecord($sourceId),
            'service_catalog' => $this->syncServiceCatalogRecord($sourceId),
            'worker_profile' => $this->syncWorkerProfileRecord($sourceId),
            'customer_feedback_case' => $this->syncCustomerFeedbackCaseRecord($sourceId),
            'repair_catalog' => $this->syncRepairCatalogRecord($sourceId),
            default => null,
        };
    }

    public function deleteSourceRecord(string $source, int $sourceId): void
    {
        if (!$this->aiKnowledgeTableExists()) {
            return;
        }

        $items = AiKnowledgeItem::query()
            ->where('source_type', $source)
            ->where('source_id', $sourceId)
            ->get(['id', 'source_key']);

        if ($items->isEmpty()) {
            return;
        }

        $collection = trim((string) config('services.qdrant.collection', ''));
        if ($collection !== '') {
            $pendingDeletionService = app(QdrantPendingDeletionService::class);

            foreach ($items as $item) {
                $pendingDeletionService->queueDeletion($collection, (int) $item->id, 'source_deleted:' . $item->source_key);
            }
        }

        AiKnowledgeItem::query()
            ->where('source_type', $source)
            ->where('source_id', $sourceId)
            ->delete();
    }

    /**
     * @param  array<int, int>  $workerIds
     */
    public function syncWorkerProfilesByIds(array $workerIds): void
    {
        foreach ($this->normalizeIds($workerIds) as $workerId) {
            $this->syncWorkerProfileRecord($workerId);
        }
    }

    /**
     * @param  array<int, int>  $bookingIds
     */
    public function syncBookingCasesByIds(array $bookingIds): void
    {
        foreach ($this->normalizeIds($bookingIds) as $bookingId) {
            $this->syncBookingCaseRecord($bookingId);
        }
    }

    /**
     * @param  array<int, int>  $symptomIds
     */
    public function syncRepairCatalogByIds(array $symptomIds): void
    {
        foreach ($this->normalizeIds($symptomIds) as $symptomId) {
            $this->syncRepairCatalogRecord($symptomId);
        }
    }

    public function syncRepairCatalogByCauseId(int $causeId): void
    {
        if (!$this->tableExists((new NguyenNhan())->getTable()) || !$this->tableExists((new TrieuChung())->getTable())) {
            return;
        }

        $symptomIds = NguyenNhan::query()
            ->whereKey($causeId)
            ->with('trieuChungs:id')
            ->get()
            ->flatMap(fn (NguyenNhan $cause) => $cause->trieuChungs->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->syncRepairCatalogByIds($symptomIds);
    }

    public function syncRepairCatalogByServiceId(int $serviceId): void
    {
        if (!$this->tableExists((new TrieuChung())->getTable())) {
            return;
        }

        $symptomIds = TrieuChung::query()
            ->where('dich_vu_id', $serviceId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->syncRepairCatalogByIds($symptomIds);
    }

    public function syncServiceDependents(int $serviceId): void
    {
        if (!$this->tableExists('tho_dich_vu') || !$this->tableExists('don_dat_lich_dich_vu')) {
            return;
        }

        $workerIds = DB::table('tho_dich_vu')
            ->where('dich_vu_id', $serviceId)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $bookingIds = DB::table('don_dat_lich_dich_vu')
            ->where('dich_vu_id', $serviceId)
            ->pluck('don_dat_lich_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->syncWorkerProfilesByIds($workerIds);
        $this->syncBookingCasesByIds($bookingIds);
        $this->syncRepairCatalogByServiceId($serviceId);
    }

    public function syncBookingCases(?int $bookingId = null): int
    {
        if (!$this->tableExists((new DonDatLich())->getTable())) {
            return 0;
        }

        $bookingIds = DonDatLich::query()
            ->when($bookingId !== null, fn ($query) => $query->whereKey($bookingId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($bookingIds as $id) {
            $this->syncBookingCaseRecord($id);
        }

        return count($bookingIds);
    }

    public function syncServiceCatalog(?int $serviceId = null): int
    {
        if (!$this->tableExists((new DanhMucDichVu())->getTable())) {
            return 0;
        }

        $serviceIds = DanhMucDichVu::query()
            ->when($serviceId !== null, fn ($query) => $query->whereKey($serviceId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($serviceIds as $id) {
            $this->syncServiceCatalogRecord($id);
        }

        return count($serviceIds);
    }

    public function syncWorkerProfiles(?int $workerId = null): int
    {
        if (!$this->tableExists((new HoSoTho())->getTable())) {
            return 0;
        }

        $workerIds = HoSoTho::query()
            ->when($workerId !== null, fn ($query) => $query->where('user_id', $workerId))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($workerIds as $id) {
            $this->syncWorkerProfileRecord($id);
        }

        return count($workerIds);
    }

    public function syncCustomerFeedbackCases(?int $caseId = null): int
    {
        if (!$this->tableExists((new CustomerFeedbackCase())->getTable())) {
            return 0;
        }

        $caseIds = CustomerFeedbackCase::query()
            ->when($caseId !== null, fn ($query) => $query->whereKey($caseId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($caseIds as $id) {
            $this->syncCustomerFeedbackCaseRecord($id);
        }

        return count($caseIds);
    }

    public function syncRepairCatalog(?int $symptomId = null): int
    {
        if (!$this->tableExists((new TrieuChung())->getTable())) {
            return 0;
        }

        $symptomIds = TrieuChung::query()
            ->when($symptomId !== null, fn ($query) => $query->whereKey($symptomId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($symptomIds as $id) {
            $this->syncRepairCatalogRecord($id);
        }

        return count($symptomIds);
    }

    public function syncBookingCaseRecord(int $bookingId): void
    {
        $booking = DonDatLich::query()
            ->with(['dichVus:id,ten_dich_vu', 'danhGias:id,don_dat_lich_id,so_sao,nhan_xet'])
            ->find($bookingId);

        if (!$booking || $booking->trang_thai !== 'da_xong') {
            $this->deleteSourceRecord('booking_case', $bookingId);
            return;
        }

        $payload = $this->buildBookingCasePayload($booking);
        if ($payload === null) {
            $this->deleteSourceRecord('booking_case', $bookingId);
            return;
        }

        $this->upsertPayload($payload);
    }

    public function syncServiceCatalogRecord(int $serviceId): void
    {
        $service = DanhMucDichVu::query()->find($serviceId);

        if (!$service) {
            $this->deleteSourceRecord('service_catalog', $serviceId);
            return;
        }

        $payload = $this->buildServiceCatalogPayload($service);
        if ($payload === null) {
            $this->deleteSourceRecord('service_catalog', $serviceId);
            return;
        }

        $this->upsertPayload($payload);
    }

    public function syncWorkerProfileRecord(int $workerId): void
    {
        $profile = HoSoTho::query()
            ->with([
                'user:id,name,avatar,is_active',
                'user.dichVus:id,ten_dich_vu',
            ])
            ->where('user_id', $workerId)
            ->first();

        if (!$profile) {
            $this->deleteSourceRecord('worker_profile', $workerId);
            return;
        }

        $payload = $this->buildWorkerProfilePayload($profile);
        if ($payload === null) {
            $this->deleteSourceRecord('worker_profile', $workerId);
            return;
        }

        $this->upsertPayload($payload);
    }

    public function syncCustomerFeedbackCaseRecord(int $caseId): void
    {
        $caseState = CustomerFeedbackCase::query()
            ->with([
                'customer:id,name,phone',
                'worker:id,name,phone',
                'booking:id,khach_hang_id,tho_id,dia_chi,loai_dat_lich',
                'booking.dichVus:id,ten_dich_vu',
            ])
            ->find($caseId);

        if (!$caseState) {
            $this->deleteSourceRecord('customer_feedback_case', $caseId);
            return;
        }

        $payload = $this->buildCustomerFeedbackPayload($caseState);
        if ($payload === null) {
            $this->deleteSourceRecord('customer_feedback_case', $caseId);
            return;
        }

        $this->upsertPayload($payload);
    }

    public function syncRepairCatalogRecord(int $symptomId): void
    {
        $symptom = TrieuChung::query()
            ->with([
                'dichVu:id,ten_dich_vu',
                'nguyenNhans:id,ten_nguyen_nhan',
                'nguyenNhans.huongXuLys:id,nguyen_nhan_id,ten_huong_xu_ly,gia_tham_khao,mo_ta_cong_viec',
            ])
            ->find($symptomId);

        if (!$symptom) {
            $this->deleteSourceRecord('repair_catalog', $symptomId);
            return;
        }

        $payload = $this->buildRepairCatalogPayload($symptom);
        if ($payload === null) {
            $this->deleteSourceRecord('repair_catalog', $symptomId);
            return;
        }

        $this->upsertPayload($payload);
    }

    private function buildBookingCasePayload(DonDatLich $booking): ?array
    {
        $symptom = trim((string) $booking->mo_ta_van_de);
        $solution = trim((string) ($booking->giai_phap ?? ''));
        $serviceNames = $booking->dichVus->pluck('ten_dich_vu')->filter()->values();
        $serviceName = $serviceNames->implode(', ');
        $hasUsableContent = $symptom !== '' || $solution !== '' || $serviceName !== '';

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
            $serviceName !== '' ? 'Dich vu: ' . $serviceName : null,
            $symptom !== '' ? 'Trieu chung: ' . $symptom : null,
            $solution !== '' ? 'Giai phap: ' . $solution : null,
            $priceContext !== '' ? 'Chi phi: ' . $priceContext : null,
            $ratingAvg !== null ? 'Danh gia trung binh: ' . $ratingAvg . '/5' : null,
            $reviewComments !== [] ? 'Nhan xet khach hang: ' . implode(' | ', $reviewComments) : null,
        ]));

        $qualityScore = $this->calculateBookingQualityScore(
            $symptom,
            $solution,
            $serviceName,
            $ratingAvg,
            (bool) $booking->gia_da_cap_nhat,
            !empty($booking->hinh_anh_ket_qua)
        );

        return [
            'source_type' => 'booking_case',
            'source_id' => $booking->id,
            'source_key' => 'booking_case:' . $booking->id,
            'primary_service_id' => $booking->dichVus->first()?->id,
            'service_name' => $serviceName !== '' ? $serviceName : null,
            'title' => $serviceName !== '' ? 'Ca sua chua: ' . $serviceName : 'Ca sua chua #' . $booking->id,
            'content' => $content,
            'normalized_content' => TextNormalizer::normalize($content),
            'symptom_text' => $symptom !== '' ? $symptom : null,
            'cause_text' => null,
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

    private function buildServiceCatalogPayload(DanhMucDichVu $service): ?array
    {
        $content = implode("\n", array_filter([
            'Danh muc dich vu: ' . $service->ten_dich_vu,
            $service->mo_ta ? 'Mo ta: ' . $service->mo_ta : null,
            'Nguon du lieu: danh muc dich vu tren he thong.',
        ]));

        return [
            'source_type' => 'service_catalog',
            'source_id' => $service->id,
            'source_key' => 'service_catalog:' . $service->id,
            'primary_service_id' => $service->id,
            'service_name' => $service->ten_dich_vu,
            'title' => 'Danh muc: ' . $service->ten_dich_vu,
            'content' => $content,
            'normalized_content' => TextNormalizer::normalize($content . ' ' . $service->ten_dich_vu),
            'symptom_text' => $service->mo_ta,
            'cause_text' => null,
            'solution_text' => null,
            'price_context' => null,
            'rating_avg' => null,
            'quality_score' => 0.45,
            'metadata' => [
                'service_id' => $service->id,
                'image' => $service->hinh_anh,
                'status' => (bool) $service->trang_thai,
            ],
            'is_active' => (bool) $service->trang_thai,
            'published_at' => $service->updated_at ?? $service->created_at ?? now(),
        ];
    }

    private function buildWorkerProfilePayload(HoSoTho $profile): ?array
    {
        $user = $profile->user;
        if ($user === null) {
            return null;
        }

        $serviceNames = $user->dichVus->pluck('ten_dich_vu')->filter()->values();
        $serviceIds = $user->dichVus->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $serviceName = $serviceNames->implode(', ');
        $experience = trim((string) $profile->kinh_nghiem);
        $pricing = trim((string) $profile->bang_gia_tham_khao);
        $displayName = trim((string) $user->name);
        $isEligible = $profile->trang_thai_duyet === 'da_duyet'
            && (bool) $profile->dang_hoat_dong
            && (bool) $user->is_active;

        if ($displayName === '' && $serviceName === '' && $experience === '' && $pricing === '') {
            return null;
        }

        $ratingAvg = $profile->danh_gia_trung_binh !== null
            ? round((float) $profile->danh_gia_trung_binh, 2)
            : null;
        $reviewCount = (int) ($profile->tong_so_danh_gia ?? 0);
        $qualityScore = 0.22;
        $qualityScore += $serviceName !== '' ? 0.18 : 0.0;
        $qualityScore += $experience !== '' ? 0.18 : 0.0;
        $qualityScore += $pricing !== '' ? 0.10 : 0.0;
        $qualityScore += $profile->dang_hoat_dong ? 0.10 : 0.0;
        $qualityScore += min(0.12, ($reviewCount / 50) * 0.12);
        if ($ratingAvg !== null && $ratingAvg > 0) {
            $qualityScore += min(0.10, ($ratingAvg / 5) * 0.10);
        }

        $content = implode("\n", array_filter([
            $displayName !== '' ? 'Tho: ' . $displayName : null,
            $serviceName !== '' ? 'Dich vu nhan lam: ' . $serviceName : null,
            $experience !== '' ? 'Kinh nghiem: ' . $experience : null,
            $pricing !== '' ? 'Bang gia tham khao: ' . $pricing : null,
            $ratingAvg !== null ? 'Danh gia trung binh: ' . $ratingAvg . '/5' : null,
            $reviewCount > 0 ? 'Tong so danh gia: ' . $reviewCount : null,
            'Trang thai duyet: ' . (string) $profile->trang_thai_duyet,
            'Trang thai hoat dong: ' . ((bool) $profile->dang_hoat_dong ? 'Dang nhan viec' : 'Tam ngung'),
        ]));

        return [
            'source_type' => 'worker_profile',
            'source_id' => $user->id,
            'source_key' => 'worker_profile:' . $user->id,
            'primary_service_id' => $serviceIds[0] ?? null,
            'service_name' => $serviceName !== '' ? $serviceName : null,
            'title' => $displayName !== '' ? 'Ho so tho: ' . $displayName : 'Ho so tho #' . $user->id,
            'content' => $content,
            'normalized_content' => TextNormalizer::normalize($content),
            'symptom_text' => $serviceName !== '' ? $serviceName : null,
            'cause_text' => null,
            'solution_text' => $experience !== '' ? $experience : null,
            'price_context' => $pricing !== '' ? $pricing : null,
            'rating_avg' => $ratingAvg,
            'quality_score' => round(min(1.0, $qualityScore), 4),
            'metadata' => [
                'worker_id' => $user->id,
                'avatar' => $user->avatar,
                'service_ids' => $serviceIds,
                'review_count' => $reviewCount,
                'approval_status' => $profile->trang_thai_duyet,
                'active_status' => (bool) $profile->dang_hoat_dong,
                'user_active' => (bool) $user->is_active,
                'status_label' => (string) ($profile->trang_thai_hoat_dong ?? ''),
            ],
            'is_active' => $isEligible,
            'published_at' => $profile->updated_at ?? $profile->created_at ?? now(),
        ];
    }

    private function buildCustomerFeedbackPayload(CustomerFeedbackCase $caseState): ?array
    {
        $snapshot = is_array($caseState->last_snapshot) ? $caseState->last_snapshot : [];
        $booking = $caseState->booking;
        $serviceNames = $booking?->dichVus?->pluck('ten_dich_vu')->filter()->values() ?? collect();
        $serviceIds = $booking?->dichVus?->pluck('id')->map(fn ($id) => (int) $id)->values()->all() ?? [];
        $serviceName = $serviceNames->implode(', ');
        $reasonCode = trim((string) ($snapshot['reason_code'] ?? ''));
        $reasonLabel = trim((string) ($snapshot['reason_label'] ?? $reasonCode));
        $note = trim((string) ($snapshot['note'] ?? ''));
        $resolutionNote = trim((string) ($caseState->resolution_note ?? ''));
        $content = implode("\n", array_filter([
            'Loai case: ' . $caseState->source_type,
            $serviceName !== '' ? 'Dich vu: ' . $serviceName : null,
            $reasonLabel !== '' ? 'Ly do: ' . $reasonLabel : null,
            $note !== '' ? 'Chi tiet khach hang: ' . $note : null,
            $resolutionNote !== '' ? 'Huong xu ly: ' . $resolutionNote : null,
            'Muc uu tien: ' . (string) $caseState->priority,
            'Trang thai: ' . (string) $caseState->status,
        ]));

        if (trim($content) === '') {
            return null;
        }

        $qualityScore = 0.20;
        $qualityScore += $reasonLabel !== '' ? 0.16 : 0.0;
        $qualityScore += $note !== '' ? 0.16 : 0.0;
        $qualityScore += $resolutionNote !== '' ? 0.28 : 0.0;
        $qualityScore += $serviceName !== '' ? 0.10 : 0.0;
        $qualityScore += $caseState->status === 'resolved' ? 0.10 : 0.0;

        return [
            'source_type' => 'customer_feedback_case',
            'source_id' => $caseState->id,
            'source_key' => 'customer_feedback_case:' . $caseState->id,
            'primary_service_id' => $serviceIds[0] ?? null,
            'service_name' => $serviceName !== '' ? $serviceName : null,
            'title' => 'Customer feedback case #' . $caseState->id,
            'content' => $content,
            'normalized_content' => TextNormalizer::normalize($content),
            'symptom_text' => $note !== '' ? $note : null,
            'cause_text' => $reasonLabel !== '' ? $reasonLabel : null,
            'solution_text' => $resolutionNote !== '' ? $resolutionNote : null,
            'price_context' => null,
            'rating_avg' => null,
            'quality_score' => round(min(1.0, $qualityScore), 4),
            'metadata' => [
                'feedback_case_id' => $caseState->id,
                'source_type' => $caseState->source_type,
                'booking_id' => $caseState->booking_id,
                'customer_id' => $caseState->customer_id,
                'worker_id' => $caseState->worker_id,
                'service_ids' => $serviceIds,
                'priority' => $caseState->priority,
                'status' => $caseState->status,
                'reason_code' => $reasonCode !== '' ? $reasonCode : null,
            ],
            'is_active' => true,
            'published_at' => $caseState->updated_at ?? $caseState->created_at ?? now(),
        ];
    }

    private function buildRepairCatalogPayload(TrieuChung $symptom): ?array
    {
        $serviceName = trim((string) ($symptom->dichVu?->ten_dich_vu ?? ''));
        $causeNames = $symptom->nguyenNhans
            ->pluck('ten_nguyen_nhan')
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->values();

        $resolutionSummaries = $symptom->nguyenNhans
            ->flatMap(function ($cause) {
                return $cause->huongXuLys->map(function ($resolution) use ($cause) {
                    $parts = [
                        trim((string) $cause->ten_nguyen_nhan) !== '' ? 'Nguyen nhan: ' . trim((string) $cause->ten_nguyen_nhan) : null,
                        trim((string) $resolution->ten_huong_xu_ly) !== '' ? 'Huong xu ly: ' . trim((string) $resolution->ten_huong_xu_ly) : null,
                        $resolution->gia_tham_khao !== null ? 'Gia tham khao: ' . $this->formatCurrency((float) $resolution->gia_tham_khao) : null,
                        trim((string) ($resolution->mo_ta_cong_viec ?? '')) !== '' ? 'Mo ta cong viec: ' . trim((string) $resolution->mo_ta_cong_viec) : null,
                    ];

                    return implode('. ', array_filter($parts));
                });
            })
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->unique()
            ->values();

        $symptomName = trim((string) $symptom->ten_trieu_chung);

        if ($symptomName === '' && $serviceName === '' && $causeNames->isEmpty() && $resolutionSummaries->isEmpty()) {
            return null;
        }

        $content = implode("\n", array_filter([
            $serviceName !== '' ? 'Dich vu: ' . $serviceName : null,
            $symptomName !== '' ? 'Trieu chung: ' . $symptomName : null,
            $causeNames->isNotEmpty() ? 'Nguyen nhan co the: ' . $causeNames->implode(' | ') : null,
            $resolutionSummaries->isNotEmpty() ? 'Huong xu ly tham khao: ' . $resolutionSummaries->implode(' || ') : null,
        ]));

        $qualityScore = 0.28;
        $qualityScore += $serviceName !== '' ? 0.14 : 0.0;
        $qualityScore += $symptomName !== '' ? 0.18 : 0.0;
        $qualityScore += $causeNames->isNotEmpty() ? 0.16 : 0.0;
        $qualityScore += $resolutionSummaries->isNotEmpty() ? 0.20 : 0.0;

        return [
            'source_type' => 'repair_catalog',
            'source_id' => $symptom->id,
            'source_key' => 'repair_catalog:symptom:' . $symptom->id,
            'primary_service_id' => $symptom->dich_vu_id,
            'service_name' => $serviceName !== '' ? $serviceName : null,
            'title' => $symptomName !== '' ? 'Catalog sua chua: ' . $symptomName : 'Catalog sua chua #' . $symptom->id,
            'content' => $content,
            'normalized_content' => TextNormalizer::normalize($content),
            'symptom_text' => $symptomName !== '' ? $symptomName : null,
            'cause_text' => $causeNames->isNotEmpty() ? $causeNames->implode(' | ') : null,
            'solution_text' => $resolutionSummaries->isNotEmpty() ? $resolutionSummaries->implode(' || ') : null,
            'price_context' => null,
            'rating_avg' => null,
            'quality_score' => round(min(1.0, $qualityScore), 4),
            'metadata' => [
                'symptom_id' => $symptom->id,
                'service_id' => $symptom->dich_vu_id,
                'cause_ids' => $symptom->nguyenNhans->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'resolution_ids' => $symptom->nguyenNhans
                    ->flatMap(fn ($cause) => $cause->huongXuLys->pluck('id'))
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all(),
            ],
            'is_active' => true,
            'published_at' => $symptom->updated_at ?? $symptom->created_at ?? now(),
        ];
    }

    private function buildPriceContext(DonDatLich $booking): string
    {
        $parts = [];

        if ((float) ($booking->phi_di_lai ?? 0) > 0) {
            $parts[] = 'phi di lai ' . $this->formatCurrency((float) $booking->phi_di_lai);
        }

        if ((float) ($booking->tien_cong ?? 0) > 0) {
            $parts[] = 'tien cong ' . $this->formatCurrency((float) $booking->tien_cong);
        }

        if ((float) ($booking->phi_linh_kien ?? 0) > 0) {
            $parts[] = 'linh kien ' . $this->formatCurrency((float) $booking->phi_linh_kien);
        }

        if ((float) ($booking->tien_thue_xe ?? 0) > 0) {
            $parts[] = 'xe cho ' . $this->formatCurrency((float) $booking->tien_thue_xe);
        }

        if ((float) ($booking->tong_tien ?? 0) > 0) {
            $parts[] = 'tong ' . $this->formatCurrency((float) $booking->tong_tien);
        }

        return implode(', ', $parts);
    }

    private function calculateBookingQualityScore(
        string $symptom,
        string $solution,
        string $serviceName,
        ?float $ratingAvg,
        bool $hasUpdatedPricing,
        bool $hasAfterImage
    ): float {
        $score = 0.0;
        $score += $symptom !== '' ? 0.28 : 0.0;
        $score += $solution !== '' ? 0.24 : 0.0;
        $score += $serviceName !== '' ? 0.08 : 0.0;
        $score += $hasUpdatedPricing ? 0.08 : 0.0;
        $score += $hasAfterImage ? 0.04 : 0.0;

        if ($ratingAvg !== null && $ratingAvg > 0) {
            $score += min(0.08, max(0.0, ($ratingAvg / 5) * 0.08));
        }

        return round(min(1.0, $score), 4);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertPayload(array $payload): void
    {
        $item = AiKnowledgeItem::query()->updateOrCreate(
            ['source_key' => $payload['source_key']],
            $payload
        );

        if (!$this->supportsQdrantSyncColumns()) {
            return;
        }

        $payloadKeys = array_keys($payload);
        $shouldReindex = $item->wasRecentlyCreated
            || $item->wasChanged($payloadKeys)
            || $item->qdrant_synced_at === null
            || $item->qdrant_document_hash === null;

        if (!$shouldReindex) {
            return;
        }

        $item->forceFill([
            'qdrant_document_hash' => null,
            'qdrant_synced_at' => null,
        ])->saveQuietly();

        $this->tryAutoIndex($item);
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function formatCurrency(float $amount): string
    {
        return number_format($amount, 0, '.', ',') . ' VND';
    }

    private function tryAutoIndex(AiKnowledgeItem $item): void
    {
        if (!$this->supportsQdrantSyncColumns()) {
            return;
        }

        if (!$this->shouldAutoIndexQdrant()) {
            return;
        }

        try {
            $indexService = app(QdrantKnowledgeIndexService::class);
            $indexService->ensureCollection();
            $indexService->indexItem($item, true);
        } catch (Throwable $exception) {
            Log::warning('Auto Qdrant re-index failed. Item left dirty for scheduled/manual indexing.', [
                'source_key' => $item->source_key,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function shouldAutoIndexQdrant(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        if (!config('services.qdrant.auto_index', true)) {
            return false;
        }

        return trim((string) config('services.qdrant.url', '')) !== ''
            && trim((string) config('services.gemini.api_key', '')) !== '';
    }

    private function supportsQdrantSyncColumns(): bool
    {
        return $this->aiKnowledgeTableExists()
            && Schema::hasColumn((new AiKnowledgeItem())->getTable(), 'qdrant_document_hash')
            && Schema::hasColumn((new AiKnowledgeItem())->getTable(), 'qdrant_synced_at');
    }

    private function aiKnowledgeTableExists(): bool
    {
        return $this->tableExists((new AiKnowledgeItem())->getTable());
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}

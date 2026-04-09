<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DanhGia\StoreDanhGiaRequest;
use App\Http\Requests\DanhGia\UpdateDanhGiaRequest;
use App\Models\DanhGia;
use App\Models\DonDatLich;
use App\Models\HoSoTho;
use App\Services\Media\CloudinaryUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DanhGiaController extends Controller
{
    private const MAX_REVIEW_VIDEO_DURATION_SECONDS = 20;

    public function store(StoreDanhGiaRequest $request, CloudinaryUploadService $cloudinaryUploadService)
    {
        $validated = $request->validated();
        $user = $request->user();

        $booking = DonDatLich::find($validated['don_dat_lich_id']);

        if (!$booking) {
            return response()->json(['message' => 'Khong tim thay don dat lich'], 404);
        }

        if ($user->role !== 'admin' && $booking->khach_hang_id !== $user->id) {
            return response()->json(['message' => 'Ban khong co quyen danh gia don nay'], 403);
        }

        if ($booking->trang_thai !== 'da_xong') {
            return response()->json(['message' => 'Chi co the danh gia khi don da hoan thanh'], 400);
        }

        $daDanhGia = DanhGia::where('don_dat_lich_id', $booking->id)->exists();
        if ($daDanhGia) {
            return response()->json(['message' => 'Ban da gui danh gia cho don nay roi'], 400);
        }

        $uploadedMedia = $this->uploadReviewMedia($request, $cloudinaryUploadService);

        DB::beginTransaction();

        try {
            $danhGia = DanhGia::create([
                'don_dat_lich_id' => $booking->id,
                'nguoi_danh_gia_id' => $user->id,
                'nguoi_bi_danh_gia_id' => $booking->tho_id,
                'so_sao' => $validated['so_sao'],
                'nhan_xet' => $validated['nhan_xet'] ?? null,
                'hinh_anh_danh_gia' => $uploadedMedia['images'] !== [] ? $uploadedMedia['images'] : null,
                'video_danh_gia' => $uploadedMedia['video'],
                'so_lan_sua' => 0,
            ]);

            $this->updateWorkerRating($booking->tho_id);
            DB::commit();

            return response()->json([
                'message' => 'Danh gia thanh cong',
                'data' => $danhGia,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Loi he thong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateDanhGiaRequest $request, string $id, CloudinaryUploadService $cloudinaryUploadService)
    {
        $validated = $request->validated();
        $user = $request->user();

        $danhGia = DanhGia::find($id);

        if (!$danhGia) {
            return response()->json(['message' => 'Khong tim thay danh gia'], 404);
        }

        if ($user->role !== 'admin' && $danhGia->nguoi_danh_gia_id !== $user->id) {
            return response()->json(['message' => 'Khong co quyen sua danh gia nay'], 403);
        }

        if ($danhGia->so_lan_sua >= 1) {
            return response()->json(['message' => 'Ban da het so lan sua doi danh gia nay (toi da 1 lan)'], 400);
        }

        $uploadedMedia = $this->uploadReviewMedia($request, $cloudinaryUploadService);
        $keptImages = $this->normalizeMediaUrls($validated['existing_hinh_anh_danh_gia'] ?? $danhGia->hinh_anh_danh_gia);
        $finalImages = array_values(array_unique(array_merge($keptImages, $uploadedMedia['images'])));

        if (count($finalImages) > 5) {
            throw ValidationException::withMessages([
                'hinh_anh_danh_gia' => ['Toi da 5 anh cho moi danh gia.'],
            ]);
        }

        $finalVideo = $uploadedMedia['video'];
        if ($finalVideo === null && $request->boolean('keep_existing_video')) {
            $finalVideo = $this->normalizeMediaUrl($danhGia->video_danh_gia);
        }

        DB::beginTransaction();

        try {
            $danhGia->update([
                'so_sao' => $validated['so_sao'],
                'nhan_xet' => $validated['nhan_xet'] ?? $danhGia->nhan_xet,
                'hinh_anh_danh_gia' => $finalImages !== [] ? $finalImages : null,
                'video_danh_gia' => $finalVideo,
                'so_lan_sua' => $danhGia->so_lan_sua + 1,
            ]);

            $this->updateWorkerRating($danhGia->nguoi_bi_danh_gia_id);
            DB::commit();

            return response()->json([
                'message' => 'Cap nhat danh gia thanh cong',
                'data' => $danhGia,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Loi he thong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexByWorker(Request $request, string $thoId)
    {
        $perPage = max(1, min((int) $request->integer('per_page', 12), 30));
        $rating = (int) $request->integer('rating');
        $serviceId = (int) $request->integer('service_id');
        $sort = (string) $request->input('sort', 'latest');

        $query = DanhGia::query()
            ->with([
                'nguoiDanhGia:id,name,avatar',
                'donDatLich:id,ngay_hen,khung_gio_hen,loai_dat_lich,dia_chi',
                'donDatLich.dichVus:id,ten_dich_vu',
            ])
            ->where('nguoi_bi_danh_gia_id', $thoId);

        if (in_array($rating, [1, 2, 3, 4, 5], true)) {
            $query->where('so_sao', $rating);
        }

        if ($request->boolean('has_comment')) {
            $query->whereNotNull('nhan_xet')
                ->where('nhan_xet', '!=', '');
        }

        if ($serviceId > 0) {
            $query->whereHas('donDatLich.dichVus', function ($serviceQuery) use ($serviceId) {
                $serviceQuery->whereKey($serviceId);
            });
        }

        switch ($sort) {
            case 'lowest':
                $query->orderBy('so_sao')->orderByDesc('created_at');
                break;
            case 'highest':
                $query->orderByDesc('so_sao')->orderByDesc('created_at');
                break;
            case 'oldest':
                $query->oldest();
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $reviews = $query->paginate($perPage)->withQueryString();
        $reviews->getCollection()->transform(function (DanhGia $review) {
            return $this->transformWorkerReview($review);
        });

        return response()->json($reviews);
    }

    public function summary(string $thoId)
    {
        $hoSoTho = HoSoTho::where('user_id', $thoId)->first();

        if (!$hoSoTho) {
            return response()->json(['message' => 'Khong tim thay ho so tho'], 404);
        }

        $reviewQuery = DanhGia::query()->where('nguoi_bi_danh_gia_id', $thoId);

        $aggregate = (clone $reviewQuery)
            ->selectRaw('COUNT(id) as total_reviews')
            ->selectRaw('COALESCE(AVG(so_sao), 0) as average_rating')
            ->selectRaw('SUM(CASE WHEN so_sao = 5 THEN 1 ELSE 0 END) as five_star_reviews')
            ->selectRaw('SUM(CASE WHEN so_sao <= 3 THEN 1 ELSE 0 END) as low_rating_reviews')
            ->selectRaw("SUM(CASE WHEN nhan_xet IS NOT NULL AND TRIM(nhan_xet) <> '' THEN 1 ELSE 0 END) as commented_reviews")
            ->first();

        $totalReviews = (int) ($aggregate->total_reviews ?? 0);
        $averageRating = round((float) ($aggregate->average_rating ?? $hoSoTho->danh_gia_trung_binh ?? 0), 1);
        $fiveStarReviews = (int) ($aggregate->five_star_reviews ?? 0);
        $lowRatingReviews = (int) ($aggregate->low_rating_reviews ?? 0);
        $commentedReviews = (int) ($aggregate->commented_reviews ?? 0);
        $recentReviews = (clone $reviewQuery)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $breakdown = [
            '5' => 0,
            '4' => 0,
            '3' => 0,
            '2' => 0,
            '1' => 0,
        ];

        foreach ((clone $reviewQuery)
            ->select('so_sao', DB::raw('COUNT(*) as total'))
            ->groupBy('so_sao')
            ->pluck('total', 'so_sao') as $stars => $count) {
            $breakdown[(string) $stars] = (int) $count;
        }

        $serviceOptions = DB::table('danh_gia as dg')
            ->join('don_dat_lich_dich_vu as booking_service', 'booking_service.don_dat_lich_id', '=', 'dg.don_dat_lich_id')
            ->join('danh_muc_dich_vu as services', 'services.id', '=', 'booking_service.dich_vu_id')
            ->where('dg.nguoi_bi_danh_gia_id', $thoId)
            ->select(
                'services.id',
                'services.ten_dich_vu',
                DB::raw('COUNT(DISTINCT dg.id) as total_reviews')
            )
            ->groupBy('services.id', 'services.ten_dich_vu')
            ->orderByDesc('total_reviews')
            ->orderBy('services.ten_dich_vu')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'name' => $row->ten_dich_vu,
                    'total_reviews' => (int) $row->total_reviews,
                ];
            })
            ->values();

        return response()->json([
            'tho_id' => $thoId,
            'danh_gia_trung_binh' => $averageRating,
            'tong_so_danh_gia' => $totalReviews,
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
            'five_star_reviews' => $fiveStarReviews,
            'five_star_ratio' => $totalReviews > 0 ? (int) round(($fiveStarReviews / $totalReviews) * 100) : 0,
            'low_rating_reviews' => $lowRatingReviews,
            'commented_reviews' => $commentedReviews,
            'comment_rate' => $totalReviews > 0 ? (int) round(($commentedReviews / $totalReviews) * 100) : 0,
            'recent_reviews' => (int) $recentReviews,
            'breakdown' => $breakdown,
            'service_options' => $serviceOptions,
        ]);
    }

    private function updateWorkerRating($thoId): void
    {
        $stats = DB::table('danh_gia')
            ->where('nguoi_bi_danh_gia_id', $thoId)
            ->selectRaw('COUNT(id) as total, AVG(so_sao) as average')
            ->first();

        HoSoTho::where('user_id', $thoId)->update([
            'tong_so_danh_gia' => $stats->total ?? 0,
            'danh_gia_trung_binh' => $stats->average ? round($stats->average, 2) : 0,
        ]);
    }

    private function transformWorkerReview(DanhGia $review): array
    {
        $booking = $review->donDatLich;
        $comment = trim((string) ($review->nhan_xet ?? ''));
        $imageUrls = $this->normalizeMediaUrls($review->hinh_anh_danh_gia);
        $videoUrl = $this->normalizeMediaUrl($review->video_danh_gia);
        $serviceNames = $booking
            ? $booking->dichVus->pluck('ten_dich_vu')->filter()->values()->all()
            : [];

        return [
            'id' => (int) $review->id,
            'so_sao' => (int) ($review->so_sao ?? 0),
            'rating' => (int) ($review->so_sao ?? 0),
            'nhan_xet' => $comment !== '' ? $comment : null,
            'has_comment' => $comment !== '',
            'hinh_anh_danh_gia' => $imageUrls,
            'video_danh_gia' => $videoUrl,
            'media_summary' => [
                'image_count' => count($imageUrls),
                'has_video' => $videoUrl !== null,
            ],
            'created_at' => $review->created_at?->toIso8601String(),
            'created_label' => optional($review->created_at)->format('d/m/Y'),
            'tone' => $this->resolveReviewTone((int) ($review->so_sao ?? 0)),
            'khach_hang' => [
                'name' => $review->nguoiDanhGia?->name ?: 'Khach hang',
                'avatar' => $review->nguoiDanhGia?->avatar ?: '/assets/images/user-default.png',
            ],
            'service_names' => $serviceNames,
            'service_label' => $this->buildWorkerServiceLabel($serviceNames),
            'booking' => $booking ? [
                'id' => (int) $booking->id,
                'booking_code' => $this->formatWorkerBookingCode((int) $booking->id),
                'schedule_label' => $this->buildWorkerScheduleLabel($booking),
                'mode_label' => $booking->loai_dat_lich === 'at_home' ? 'Sua tai nha' : 'Mang toi cua hang',
                'address_excerpt' => $booking->dia_chi ? Str::limit($booking->dia_chi, 80, '...') : null,
                'detail_url' => '/worker/jobs/' . $booking->id,
            ] : null,
        ];
    }

    private function uploadReviewMedia(Request $request, CloudinaryUploadService $cloudinaryUploadService): array
    {
        $imageUrls = [];

        foreach ($request->file('hinh_anh_danh_gia', []) as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $uploadResult = $cloudinaryUploadService->uploadUploadedFile($file, [
                'folder' => 'reviews/images',
            ]);

            $secureUrl = $this->normalizeMediaUrl($uploadResult['secure_url'] ?? null);
            if ($secureUrl !== null) {
                $imageUrls[] = $secureUrl;
            }
        }

        $videoUrl = null;

        if ($request->hasFile('video_danh_gia')) {
            $uploadResult = $cloudinaryUploadService->uploadUploadedFile($request->file('video_danh_gia'), [
                'folder' => 'reviews/videos',
                'resource_type' => 'video',
            ]);

            $this->assertReviewVideoDuration(
                $uploadResult['duration'] ?? null,
                $request->input('video_duration')
            );

            $videoUrl = $this->normalizeMediaUrl($uploadResult['secure_url'] ?? null);
        }

        return [
            'images' => array_values(array_unique($imageUrls)),
            'video' => $videoUrl,
        ];
    }

    private function assertReviewVideoDuration(mixed $uploadedDuration, mixed $reportedDuration): void
    {
        $duration = $this->normalizeVideoDuration($uploadedDuration);

        if ($duration === null) {
            $duration = $this->normalizeVideoDuration($reportedDuration);
        }

        if ($duration !== null && $duration > self::MAX_REVIEW_VIDEO_DURATION_SECONDS) {
            throw ValidationException::withMessages([
                'video_danh_gia' => ['Video review khong duoc vuot qua 20 giay.'],
            ]);
        }
    }

    private function normalizeVideoDuration(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function normalizeMediaUrls(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            return $this->normalizeMediaUrl($item);
        }, $value)));
    }

    private function normalizeMediaUrl(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private function resolveReviewTone(int $rating): string
    {
        if ($rating >= 5) {
            return 'excellent';
        }

        if ($rating >= 4) {
            return 'positive';
        }

        if ($rating === 3) {
            return 'neutral';
        }

        return 'warning';
    }

    private function formatWorkerBookingCode(int $bookingId): string
    {
        return 'DD-' . str_pad((string) $bookingId, 4, '0', STR_PAD_LEFT);
    }

    private function buildWorkerServiceLabel(array $serviceNames): string
    {
        if ($serviceNames === []) {
            return 'Dich vu chua xac dinh';
        }

        $visibleNames = array_slice($serviceNames, 0, 2);
        $remainingCount = max(0, count($serviceNames) - count($visibleNames));
        $label = implode(' • ', $visibleNames);

        if ($remainingCount > 0) {
            $label .= ' +' . $remainingCount;
        }

        return $label;
    }

    private function buildWorkerScheduleLabel(DonDatLich $booking): string
    {
        $dateLabel = $booking->ngay_hen ? $booking->ngay_hen->format('d/m/Y') : 'Chua ro ngay';
        $slotLabel = $booking->khung_gio_hen ? str_replace('-', ' - ', $booking->khung_gio_hen) : 'Chua ro khung gio';

        return $dateLabel . ' • ' . $slotLabel;
    }
}

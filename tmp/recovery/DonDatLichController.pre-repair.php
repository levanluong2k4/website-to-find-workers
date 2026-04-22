<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonDatLich\RescheduleBookingRequest;
use App\Http\Requests\DonDatLich\UpdateBookingCostsRequest;
use App\Http\Requests\DonDatLich\UpdateTrangThaiRequest;
use App\Models\DonDatLich;
use App\Models\HuongXuLy;
use App\Models\LinhKien;
use App\Models\User;
use App\Notifications\BookingStatusNotification;
use App\Notifications\NewBookingNotification;
use App\Services\Media\CloudinaryUploadService;
use App\Services\TravelFeeConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DonDatLichController extends Controller
{
    private const FIXED_TIME_SLOTS = [
        '08:00-10:00',
        '10:00-12:00',
        '12:00-14:00',
        '14:00-17:00',
    ];

    private const MAX_CUSTOMER_RESCHEDULES = 1;

    private const MIN_FUTURE_RESCHEDULE_SLOTS = 2;

    private const CUSTOMER_RESCHEDULE_WINDOW_DAYS = 7;

    public function index(Request $request)
    {
        $user = $request->user();

        $query = DonDatLich::with([
            'khachHang:id,name,avatar,phone',
            'tho:id,name,avatar,phone',
            'dichVus:id,ten_dich_vu,hinh_anh',
            'danhGias',
        ]);

        if ($this->canActAsCustomer($user) && !$this->isAdmin($user)) {
            $query->where('khach_hang_id', $user->id);
        } elseif ($this->canActAsWorker($user) && !$this->isAdmin($user)) {
            $query->where('tho_id', $user->id);
        }

        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(
        \App\Http\Requests\DonDatLich\StoreDonDatLichRequest $request,
        TravelFeeConfigService $travelFeeConfigService,
        CloudinaryUploadService $cloudinaryUploadService
    )
    {
        $user = $request->user();
        if (!$this->canActAsCustomer($user)) {
            return response()->json(['message' => 'Chi khach hang moi co quyen dat lich'], 403);
        }

        try {
            \Illuminate\Support\Facades\Log::info('Booking store started', ['request' => $request->all()]);
            $validated = $request->validated();
            \Illuminate\Support\Facades\Log::info('Validation passed', ['validated' => $validated]);
            $serviceIds = $this->normalizeValidatedServiceIds($validated);
            $assignedWorkerId = !empty($validated['tho_id']) ? (int) $validated['tho_id'] : null;
            $isFixedWorkerBooking = $assignedWorkerId !== null;
            $normalizedTimeSlot = DonDatLich::normalizeTimeSlot($validated['khung_gio_hen'] ?? '');
            $bookingDate = $validated['ngay_hen'] ?? now()->toDateString();

            $khoangCach = null;
            $phiDiLai = 0;
            $hoSoTho = null;

            if (($validated['loai_dat_lich'] ?? '') === 'at_home') {
                $storeLat = 12.2618;
                $storeLng = 109.1995;

                if ($assignedWorkerId) {
                    $hoSoTho = \App\Models\HoSoTho::where('user_id', $assignedWorkerId)->first();
                    if ($hoSoTho && $hoSoTho->vi_do && $hoSoTho->kinh_do) {
                        $storeLat = $hoSoTho->vi_do;
                        $storeLng = $hoSoTho->kinh_do;
                        \Illuminate\Support\Facades\Log::info('Using worker coordinates for distance calculation', ['lat' => $storeLat, 'lng' => $storeLng]);
                    }
                }

                $lat = $validated['vi_do'] ?? 0;
                $lng = $validated['kinh_do'] ?? 0;

                $earthRadius = 6371;
                $dLat = deg2rad($lat - $storeLat);
                $dLng = deg2rad($lng - $storeLng);
                $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($storeLat)) * cos(deg2rad($lat)) * sin($dLng / 2) * sin($dLng / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $khoangCach = $earthRadius * $c;

                $maxDistance = 8;
                if ($assignedWorkerId && $hoSoTho) {
                    $maxDistance = min((float) ($hoSoTho->ban_kinh_phuc_vu ?? 8), 8);
                }

                if ($khoangCach > $maxDistance) {
                    return response()->json([
                        'message' => 'Dia chi cua ban qua xa khoang cach phuc vu (>' . $maxDistance . 'km). Khoang cach hien tai: ' . round($khoangCach, 1) . 'km. Vui long chon tho khac hoac mang den cua hang.',
                        'current_distance' => round($khoangCach, 1),
                    ], 400);
                }

                $phiDiLai = $travelFeeConfigService->resolveFee((float) $khoangCach);
            }

            $uploadedImages = [];
            $uploadedVideoUrl = null;

            $khungGio = $normalizedTimeSlot;
            $gioBatDau = '08:00';
            if (str_contains($khungGio, '-')) {
                $parts = explode('-', $khungGio);
                $gioBatDau = trim($parts[0]);
            }

            \Illuminate\Support\Facades\Log::info('Preparing media upload');
            if ($request->hasFile('hinh_anh_mo_ta')) {
                foreach ($request->file('hinh_anh_mo_ta') as $file) {
                    \Illuminate\Support\Facades\Log::info('Uploading image', ['name' => $file->getClientOriginalName()]);
                    $uploadResult = $cloudinaryUploadService->uploadUploadedFile($file, [
                        'folder' => 'bookings/images',
                    ]);
                    $uploadedImages[] = $uploadResult['secure_url'];
                }
            }

            if ($request->hasFile('video_mo_ta')) {
                \Illuminate\Support\Facades\Log::info('Uploading video');
                $uploadResult = $cloudinaryUploadService->uploadUploadedFile($request->file('video_mo_ta'), [
                    'folder' => 'bookings/videos',
                    'resource_type' => 'video',
                ]);
                $uploadedVideoUrl = $uploadResult['secure_url'];
            }

            $result = DB::transaction(function () use (
                $user,
                $validated,
                $serviceIds,
                $assignedWorkerId,
                $isFixedWorkerBooking,
                $bookingDate,
                $normalizedTimeSlot,
                $gioBatDau,
                $khoangCach,
                $phiDiLai,
                $uploadedImages,
                $uploadedVideoUrl
            ) {
                if ($assignedWorkerId) {
                    User::query()->whereKey($assignedWorkerId)->lockForUpdate()->first();

                    if ($this->workerHasScheduleConflict($assignedWorkerId, $bookingDate, $normalizedTimeSlot)) {
                        return response()->json([
                            'message' => 'Da co khach dat vao thoi gian nay roi.',
                            'errors' => [
                                'khung_gio_hen' => ['Da co khach dat vao thoi gian nay roi.'],
                            ],
                        ], 409);
                    }
                }

                $booking = new DonDatLich();
                $booking->khach_hang_id = $user->id;
                $booking->tho_id = $assignedWorkerId;
                $this->hydrateLegacyServiceColumn($booking, $serviceIds);
                $booking->loai_dat_lich = $validated['loai_dat_lich'] ?? 'at_store';
                $booking->ngay_hen = $bookingDate;
                $booking->khung_gio_hen = $normalizedTimeSlot ?: '08:00-10:00';
                $booking->thoi_gian_hen = Carbon::parse($bookingDate . ' ' . $gioBatDau . ':00');
                $booking->mo_ta_van_de = $validated['mo_ta_van_de'] ?? null;
                $booking->thue_xe_cho = $validated['thue_xe_cho'] ?? false;
                $booking->trang_thai = $isFixedWorkerBooking ? 'da_xac_nhan' : 'cho_xac_nhan';
                $booking->phuong_thuc_thanh_toan = 'cod';
                $booking->thoi_gian_het_han_nhan = null;

                if (($validated['loai_dat_lich'] ?? '') === 'at_home') {
                    $booking->dia_chi = $validated['dia_chi'] ?? '';
                    $booking->vi_do = $validated['vi_do'] ?? 0;
                    $booking->kinh_do = $validated['kinh_do'] ?? 0;
                    $booking->khoang_cach = round((float) $khoangCach, 2);
                    $booking->phi_di_lai = $phiDiLai;
                } else {
                    $booking->dia_chi = '2 Duong Nguyen Dinh Chieu, Vinh Tho, Nha Trang, Khanh Hoa';
                    $booking->phi_di_lai = 0;
                }

                if (!empty($uploadedImages)) {
                    $booking->hinh_anh_mo_ta = $uploadedImages;
                }

                if ($uploadedVideoUrl) {
                    $booking->video_mo_ta = $uploadedVideoUrl;
                }

                \Illuminate\Support\Facades\Log::info('Saving booking');
                $booking->save();
                $booking->dichVus()->sync($serviceIds);

                return $booking;
            });

            if ($result instanceof \Illuminate\Http\JsonResponse) {
                return $result;
            }

            $booking = $result;

            if ($booking->tho_id) {
                $tho = User::find($booking->tho_id);
                if ($tho) {
                    $tho->notify(new NewBookingNotification($booking));
                }
            } else {
                $thoList = $this->getEligibleWorkersForServiceIds($serviceIds);
                Notification::send($thoList, new NewBookingNotification($booking));
            }

            return response()->json([
                'message' => 'Dat lich thanh cong',
                'data' => $booking->load(['khachHang', 'tho', 'dichVus']),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Du lieu dat lich khong hop le.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in DonDatLichController@store', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Co loi xay ra: ' . $e->getMessage(),
                'debug_info' => $e->getFile() . ' L:' . $e->getLine(),
            ], 500);
        }
    }

    public function availableJobs(Request $request)
    {
        $user = $request->user();
        if (!$this->canActAsWorker($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workerProfile = $user->hoSoTho()->first(['vi_do', 'kinh_do']);
        $workerLat = is_numeric($workerProfile?->vi_do) ? (float) $workerProfile->vi_do : null;
        $workerLng = is_numeric($workerProfile?->kinh_do) ? (float) $workerProfile->kinh_do : null;

        $jobs = DonDatLich::with([
            'khachHang:id,name,avatar,phone',
            'dichVus:id,ten_dich_vu,hinh_anh',
        ])
            ->whereNull('tho_id')
            ->where('trang_thai', 'cho_xac_nhan')
            ->when(
                $user->dichVus()->exists(),
                function ($query) use ($user) {
                    $workerServiceIds = $user->dichVus()->pluck('danh_muc_dich_vu.id')->all();

                    $query->whereHas('dichVus')
                        ->whereDoesntHave('dichVus', function ($serviceQuery) use ($workerServiceIds) {
                            $serviceQuery->whereNotIn('danh_muc_dich_vu.id', $workerServiceIds);
                        });
                },
                function ($query) {
                    $query->whereRaw('1 = 0');
                }
            )
            ->orderByDesc('created_at')
            ->get()
            ->map(function (DonDatLich $job) use ($workerLat, $workerLng) {
                $workerDistanceKm = null;

                if (
                    $job->loai_dat_lich === 'at_home'
                    && $workerLat !== null
                    && $workerLng !== null
                    && is_numeric($job->vi_do)
                    && is_numeric($job->kinh_do)
                ) {
                    $workerDistanceKm = round(
                        $this->calculateDistanceKm(
                            $workerLat,
                            $workerLng,
                            (float) $job->vi_do,
                            (float) $job->kinh_do
                        ),
                        1
                    );
                } elseif ($job->loai_dat_lich === 'at_home' && is_numeric($job->khoang_cach)) {
                    $workerDistanceKm = round((float) $job->khoang_cach, 1);
                }

                $job->worker_distance_km = $workerDistanceKm;

                return $job;
            });

        return response()->json($jobs);
    }

    private function calculateDistanceKm(float $originLat, float $originLng, float $destinationLat, float $destinationLng): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($destinationLat - $originLat);
        $dLng = deg2rad($destinationLng - $originLng);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($originLat)) * cos(deg2rad($destinationLat))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function claimJob(Request $request, $id)
    {
        $user = $request->user();
        if (!$this->canActAsWorker($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking = DonDatLich::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Khong tim thay don dat lich'], 404);
        }

        if ($booking->tho_id !== null || $booking->trang_thai !== 'cho_xac_nhan') {
            return response()->json(['message' => 'Don nay da duoc tho khac nhan hoac khong con kha dung'], 400);
        }

        if (!$this->workerSupportsBookingServices($user, $booking)) {
            return response()->json(['message' => 'Ban chi co the nhan don nam trong nhom dich vu minh co the sua'], 400);
        }

        $booking->tho_id = $user->id;
        $booking->trang_thai = 'da_xac_nhan';
        $booking->save();
        $this->loadBookingResponseRelations($booking);

        $this->notifyCustomerAboutBookingUpdate(
            $booking,
            'Ã„ÂÃ†Â¡n Ã„â€˜Ã¡ÂºÂ·t lÃ¡Â»â€¹ch Ã„â€˜ÃƒÂ£ Ã„â€˜Ã†Â°Ã¡Â»Â£c nhÃ¡ÂºÂ­n',
            'ThÃ¡Â»Â£ ' . ($user->name ?? 'hÃ¡Â»â€¡ thÃ¡Â»â€˜ng') . ' Ã„â€˜ÃƒÂ£ nhÃ¡ÂºÂ­n Ã„â€˜Ã†Â¡n Ã„â€˜Ã¡ÂºÂ·t lÃ¡Â»â€¹ch #' . $booking->id . ' cÃ¡Â»Â§a bÃ¡ÂºÂ¡n.',
            'booking_claimed'
        );

        return response()->json(['message' => 'Nhan viec thanh cong', 'data' => $booking]);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $booking = DonDatLich::with([
            'khachHang:id,name,avatar,phone,address',
            'tho:id,name,avatar,phone',
            'tho.hoSoTho:user_id,danh_gia_trung_binh,tong_so_danh_gia',
            'dichVus:id,ten_dich_vu,hinh_anh',
            'danhGias',
            'thanhToans',
        ])->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Khong tim thay don dat lich'], 404);
        }

        $isOwner = $booking->khach_hang_id === $user->id;
        $isAssignedWorker = $booking->tho_id === $user->id;
        $isAvailableJobForWorker = $this->canActAsWorker($user) && $booking->tho_id === null && $booking->trang_thai === 'cho_xac_nhan';

        if (!$isOwner && !$isAssignedWorker && !$isAvailableJobForWorker && !$this->isAdmin($user)) {
            return response()->json(['message' => 'Ban khong co quyen xem don nay'], 403);
        }

        return response()->json($this->serializeBookingDetail($booking));
    }

    public function reschedule(RescheduleBookingRequest $request, string $id)
    {
        $user = $request->user();
        $booking = DonDatLich::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Khong tim thay don dat lich'], 404);
        }

        if (!$this->isAdmin($user) && $booking->khach_hang_id !== $user->id) {
            return response()->json(['message' => 'Ban khong co quyen doi lich cho don nay'], 403);
        }

        if ($booking->trang_thai !== 'da_xac_nhan') {
            return response()->json([
                'message' => 'Chi don da duoc tho xac nhan moi duoc doi lich hen.',
            ], 422);
        }

        if ((int) ($booking->so_lan_doi_lich ?? 0) >= self::MAX_CUSTOMER_RESCHEDULES) {
            return response()->json([
                'message' => 'Moi don dat lich chi duoc doi lich toi da 1 lan.',
            ], 422);
        }

        $validated = $request->validated();
        $requestedSchedule = $this->resolveScheduledAt($validated['ngay_hen'], $validated['khung_gio_hen']);
        $minimumSchedule = $this->resolveMinimumRescheduleSlotStart();
        $maximumScheduleDate = $this->resolveMaximumRescheduleDate();

        if ($requestedSchedule === null || $minimumSchedule === null || $maximumScheduleDate === null) {
            return response()->json([
                'message' => 'Khong xac dinh duoc lich hen hop le de cap nhat.',
            ], 422);
        }

        if (
            $booking->ngay_hen?->toDateString() === $validated['ngay_hen']
            && (string) $booking->khung_gio_hen === (string) $validated['khung_gio_hen']
        ) {
            return response()->json([
                'message' => 'Lich moi phai khac lich hen hien tai.',
            ], 422);
        }

        if ($requestedSchedule->lt($minimumSchedule)) {
            return response()->json([
                'message' => 'Lich moi phai tu ' . $this->formatScheduleForDisplay($minimumSchedule) . ' tro di.',
                'minimum_allowed_at' => $minimumSchedule->toIso8601String(),
                'minimum_allowed_date' => $minimumSchedule->toDateString(),
                'minimum_allowed_slot' => $this->resolveSlotValueFromStart($minimumSchedule),
            ], 422);
        }

        if ($requestedSchedule->gt($maximumScheduleDate->copy()->endOfDay())) {
            return response()->json([
                'message' => 'Lich moi chi duoc doi trong ' . self::CUSTOMER_RESCHEDULE_WINDOW_DAYS . ' ngay toi, den het ngay ' . $maximumScheduleDate->format('d/m/Y') . '.',
                'maximum_allowed_date' => $maximumScheduleDate->toDateString(),
            ], 422);
        }

        $booking->ngay_hen = $validated['ngay_hen'];
        $booking->khung_gio_hen = $validated['khung_gio_hen'];
        $booking->thoi_gian_hen = $requestedSchedule;
        $booking->so_lan_doi_lich = (int) ($booking->so_lan_doi_lich ?? 0) + 1;

        static $hasWorkerReminderSentAtColumn = null;
        if ($hasWorkerReminderSentAtColumn === null) {
            $hasWorkerReminderSentAtColumn = Schema::hasColumn($booking->getTable(), 'worker_reminder_sent_at');
        }

        if ($hasWorkerReminderSentAtColumn) {
            $booking->worker_reminder_sent_at = null;
        }

        $booking->sâ€¦5950 tokens truncatedâ€¦atLich $booking = null): array
    {
        $normalizedItems = array_values(array_filter($items, function ($item): bool {
            return is_array($item)
                && (
                    trim((string) ($item['noi_dung'] ?? '')) !== ''
                    || (float) ($item['so_tien'] ?? 0) > 0
                    || (float) ($item['don_gia'] ?? 0) > 0
                    || ($item['bao_hanh_thang'] ?? null) !== null
                    || ($item['bao_hanh_thang'] ?? null) === '0'
                    || filter_var($item['bao_hanh_da_su_dung'] ?? false, FILTER_VALIDATE_BOOLEAN)
                    || !empty($item['linh_kien_id'])
                );
        }));

        $catalogPartIds = collect($normalizedItems)
            ->pluck('linh_kien_id')
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $catalogParts = $catalogPartIds->isEmpty()
            ? collect()
            : LinhKien::query()
                ->whereIn('id', $catalogPartIds->all())
                ->get()
                ->keyBy('id');

        $allowedServiceIds = $booking === null
            ? []
            : $this->resolveBookingServiceIds($booking);

        $invalidPartIds = [];

        $partItems = array_map(function (array $item) use ($catalogParts, $allowedServiceIds, &$invalidPartIds): ?array {
            $warrantyMonths = $item['bao_hanh_thang'] ?? null;
            $warrantyUsed = filter_var($item['bao_hanh_da_su_dung'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $resolvedQuantity = max(1, (int) ($item['so_luong'] ?? 1));
            $catalogPartId = isset($item['linh_kien_id']) && $item['linh_kien_id'] !== ''
                ? (int) $item['linh_kien_id']
                : null;

            if ($catalogPartId !== null) {
                /** @var \App\Models\LinhKien|null $catalogPart */
                $catalogPart = $catalogParts->get($catalogPartId);

                if (
                    $catalogPart === null
                    || ($allowedServiceIds !== [] && !in_array((int) $catalogPart->dich_vu_id, $allowedServiceIds, true))
                ) {
                    $invalidPartIds[] = $catalogPartId;
                    return null;
                }
            }

            $resolvedUnitPrice = $catalogPartId !== null
                ? (float) ($catalogParts->get($catalogPartId)?->gia ?? $item['don_gia'] ?? $item['so_tien'] ?? 0)
                : (float) ($item['don_gia'] ?? $item['so_tien'] ?? 0);
            $resolvedAmount = $resolvedUnitPrice * $resolvedQuantity;
            $resolvedDescription = $catalogPartId !== null
                ? trim((string) ($catalogParts->get($catalogPartId)?->ten_linh_kien ?? $item['noi_dung'] ?? ''))
                : trim((string) ($item['noi_dung'] ?? ''));

            return [
                'linh_kien_id' => $catalogPartId,
                'dich_vu_id' => $catalogPartId !== null
                    ? (int) ($catalogParts->get($catalogPartId)?->dich_vu_id)
                    : (isset($item['dich_vu_id']) ? (int) $item['dich_vu_id'] : null),
                'hinh_anh' => $catalogPartId !== null
                    ? $catalogParts->get($catalogPartId)?->hinh_anh
                    : ($item['hinh_anh'] ?? null),
                'noi_dung' => $resolvedDescription,
                'don_gia' => $resolvedUnitPrice,
                'so_luong' => $resolvedQuantity,
                'so_tien' => $resolvedAmount,
                'bao_hanh_thang' => $warrantyMonths === null || $warrantyMonths === ''
                    ? null
                    : (int) $warrantyMonths,
                'bao_hanh_da_su_dung' => $warrantyUsed,
            ];
        }, $normalizedItems);

        if ($invalidPartIds !== []) {
            throw ValidationException::withMessages([
                'chi_tiet_linh_kien' => ['CÃƒÂ³ linh kiÃ¡Â»â€¡n khÃƒÂ´ng thuÃ¡Â»â„¢c nhÃƒÂ³m dÃ¡Â»â€¹ch vÃ¡Â»Â¥ cÃ¡Â»Â§a Ã„â€˜Ã†Â¡n hoÃ¡ÂºÂ·c khÃƒÂ´ng tÃ¡Â»â€œn tÃ¡ÂºÂ¡i.'],
            ]);
        }

        return array_values(array_filter($partItems));
    }

    private function sumCostItems(array $items): float
    {
        return (float) collect($items)->sum(function (array $item): float {
            return (float) ($item['so_tien'] ?? 0);
        });
    }

    private function resolveBookingTotal(DonDatLich $booking): float
    {
        $total = (float) ($booking->tong_tien ?? 0);
        if ($total > 0) {
            return $total;
        }

        return (float) ($booking->phi_di_lai ?? 0)
            + (float) ($booking->phi_linh_kien ?? 0)
            + (float) ($booking->tien_cong ?? 0)
            + (float) ($booking->tien_thue_xe ?? 0);
    }

    private function serializeBookingDetail(DonDatLich $booking): array
    {
        $payload = $booking->toArray();
        $payload['ngay_hen'] = $booking->ngay_hen?->toDateString();
        $payload['thoi_gian_hen'] = $booking->thoi_gian_hen?->toIso8601String();
        $payload['so_lan_doi_lich'] = (int) ($booking->so_lan_doi_lich ?? 0);
        $payload['reschedule_policy'] = $this->buildReschedulePolicy($booking);

        return $payload;
    }

    private function buildReschedulePolicy(DonDatLich $booking): array
    {
        $rescheduleCount = (int) ($booking->so_lan_doi_lich ?? 0);
        $remainingChanges = max(0, self::MAX_CUSTOMER_RESCHEDULES - $rescheduleCount);
        $statusAllowsReschedule = $booking->trang_thai === 'da_xac_nhan';
        $minimumSchedule = $this->resolveMinimumRescheduleSlotStart();
        $maximumScheduleDate = $this->resolveMaximumRescheduleDate();
        $hasFutureSlotInWindow = $minimumSchedule !== null
            && $maximumScheduleDate !== null
            && $minimumSchedule->lte($maximumScheduleDate->copy()->endOfDay());
        $currentSchedule = $booking->ngay_hen && $booking->khung_gio_hen
            ? $this->resolveScheduledAt($booking->ngay_hen->toDateString(), (string) $booking->khung_gio_hen)
            : null;

        $reason = null;
        if (!$statusAllowsReschedule) {
            $reason = 'status_not_allowed';
        } elseif ($remainingChanges <= 0) {
            $reason = 'limit_reached';
        } elseif ($minimumSchedule === null || !$hasFutureSlotInWindow) {
            $reason = 'no_future_slot';
        }

        return [
            'can_reschedule' => $statusAllowsReschedule && $remainingChanges > 0 && $hasFutureSlotInWindow,
            'status_allows_reschedule' => $statusAllowsReschedule,
            'max_changes' => self::MAX_CUSTOMER_RESCHEDULES,
            'reschedule_count' => $rescheduleCount,
            'remaining_changes' => $remainingChanges,
            'window_days' => self::CUSTOMER_RESCHEDULE_WINDOW_DAYS,
            'time_slots' => self::FIXED_TIME_SLOTS,
            'minimum_allowed_at' => $minimumSchedule?->toIso8601String(),
            'minimum_allowed_date' => $minimumSchedule?->toDateString(),
            'minimum_allowed_slot' => $minimumSchedule ? $this->resolveSlotValueFromStart($minimumSchedule) : null,
            'minimum_allowed_label' => $minimumSchedule ? $this->formatScheduleForDisplay($minimumSchedule) : null,
            'maximum_allowed_date' => $maximumScheduleDate?->toDateString(),
            'maximum_allowed_label' => $maximumScheduleDate?->format('d/m/Y'),
            'current_schedule_label' => $currentSchedule ? $this->formatScheduleForDisplay($currentSchedule) : null,
            'reason' => $reason,
        ];
    }

    private function resolveMinimumRescheduleSlotStart(?Carbon $now = null): ?Carbon
    {
        $current = ($now ?? now())->copy()->seconds(0);

        return $this->buildFutureSlotStarts($current)
            ->values()
            ->get(self::MIN_FUTURE_RESCHEDULE_SLOTS - 1);
    }

    private function buildFutureSlotStarts(Carbon $from): Collection
    {
        $candidates = collect();
        $baseDate = $from->copy()->startOfDay();

        for ($dayOffset = 0; $dayOffset < 30; $dayOffset += 1) {
            $dateValue = $baseDate->copy()->addDays($dayOffset)->toDateString();

            foreach (self::FIXED_TIME_SLOTS as $slot) {
                $scheduledAt = $this->resolveScheduledAt($dateValue, $slot);

                if ($scheduledAt !== null && $scheduledAt->gt($from)) {
                    $candidates->push($scheduledAt);
                }
            }
        }

        return $candidates;
    }

    private function resolveMaximumRescheduleDate(?Carbon $now = null): Carbon
    {
        return ($now ?? now())
            ->copy()
            ->startOfDay()
            ->addDays(self::CUSTOMER_RESCHEDULE_WINDOW_DAYS - 1);
    }

    private function resolveScheduledAt(string $date, string $slot): ?Carbon
    {
        $startTime = trim(explode('-', $slot, 2)[0] ?? '');
        if ($startTime === '') {
            return null;
        }

        $timezone = config('app.timezone') ?: 'Asia/Ho_Chi_Minh';

        try {
            return Carbon::createFromFormat('Y-m-d H:i', "{$date} {$startTime}", $timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveSlotValueFromStart(Carbon $scheduledAt): ?string
    {
        $startTime = $scheduledAt->format('H:i');

        foreach (self::FIXED_TIME_SLOTS as $slot) {
            $slotStart = trim(explode('-', $slot, 2)[0] ?? '');

            if ($slotStart === $startTime) {
                return $slot;
            }
        }

        return null;
    }

    private function formatSlotForDisplay(string $slot): string
    {
        return str_replace('-', ' - ', $slot);
    }

    private function formatScheduleForDisplay(Carbon $scheduledAt): string
    {
        $slot = $this->resolveSlotValueFromStart($scheduledAt);

        return trim(
            ($slot ? $this->formatSlotForDisplay($slot) : $scheduledAt->format('H:i'))
            . ' ngÃƒÂ y '
            . $scheduledAt->format('d/m/Y')
        );
    }

    private function notifyCustomerAboutBookingUpdate(
        DonDatLich $booking,
        string $title,
        string $message,
        string $type = 'booking_status_updated',
        ?string $actionLabel = null
    ): void {
        try {
            $booking->khachHang?->notify(new BookingStatusNotification(
                $booking,
                $title,
                $message,
                $type,
                $actionLabel
            ));
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Customer booking notification failed', [
                'booking_id' => $booking->id,
                'customer_id' => $booking->khach_hang_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function buildCustomerStatusNotificationContent(DonDatLich $booking, User $actor, string $previousStatus): ?array
    {
        $actorName = $booking->tho?->name ?? $actor->name ?? 'hÃ¡Â»â€¡ thÃ¡Â»â€˜ng';
        $status = $booking->trang_thai;

        if ($previousStatus === $status) {
            return null;
        }

        return match ($status) {
            'da_xac_nhan' => [
                'title' => 'Ã„ÂÃ†Â¡n Ã„â€˜Ã¡ÂºÂ·t lÃ¡Â»â€¹ch Ã„â€˜ÃƒÂ£ Ã„â€˜Ã†Â°Ã¡Â»Â£c xÃƒÂ¡c nhÃ¡ÂºÂ­n',
                'message' => 'ThÃ¡Â»Â£ ' . $actorName . ' Ã„â€˜ÃƒÂ£ xÃƒÂ¡c nhÃ¡ÂºÂ­n lÃ¡Â»â€¹ch hÃ¡ÂºÂ¹n cho Ã„â€˜Ã†Â¡n #' . $booking->id . '.',
                'type' => 'booking_claimed',
            ],
            'dang_lam' => [
                'title' => 'ThÃ¡Â»Â£ Ã„â€˜ang xÃ¡Â»Â­ lÃƒÂ½ Ã„â€˜Ã†Â¡n Ã„â€˜Ã¡ÂºÂ·t lÃ¡Â»â€¹ch',
                'message' => 'ThÃ¡Â»Â£ ' . $actorName . ' Ã„â€˜ang bÃ¡ÂºÂ¯t Ã„â€˜Ã¡ÂºÂ§u xÃ¡Â»Â­ lÃƒÂ½ Ã„â€˜Ã†Â¡n #' . $booking->id . ' cÃ¡Â»Â§a bÃ¡ÂºÂ¡n.',
                'type' => 'booking_in_progress',
            ],
            'cho_hoan_thanh' => [
                'title' => 'Ã„ÂÃ†Â¡n Ã„â€˜Ã¡ÂºÂ·t lÃ¡Â»â€¹ch Ã„â€˜ang chÃ¡Â»Â hoÃƒÂ n tÃ¡ÂºÂ¥t',
                'message' => 'ThÃ¡Â»Â£ ' . $actorName . ' Ã„â€˜ÃƒÂ£ cÃ¡ÂºÂ­p nhÃ¡ÂºÂ­t Ã„â€˜Ã†Â¡n #' . $booking->id . ' sang trÃ¡ÂºÂ¡ng thÃƒÂ¡i chÃ¡Â»Â xÃƒÂ¡c nhÃ¡ÂºÂ­n tiÃ¡Â»Ân mÃ¡ÂºÂ·t.',
                'type' => 'booking_waiting_completion',
            ],
            'cho_thanh_toan' => [
                'title' => 'Ã„ÂÃ†Â¡n Ã„â€˜Ã¡ÂºÂ·t lÃ¡Â»â€¹ch Ã„â€˜ang chÃ¡Â»Â thanh toÃƒÂ¡n trÃ¡Â»Â±c tuyÃ¡ÂºÂ¿n',
                'message' => 'ThÃ¡Â»Â£ ' . $actorName . ' Ã„â€˜ÃƒÂ£ hoÃƒÂ n thÃƒÂ nh Ã„â€˜Ã†Â¡n #' . $booking->id . '. Vui lÃƒÂ²ng chÃ¡Â»Ân cÃ¡Â»â€¢ng thanh toÃƒÂ¡n Ã„â€˜Ã¡Â»Æ’ hoÃƒÂ n tÃ¡ÂºÂ¥t.',
                'type' => 'booking_waiting_completion',
            ],
            'cho_thanh_toan' => [
                'title' => 'Ãƒâ€žÃ‚ÂÃƒâ€ Ã‚Â¡n Ãƒâ€žÃ¢â‚¬ËœÃƒÂ¡Ã‚ÂºÃ‚Â·t lÃƒÂ¡Ã‚Â»Ã¢â‚¬Â¹ch Ãƒâ€žÃ¢â‚¬Ëœang chÃƒÂ¡Ã‚Â»Ã‚Â thanh toÃƒÆ’Ã‚Â¡n trÃƒÂ¡Ã‚Â»Ã‚Â±c tuyÃƒÂ¡Ã‚ÂºÃ‚Â¿n',
                'message' => 'ThÃƒÂ¡Ã‚Â»Ã‚Â£ ' . $actorName . ' Ãƒâ€žÃ¢â‚¬ËœÃƒÆ’Ã‚Â£ hoÃƒÆ’Ã‚Â n thÃƒÆ’Ã‚Â nh Ãƒâ€žÃ¢â‚¬ËœÃƒâ€ Ã‚Â¡n #' . $booking->id . '. Vui lÃƒÆ’Ã‚Â²ng chÃƒÂ¡Ã‚Â»Ã‚Ân cÃƒÆ’Ã‚Â´ng thanh toÃƒÆ’Ã‚Â¡n Ãƒâ€žÃ¢â‚¬ËœÃƒÂ¡Ã‚Â»Ã†â€™ hoÃƒÆ’Ã‚Â n tÃƒÂ¡Ã‚ÂºÃ‚Â¥t.',
                'type' => 'booking_waiting_completion',
            ],
            'da_huy' => [
                'title' => 'Ã„ÂÃ†Â¡n Ã„â€˜Ã¡ÂºÂ·t lÃ¡Â»â€¹ch Ã„â€˜ÃƒÂ£ bÃ¡Â»â€¹ hÃ¡Â»Â§y',
                'message' => 'Ã„ÂÃ†Â¡n #' . $booking->id . ' Ã„â€˜ÃƒÂ£ bÃ¡Â»â€¹ hÃ¡Â»Â§y.'
                    . ($booking->ly_do_huy ? ' LÃƒÂ½ do: ' . $booking->ly_do_huy . '.' : ''),
                'type' => 'booking_cancelled',
            ],
            'da_xong' => [
                'title' => 'Ã„ÂÃ†Â¡n Ã„â€˜Ã¡ÂºÂ·t lÃ¡Â»â€¹ch Ã„â€˜ÃƒÂ£ hoÃƒÂ n tÃ¡ÂºÂ¥t',
                'message' => 'Ã„ÂÃ†Â¡n #' . $booking->id . ' Ã„â€˜ÃƒÂ£ Ã„â€˜Ã†Â°Ã¡Â»Â£c cÃ¡ÂºÂ­p nhÃ¡ÂºÂ­t thÃƒÂ nh hoÃƒÂ n tÃ¡ÂºÂ¥t.',
                'type' => 'booking_completed',
            ],
            default => [
                'title' => 'Ã„ÂÃ†Â¡n Ã„â€˜Ã¡ÂºÂ·t lÃ¡Â»â€¹ch vÃ¡Â»Â«a Ã„â€˜Ã†Â°Ã¡Â»Â£c cÃ¡ÂºÂ­p nhÃ¡ÂºÂ­t',
                'message' => 'Ã„ÂÃ†Â¡n #' . $booking->id . ' Ã„â€˜ÃƒÂ£ Ã„â€˜Ã†Â°Ã¡Â»Â£c cÃ¡ÂºÂ­p nhÃ¡ÂºÂ­t sang trÃ¡ÂºÂ¡ng thÃƒÂ¡i "' . $this->resolveStatusLabel($status) . '".',
                'type' => 'booking_status_updated',
            ],
        };
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }

    private function canActAsCustomer(User $user): bool
    {
        return in_array($user->role, ['customer', 'admin'], true);
    }

    private function canActAsWorker(User $user): bool
    {
        return in_array($user->role, ['worker', 'admin'], true);
    }

    private function normalizeValidatedServiceIds(array $validated): array
    {
        $serviceIds = $validated['dich_vu_ids'] ?? [];

        if (!is_array($serviceIds)) {
            $serviceIds = [$serviceIds];
        }

        return collect($serviceIds)
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function hydrateLegacyServiceColumn(DonDatLich $booking, array $serviceIds): void
    {
        static $hasLegacyServiceColumn = null;

        if ($hasLegacyServiceColumn === null) {
            $hasLegacyServiceColumn = Schema::hasColumn($booking->getTable(), 'dich_vu_id');
        }

        if ($hasLegacyServiceColumn) {
            $booking->dich_vu_id = $serviceIds[0] ?? null;
        }
    }

    private function workerHasScheduleConflict(int $workerId, string $date, string $timeSlot): bool
    {
        return DonDatLich::query()
            ->conflictsWithWorkerSchedule($workerId, $date, $timeSlot)
            ->exists();
    }

    private function getEligibleWorkersForServiceIds(array $serviceIds): Collection
    {
        $serviceIds = collect($serviceIds)
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($serviceIds)) {
            return collect();
        }

        $query = User::query()
            ->where('role', 'worker')
            ->where('is_active', true)
            ->whereHas('hoSoTho', function ($profileQuery) {
                $profileQuery
                    ->where('trang_thai_duyet', 'da_duyet')
                    ->where('dang_hoat_dong', true);
            });

        foreach ($serviceIds as $serviceId) {
            $query->whereHas('dichVus', function ($serviceQuery) use ($serviceId) {
                $serviceQuery->whereKey($serviceId);
            });
        }

        return $query->get();
    }

    private function workerSupportsBookingServices(User $worker, DonDatLich $booking): bool
    {
        $bookingServiceIds = collect($this->resolveBookingServiceIds($booking));

        if ($bookingServiceIds->isEmpty()) {
            return false;
        }

        $workerServiceIds = $worker->dichVus()
            ->pluck('danh_muc_dich_vu.id')
            ->map(static fn ($id) => (int) $id);

        return $bookingServiceIds->diff($workerServiceIds)->isEmpty();
    }

    private function applyCancellationReason(DonDatLich $booking, ?string $reasonCode, ?string $fallbackMessage = null): void
    {
        $booking->ma_ly_do_huy = $reasonCode;
        $booking->ly_do_huy = DonDatLich::cancelReasonLabel($reasonCode)
            ?? ($fallbackMessage !== null && trim($fallbackMessage) !== '' ? trim($fallbackMessage) : null);
    }

    private function resolveBookingServiceIds(DonDatLich $booking): array
    {
        $serviceIds = $booking->relationLoaded('dichVus')
            ? collect($booking->dichVus)->pluck('id')
            : $booking->dichVus()->pluck('danh_muc_dich_vu.id');

        if ($serviceIds->isEmpty() && !empty($booking->dich_vu_id)) {
            $serviceIds = collect([(int) $booking->dich_vu_id]);
        }

        return $serviceIds
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function loadBookingResponseRelations(DonDatLich $booking): void
    {
        static $serviceRelationTablesReady = null;

        $relations = ['tho:id,name', 'khachHang:id,name,email'];

        if ($serviceRelationTablesReady === null) {
            $serviceRelationTablesReady = Schema::hasTable('danh_muc_dich_vu')
                && Schema::hasTable('don_dat_lich_dich_vu');
        }

        if ($serviceRelationTablesReady) {
            $relations[] = 'dichVus:id,ten_dich_vu';
        }

        $booking->loadMissing($relations);
    }

    private function resolveStatusLabel(?string $status): string
    {
        return match ($status) {
            'cho_xac_nhan' => 'Ã„Âang tÃƒÂ¬m thÃ¡Â»Â£',
            'da_xac_nhan' => 'Ã„ÂÃƒÂ£ cÃƒÂ³ thÃ¡Â»Â£ nhÃ¡ÂºÂ­n',
            'dang_lam' => 'Ã„Âang xÃ¡Â»Â­ lÃƒÂ½',
            'cho_hoan_thanh' => 'ChÃ¡Â»Â xÃƒÂ¡c nhÃ¡ÂºÂ­n COD',
            'cho_thanh_toan' => 'ChÃ¡Â»Â thanh toÃƒÂ¡n trÃ¡Â»Â±c tuyÃ¡ÂºÂ¿n',
            'da_xong' => 'Ã„ÂÃƒÂ£ hoÃƒÂ n tÃ¡ÂºÂ¥t',
            'da_huy' => 'Ã„ÂÃƒÂ£ hÃ¡Â»Â§y',
            default => 'Ã„Âang cÃ¡ÂºÂ­p nhÃ¡ÂºÂ­t',
        };
    }

    private function resolvePendingPaymentStatus(?string $paymentMethod): string
    {
        return $paymentMethod === 'transfer' ? 'cho_thanh_toan' : 'cho_hoan_thanh';
    }
}

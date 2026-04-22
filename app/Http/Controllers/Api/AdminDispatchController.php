<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonDatLich;
use App\Models\User;
use App\Notifications\BookingStatusNotification;
use App\Notifications\NewBookingNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminDispatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $serviceId = trim((string) $request->query('service_id', ''));
        $date = trim((string) $request->query('date', ''));
        $now = Carbon::now();

        $queue = DonDatLich::query()
            ->with([
                'khachHang:id,name,phone,avatar',
                'dichVus:id,ten_dich_vu',
            ])
            ->whereNull('tho_id')
            ->where('trang_thai', 'cho_xac_nhan')
            ->get();

        $serviceOptions = $queue
            ->flatMap(function (DonDatLich $booking) {
                return $booking->dichVus->map(fn ($service) => [
                    'id' => (int) $service->id,
                    'name' => $service->ten_dich_vu,
                ]);
            })
            ->groupBy('id')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'id' => (int) ($first['id'] ?? 0),
                    'name' => (string) ($first['name'] ?? 'Dịch vụ'),
                    'count' => $items->count(),
                ];
            })
            ->sortBy('name')
            ->values();

        $filteredQueue = $queue
            ->filter(function (DonDatLich $booking) use ($search, $serviceId, $date) {
                $serviceIds = $booking->resolveServiceIds();
                $bookingDate = $booking->ngay_hen?->toDateString()
                    ?? $booking->thoi_gian_hen?->toDateString();

                if ($serviceId !== '' && !in_array((int) $serviceId, $serviceIds, true)) {
                    return false;
                }

                if ($date !== '' && $bookingDate !== $date) {
                    return false;
                }

                if ($search !== '') {
                    $haystack = Str::lower(implode(' ', [
                        $this->formatBookingCode($booking->id),
                        $booking->khachHang?->name ?? '',
                        $booking->khachHang?->phone ?? '',
                        $booking->khachHang?->avatar ?? '',
                        $this->buildServiceLabel($booking),
                        $booking->dia_chi ?? '',
                        $booking->mo_ta_van_de ?? '',
                    ]));

                    if (!str_contains($haystack, Str::lower($search))) {
                        return false;
                    }
                }

                return true;
            })
            ->sort(function (DonDatLich $left, DonDatLich $right) {
                $leftSchedule = $left->thoi_gian_hen?->timestamp
                    ?? $left->ngay_hen?->startOfDay()->timestamp
                    ?? PHP_INT_MAX;
                $rightSchedule = $right->thoi_gian_hen?->timestamp
                    ?? $right->ngay_hen?->startOfDay()->timestamp
                    ?? PHP_INT_MAX;

                if ($leftSchedule === $rightSchedule) {
                    return (int) ($left->created_at?->timestamp ?? 0) <=> (int) ($right->created_at?->timestamp ?? 0);
                }

                return $leftSchedule <=> $rightSchedule;
            })
            ->values();

        $dateOptions = $queue
            ->map(fn (DonDatLich $booking) => $booking->ngay_hen?->toDateString() ?? $booking->thoi_gian_hen?->toDateString())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_pending' => $queue->count(),
                    'filtered_count' => $filteredQueue->count(),
                    'urgent_count' => $filteredQueue->filter(fn (DonDatLich $booking) => $this->resolveUrgencyTone($booking, $now) === 'danger')->count(),
                    'today_count' => $filteredQueue->filter(fn (DonDatLich $booking) => $this->resolveBookingDate($booking)?->isSameDay($now))->count(),
                    'tomorrow_count' => $filteredQueue->filter(fn (DonDatLich $booking) => $this->resolveBookingDate($booking)?->isSameDay($now->copy()->addDay()))->count(),
                ],
                'filters' => [
                    'search' => $search,
                    'service_id' => $serviceId,
                    'date' => $date,
                    'services' => $serviceOptions,
                    'dates' => $dateOptions,
                ],
                'queue' => $filteredQueue->map(function (DonDatLich $booking) use ($now) {
                    $urgencyTone = $this->resolveUrgencyTone($booking, $now);

                    return [
                        'id' => (int) $booking->id,
                        'code' => $this->formatBookingCode($booking->id),
                        'customer_name' => $booking->khachHang?->name ?: 'Khách hàng',
                        'customer_phone' => $booking->khachHang?->phone,
                        'customer_avatar' => $booking->khachHang?->avatar,
                        'service_label' => $this->buildServiceLabel($booking),
                        'service_ids' => $booking->resolveServiceIds(),
                        'schedule_label' => $this->formatScheduleLabel($booking),
                        'booking_date' => $booking->ngay_hen?->toDateString()
                            ?? $booking->thoi_gian_hen?->toDateString(),
                        'time_slot' => DonDatLich::normalizeTimeSlot((string) $booking->khung_gio_hen),
                        'mode_label' => $booking->loai_dat_lich === 'at_home' ? 'Tại nhà' : 'Tại cửa hàng',
                        'address' => $booking->loai_dat_lich === 'at_home'
                            ? ($booking->dia_chi ?: 'Chưa cập nhật địa chỉ')
                            : 'Khách mang thiết bị đến cửa hàng',
                        'area_label' => $this->extractArea($booking->dia_chi),
                        'problem_excerpt' => $this->truncateText((string) ($booking->mo_ta_van_de ?: 'Khách chưa để mô tả vấn đề.'), 96),
                        'created_label' => $booking->created_at?->format('d/m/Y H:i'),
                        'waiting_label' => $this->formatAgeLabel($booking->created_at, $now),
                        'urgency_tone' => $urgencyTone,
                        'urgency_label' => match ($urgencyTone) {
                            'danger' => 'Cần ưu tiên',
                            'warning' => 'Sắp đến giờ',
                            default => 'Đang chờ',
                        },
                    ];
                })->values(),
            ],
        ]);
    }

    public function show(string $bookingId): JsonResponse
    {
        $booking = $this->findDispatchBooking($bookingId);

        if (!$booking) {
            return $this->errorResponse('Không tìm thấy đơn cần phân công.', 404);
        }

        $candidateBundle = $this->buildCandidateBundle($booking);

        return response()->json([
            'status' => 'success',
            'data' => [
                'booking' => [
                    'id' => (int) $booking->id,
                    'code' => $this->formatBookingCode($booking->id),
                    'status_label' => $this->formatStatusLabel($booking->trang_thai),
                    'status_tone' => $this->resolveStatusTone($booking->trang_thai),
                    'service_label' => $this->buildServiceLabel($booking),
                    'service_ids' => $booking->resolveServiceIds(),
                    'service_labels' => $booking->dichVus->pluck('ten_dich_vu')->filter()->values(),
                    'schedule_label' => $this->formatScheduleLabel($booking),
                    'booking_date' => $booking->ngay_hen?->toDateString()
                        ?? $booking->thoi_gian_hen?->toDateString(),
                    'time_slot' => DonDatLich::normalizeTimeSlot((string) $booking->khung_gio_hen),
                    'mode_label' => $booking->loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Tại cửa hàng',
                    'address' => $booking->loai_dat_lich === 'at_home'
                        ? ($booking->dia_chi ?: 'Chưa cập nhật địa chỉ')
                        : 'Khách mang thiết bị đến cửa hàng',
                    'area_label' => $this->extractArea($booking->dia_chi),
                    'problem_excerpt' => $this->truncateText((string) ($booking->mo_ta_van_de ?: 'Khách chưa để mô tả vấn đề.'), 140),
                    'created_label' => $booking->created_at?->format('d/m/Y H:i'),
                    'customer' => [
                        'id' => (int) ($booking->khachHang?->id ?? 0),
                        'name' => $booking->khachHang?->name ?: 'Khách hàng',
                        'phone' => $booking->khachHang?->phone,
                        'avatar' => $booking->khachHang?->avatar,
                    ],
                ],
                'eligibility' => [
                    'required_service_count' => count($booking->resolveServiceIds()),
                    'matching_workers' => $candidateBundle['matching_workers'],
                    'available_workers' => $candidateBundle['available_workers'],
                    'unavailable_workers' => $candidateBundle['unavailable_workers'],
                    'busy_workers' => $candidateBundle['busy_workers'],
                    'criteria' => [
                        'Đúng nhóm dịch vụ của đơn',
                        'Không trùng lịch trong cùng khung giờ',
                        'Hồ sơ thợ đã duyệt và đang hoạt động',
                    ],
                ],
                'candidates' => $candidateBundle['candidates'],
                'unavailable_candidates' => $candidateBundle['unavailable_candidates'],
            ],
        ]);
    }

    public function assign(Request $request, string $bookingId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'worker_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Dữ liệu phân công không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $booking = $this->findDispatchBooking($bookingId);

        if (!$booking) {
            return $this->errorResponse('Không tìm thấy đơn cần phân công.', 404);
        }

        if ($booking->tho_id !== null || $booking->trang_thai !== 'cho_xac_nhan') {
            return $this->errorResponse('Đơn này đã được nhận hoặc không còn nằm trong hàng chờ.', 409);
        }

        $worker = User::query()
            ->with([
                'hoSoTho:user_id,trang_thai_duyet,dang_hoat_dong,trang_thai_hoat_dong,danh_gia_trung_binh,tong_so_danh_gia',
                'dichVus:id,ten_dich_vu',
            ])
            ->where('role', 'worker')
            ->find($request->integer('worker_id'));

        if (!$worker) {
            return $this->errorResponse('Không tìm thấy thợ được chọn.', 404);
        }

        if (!$worker->is_active || !$worker->hoSoTho || $worker->hoSoTho->trang_thai_duyet !== 'da_duyet' || !$worker->hoSoTho->dang_hoat_dong) {
            return $this->errorResponse('Thợ này hiện không sẵn sàng nhận việc.', 422);
        }

        if (!$worker->supportsServiceIds($booking->resolveServiceIds())) {
            return $this->errorResponse('Thợ này không thuộc nhóm dịch vụ của đơn.', 422);
        }

        $bookingDate = $booking->ngay_hen?->toDateString()
            ?? $booking->thoi_gian_hen?->toDateString();
        $timeSlot = DonDatLich::normalizeTimeSlot((string) $booking->khung_gio_hen);

        if ($bookingDate === null || $timeSlot === '') {
            return $this->errorResponse('Đơn đặt lịch chưa có lịch hẹn hợp lệ để phân công.', 422);
        }

        $result = DB::transaction(function () use ($booking, $worker, $bookingDate, $timeSlot) {
            $lockedBooking = DonDatLich::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedBooking) {
                return $this->errorResponse('Không tìm thấy đơn cần phân công.', 404);
            }

            if ($lockedBooking->tho_id !== null || $lockedBooking->trang_thai !== 'cho_xac_nhan') {
                return $this->errorResponse('Đơn này vừa được nhận bởi người khác. Vui lòng tải lại.', 409);
            }

            User::query()->whereKey($worker->id)->lockForUpdate()->first();

            if (DonDatLich::query()->conflictsWithWorkerSchedule($worker->id, $bookingDate, $timeSlot)->exists()) {
                return $this->errorResponse('Thợ này vừa phát sinh lịch trùng khung giờ. Vui lòng chọn người khác.', 409, [
                    'worker_id' => ['Lịch thợ đã thay đổi.'],
                ]);
            }

            $lockedBooking->tho_id = $worker->id;
            $lockedBooking->trang_thai = 'da_xac_nhan';
            $lockedBooking->save();

            return $lockedBooking;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        /** @var DonDatLich $assignedBooking */
        $assignedBooking = $result;
        $assignedBooking->load([
            'khachHang:id,name,email,phone,avatar',
            'tho:id,name,email,phone,avatar',
            'dichVus:id,ten_dich_vu',
        ]);

        $this->notifyWorkerAboutAssignment($worker, $assignedBooking);
        $this->notifyCustomerAboutAssignment($assignedBooking, $worker);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã phân công đơn cho thợ thành công.',
            'data' => [
                'booking' => [
                    'id' => (int) $assignedBooking->id,
                    'code' => $this->formatBookingCode($assignedBooking->id),
                    'status_label' => $this->formatStatusLabel($assignedBooking->trang_thai),
                    'worker_name' => $assignedBooking->tho?->name ?: $worker->name,
                ],
            ],
        ]);
    }

    private function findDispatchBooking(string $bookingId): ?DonDatLich
    {
        return DonDatLich::query()
            ->with([
                'khachHang:id,name,email,phone,avatar',
                'tho:id,name,email,phone,avatar',
                'dichVus:id,ten_dich_vu',
            ])
            ->whereKey($bookingId)
            ->first();
    }

    private function buildCandidateBundle(DonDatLich $booking): array
    {
        $serviceIds = $booking->resolveServiceIds();
        $bookingDate = $booking->ngay_hen?->toDateString()
            ?? $booking->thoi_gian_hen?->toDateString();
        $timeSlot = DonDatLich::normalizeTimeSlot((string) $booking->khung_gio_hen);

        if (empty($serviceIds) || $bookingDate === null || $timeSlot === '') {
            return [
                'matching_workers' => 0,
                'available_workers' => 0,
                'unavailable_workers' => 0,
                'busy_workers' => 0,
                'candidates' => [],
                'unavailable_candidates' => [],
            ];
        }

        $matchingWorkersQuery = User::query()
            ->with([
                'hoSoTho:user_id,vi_do,kinh_do,trang_thai_duyet,dang_hoat_dong,trang_thai_hoat_dong,danh_gia_trung_binh,tong_so_danh_gia',
                'dichVus:id,ten_dich_vu',
            ])
            ->where('role', 'worker')
            ->where('is_active', true);

        foreach ($serviceIds as $serviceId) {
            $matchingWorkersQuery->whereHas('dichVus', function ($query) use ($serviceId) {
                $query->whereKey($serviceId);
            });
        }

        $matchingWorkers = $matchingWorkersQuery->get();
        $workerIds = $matchingWorkers->pluck('id')->values();

        if ($workerIds->isEmpty()) {
            return [
                'matching_workers' => 0,
                'available_workers' => 0,
                'unavailable_workers' => 0,
                'busy_workers' => 0,
                'candidates' => [],
                'unavailable_candidates' => [],
            ];
        }

        $sameDaySchedules = DonDatLich::query()
            ->select(['id', 'tho_id', 'ngay_hen', 'khung_gio_hen', 'trang_thai'])
            ->whereIn('tho_id', $workerIds)
            ->whereDate('ngay_hen', $bookingDate)
            ->whereIn('trang_thai', DonDatLich::scheduleBlockingStatuses())
            ->orderBy('khung_gio_hen')
            ->get()
            ->groupBy('tho_id');

        $workloadMap = DonDatLich::query()
            ->selectRaw('tho_id, COUNT(*) as total')
            ->whereIn('tho_id', $workerIds)
            ->whereIn('trang_thai', DonDatLich::scheduleBlockingStatuses())
            ->groupBy('tho_id')
            ->pluck('total', 'tho_id');

        $candidatePool = $matchingWorkers
            ->map(function (User $worker) use ($sameDaySchedules, $workloadMap, $booking, $timeSlot) {
                $sameDayItems = collect($sameDaySchedules->get($worker->id, collect()));
                $hasConflict = $sameDayItems->contains(function (DonDatLich $item) use ($timeSlot) {
                    return DonDatLich::normalizeTimeSlot((string) $item->khung_gio_hen) === $timeSlot;
                });

                $profile = $worker->hoSoTho;
                $approvalStatus = (string) ($profile?->trang_thai_duyet ?? '');
                $operationalStatus = (string) ($profile?->trang_thai_hoat_dong ?? '');
                $isApproved = $approvalStatus === 'da_duyet';
                $isOperational = (bool) ($profile?->dang_hoat_dong ?? false);

                $sameDaySchedule = $sameDayItems
                    ->map(function (DonDatLich $item) use ($timeSlot) {
                        $slot = DonDatLich::normalizeTimeSlot((string) $item->khung_gio_hen);

                        return [
                            'slot' => $slot,
                            'status_label' => $this->formatStatusLabel($item->trang_thai),
                            'is_conflict' => $slot === $timeSlot,
                        ];
                    })
                    ->values();

                $distanceKm = $this->resolveDistanceKm($booking, $worker);
                $activeBookingCount = (int) ($workloadMap[$worker->id] ?? 0);
                $sameDayCount = $sameDayItems->count();
                $rating = (float) ($worker->hoSoTho?->danh_gia_trung_binh ?? 0);

                $availabilityReason = null;
                $availabilityTone = 'muted';
                if (!$profile || !$isApproved) {
                    $availabilityReason = 'Chưa duyệt';
                    $availabilityTone = 'warning';
                } elseif ($operationalStatus === 'tam_khoa') {
                    $availabilityReason = 'Đang khóa';
                    $availabilityTone = 'danger';
                } elseif (!$isOperational || $operationalStatus === 'ngung_hoat_dong') {
                    $availabilityReason = 'Offline';
                    $availabilityTone = 'muted';
                } elseif ($operationalStatus === 'dang_ban') {
                    $availabilityReason = 'Đang bận';
                    $availabilityTone = 'warning';
                } elseif ($hasConflict) {
                    $availabilityReason = 'Trùng lịch';
                    $availabilityTone = 'danger';
                }

                $badges = collect([
                    'Hợp dịch vụ',
                    $availabilityReason === null
                        ? ($sameDayCount === 0 ? 'Trống cả ngày' : 'Không trùng slot')
                        : null,
                    $activeBookingCount <= 1 ? 'Tải nhẹ' : null,
                    $distanceKm !== null && $distanceKm <= 5 ? 'Gần địa chỉ' : null,
                ])->filter()->values();

                return [
                    'id' => (int) $worker->id,
                    'name' => $worker->name,
                    'phone' => $worker->phone,
                    'avatar' => $worker->avatar,
                    'rating_avg' => $rating > 0 ? round($rating, 1) : null,
                    'rating_count' => (int) ($worker->hoSoTho?->tong_so_danh_gia ?? 0),
                    'active_booking_count' => $activeBookingCount,
                    'same_day_booking_count' => $sameDayCount,
                    'distance_km' => $distanceKm,
                    'services' => $worker->dichVus->pluck('ten_dich_vu')->filter()->values(),
                    'operational_status' => $operationalStatus ?: 'dang_hoat_dong',
                    'operational_label' => $this->formatOperationalStatus($operationalStatus ?: 'dang_hoat_dong'),
                    'day_schedule' => $sameDaySchedule,
                    'is_available' => $availabilityReason === null,
                    'availability_reason' => $availabilityReason,
                    'availability_tone' => $availabilityTone,
                    'badges' => $badges,
                    'recommendation_score' => round(($rating * 20) - ($activeBookingCount * 8) - ($sameDayCount * 10) - ($distanceKm ?? 0), 2),
                ];
            })
            ->values();

        $availableCandidates = $candidatePool
            ->where('is_available', true)
            ->sortByDesc('recommendation_score')
            ->values()
            ->map(function (array $candidate, int $index) {
                unset($candidate['recommendation_score']);
                $candidate['is_recommended'] = $index === 0;

                return $candidate;
            })
            ->values();

        $unavailableCandidates = $candidatePool
            ->where('is_available', false)
            ->sortBy(function (array $candidate) {
                $priority = match ($candidate['availability_reason'] ?? '') {
                    'Trùng lịch' => 1,
                    'Đang bận' => 2,
                    'Offline' => 3,
                    'Đang khóa' => 4,
                    default => 5,
                };

                return $priority . '-' . ($candidate['name'] ?? '');
            })
            ->values()
            ->map(function (array $candidate) {
                unset($candidate['recommendation_score']);
                $candidate['is_recommended'] = false;

                return $candidate;
            })
            ->values();

        return [
            'matching_workers' => $matchingWorkers->count(),
            'available_workers' => $availableCandidates->count(),
            'unavailable_workers' => $unavailableCandidates->count(),
            'busy_workers' => $unavailableCandidates
                ->where('availability_reason', 'Trùng lịch')
                ->count(),
            'candidates' => $availableCandidates,
            'unavailable_candidates' => $unavailableCandidates,
        ];
    }

    private function notifyWorkerAboutAssignment(User $worker, DonDatLich $booking): void
    {
        try {
            $worker->notify(new NewBookingNotification($booking));
        } catch (\Throwable $exception) {
            Log::warning('Dispatch worker notification failed', [
                'booking_id' => $booking->id,
                'worker_id' => $worker->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function notifyCustomerAboutAssignment(DonDatLich $booking, User $worker): void
    {
        try {
            $booking->khachHang?->notify(new BookingStatusNotification(
                $booking,
                'Đơn đặt lịch đã có thợ phụ trách',
                'Admin đã phân công thợ ' . ($worker->name ?? 'hệ thống') . ' cho đơn #' . $booking->id . ' của bạn.',
                'booking_assigned_by_admin',
                'Xem lịch hẹn'
            ));
        } catch (\Throwable $exception) {
            Log::warning('Dispatch customer notification failed', [
                'booking_id' => $booking->id,
                'customer_id' => $booking->khach_hang_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function buildServiceLabel(DonDatLich $booking): string
    {
        return $booking->dichVus->pluck('ten_dich_vu')->filter()->implode(', ') ?: 'Chưa gán dịch vụ';
    }

    private function formatBookingCode(int|string $id): string
    {
        return 'DD-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    private function formatStatusLabel(?string $status): string
    {
        return match ($status) {
            'cho_xac_nhan' => 'Chờ xác nhận',
            'da_xac_nhan' => 'Đã xác nhận',
            'dang_lam' => 'Đang làm',
            'cho_hoan_thanh' => 'Chờ nghiệm thu',
            'cho_thanh_toan' => 'Chờ thanh toán',
            'da_xong', 'hoan_thanh' => 'Hoàn thành',
            'da_huy' => 'Đã hủy',
            default => 'Chưa cập nhật',
        };
    }

    private function resolveStatusTone(?string $status): string
    {
        return match ($status) {
            'cho_xac_nhan', 'da_xac_nhan' => 'info',
            'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan' => 'warning',
            'da_xong', 'hoan_thanh' => 'success',
            'da_huy' => 'danger',
            default => 'muted',
        };
    }

    private function formatScheduleLabel(DonDatLich $booking): string
    {
        if ($booking->thoi_gian_hen) {
            return $booking->thoi_gian_hen->format('d/m/Y H:i');
        }

        if ($booking->ngay_hen && $booking->khung_gio_hen) {
            return $booking->ngay_hen->format('d/m/Y') . ' - ' . $booking->khung_gio_hen;
        }

        if ($booking->ngay_hen) {
            return $booking->ngay_hen->format('d/m/Y');
        }

        return optional($booking->created_at)->format('d/m/Y H:i') ?: 'Chưa chốt lịch';
    }

    private function resolveBookingDate(DonDatLich $booking): ?Carbon
    {
        if ($booking->thoi_gian_hen) {
            return $booking->thoi_gian_hen->copy();
        }

        if ($booking->ngay_hen) {
            return $booking->ngay_hen->copy()->startOfDay();
        }

        return null;
    }

    private function resolveUrgencyTone(DonDatLich $booking, Carbon $now): string
    {
        $scheduledAt = $this->resolveBookingDate($booking);

        if ($scheduledAt && $scheduledAt->isSameDay($now)) {
            return 'danger';
        }

        $waitingMinutes = $booking->created_at?->diffInMinutes($now) ?? 0;

        if ($waitingMinutes >= 30) {
            return 'warning';
        }

        return 'info';
    }

    private function formatAgeLabel(?Carbon $from, Carbon $now): string
    {
        if (!$from) {
            return 'Mới tạo';
        }

        $minutes = max(0, $from->diffInMinutes($now));

        if ($minutes < 60) {
            return $minutes . ' phút';
        }

        $hours = (int) floor($minutes / 60);

        if ($hours < 24) {
            return $hours . ' giờ';
        }

        $days = (int) floor($hours / 24);

        return $days . ' ngày';
    }

    private function extractArea(?string $address): ?string
    {
        if ($address === null || trim($address) === '') {
            return null;
        }

        $parts = collect(explode(',', $address))
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->values();

        if ($parts->isEmpty()) {
            return null;
        }

        if ($parts->count() >= 2) {
            return Str::limit($parts->slice(-2)->implode(', '), 48, '...');
        }

        return Str::limit((string) $parts->last(), 48, '...');
    }

    private function truncateText(string $text, int $limit = 100): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', trim($text)) ?: '', $limit, '...');
    }

    private function formatOperationalStatus(string $status): string
    {
        return match ($status) {
            'dang_ban' => 'Đang bận',
            'ngung_hoat_dong' => 'Tạm nghỉ',
            'tam_khoa' => 'Tạm khóa',
            default => 'Sẵn sàng',
        };
    }

    private function resolveDistanceKm(DonDatLich $booking, User $worker): ?float
    {
        if (
            $booking->loai_dat_lich !== 'at_home'
            || !is_numeric($booking->vi_do)
            || !is_numeric($booking->kinh_do)
            || !is_numeric($worker->hoSoTho?->vi_do)
            || !is_numeric($worker->hoSoTho?->kinh_do)
        ) {
            return null;
        }

        $lat1 = deg2rad((float) $booking->vi_do);
        $lng1 = deg2rad((float) $booking->kinh_do);
        $lat2 = deg2rad((float) $worker->hoSoTho?->vi_do);
        $lng2 = deg2rad((float) $worker->hoSoTho?->kinh_do);

        $latDelta = $lat2 - $lat1;
        $lngDelta = $lng2 - $lng1;

        $a = sin($latDelta / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($lngDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(6371 * $c, 1);
    }

    private function errorResponse(string $message, int $status, array $errors = []): JsonResponse
    {
        $payload = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}

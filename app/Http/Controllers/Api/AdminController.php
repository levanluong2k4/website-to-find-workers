<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiKnowledgeItem;
use App\Models\CustomerFeedbackCase;
use App\Models\DanhMucDichVu;
use App\Models\DanhGia;
use App\Models\DonDatLich;
use App\Models\HoSoTho;
use App\Models\HuongXuLy;
use App\Models\LinhKien;
use App\Models\NguyenNhan;
use App\Models\ThanhToan;
use App\Models\TrieuChung;
use App\Models\User;
use App\Services\Chat\AssistantSoulConfigService;
use App\Services\Chat\AiKnowledgeSyncService;
use App\Services\Chat\TextNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function getDashboardStats(Request $request)
    {
        $range = $this->resolveDashboardRange($request->query('period'));
        $completedStatuses = ['hoan_thanh', 'da_xong'];
        $comparisonLabel = $this->dashboardPeriodComparisonLabel($range['period']);
        $now = Carbon::now();

        $totalCustomers = User::where('role', 'customer')->count();
        $totalWorkers = User::where('role', 'worker')->count();
        $workerProfiles = HoSoTho::query();
        $pendingWorkerProfiles = (clone $workerProfiles)
            ->where('trang_thai_duyet', 'cho_duyet')
            ->count();
        $activeWorkers = (clone $workerProfiles)
            ->where('trang_thai_duyet', 'da_duyet')
            ->where('dang_hoat_dong', true)
            ->count();
        $lowRatingWorkers = (clone $workerProfiles)
            ->where('tong_so_danh_gia', '>', 0)
            ->where('danh_gia_trung_binh', '<', 3.5)
            ->count();

        $totalBookings = DonDatLich::count();
        $completedBookings = DonDatLich::whereIn('trang_thai', $completedStatuses)->count();
        $canceledBookings = DonDatLich::where('trang_thai', 'da_huy')->count();

        $completedPeriodQuery = DonDatLich::query()
            ->whereIn('trang_thai', $completedStatuses)
            ->whereBetween(
                DB::raw('COALESCE(thoi_gian_hoan_thanh, created_at)'),
                [$range['start'], $range['end']]
            );

        $completedTodayQuery = DonDatLich::query()
            ->whereIn('trang_thai', $completedStatuses)
            ->whereBetween(
                DB::raw('COALESCE(thoi_gian_hoan_thanh, created_at)'),
                [$range['today_start'], $range['today_end']]
            );

        $completedPreviousQuery = DonDatLich::query()
            ->whereIn('trang_thai', $completedStatuses)
            ->whereBetween(
                DB::raw('COALESCE(thoi_gian_hoan_thanh, created_at)'),
                [$range['previous_start'], $range['previous_end']]
            );
        $completedYesterdayQuery = DonDatLich::query()
            ->whereIn('trang_thai', $completedStatuses)
            ->whereBetween(
                DB::raw('COALESCE(thoi_gian_hoan_thanh, created_at)'),
                [$range['today_start']->copy()->subDay(), $range['today_end']->copy()->subDay()]
            );

        $totalRevenue = (float) (clone $completedPeriodQuery)->sum('tong_tien');
        $revenueToday = (float) (clone $completedTodayQuery)->sum('tong_tien');
        $previousRevenue = (float) (clone $completedPreviousQuery)->sum('tong_tien');
        $yesterdayRevenue = (float) (clone $completedYesterdayQuery)->sum('tong_tien');
        $systemCommission = round($totalRevenue * 0.10, 2);
        $revenueDelta = $this->calculateGrowthPercent($totalRevenue, $previousRevenue);
        $todayRevenueDelta = $this->calculateGrowthPercent($revenueToday, $yesterdayRevenue);

        $todayBookingsBase = $this->dashboardBookingsForDay(Carbon::today());
        $bookingsToday = (clone $todayBookingsBase)->count();
        $bookingsPendingToday = (clone $todayBookingsBase)
            ->where('trang_thai', 'cho_xac_nhan')
            ->count();
        $bookingsInProgressToday = (clone $todayBookingsBase)
            ->whereIn('trang_thai', ['da_xac_nhan', 'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan'])
            ->count();
        $bookingsCompletedToday = (clone $todayBookingsBase)
            ->whereIn('trang_thai', $completedStatuses)
            ->count();

        $stalePendingBookings = DonDatLich::query()
            ->where('trang_thai', 'cho_xac_nhan')
            ->where('created_at', '<=', $now->copy()->subMinutes(30))
            ->count();
        $pendingTransferBookings = DonDatLich::query()
            ->where('phuong_thuc_thanh_toan', 'transfer')
            ->where('trang_thai_thanh_toan', false)
            ->where('trang_thai', '!=', 'da_huy')
            ->count();
        $canceledToday = DonDatLich::query()
            ->where('trang_thai', 'da_huy')
            ->whereBetween('updated_at', [$range['today_start'], $range['today_end']])
            ->count();

        $lowRatingsInPeriod = DanhGia::query()
            ->where('so_sao', '<=', 2)
            ->whereBetween('created_at', [$range['start'], $range['end']]);
        $veryLowRatingsInPeriod = DanhGia::query()
            ->where('so_sao', 1)
            ->whereBetween('created_at', [$range['start'], $range['end']]);
        $cancellationsWithReasonInPeriod = DonDatLich::query()
            ->where('trang_thai', 'da_huy')
            ->whereNotNull('ly_do_huy')
            ->whereBetween('updated_at', [$range['start'], $range['end']]);

        $complaintsNew = (clone $lowRatingsInPeriod)->count() + (clone $cancellationsWithReasonInPeriod)->count();
        $complaintsCritical = (clone $veryLowRatingsInPeriod)->count();
        $complaintsCanceled = (clone $cancellationsWithReasonInPeriod)->count();

        $transferRevenue = (float) (clone $completedPeriodQuery)
            ->where('phuong_thuc_thanh_toan', 'transfer')
            ->sum('tong_tien');
        $transferShare = $totalRevenue > 0
            ? round(($transferRevenue / $totalRevenue) * 100)
            : 0;

        $topService = DB::table('don_dat_lich_dich_vu as pivot')
            ->join('don_dat_lich as bookings', 'bookings.id', '=', 'pivot.don_dat_lich_id')
            ->join('danh_muc_dich_vu as services', 'services.id', '=', 'pivot.dich_vu_id')
            ->whereIn('bookings.trang_thai', $completedStatuses)
            ->whereBetween(
                DB::raw('COALESCE(bookings.thoi_gian_hoan_thanh, bookings.created_at)'),
                [$range['start'], $range['end']]
            )
            ->select('services.ten_dich_vu', DB::raw('COUNT(*) as total'))
            ->groupBy('services.id', 'services.ten_dich_vu')
            ->orderByDesc('total')
            ->first();

        $trend = $this->buildDashboardRevenueTrend($range, $completedStatuses);

        $recentRevenueRows = DonDatLich::query()
            ->with([
                'khachHang:id,name',
                'tho:id,name',
                'dichVus:id,ten_dich_vu',
            ])
            ->whereIn('trang_thai', $completedStatuses)
            ->orderByRaw('COALESCE(thoi_gian_hoan_thanh, created_at) DESC')
            ->limit(4)
            ->get()
            ->map(function (DonDatLich $booking) {
                $serviceName = $booking->dichVus->pluck('ten_dich_vu')->filter()->implode(', ');

                return [
                    'booking_code' => 'DD-' . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT),
                    'service_name' => $serviceName ?: 'Dịch vụ chưa gán',
                    'date_label' => optional($booking->thoi_gian_hoan_thanh ?? $booking->created_at)->format('d/m - H:i'),
                    'total_amount' => (float) ($booking->tong_tien ?? 0),
                    'commission_amount' => round(((float) ($booking->tong_tien ?? 0)) * 0.10, 2),
                ];
            })
            ->values();

        $highRiskWorkers = DonDatLich::query()
            ->join('users as workers', 'workers.id', '=', 'don_dat_lich.tho_id')
            ->where('don_dat_lich.trang_thai', 'da_huy')
            ->whereNotNull('don_dat_lich.tho_id')
            ->whereBetween('don_dat_lich.updated_at', [$range['today_start']->copy()->subDays(6), $range['today_end']])
            ->select('workers.name', DB::raw('COUNT(*) as total'))
            ->groupBy('workers.id', 'workers.name')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('total')
            ->limit(1)
            ->get();

        $workerWatchItems = collect([
            $highRiskWorkers->first()
                ? $highRiskWorkers->first()->name . ' có ' . $highRiskWorkers->first()->total . ' đơn hủy trong 7 ngày.'
                : null,
            $pendingWorkerProfiles > 0
                ? $pendingWorkerProfiles . ' hồ sơ đang chờ admin phê duyệt.'
                : null,
            $lowRatingWorkers > 0
                ? $lowRatingWorkers . ' thợ có điểm trung bình dưới 3.5 sao.'
                : null,
            (($totalWorkers - $activeWorkers) > 0)
                ? ($totalWorkers - $activeWorkers) . ' tài khoản thợ chưa sẵn sàng nhận việc.'
                : null,
        ])->filter()->values();

        $complaintItems = $this->buildComplaintItems($range['start'], $range['end']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'meta' => [
                    'period' => $range['period'],
                    'period_label' => $range['label'],
                    'updated_at' => $now->format('H:i'),
                ],
                'summary' => [
                    'revenue_today' => [
                        'value' => $revenueToday,
                        'change_percent' => $todayRevenueDelta,
                        'note' => $this->formatGrowthLabel($todayRevenueDelta, 'so với hôm qua'),
                    ],
                    'bookings_today' => [
                        'value' => $bookingsToday,
                        'note' => $bookingsPendingToday . ' đơn cần xác nhận',
                    ],
                    'commission' => [
                        'value' => $systemCommission,
                        'note' => $transferShare . '% giao dịch chuyển khoản',
                    ],
                    'complaints' => [
                        'value' => $complaintsNew,
                        'note' => $complaintsCritical . ' vụ mức ưu tiên cao',
                    ],
                ],
                'users' => [
                    'customers' => $totalCustomers,
                    'workers' => $totalWorkers,
                    'pending_worker_profiles' => $pendingWorkerProfiles,
                ],
                'bookings' => [
                    'total' => $totalBookings,
                    'completed' => $completedBookings,
                    'canceled' => $canceledBookings,
                    'today' => $bookingsToday,
                    'today_pending' => $bookingsPendingToday,
                    'today_in_progress' => $bookingsInProgressToday,
                    'today_completed' => $bookingsCompletedToday,
                    'queue' => [
                        [
                            'tone' => 'warning',
                            'label' => $stalePendingBookings . ' đơn chưa có thợ nhận sau 30 phút',
                        ],
                        [
                            'tone' => 'info',
                            'label' => $pendingTransferBookings . ' đơn chờ đối soát thanh toán',
                        ],
                        [
                            'tone' => 'danger',
                            'label' => $canceledToday . ' đơn hủy trong ngày cần xem lý do',
                        ],
                    ],
                ],
                'revenue' => [
                    'total_revenue' => $totalRevenue,
                    'revenue_today' => $revenueToday,
                    'system_commission' => $systemCommission,
                    'change_percent' => $revenueDelta,
                    'change_note' => $this->formatGrowthLabel($revenueDelta, $comparisonLabel),
                    'trend' => $trend,
                    'top_service' => $topService?->ten_dich_vu ?? 'Chưa có dữ liệu',
                    'transfer_share' => $transferShare,
                ],
                'alerts' => [
                    'items' => [
                        [
                            'priority' => 'P1',
                            'title' => 'Phê duyệt thợ',
                            'detail' => $pendingWorkerProfiles . ' hồ sơ đang chờ duyệt. Ưu tiên nhóm đã bổ sung đủ giấy tờ.',
                            'tone' => 'warning',
                        ],
                        [
                            'priority' => 'P2',
                            'title' => 'Đối soát',
                            'detail' => $pendingTransferBookings . ' giao dịch chuyển khoản chưa xác nhận trạng thái.',
                            'tone' => 'info',
                        ],
                        [
                            'priority' => 'P1',
                            'title' => 'Phản ánh mới',
                            'detail' => $complaintsNew . ' phản ánh mới từ khách hàng cần kiểm tra trong ca.',
                            'tone' => 'danger',
                        ],
                    ],
                    'footer' => 'Cập nhật ' . $now->format('H:i') . ' · Nguồn: dữ liệu dashboard admin',
                ],
                'workers_summary' => [
                    'total' => $totalWorkers,
                    'active' => $activeWorkers,
                    'pending_approval' => $pendingWorkerProfiles,
                    'low_rating' => $lowRatingWorkers,
                    'watch_items' => $workerWatchItems,
                ],
                'workers_map' => $this->buildDashboardWorkerMap($now),
                'revenue_table' => $recentRevenueRows,
                'complaints' => [
                    'new' => $complaintsNew,
                    'low_rating' => (clone $lowRatingsInPeriod)->count(),
                    'canceled' => $complaintsCanceled,
                    'items' => $complaintItems,
                ],
            ],
        ]);
    }

    private function buildDashboardWorkerMap(Carbon $now): array
    {
        $workers = User::query()
            ->with([
                'hoSoTho:user_id,vi_do,kinh_do,trang_thai_duyet,dang_hoat_dong,trang_thai_hoat_dong,danh_gia_trung_binh,tong_so_danh_gia',
                'dichVus:id,ten_dich_vu',
                'donDatLichAsTho' => function ($query) {
                    $query
                        ->select(['id', 'tho_id', 'dia_chi', 'ngay_hen', 'khung_gio_hen', 'trang_thai', 'created_at'])
                        ->with('dichVus:id,ten_dich_vu')
                        ->whereIn('trang_thai', DonDatLich::scheduleBlockingStatuses())
                        ->orderByRaw("
                            CASE trang_thai
                                WHEN 'dang_lam' THEN 0
                                WHEN 'da_xac_nhan' THEN 1
                                WHEN 'cho_hoan_thanh' THEN 2
                                WHEN 'cho_thanh_toan' THEN 3
                                WHEN 'cho_xac_nhan' THEN 4
                                ELSE 5
                            END ASC
                        ")
                        ->orderBy('ngay_hen')
                        ->orderByDesc('created_at');
                },
            ])
            ->where('role', 'worker')
            ->where('is_active', true)
            ->whereHas('hoSoTho', function ($query) {
                $query->where('trang_thai_duyet', 'da_duyet');
            })
            ->get();

        $snapshots = $workers->map(function (User $worker) {
            $profile = $worker->hoSoTho;
            $lat = $this->normalizeDashboardCoordinate($profile?->vi_do, -90, 90);
            $lng = $this->normalizeDashboardCoordinate($profile?->kinh_do, -180, 180);
            $hasCoordinates = $lat !== null && $lng !== null;
            $currentBooking = $this->resolveDashboardWorkerCurrentBooking($worker->donDatLichAsTho ?? collect());
            $status = $this->resolveDashboardWorkerMapStatus($worker, $currentBooking);
            $serviceNames = $worker->dichVus->pluck('ten_dich_vu')->filter()->values();
            $servicesLabel = $serviceNames->take(2)->implode(', ');

            if ($serviceNames->count() > 2) {
                $servicesLabel .= ' +' . ($serviceNames->count() - 2);
            }

            $ratingAvg = (float) ($profile?->danh_gia_trung_binh ?? 0);
            $ratingCount = (int) ($profile?->tong_so_danh_gia ?? 0);
            $bookingServiceLabel = $currentBooking
                ? ($currentBooking->dichVus->pluck('ten_dich_vu')->filter()->implode(', ') ?: 'Don dang mo')
                : null;

            return [
                'id' => (int) $worker->id,
                'name' => $worker->name ?: 'Tho ky thuat',
                'avatar' => $worker->avatar ?: '/assets/images/user-default.png',
                'point' => [
                    'lat' => $lat,
                    'lng' => $lng,
                ],
                'map_status' => $status['key'],
                'map_tone' => $status['tone'],
                'map_status_label' => $status['label'],
                'status_detail' => $status['detail'],
                'services_label' => $servicesLabel !== '' ? $servicesLabel : 'Chua gan nhom dich vu',
                'rating_label' => $ratingCount > 0
                    ? number_format($ratingAvg, 1) . '/5 • ' . $ratingCount . ' danh gia'
                    : 'Chua co danh gia',
                'schedule_label' => $this->buildDashboardWorkerScheduleLabel($currentBooking),
                'area_label' => $this->extractCustomerArea($currentBooking?->dia_chi ?: $worker->address) ?: 'Chua co khu vuc',
                'current_job_label' => $currentBooking
                    ? $this->formatDashboardBookingCode($currentBooking->id) . ' • ' . $bookingServiceLabel
                    : 'Khong co don dang mo',
                'has_coordinates' => $hasCoordinates,
                'status_sort' => $this->resolveDashboardWorkerStatusOrder($status['key']),
            ];
        });

        $trackedWorkers = $snapshots
            ->where('has_coordinates', true)
            ->sortBy(function (array $worker) {
                return sprintf(
                    '%02d-%s',
                    (int) ($worker['status_sort'] ?? 99),
                    Str::lower((string) ($worker['name'] ?? ''))
                );
            })
            ->values()
            ->map(function (array $worker) {
                unset($worker['has_coordinates'], $worker['status_sort']);

                return $worker;
            })
            ->values();

        $defaultCenter = [
            'lat' => 12.2388,
            'lng' => 109.1967,
        ];

        $center = $trackedWorkers->isNotEmpty()
            ? [
                'lat' => round((float) $trackedWorkers->avg(fn (array $worker) => (float) data_get($worker, 'point.lat', 0)), 6),
                'lng' => round((float) $trackedWorkers->avg(fn (array $worker) => (float) data_get($worker, 'point.lng', 0)), 6),
            ]
            : $defaultCenter;

        return [
            'center' => $center,
            'tracked_count' => $trackedWorkers->count(),
            'repairing_count' => $trackedWorkers->where('map_status', 'repairing')->count(),
            'scheduled_count' => $trackedWorkers->where('map_status', 'scheduled')->count(),
            'available_count' => $trackedWorkers->where('map_status', 'available')->count(),
            'offline_count' => $trackedWorkers->where('map_status', 'offline')->count(),
            'missing_location_count' => $snapshots->where('has_coordinates', false)->count(),
            'poll_interval_seconds' => 30,
            'workers' => $trackedWorkers->all(),
            'updated_at' => $now->format('H:i'),
        ];
    }

    private function resolveDashboardWorkerCurrentBooking(Collection $bookings): ?DonDatLich
    {
        if ($bookings->isEmpty()) {
            return null;
        }

        return $bookings
            ->sortBy(function (DonDatLich $booking) {
                $priority = match ((string) $booking->trang_thai) {
                    'dang_lam' => 0,
                    'da_xac_nhan' => 1,
                    'cho_hoan_thanh' => 2,
                    'cho_thanh_toan' => 3,
                    'cho_xac_nhan' => 4,
                    default => 5,
                };
                $timestamp = $booking->ngay_hen?->startOfDay()?->timestamp
                    ?? $booking->created_at?->timestamp
                    ?? PHP_INT_MAX;

                return sprintf('%02d-%020d', $priority, $timestamp);
            })
            ->first();
    }

    private function resolveDashboardWorkerMapStatus(User $worker, ?DonDatLich $currentBooking): array
    {
        $profile = $worker->hoSoTho;
        $operationalStatus = (string) ($profile?->trang_thai_hoat_dong ?: 'dang_hoat_dong');
        $isOperational = (bool) ($profile?->dang_hoat_dong ?? false);

        if ($currentBooking) {
            return match ((string) $currentBooking->trang_thai) {
                'dang_lam' => [
                    'key' => 'repairing',
                    'tone' => 'busy',
                    'label' => 'Dang sua chua',
                    'detail' => 'Dang xu ly ' . ($currentBooking->dichVus->pluck('ten_dich_vu')->filter()->implode(', ') ?: 'mot don dang mo'),
                ],
                'cho_hoan_thanh', 'cho_thanh_toan' => [
                    'key' => 'scheduled',
                    'tone' => 'scheduled',
                    'label' => 'Cho chot don',
                    'detail' => 'Dang o giai doan hoan tat / thanh toan don hien tai.',
                ],
                default => [
                    'key' => 'scheduled',
                    'tone' => 'scheduled',
                    'label' => 'Dang co lich',
                    'detail' => 'Tho da nhan lich va dang bi khoa slot lam viec.',
                ],
            };
        }

        if ($operationalStatus === 'tam_khoa') {
            return [
                'key' => 'offline',
                'tone' => 'offline',
                'label' => 'Tam khoa',
                'detail' => 'Tai khoan tam khoa, khong the dieu phoi.',
            ];
        }

        if (!$isOperational || $operationalStatus === 'ngung_hoat_dong') {
            return [
                'key' => 'offline',
                'tone' => 'offline',
                'label' => 'Tam nghi',
                'detail' => 'Tho da tam dung nhan lich tren he thong.',
            ];
        }

        if ($operationalStatus === 'dang_ban') {
            return [
                'key' => 'scheduled',
                'tone' => 'scheduled',
                'label' => 'Dang ban',
                'detail' => 'Tho dang duoc danh dau ban va can admin kiem tra them.',
            ];
        }

        return [
            'key' => 'available',
            'tone' => 'free',
            'label' => 'Trong lich',
            'detail' => 'Chua co lich dang mo, co the dieu phoi ngay.',
        ];
    }

    private function buildDashboardWorkerScheduleLabel(?DonDatLich $booking): string
    {
        if (!$booking) {
            return 'Chua co lich dang mo';
        }

        $dateLabel = $booking->ngay_hen?->format('d/m/Y') ?: 'Chua co ngay hen';
        $slotLabel = trim((string) ($booking->khung_gio_hen ?? ''));

        return $slotLabel !== ''
            ? $dateLabel . ' • ' . $slotLabel
            : $dateLabel;
    }

    private function formatDashboardBookingCode(int|string $bookingId): string
    {
        return 'DD-' . str_pad((string) $bookingId, 4, '0', STR_PAD_LEFT);
    }

    private function resolveDashboardWorkerStatusOrder(string $status): int
    {
        return match ($status) {
            'repairing' => 0,
            'scheduled' => 1,
            'available' => 2,
            default => 3,
        };
    }

    private function normalizeDashboardCoordinate($value, float $min, float $max): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $normalized = (float) $value;

        if ($normalized < $min || $normalized > $max || ($normalized === 0.0 && $min < 0 && $max > 0)) {
            return null;
        }

        return $normalized;
    }

    private function resolveDashboardRange(?string $period): array
    {
        $normalizedPeriod = in_array($period, ['day', 'month', 'year', 'today', '7d', '30d'], true) ? $period : 'month';
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $monthStart = $todayStart->copy()->startOfMonth();
        $yearStart = $todayStart->copy()->startOfYear();

        $monthElapsedDays = $monthStart->diffInDays($todayStart) + 1;
        $previousMonthStart = $monthStart->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $previousMonthStart->copy()->addDays($monthElapsedDays - 1)->endOfDay();
        if ($previousMonthEnd->gt($previousMonthStart->copy()->endOfMonth())) {
            $previousMonthEnd = $previousMonthStart->copy()->endOfMonth();
        }

        $yearElapsedDays = $yearStart->diffInDays($todayStart) + 1;
        $previousYearStart = $yearStart->copy()->subYear()->startOfYear();
        $previousYearEnd = $previousYearStart->copy()->addDays($yearElapsedDays - 1)->endOfDay();
        if ($previousYearEnd->gt($previousYearStart->copy()->endOfYear())) {
            $previousYearEnd = $previousYearStart->copy()->endOfYear();
        }

        return match ($normalizedPeriod) {
            'day' => [
                'period' => 'day',
                'label' => 'Hom nay',
                'start' => $todayStart->copy(),
                'end' => $todayEnd->copy(),
                'previous_start' => $todayStart->copy()->subDay(),
                'previous_end' => $todayEnd->copy()->subDay(),
                'today_start' => $todayStart->copy(),
                'today_end' => $todayEnd->copy(),
            ],
            'month' => [
                'period' => 'month',
                'label' => 'Thang nay',
                'start' => $monthStart->copy(),
                'end' => $todayEnd->copy(),
                'previous_start' => $previousMonthStart->copy(),
                'previous_end' => $previousMonthEnd->copy(),
                'today_start' => $todayStart->copy(),
                'today_end' => $todayEnd->copy(),
            ],
            'year' => [
                'period' => 'year',
                'label' => 'Nam nay',
                'start' => $yearStart->copy(),
                'end' => $todayEnd->copy(),
                'previous_start' => $previousYearStart->copy(),
                'previous_end' => $previousYearEnd->copy(),
                'today_start' => $todayStart->copy(),
                'today_end' => $todayEnd->copy(),
            ],
            'today' => [
                'period' => 'day',
                'label' => 'Hôm nay',
                'start' => $todayStart->copy(),
                'end' => $todayEnd->copy(),
                'previous_start' => $todayStart->copy()->subDay(),
                'previous_end' => $todayEnd->copy()->subDay(),
                'today_start' => $todayStart->copy(),
                'today_end' => $todayEnd->copy(),
            ],
            '30d' => [
                'period' => '30d',
                'label' => '30 ngày',
                'start' => $todayStart->copy()->subDays(29),
                'end' => $todayEnd->copy(),
                'previous_start' => $todayStart->copy()->subDays(59),
                'previous_end' => $todayEnd->copy()->subDays(30),
                'today_start' => $todayStart->copy(),
                'today_end' => $todayEnd->copy(),
            ],
            default => [
                'period' => '7d',
                'label' => '7 ngày',
                'start' => $todayStart->copy()->subDays(6),
                'end' => $todayEnd->copy(),
                'previous_start' => $todayStart->copy()->subDays(13),
                'previous_end' => $todayEnd->copy()->subDays(7),
                'today_start' => $todayStart->copy(),
                'today_end' => $todayEnd->copy(),
            ],
        };
    }

    private function dashboardPeriodComparisonLabel(string $period): string
    {
        return match ($period) {
            'day' => 'so voi hom qua',
            'month' => 'so voi thang truoc',
            'year' => 'so voi nam truoc',
            'today' => 'so với hôm qua',
            '30d' => 'so với 30 ngày trước',
            default => 'so với 7 ngày trước',
        };
    }

    private function calculateGrowthPercent(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function formatGrowthLabel(float $percent, string $comparisonLabel): string
    {
        $prefix = $percent > 0 ? '+' : '';

        return $prefix . number_format($percent, 1) . '% ' . $comparisonLabel;
    }

    private function dashboardBookingsForDay(Carbon $day): Builder
    {
        return DonDatLich::query()->where(function (Builder $query) use ($day) {
            $query->whereDate('ngay_hen', $day->toDateString())
                ->orWhere(function (Builder $fallback) use ($day) {
                    $fallback->whereNull('ngay_hen')
                        ->whereDate('created_at', $day->toDateString());
                });
        });
    }

    private function dashboardDayLabel(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            Carbon::SUNDAY => 'CN',
            Carbon::MONDAY => 'T2',
            Carbon::TUESDAY => 'T3',
            Carbon::WEDNESDAY => 'T4',
            Carbon::THURSDAY => 'T5',
            Carbon::FRIDAY => 'T6',
            default => 'T7',
        };
    }

    private function buildDashboardRevenueTrend(array $range, array $completedStatuses): Collection
    {
        $timestampExpression = 'COALESCE(thoi_gian_hoan_thanh, created_at)';

        return match ($range['period']) {
            'day' => $this->buildDashboardHourlyTrend($range['start'], $range['end'], $completedStatuses, $timestampExpression),
            'month' => $this->buildDashboardMonthTrend($range['start'], $range['end'], $completedStatuses, $timestampExpression),
            'year' => $this->buildDashboardYearTrend($range['start'], $range['end'], $completedStatuses, $timestampExpression),
            '30d' => $this->buildDashboardRollingDayTrend($range['start'], $range['end'], $completedStatuses, $timestampExpression, 'd/m'),
            default => $this->buildDashboardRollingDayTrend($range['start'], $range['end'], $completedStatuses, $timestampExpression, null),
        };
    }

    private function buildDashboardHourlyTrend(Carbon $start, Carbon $end, array $completedStatuses, string $timestampExpression): Collection
    {
        $trendRaw = DonDatLich::query()
            ->whereIn('trang_thai', $completedStatuses)
            ->whereBetween(DB::raw($timestampExpression), [$start, $end])
            ->selectRaw('HOUR(' . $timestampExpression . ') as report_hour, SUM(tong_tien) as total')
            ->groupBy('report_hour')
            ->pluck('total', 'report_hour');

        return collect(range(0, 23))->map(function (int $hour) use ($start, $trendRaw) {
            return [
                'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . 'h',
                'date' => $start->copy()->setHour($hour)->format('Y-m-d H:00:00'),
                'value' => (float) ($trendRaw[$hour] ?? 0),
            ];
        })->values();
    }

    private function buildDashboardMonthTrend(Carbon $start, Carbon $end, array $completedStatuses, string $timestampExpression): Collection
    {
        return $this->buildDashboardRollingDayTrend($start, $end, $completedStatuses, $timestampExpression, 'd');
    }

    private function buildDashboardYearTrend(Carbon $start, Carbon $end, array $completedStatuses, string $timestampExpression): Collection
    {
        $trendRaw = DonDatLich::query()
            ->whereIn('trang_thai', $completedStatuses)
            ->whereBetween(DB::raw($timestampExpression), [$start, $end])
            ->selectRaw('MONTH(' . $timestampExpression . ') as report_month, SUM(tong_tien) as total')
            ->groupBy('report_month')
            ->pluck('total', 'report_month');

        return collect(range(1, (int) $end->month))->map(function (int $month) use ($start, $trendRaw) {
            return [
                'label' => 'T' . $month,
                'date' => $start->copy()->month($month)->startOfMonth()->toDateString(),
                'value' => (float) ($trendRaw[$month] ?? 0),
            ];
        })->values();
    }

    private function buildDashboardRollingDayTrend(
        Carbon $start,
        Carbon $end,
        array $completedStatuses,
        string $timestampExpression,
        ?string $labelFormat
    ): Collection {
        $trendRaw = DonDatLich::query()
            ->whereIn('trang_thai', $completedStatuses)
            ->whereBetween(DB::raw($timestampExpression), [$start, $end])
            ->selectRaw('DATE(' . $timestampExpression . ') as report_date, SUM(tong_tien) as total')
            ->groupBy('report_date')
            ->pluck('total', 'report_date');

        $totalDays = $start->diffInDays($end) + 1;

        return collect(range(0, max($totalDays - 1, 0)))->map(function (int $offset) use ($start, $trendRaw, $labelFormat) {
            $day = $start->copy()->addDays($offset);

            return [
                'label' => $labelFormat ? $day->format($labelFormat) : $this->dashboardDayLabel($day),
                'date' => $day->toDateString(),
                'value' => (float) ($trendRaw[$day->toDateString()] ?? 0),
            ];
        })->values();
    }

    private function buildComplaintItems(Carbon $start, Carbon $end): array
    {
        $ratings = DanhGia::query()
            ->with([
                'donDatLich:id,khach_hang_id,tho_id',
                'donDatLich.khachHang:id,name',
                'donDatLich.tho:id,name',
            ])
            ->where('so_sao', '<=', 2)
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->limit(3)
            ->get()
            ->map(function (DanhGia $review) {
                $booking = $review->donDatLich;

                return [
                    'sort_at' => optional($review->created_at)->timestamp ?? 0,
                    'date' => optional($review->created_at)->format('d/m'),
                    'booking_code' => $booking ? 'DD-' . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT) : 'Đánh giá',
                    'summary' => $this->truncateDashboardText(
                        trim((string) ($review->nhan_xet ?: 'Khách hàng để lại đánh giá thấp cho đơn này.')),
                        96
                    ),
                    'tone' => 'danger',
                ];
            });

        $cancellations = DonDatLich::query()
            ->where('trang_thai', 'da_huy')
            ->whereNotNull('ly_do_huy')
            ->whereBetween('updated_at', [$start, $end])
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->map(function (DonDatLich $booking) {
                return [
                    'sort_at' => optional($booking->updated_at)->timestamp ?? 0,
                    'date' => optional($booking->updated_at)->format('d/m'),
                    'booking_code' => 'DD-' . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT),
                    'summary' => $this->truncateDashboardText(
                        trim((string) ($booking->ly_do_huy ?: 'Đơn đã hủy và cần rà soát lý do từ khách hàng.')),
                        96
                    ),
                    'tone' => 'warning',
                ];
            });

        return $ratings
            ->concat($cancellations)
            ->sortByDesc(function (array $item) {
                return $item['sort_at'];
            })
            ->take(3)
            ->map(function (array $item) {
                unset($item['sort_at']);

                return $item;
            })
            ->values()
            ->all();
    }

    private function truncateDashboardText(string $text, int $limit = 100): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', trim($text)), $limit);
    }

    public function getCustomers(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $sort = trim((string) $request->query('sort', 'latest'));
        $segment = trim((string) $request->query('segment', ''));
        $tag = trim((string) $request->query('tag', ''));
        $area = trim((string) $request->query('area', ''));
        $followUp = trim((string) $request->query('follow_up', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $spentMin = is_numeric($request->query('spent_min')) ? (float) $request->query('spent_min') : null;
        $spentMax = is_numeric($request->query('spent_max')) ? (float) $request->query('spent_max') : null;
        $bookingCountMin = is_numeric($request->query('booking_count_min')) ? (int) $request->query('booking_count_min') : null;
        $bookingCountMax = is_numeric($request->query('booking_count_max')) ? (int) $request->query('booking_count_max') : null;
        $openStatuses = $this->adminCustomerOpenStatuses();
        $completedStatuses = $this->adminCustomerCompletedStatuses();
        $now = Carbon::now();
        $dateFromValue = $dateFrom !== '' && strtotime($dateFrom) !== false ? Carbon::parse($dateFrom)->startOfDay() : null;
        $dateToValue = $dateTo !== '' && strtotime($dateTo) !== false ? Carbon::parse($dateTo)->endOfDay() : null;

        $customers = User::query()
            ->where('role', 'customer')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->with([
                'donDatLichAsKhach' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'khach_hang_id',
                            'tho_id',
                            'trang_thai',
                            'tong_tien',
                            'dia_chi',
                            'created_at',
                            'ngay_hen',
                        ])
                        ->with([
                            'dichVus:id,ten_dich_vu',
                            'danhGias:id,don_dat_lich_id,so_sao',
                        ])
                        ->latest('created_at');
                },
            ])
            ->latest('created_at')
            ->get()
            ->map(function (User $customer) use ($openStatuses, $completedStatuses, $now) {
                $bookings = $customer->donDatLichAsKhach;
                $latestBooking = $bookings->sortByDesc(fn ($booking) => optional($booking->created_at)->timestamp ?? 0)->first();
                $completedBookings = $bookings->filter(fn ($booking) => in_array($booking->trang_thai, $completedStatuses, true));
                $openBookings = $bookings->filter(fn ($booking) => in_array($booking->trang_thai, $openStatuses, true));
                $ratings = $bookings
                    ->flatMap(fn ($booking) => $booking->danhGias)
                    ->pluck('so_sao')
                    ->filter(fn ($value) => is_numeric($value))
                    ->map(fn ($value) => (float) $value)
                    ->values();

                $totalSpent = (float) $completedBookings->sum(fn ($booking) => (float) ($booking->tong_tien ?? 0));
                $completedBookingCount = $completedBookings->count();
                $lowFeedbackCount = $ratings->filter(fn ($value) => $value <= 2)->count();
                $lastBookingAt = $latestBooking?->created_at;
                $cancelWithReasonCount = $bookings
                    ->where('trang_thai', 'da_huy')
                    ->filter(fn ($booking) => filled($booking->ly_do_huy ?? null))
                    ->count();
                $daysSinceLastBooking = $lastBookingAt ? $lastBookingAt->diffInDays($now) : null;
                $relationshipStatus = $this->resolveCustomerRelationshipStatus(
                    $customer,
                    $latestBooking,
                    $openBookings->count(),
                    $lowFeedbackCount,
                    $completedBookingCount,
                    $now
                );
                $segments = $this->buildCustomerSegments([
                    'created_days' => $customer->created_at ? $customer->created_at->diffInDays($now) : null,
                    'order_count' => $bookings->count(),
                    'active_booking_count' => $openBookings->count(),
                    'completed_booking_count' => $completedBookingCount,
                    'total_spent' => $totalSpent,
                    'low_feedback_count' => $lowFeedbackCount,
                    'days_since_last_booking' => $daysSinceLastBooking,
                    'cancel_with_reason_count' => $cancelWithReasonCount,
                ]);

                return [
                    'id' => $customer->id,
                    'code' => 'KH-' . str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT),
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'avatar' => $customer->avatar,
                    'created_at' => optional($customer->created_at)->toIso8601String(),
                    'joined_label' => optional($customer->created_at)->format('d/m/Y'),
                    'order_count' => $bookings->count(),
                    'active_booking_count' => $openBookings->count(),
                    'completed_booking_count' => $completedBookingCount,
                    'canceled_booking_count' => $bookings->where('trang_thai', 'da_huy')->count(),
                    'total_spent' => round($totalSpent, 2),
                    'average_order_value' => $completedBookingCount > 0 ? round($totalSpent / $completedBookingCount, 2) : 0,
                    'average_rating_given' => $ratings->isNotEmpty() ? round($ratings->avg(), 1) : null,
                    'low_feedback_count' => $lowFeedbackCount,
                    'relationship_label' => $this->formatCustomerRelationshipLabel($relationshipStatus),
                    'last_booking_at' => optional($lastBookingAt)->toIso8601String(),
                    'last_booking_label' => $lastBookingAt ? $lastBookingAt->format('d/m/Y') : 'Chua dat',
                    'last_booking_status' => $latestBooking?->trang_thai,
                    'last_booking_service' => $latestBooking
                        ? ($latestBooking->dichVus->pluck('ten_dich_vu')->filter()->implode(', ') ?: 'Chua gan dich vu')
                        : 'Chua co don',
                    'current_area' => $this->extractCustomerArea($latestBooking?->dia_chi ?: $customer->address),
                    'latest_address' => $latestBooking?->dia_chi ?: $customer->address,
                    'relationship_status' => $relationshipStatus,
                    'quick_note' => $this->buildCustomerQuickNote($relationshipStatus, $openBookings->count(), $lowFeedbackCount, $lastBookingAt, $now),
                    'tags' => [],
                    'segments' => $segments,
                    'primary_segment' => $segments[0] ?? null,
                    'next_follow_up' => null,
                    'follow_up_stats' => [
                        'pending_count' => 0,
                        'completed_count' => 0,
                        'due_today_count' => 0,
                        'overdue_count' => 0,
                    ],
                    '_created_at_sort' => optional($customer->created_at)->timestamp ?? 0,
                    '_last_booking_at_sort' => optional($lastBookingAt)->timestamp ?? 0,
                    '_next_follow_up_sort' => PHP_INT_MAX,
                ];
            })
            ->filter(function (array $customer) use (
                $status,
                $segment,
                $tag,
                $area,
                $followUp,
                $dateFromValue,
                $dateToValue,
                $spentMin,
                $spentMax,
                $bookingCountMin,
                $bookingCountMax,
                $now
            ) {
                if ($status === '') {
                    // continue
                } elseif (!match ($status) {
                    'new_customer' => $customer['_created_at_sort'] >= $now->copy()->subDays(30)->timestamp,
                    'new_30d' => $customer['_created_at_sort'] >= $now->copy()->subDays(30)->timestamp,
                    'has_booking' => $customer['order_count'] > 0,
                    'active_booking' => $customer['active_booking_count'] > 0,
                    'needs_attention' => $customer['relationship_status'] === 'needs_attention',
                    'inactive_60d' => $customer['relationship_status'] === 'inactive',
                    'vip' => $customer['total_spent'] >= 2000000 || $customer['completed_booking_count'] >= 5,
                    default => true,
                }) {
                    return false;
                }

                if ($segment !== '' && !$this->customerHasSegment($customer['segments'] ?? [], $segment)) {
                    return false;
                }

                if ($tag !== '') {
                    $matchesTag = collect($customer['tags'] ?? [])->contains(function (array $item) use ($tag) {
                        $needle = Str::lower($tag);

                        return Str::lower((string) ($item['label'] ?? '')) === $needle
                            || Str::lower((string) ($item['slug'] ?? '')) === $needle;
                    });

                    if (!$matchesTag) {
                        return false;
                    }
                }

                if ($area !== '' && !str_contains(Str::lower((string) ($customer['current_area'] ?? '')), Str::lower($area))) {
                    return false;
                }

                if ($followUp !== '') {
                    $followUpStats = $customer['follow_up_stats'] ?? [];
                    $hasPending = ($followUpStats['pending_count'] ?? 0) > 0;

                    $matchesFollowUp = match ($followUp) {
                        'pending' => $hasPending,
                        'due_today' => ($followUpStats['due_today_count'] ?? 0) > 0,
                        'overdue' => ($followUpStats['overdue_count'] ?? 0) > 0,
                        'none' => !$hasPending,
                        default => true,
                    };

                    if (!$matchesFollowUp) {
                        return false;
                    }
                }

                if ($dateFromValue || $dateToValue) {
                    if (!$customer['_last_booking_at_sort']) {
                        return false;
                    }

                    $lastBookingAt = Carbon::createFromTimestamp($customer['_last_booking_at_sort']);
                    if ($dateFromValue && $lastBookingAt->lt($dateFromValue)) {
                        return false;
                    }

                    if ($dateToValue && $lastBookingAt->gt($dateToValue)) {
                        return false;
                    }
                }

                if ($spentMin !== null && (float) $customer['total_spent'] < $spentMin) {
                    return false;
                }

                if ($spentMax !== null && (float) $customer['total_spent'] > $spentMax) {
                    return false;
                }

                if ($bookingCountMin !== null && (int) $customer['order_count'] < $bookingCountMin) {
                    return false;
                }

                if ($bookingCountMax !== null && (int) $customer['order_count'] > $bookingCountMax) {
                    return false;
                }

                return true;
            })
            ->values();

        $customers = match ($sort) {
            'spent_desc' => $customers->sortByDesc('total_spent')->values(),
            'bookings_desc' => $customers->sortByDesc('order_count')->values(),
            'name_asc' => $customers->sortBy(fn ($customer) => mb_strtolower((string) $customer['name']))->values(),
            'follow_up_soon' => $customers->sortBy(function (array $customer) {
                $overdueWeight = (($customer['follow_up_stats']['overdue_count'] ?? 0) > 0) ? 0 : 1;
                $nextFollowUpSort = $customer['_next_follow_up_sort'] ?? PHP_INT_MAX;

                return sprintf('%01d-%015d', $overdueWeight, $nextFollowUpSort);
            })->values(),
            default => $customers->sortByDesc(fn ($customer) => max($customer['_last_booking_at_sort'], $customer['_created_at_sort']))->values(),
        };

        $summary = [
            'total_customers' => $customers->count(),
            'new_customers_7d' => $customers->filter(fn ($customer) => $customer['_created_at_sort'] >= $now->copy()->subDays(7)->timestamp)->count(),
            'new_customers_30d' => $customers->filter(fn ($customer) => $customer['_created_at_sort'] >= $now->copy()->subDays(30)->timestamp)->count(),
            'booked_customers' => $customers->filter(fn ($customer) => $customer['order_count'] > 0)->count(),
            'active_booking_customers' => $customers->filter(fn ($customer) => $customer['active_booking_count'] > 0)->count(),
            'needs_attention_customers' => $customers->filter(fn ($customer) => $customer['relationship_status'] === 'needs_attention')->count(),
            'low_feedback_customers' => $customers->filter(fn ($customer) => $customer['low_feedback_count'] > 0)->count(),
            'returning_customers' => $customers->filter(fn ($customer) => $customer['completed_booking_count'] >= 2)->count(),
            'follow_up_due_today' => $customers->filter(fn ($customer) => ($customer['follow_up_stats']['due_today_count'] ?? 0) > 0)->count(),
            'follow_up_overdue' => $customers->filter(fn ($customer) => ($customer['follow_up_stats']['overdue_count'] ?? 0) > 0)->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => $summary,
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'sort' => $sort,
                    'segment' => $segment,
                    'tag' => $tag,
                    'area' => $area,
                    'follow_up' => $followUp,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'spent_min' => $spentMin,
                    'spent_max' => $spentMax,
                    'booking_count_min' => $bookingCountMin,
                    'booking_count_max' => $bookingCountMax,
                ],
                'filter_options' => [
                    'tags' => [],
                    'segments' => collect([
                        'needs_care',
                        'vip',
                        'loyal',
                        'active_booking',
                        'churn_risk',
                        'new_customer',
                        'standard',
                    ])->map(fn (string $code) => [
                        'code' => $code,
                        'label' => $this->formatCustomerSegmentLabel($code),
                        'tone' => $this->resolveCustomerSegmentTone($code),
                    ])->values(),
                ],
                'customers' => $customers->map(function (array $customer) {
                    unset($customer['_created_at_sort'], $customer['_last_booking_at_sort'], $customer['_next_follow_up_sort']);

                    return $customer;
                })->values(),
            ],
        ]);
    }

    public function getCustomerDetail(string $id)
    {
        $openStatuses = $this->adminCustomerOpenStatuses();
        $completedStatuses = $this->adminCustomerCompletedStatuses();
        $now = Carbon::now();

        $customer = User::query()
            ->where('role', 'customer')
            ->whereKey($id)
            ->with([
                'donDatLichAsKhach' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'khach_hang_id',
                            'tho_id',
                            'loai_dat_lich',
                            'thoi_gian_hen',
                            'thoi_gian_hoan_thanh',
                            'ngay_hen',
                            'khung_gio_hen',
                            'dia_chi',
                            'mo_ta_van_de',
                            'trang_thai',
                            'ma_ly_do_huy',
                            'ly_do_huy',
                            'tong_tien',
                            'phuong_thuc_thanh_toan',
                            'trang_thai_thanh_toan',
                            'created_at',
                            'updated_at',
                        ])
                        ->with([
                            'dichVus:id,ten_dich_vu',
                            'tho:id,name,phone',
                            'danhGias:id,don_dat_lich_id,nguoi_bi_danh_gia_id,so_sao,nhan_xet,created_at',
                            'danhGias.nguoiBiDanhGia:id,name',
                        ])
                        ->latest('created_at');
                },
                'customerNotes' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'customer_id',
                            'admin_id',
                            'category',
                            'content',
                            'is_pinned',
                            'created_at',
                        ])
                        ->with('admin:id,name')
                        ->latest('created_at');
                },
                'customerTags:id,label,slug,color',
                'customerFollowUps' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'customer_id',
                            'booking_id',
                            'created_by_admin_id',
                            'assigned_admin_id',
                            'title',
                            'channel',
                            'priority',
                            'status',
                            'scheduled_at',
                            'completed_at',
                            'note',
                            'outcome_note',
                            'created_at',
                        ])
                        ->with([
                            'assignedAdmin:id,name',
                            'createdByAdmin:id,name',
                        ])
                        ->latest('scheduled_at');
                },
            ])
            ->firstOrFail();

        $bookings = $customer->donDatLichAsKhach
            ->sortByDesc(fn (DonDatLich $booking) => optional($booking->created_at)->timestamp ?? 0)
            ->values();

        $latestBooking = $bookings->first();
        $completedBookings = $bookings
            ->filter(fn (DonDatLich $booking) => in_array($booking->trang_thai, $completedStatuses, true))
            ->values();
        $openBookings = $bookings
            ->filter(fn (DonDatLich $booking) => in_array($booking->trang_thai, $openStatuses, true))
            ->values();
        $canceledBookings = $bookings
            ->filter(fn (DonDatLich $booking) => $booking->trang_thai === 'da_huy')
            ->values();

        $reviewEntries = $bookings
            ->flatMap(function (DonDatLich $booking) {
                return $booking->danhGias->map(function (DanhGia $review) use ($booking) {
                    return [
                        'booking' => $booking,
                        'review' => $review,
                    ];
                });
            })
            ->values();

        $ratingValues = $reviewEntries
            ->map(fn (array $entry) => is_numeric($entry['review']->so_sao) ? (float) $entry['review']->so_sao : null)
            ->filter(fn ($value) => $value !== null)
            ->values();

        $completedBookingCount = $completedBookings->count();
        $totalSpent = (float) $completedBookings->sum(fn (DonDatLich $booking) => (float) ($booking->tong_tien ?? 0));
        $averageOrderValue = $completedBookingCount > 0 ? round($totalSpent / $completedBookingCount, 2) : 0;
        $lowFeedbackCount = $ratingValues->filter(fn (float $value) => $value <= 2)->count();
        $pendingPaymentCount = $bookings
            ->filter(function (DonDatLich $booking) use ($completedStatuses) {
                if ($booking->trang_thai === 'da_huy') {
                    return false;
                }

                return !$booking->trang_thai_thanh_toan
                    && (
                        in_array($booking->trang_thai, $completedStatuses, true)
                        || $booking->trang_thai === 'cho_thanh_toan'
                    );
            })
            ->count();
        $lastBookingAt = $latestBooking?->created_at;
        $relationshipStatus = $this->resolveCustomerRelationshipStatus(
            $customer,
            $latestBooking,
            $openBookings->count(),
            $lowFeedbackCount,
            $completedBookingCount,
            $now
        );

        $topServices = $bookings
            ->flatMap(fn (DonDatLich $booking) => $booking->dichVus->pluck('ten_dich_vu')->filter())
            ->countBy()
            ->sortDesc()
            ->take(4)
            ->map(fn ($count, $label) => [
                'label' => $label,
                'count' => $count,
            ])
            ->values();

        $topAreas = $bookings
            ->map(fn (DonDatLich $booking) => $this->extractCustomerArea($booking->dia_chi))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(4)
            ->map(fn ($count, $label) => [
                'label' => $label,
                'count' => $count,
            ])
            ->values();

        $topWorkers = $bookings
            ->filter(fn (DonDatLich $booking) => $booking->tho !== null)
            ->countBy(fn (DonDatLich $booking) => $booking->tho->name)
            ->sortDesc()
            ->take(4)
            ->map(fn ($count, $label) => [
                'label' => $label,
                'count' => $count,
            ])
            ->values();

        $bookingModes = collect([
            [
                'label' => 'Sua tai nha',
                'count' => $bookings->where('loai_dat_lich', 'at_home')->count(),
            ],
            [
                'label' => 'Tai cua hang',
                'count' => $bookings->where('loai_dat_lich', 'at_store')->count(),
            ],
        ])->filter(fn (array $item) => $item['count'] > 0)->values();

        $paymentMethods = $bookings
            ->filter(fn (DonDatLich $booking) => filled($booking->phuong_thuc_thanh_toan))
            ->countBy(fn (DonDatLich $booking) => $this->formatCustomerPaymentMethodLabel($booking->phuong_thuc_thanh_toan))
            ->sortDesc()
            ->take(4)
            ->map(fn ($count, $label) => [
                'label' => $label,
                'count' => $count,
            ])
            ->values();

        $tags = [];
        $notes = [];
        $availableTags = [];

        $nextOpenBooking = $openBookings
            ->sortBy(fn (DonDatLich $booking) => $this->resolveCustomerBookingSortTimestamp($booking))
            ->first();

        $daysSinceLastBooking = $lastBookingAt ? $lastBookingAt->diffInDays($now) : null;
        $cancelWithReasonCount = $canceledBookings
            ->filter(fn (DonDatLich $booking) => filled($booking->ly_do_huy))
            ->count();
        $segments = $this->buildCustomerSegments([
            'created_days' => $customer->created_at ? $customer->created_at->diffInDays($now) : null,
            'order_count' => $bookings->count(),
            'active_booking_count' => $openBookings->count(),
            'completed_booking_count' => $completedBookingCount,
            'total_spent' => $totalSpent,
            'low_feedback_count' => $lowFeedbackCount,
            'days_since_last_booking' => $daysSinceLastBooking,
            'cancel_with_reason_count' => $cancelWithReasonCount,
        ]);

        $currentStateTitle = 'Quan he on dinh';
        $currentStateDetail = 'Khach hang khong co dau hieu can can thiep ngay.';
        $currentStateTone = 'success';

        if ($openBookings->count() > 0) {
            $currentStateTitle = $openBookings->count() . ' don dang mo';
            $currentStateDetail = $nextOpenBooking
                ? 'Gan nhat: ' . $this->buildCustomerServiceLabel($nextOpenBooking) . ' - ' . $this->formatCustomerScheduleLabel($nextOpenBooking)
                : 'Khach hang dang co don can theo doi.';
            $currentStateTone = 'info';
        } elseif ($lowFeedbackCount > 0) {
            $currentStateTitle = $lowFeedbackCount . ' danh gia thap can xu ly';
            $currentStateDetail = 'Can xem lai trai nghiem dich vu va thong tin don lien quan.';
            $currentStateTone = 'warning';
        } elseif ($daysSinceLastBooking !== null && $daysSinceLastBooking >= 60) {
            $currentStateTitle = 'Khach dang ngung tuong tac';
            $currentStateDetail = 'Lan dat gan nhat da qua ' . $daysSinceLastBooking . ' ngay.';
            $currentStateTone = 'muted';
        } elseif ($bookings->isEmpty()) {
            $currentStateTitle = 'Chua phat sinh don';
            $currentStateDetail = 'Khach hang da co tai khoan nhung chua tao don dat lich nao.';
            $currentStateTone = 'muted';
        }

        $alerts = collect();

        if ($openBookings->count() > 0) {
            $alerts->push([
                'tone' => 'info',
                'title' => 'Don dang mo',
                'detail' => $openBookings->count() . ' don dang duoc he thong theo doi.',
            ]);
        }

        if ($pendingPaymentCount > 0) {
            $alerts->push([
                'tone' => 'warning',
                'title' => 'Cho thanh toan',
                'detail' => $pendingPaymentCount . ' don da xong nhung chua chot thanh toan.',
            ]);
        }

        if ($lowFeedbackCount > 0) {
            $alerts->push([
                'tone' => 'danger',
                'title' => 'Danh gia thap',
                'detail' => $lowFeedbackCount . ' review can admin kiem tra lai.',
            ]);
        }

        if ($cancelWithReasonCount > 0) {
            $alerts->push([
                'tone' => 'warning',
                'title' => 'Huy don co ly do',
                'detail' => $cancelWithReasonCount . ' don huy co de lai ly do can ra soat.',
            ]);
        }

        if (($followUpSummary['overdue_count'] ?? 0) > 0) {
            $alerts->push([
                'tone' => 'danger',
                'title' => 'Nhac goi lai qua han',
                'detail' => ($followUpSummary['overdue_count'] ?? 0) . ' lich cham soc da qua han can xu ly ngay.',
            ]);
        }

        if (($followUpSummary['due_today_count'] ?? 0) > 0) {
            $alerts->push([
                'tone' => 'warning',
                'title' => 'Lich cham soc hom nay',
                'detail' => ($followUpSummary['due_today_count'] ?? 0) . ' lich can lien he trong ngay.',
            ]);
        }

        $recentBookings = $bookings
            ->take(6)
            ->map(function (DonDatLich $booking) {
                return [
                    'id' => $booking->id,
                    'code' => $this->formatCustomerBookingCode($booking->id),
                    'service_label' => $this->buildCustomerServiceLabel($booking),
                    'status' => $booking->trang_thai,
                    'status_label' => $this->formatCustomerBookingStatusLabel($booking->trang_thai),
                    'status_tone' => $this->resolveCustomerBookingTone($booking->trang_thai),
                    'schedule_label' => $this->formatCustomerScheduleLabel($booking),
                    'location_label' => $booking->loai_dat_lich === 'at_home'
                        ? ($booking->dia_chi ?: 'Chua cap nhat dia chi')
                        : 'Sua tai cua hang',
                    'worker_name' => $booking->tho?->name ?: 'Chua co tho nhan',
                    'payment_label' => $booking->trang_thai_thanh_toan ? 'Da thanh toan' : 'Chua thanh toan',
                    'total_amount' => (float) ($booking->tong_tien ?? 0),
                    'problem_excerpt' => $this->truncateDashboardText((string) ($booking->mo_ta_van_de ?: 'Khach hang chua de mo ta chi tiet.'), 112),
                    'detail_url' => '/customer/my-bookings/' . $booking->id,
                ];
            })
            ->values();

        $reviews = $reviewEntries
            ->sortByDesc(fn (array $entry) => optional($entry['review']->created_at)->timestamp ?? 0)
            ->take(6)
            ->map(function (array $entry) {
                /** @var \App\Models\DanhGia $review */
                $review = $entry['review'];
                /** @var \App\Models\DonDatLich $booking */
                $booking = $entry['booking'];

                return [
                    'rating' => (float) ($review->so_sao ?? 0),
                    'comment' => trim((string) ($review->nhan_xet ?: 'Khach hang khong de lai nhan xet chi tiet.')),
                    'created_label' => optional($review->created_at)->format('d/m/Y'),
                    'service_label' => $this->buildCustomerServiceLabel($booking),
                    'worker_name' => $booking->tho?->name ?: ($review->nguoiBiDanhGia?->name ?: 'Chua gan tho'),
                    'booking_code' => $this->formatCustomerBookingCode($booking->id),
                    'detail_url' => '/customer/my-bookings/' . $booking->id,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'profile' => [
                    'id' => $customer->id,
                    'code' => 'KH-' . str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT),
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'avatar' => $customer->avatar,
                    'joined_label' => optional($customer->created_at)->format('d/m/Y'),
                    'joined_relative_days' => $customer->created_at ? $customer->created_at->diffInDays($now) : null,
                    'relationship_status' => $relationshipStatus,
                    'relationship_label' => $this->formatCustomerRelationshipLabel($relationshipStatus),
                    'segments' => $segments,
                    'primary_segment' => $segments[0] ?? null,
                    'quick_note' => $this->buildCustomerQuickNote($relationshipStatus, $openBookings->count(), $lowFeedbackCount, $lastBookingAt, $now),
                    'current_area' => $this->extractCustomerArea($latestBooking?->dia_chi ?: $customer->address),
                    'default_address' => $customer->address,
                    'latest_address' => $latestBooking?->dia_chi ?: $customer->address,
                    'last_booking_label' => $lastBookingAt ? $lastBookingAt->format('d/m/Y') : 'Chua dat',
                    'last_booking_service' => $latestBooking ? $this->buildCustomerServiceLabel($latestBooking) : 'Chua co don',
                    'is_active' => (bool) $customer->is_active,
                    'history_url' => '/admin/customers/' . $customer->id . '/bookings',
                    'feedback_url' => '/admin/customer-feedback?customer=' . $customer->id,
                ],
                'summary' => [
                    'order_count' => $bookings->count(),
                    'active_booking_count' => $openBookings->count(),
                    'completed_booking_count' => $completedBookingCount,
                    'canceled_booking_count' => $canceledBookings->count(),
                    'total_spent' => round($totalSpent, 2),
                    'average_order_value' => $averageOrderValue,
                    'average_rating' => $ratingValues->isNotEmpty() ? round($ratingValues->avg(), 1) : null,
                    'total_reviews' => $reviewEntries->count(),
                    'low_feedback_count' => $lowFeedbackCount,
                    'pending_payment_count' => $pendingPaymentCount,
                    'days_since_last_booking' => $daysSinceLastBooking,
                    'follow_up_pending_count' => $followUpSummary['pending_count'] ?? 0,
                    'follow_up_due_today' => $followUpSummary['due_today_count'] ?? 0,
                    'follow_up_overdue' => $followUpSummary['overdue_count'] ?? 0,
                ],
                'current_state' => [
                    'title' => $currentStateTitle,
                    'detail' => $currentStateDetail,
                    'tone' => $currentStateTone,
                    'next_booking_label' => $nextOpenBooking ? $this->formatCustomerScheduleLabel($nextOpenBooking) : null,
                    'active_bookings' => $openBookings
                        ->take(3)
                        ->map(function (DonDatLich $booking) {
                            return [
                                'code' => $this->formatCustomerBookingCode($booking->id),
                                'service_label' => $this->buildCustomerServiceLabel($booking),
                                'status_label' => $this->formatCustomerBookingStatusLabel($booking->trang_thai),
                                'status_tone' => $this->resolveCustomerBookingTone($booking->trang_thai),
                                'schedule_label' => $this->formatCustomerScheduleLabel($booking),
                                'worker_name' => $booking->tho?->name ?: 'Dang tim tho',
                                'detail_url' => '/customer/my-bookings/' . $booking->id,
                            ];
                        })
                        ->values(),
                    'meta' => [
                        [
                            'label' => 'Don dang mo',
                            'value' => $openBookings->count(),
                        ],
                        [
                            'label' => 'Cho thanh toan',
                            'value' => $pendingPaymentCount,
                        ],
                        [
                            'label' => 'Can chu y',
                            'value' => $lowFeedbackCount + $cancelWithReasonCount,
                        ],
                        [
                            'label' => 'Nhac goi lai',
                            'value' => $followUpSummary['pending_count'] ?? 0,
                        ],
                    ],
                ],
                'patterns' => [
                    'top_services' => $topServices,
                    'top_areas' => $topAreas,
                    'booking_modes' => $bookingModes,
                    'top_workers' => $topWorkers,
                    'payment_methods' => $paymentMethods,
                ],
                'recent_bookings' => $recentBookings,
                'reviews' => $reviews,
                'timeline' => $this->buildCustomerTimeline($customer, $bookings),
                'alerts' => $alerts->values(),
                'tags' => $tags->values(),
                'available_tags' => $availableTags->values(),
                'notes' => $notes->values(),
                'follow_ups' => $followUpSummary['items'],
                'follow_up_summary' => [
                    'pending_count' => $followUpSummary['pending_count'] ?? 0,
                    'completed_count' => $followUpSummary['completed_count'] ?? 0,
                    'due_today_count' => $followUpSummary['due_today_count'] ?? 0,
                    'overdue_count' => $followUpSummary['overdue_count'] ?? 0,
                    'next_pending' => $followUpSummary['next_pending'],
                ],
                'admin_options' => $this->getAssignableAdmins(),
                'booking_options' => $bookings
                    ->take(16)
                    ->map(fn (DonDatLich $booking) => [
                        'id' => $booking->id,
                        'code' => $this->formatCustomerBookingCode($booking->id),
                        'service_label' => $this->buildCustomerServiceLabel($booking),
                    ])
                    ->values(),
            ],
        ]);
    }

    public function getCustomerBookings(Request $request, string $id)
    {
        $customer = User::query()
            ->where('role', 'customer')
            ->whereKey($id)
            ->firstOrFail();

        $openStatuses = $this->adminCustomerOpenStatuses();
        $completedStatuses = $this->adminCustomerCompletedStatuses();
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $payment = trim((string) $request->query('payment', ''));
        $mode = trim((string) $request->query('mode', ''));
        $service = trim((string) $request->query('service', ''));
        $workerId = trim((string) $request->query('worker_id', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $amountMin = is_numeric($request->query('amount_min')) ? (float) $request->query('amount_min') : null;
        $amountMax = is_numeric($request->query('amount_max')) ? (float) $request->query('amount_max') : null;

        $bookings = DonDatLich::query()
            ->where('khach_hang_id', $customer->id)
            ->with([
                'dichVus:id,ten_dich_vu',
                'tho:id,name,phone',
                'danhGias:id,don_dat_lich_id,so_sao',
            ])
            ->latest('created_at')
            ->get();

        $filteredBookings = $bookings
            ->filter(function (DonDatLich $booking) use ($search, $status, $payment, $mode, $service, $workerId, $dateFrom, $dateTo, $amountMin, $amountMax) {
                $serviceLabel = $this->buildCustomerServiceLabel($booking);
                $bookingCode = $this->formatCustomerBookingCode($booking->id);
                $bookingDate = $booking->ngay_hen?->toDateString()
                    ?? $booking->thoi_gian_hen?->toDateString()
                    ?? $booking->created_at?->toDateString();

                if ($search !== '') {
                    $haystack = Str::lower(implode(' ', [
                        $bookingCode,
                        $serviceLabel,
                        $booking->tho?->name,
                        $booking->dia_chi,
                        $booking->mo_ta_van_de,
                    ]));

                    if (!str_contains($haystack, Str::lower($search))) {
                        return false;
                    }
                }

                if ($status !== '' && $booking->trang_thai !== $status) {
                    return false;
                }

                if ($payment === 'paid' && !$booking->trang_thai_thanh_toan) {
                    return false;
                }

                if ($payment === 'unpaid' && $booking->trang_thai_thanh_toan) {
                    return false;
                }

                if ($mode !== '' && $booking->loai_dat_lich !== $mode) {
                    return false;
                }

                if ($service !== '' && !str_contains(Str::lower($serviceLabel), Str::lower($service))) {
                    return false;
                }

                if ($workerId !== '' && (string) ($booking->tho?->id ?? '') !== $workerId) {
                    return false;
                }

                if ($dateFrom !== '' && $bookingDate !== null && $bookingDate < $dateFrom) {
                    return false;
                }

                if ($dateTo !== '' && $bookingDate !== null && $bookingDate > $dateTo) {
                    return false;
                }

                if ($amountMin !== null && (float) ($booking->tong_tien ?? 0) < $amountMin) {
                    return false;
                }

                if ($amountMax !== null && (float) ($booking->tong_tien ?? 0) > $amountMax) {
                    return false;
                }

                return true;
            })
            ->values();

        $summary = [
            'order_count' => $bookings->count(),
            'active_booking_count' => $bookings->filter(fn (DonDatLich $booking) => in_array($booking->trang_thai, $openStatuses, true))->count(),
            'completed_booking_count' => $bookings->filter(fn (DonDatLich $booking) => in_array($booking->trang_thai, $completedStatuses, true))->count(),
            'canceled_booking_count' => $bookings->where('trang_thai', 'da_huy')->count(),
            'total_spent' => (float) $bookings
                ->filter(fn (DonDatLich $booking) => in_array($booking->trang_thai, $completedStatuses, true))
                ->sum(fn (DonDatLich $booking) => (float) ($booking->tong_tien ?? 0)),
            'filtered_count' => $filteredBookings->count(),
        ];

        $availableServices = $bookings
            ->flatMap(fn (DonDatLich $booking) => $booking->dichVus->pluck('ten_dich_vu')->filter())
            ->unique()
            ->sort()
            ->values();
        $availableWorkers = $bookings
            ->filter(fn (DonDatLich $booking) => $booking->tho !== null)
            ->map(fn (DonDatLich $booking) => [
                'id' => $booking->tho?->id,
                'name' => $booking->tho?->name,
            ])
            ->unique('id')
            ->sortBy('name')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'code' => 'KH-' . str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT),
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'detail_url' => '/admin/customers/' . $customer->id,
                ],
                'summary' => $summary,
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'payment' => $payment,
                    'mode' => $mode,
                    'service' => $service,
                    'worker_id' => $workerId,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'amount_min' => $amountMin,
                    'amount_max' => $amountMax,
                    'available_services' => $availableServices,
                    'available_workers' => $availableWorkers,
                ],
                'bookings' => $filteredBookings->map(function (DonDatLich $booking) {
                    $ratings = $booking->danhGias
                        ->pluck('so_sao')
                        ->filter(fn ($value) => is_numeric($value))
                        ->map(fn ($value) => (float) $value)
                        ->values();

                    return [
                        'id' => $booking->id,
                        'code' => $this->formatCustomerBookingCode($booking->id),
                        'service_label' => $this->buildCustomerServiceLabel($booking),
                        'schedule_label' => $this->formatCustomerScheduleLabel($booking),
                        'mode_label' => $booking->loai_dat_lich === 'at_home' ? 'Sua tai nha' : 'Tai cua hang',
                        'worker_name' => $booking->tho?->name ?: 'Chua gan tho',
                        'status_label' => $this->formatCustomerBookingStatusLabel($booking->trang_thai),
                        'status_tone' => $this->resolveCustomerBookingTone($booking->trang_thai),
                        'payment_label' => $booking->trang_thai_thanh_toan ? 'Da thanh toan' : 'Chua thanh toan',
                        'payment_tone' => $booking->trang_thai_thanh_toan ? 'success' : 'warning',
                        'total_amount' => (float) ($booking->tong_tien ?? 0),
                        'travel_fee' => (float) ($booking->phi_di_lai ?? 0),
                        'transport_fee' => (float) ($booking->tien_thue_xe ?? 0),
                        'transport_requested' => (bool) ($booking->thue_xe_cho ?? false),
                        'address' => $booking->loai_dat_lich === 'at_home'
                            ? ($booking->dia_chi ?: 'Chua cap nhat dia chi')
                            : 'Khach mang thiet bi den cua hang',
                        'review_label' => $ratings->isNotEmpty() ? round($ratings->avg(), 1) . '/5' : 'Chua review',
                        'review_tone' => $ratings->isNotEmpty() && $ratings->avg() <= 2 ? 'danger' : ($ratings->isNotEmpty() ? 'success' : 'muted'),
                        'problem_excerpt' => $this->truncateDashboardText((string) ($booking->mo_ta_van_de ?: 'Khach chua de mo ta van de.'), 96),
                        'detail_url' => '/customer/my-bookings/' . $booking->id,
                    ];
                })->values(),
            ],
        ]);
    }

    public function getCustomerFeedback(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $priority = trim((string) $request->query('priority', ''));
        $status = trim((string) $request->query('status', ''));
        $customerId = trim((string) $request->query('customer', ''));
        $assignedAdminId = trim((string) $request->query('assigned_admin_id', ''));
        $dueState = trim((string) $request->query('due_state', ''));
        $now = Carbon::now();
        $caseStates = CustomerFeedbackCase::query()
            ->with('assignedAdmin:id,name')
            ->get()
            ->keyBy(fn (CustomerFeedbackCase $case) => $this->formatCustomerFeedbackSourceKey($case->source_type, $case->source_id));

        $reviewCases = DanhGia::query()
            ->with([
                'donDatLich:id,khach_hang_id,tho_id,loai_dat_lich,dia_chi,trang_thai,created_at,updated_at',
                'donDatLich.khachHang:id,name,phone',
                'donDatLich.tho:id,name,phone',
                'donDatLich.dichVus:id,ten_dich_vu',
            ])
            ->where('so_sao', '<=', 3)
            ->latest('created_at')
            ->limit(200)
            ->get()
            ->map(function (DanhGia $review) use ($caseStates) {
                $case = $this->mapLowRatingFeedbackCase($review);

                return $this->applyCustomerFeedbackCaseState(
                    $case,
                    $caseStates->get($this->formatCustomerFeedbackSourceKey($case['source_type'], $case['source_id']))
                );
            });

        $cancellationCases = DonDatLich::query()
            ->with([
                'khachHang:id,name,phone',
                'tho:id,name,phone',
                'dichVus:id,ten_dich_vu',
            ])
            ->where('trang_thai', 'da_huy')
            ->whereNotNull('ly_do_huy')
            ->latest('updated_at')
            ->limit(200)
            ->get()
            ->map(function (DonDatLich $booking) use ($caseStates) {
                $case = $this->mapCancellationFeedbackCase($booking);

                return $this->applyCustomerFeedbackCaseState(
                    $case,
                    $caseStates->get($this->formatCustomerFeedbackSourceKey($case['source_type'], $case['source_id']))
                );
            });

        $complaintCases = CustomerFeedbackCase::query()
            ->with([
                'customer:id,name,phone',
                'worker:id,name,phone',
                'booking:id,khach_hang_id,tho_id,loai_dat_lich,dia_chi,trang_thai,created_at,updated_at',
                'booking.dichVus:id,ten_dich_vu',
                'assignedAdmin:id,name',
            ])
            ->where('source_type', 'customer_complaint')
            ->latest('created_at')
            ->limit(200)
            ->get()
            ->map(function (CustomerFeedbackCase $caseState) {
                return $this->mapCustomerComplaintFeedbackCase($caseState);
            });

        $cases = $reviewCases
            ->concat($cancellationCases)
            ->concat($complaintCases)
            ->filter(function (array $case) use ($search, $type, $priority, $status, $customerId, $assignedAdminId, $dueState) {
                if ($type !== '' && $case['type'] !== $type) {
                    return false;
                }

                if ($priority !== '' && $case['priority'] !== $priority) {
                    return false;
                }

                if ($status !== '' && $case['status'] !== $status) {
                    return false;
                }

                if ($customerId !== '' && (string) ($case['customer_id'] ?? '') !== $customerId) {
                    return false;
                }

                if ($assignedAdminId !== '' && (string) ($case['assigned_admin_id'] ?? '') !== $assignedAdminId) {
                    return false;
                }

                if ($dueState !== '' && ($case['due_state'] ?? 'no_deadline') !== $dueState) {
                    return false;
                }

                if ($search !== '') {
                    $haystack = Str::lower(implode(' ', [
                        $case['customer_name'] ?? '',
                        $case['worker_name'] ?? '',
                        $case['assigned_admin_name'] ?? '',
                        $case['service_label'] ?? '',
                        $case['booking_code'] ?? '',
                        $case['summary'] ?? '',
                    ]));

                    if (!str_contains($haystack, Str::lower($search))) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn (array $case) => strtotime((string) ($case['created_at'] ?? 'now')))
            ->values();

        $summary = [
            'total_cases' => $cases->count(),
            'review_cases' => $cases->where('type', 'low_rating')->count(),
            'cancellation_cases' => $cases->where('type', 'cancellation')->count(),
            'complaint_cases' => $cases->where('type', 'customer_complaint')->count(),
            'high_priority_cases' => $cases->where('priority', 'high')->count(),
            'in_progress_cases' => $cases->where('status', 'in_progress')->count(),
            'resolved_cases' => $cases->where('status', 'resolved')->count(),
            'affected_customers' => $cases->pluck('customer_id')->filter()->unique()->count(),
            'overdue_cases' => $cases->where('due_state', 'overdue')->count(),
            'due_today_cases' => $cases->where('due_state', 'due_today')->count(),
            'unassigned_cases' => $cases->filter(fn (array $case) => empty($case['assigned_admin_id']))->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => $summary,
                'filters' => [
                    'search' => $search,
                    'type' => $type,
                    'priority' => $priority,
                    'status' => $status,
                    'customer' => $customerId,
                    'assigned_admin_id' => $assignedAdminId,
                    'due_state' => $dueState,
                ],
                'filter_options' => [
                    'admins' => $this->getAssignableAdmins(),
                    'generated_at' => $now->toIso8601String(),
                ],
                'cases' => $cases,
            ],
        ]);
    }

    public function claimCustomerFeedbackCase(Request $request, string $caseKey)
    {
        $source = $this->resolveCustomerFeedbackSourceCase($caseKey);
        $user = $request->user();
        $now = Carbon::now();

        $caseState = CustomerFeedbackCase::query()->firstOrNew([
            'source_type' => $source['source_type'],
            'source_id' => $source['source_id'],
        ]);

        $caseState->fill([
            'customer_id' => $source['customer_id'],
            'booking_id' => $source['booking_id'],
            'worker_id' => $source['worker_id'],
            'priority' => $source['priority'],
            'status' => 'in_progress',
            'assigned_admin_id' => $user?->id,
            'assigned_at' => $caseState->assigned_at ?: $now,
            'resolved_at' => null,
            'resolution_note' => null,
            'last_snapshot' => $source,
        ]);
        $caseState->save();
        $caseState->load('assignedAdmin:id,name');

        return response()->json([
            'status' => 'success',
            'message' => 'Case da duoc nhan xu ly.',
            'data' => [
                'case' => $this->applyCustomerFeedbackCaseState($source, $caseState),
            ],
        ]);
    }

    public function resolveCustomerFeedbackCase(Request $request, string $caseKey)
    {
        $validator = Validator::make($request->all(), [
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu xu ly khong hop le.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $source = $this->resolveCustomerFeedbackSourceCase($caseKey);
        $user = $request->user();
        $now = Carbon::now();

        $caseState = CustomerFeedbackCase::query()->firstOrNew([
            'source_type' => $source['source_type'],
            'source_id' => $source['source_id'],
        ]);

        $caseState->fill([
            'customer_id' => $source['customer_id'],
            'booking_id' => $source['booking_id'],
            'worker_id' => $source['worker_id'],
            'priority' => $source['priority'],
            'status' => 'resolved',
            'assigned_admin_id' => $caseState->assigned_admin_id ?: $user?->id,
            'assigned_at' => $caseState->assigned_at ?: $now,
            'resolved_at' => $now,
            'resolution_note' => trim((string) $request->input('resolution_note', '')) ?: null,
            'last_snapshot' => $source,
        ]);
        $caseState->save();
        $caseState->load('assignedAdmin:id,name');

        return response()->json([
            'status' => 'success',
            'message' => 'Case da duoc danh dau da xu ly.',
            'data' => [
                'case' => $this->applyCustomerFeedbackCaseState($source, $caseState),
            ],
        ]);
    }


    public function getUsers(Request $request)
    {
        $role = $request->query('role');

        $query = User::query()
            ->with([
                'hoSoTho',
                'dichVus:id,ten_dich_vu',
            ])
            ->when($role === 'admin', function (Builder $builder) {
                $builder->where('role', 'admin');
            }, function (Builder $builder) {
                $builder->where('role', '!=', 'admin');
            });

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->orderByDesc('created_at')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    private function resolveCustomerRelationshipStatus(
        User $customer,
        ?DonDatLich $latestBooking,
        int $activeBookingCount,
        int $lowFeedbackCount,
        int $completedBookingCount,
        Carbon $now
    ): string {
        if ($lowFeedbackCount > 0) {
            return 'needs_attention';
        }

        if ($activeBookingCount > 0) {
            return 'active_booking';
        }

        if (($customer->created_at?->gte($now->copy()->subDays(30)) ?? false) && $completedBookingCount <= 1) {
            return 'new_customer';
        }

        if (($latestBooking?->created_at?->lte($now->copy()->subDays(60)) ?? false) || $latestBooking === null) {
            return 'inactive';
        }

        if ($completedBookingCount >= 3) {
            return 'loyal';
        }

        return 'healthy';
    }

    private function extractCustomerArea(?string $address): ?string
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

    private function buildCustomerQuickNote(
        string $relationshipStatus,
        int $activeBookingCount,
        int $lowFeedbackCount,
        ?Carbon $lastBookingAt,
        Carbon $now
    ): string {
        return match ($relationshipStatus) {
            'needs_attention' => $lowFeedbackCount . ' feedback can admin xu ly som.',
            'active_booking' => $activeBookingCount . ' don dang mo tren he thong.',
            'new_customer' => 'Khach moi, can theo doi don dau tien.',
            'inactive' => $lastBookingAt
                ? 'Lan dat gan nhat da qua ' . $lastBookingAt->diffInDays($now) . ' ngay.'
                : 'Chua co don dat lich nao.',
            'loyal' => 'Khach quay lai nhieu lan, nen uu tien cham soc.',
            default => 'Hoat dong on dinh, khong co canh bao lon.',
        };
    }

    private function adminCustomerOpenStatuses(): array
    {
        return ['cho_xac_nhan', 'da_xac_nhan', DonDatLich::STATUS_CUSTOMER_UNREACHABLE, 'dang_lam', 'cho_thanh_toan', 'cho_hoan_thanh'];
    }

    private function adminCustomerCompletedStatuses(): array
    {
        return ['da_xong', 'hoan_thanh'];
    }

    private function formatCustomerRelationshipLabel(string $relationshipStatus): string
    {
        return match ($relationshipStatus) {
            'active_booking' => 'Dang co don mo',
            'needs_attention' => 'Can xu ly',
            'new_customer' => 'Khach moi',
            'inactive' => 'Lau quay lai',
            'loyal' => 'Quay lai deu',
            default => 'On dinh',
        };
    }

    private function buildCustomerServiceLabel(DonDatLich $booking): string
    {
        return $booking->dichVus->pluck('ten_dich_vu')->filter()->implode(', ') ?: 'Chua gan dich vu';
    }

    private function formatCustomerBookingCode(int|string $id): string
    {
        return 'DD-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    private function formatCustomerBookingStatusLabel(?string $status): string
    {
        return match ($status) {
            'cho_xac_nhan' => 'Chờ xác nhận',
            'da_xac_nhan' => 'Đã xác nhận',
            DonDatLich::STATUS_CUSTOMER_UNREACHABLE => 'Không liên lạc được với khách hàng',
            'dang_lam' => 'Đang làm',
            'cho_hoan_thanh' => 'Chờ nghiệm thu',
            'cho_thanh_toan' => 'Chờ thanh toán',
            'da_xong', 'hoan_thanh' => 'Hoàn thành',
            'da_huy' => 'Đã hủy',
            default => 'Chưa cập nhật',
        };
    }
    private function resolveCustomerBookingTone(?string $status): string
    {
        return match ($status) {
            'cho_xac_nhan', 'da_xac_nhan' => 'info',
            DonDatLich::STATUS_CUSTOMER_UNREACHABLE => 'danger',
            'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan' => 'warning',
            'da_xong', 'hoan_thanh' => 'success',
            'da_huy' => 'danger',
            default => 'muted',
        };
    }
    private function formatCustomerPaymentMethodLabel(?string $method): string
    {
        return match ($method) {
            'cash', 'cod' => 'Tiền mặt',
            'transfer' => 'Chuyển khoản',
            'vnpay' => 'VNPay',
            'momo' => 'MoMo',
            'zalopay' => 'ZaloPay',
            'test' => 'Test',
            default => 'Khác',
        };
    }

    private function formatCustomerScheduleLabel(DonDatLich $booking): string
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

    private function resolveCustomerBookingSortTimestamp(DonDatLich $booking): int
    {
        return $booking->thoi_gian_hen?->timestamp
            ?? $booking->ngay_hen?->startOfDay()->timestamp
            ?? $booking->created_at?->timestamp
            ?? PHP_INT_MAX;
    }

    private function buildCustomerTimeline(User $customer, $bookings): array
    {
        $events = collect();

        if ($customer->created_at) {
            $events->push([
                'kind' => 'join',
                'title' => 'Khach tham gia he thong',
                'detail' => 'Tai khoan duoc tao va san sang dat lich.',
                'time_label' => $customer->created_at->format('d/m/Y H:i'),
                'tone' => 'info',
                'sort_at' => $customer->created_at->timestamp,
                'booking_url' => null,
            ]);
        }

        foreach ($bookings as $booking) {
            if (!$booking instanceof DonDatLich) {
                continue;
            }

            $bookingCode = $this->formatCustomerBookingCode($booking->id);
            $serviceLabel = $this->buildCustomerServiceLabel($booking);
            $bookingUrl = '/customer/my-bookings/' . $booking->id;

            if ($booking->created_at) {
                $events->push([
                    'kind' => 'booking',
                    'title' => 'Tao don ' . $bookingCode,
                    'detail' => $serviceLabel . ' - ' . $this->formatCustomerScheduleLabel($booking),
                    'time_label' => $booking->created_at->format('d/m/Y H:i'),
                    'tone' => 'muted',
                    'sort_at' => $booking->created_at->timestamp,
                    'booking_url' => $bookingUrl,
                ]);
            }

            if ($booking->ngay_hen || $booking->thoi_gian_hen) {
                $scheduledAt = $booking->thoi_gian_hen?->timestamp
                    ?? $booking->ngay_hen?->startOfDay()->timestamp
                    ?? $booking->created_at?->timestamp
                    ?? 0;

                $events->push([
                    'kind' => 'schedule',
                    'title' => 'Hen lich xu ly',
                    'detail' => $bookingCode . ' - ' . $this->formatCustomerScheduleLabel($booking),
                    'time_label' => $this->formatCustomerScheduleLabel($booking),
                    'tone' => 'info',
                    'sort_at' => $scheduledAt,
                    'booking_url' => $bookingUrl,
                ]);
            }

            if ($booking->thoi_gian_hoan_thanh) {
                $events->push([
                    'kind' => 'complete',
                    'title' => 'Hoan tat don',
                    'detail' => $bookingCode . ' - ' . $serviceLabel,
                    'time_label' => $booking->thoi_gian_hoan_thanh->format('d/m/Y H:i'),
                    'tone' => 'success',
                    'sort_at' => $booking->thoi_gian_hoan_thanh->timestamp,
                    'booking_url' => $bookingUrl,
                ]);
            }

            if ($booking->trang_thai === 'da_huy') {
                $events->push([
                    'kind' => 'cancel',
                    'title' => 'Huy don',
                    'detail' => $booking->ly_do_huy
                        ? $bookingCode . ' - ' . $this->truncateDashboardText($booking->ly_do_huy, 96)
                        : $bookingCode . ' da huy tren he thong.',
                    'time_label' => optional($booking->updated_at)->format('d/m/Y H:i') ?: 'Khong ro thoi diem',
                    'tone' => 'danger',
                    'sort_at' => optional($booking->updated_at)->timestamp ?? 0,
                    'booking_url' => $bookingUrl,
                ]);
            }

            foreach ($booking->danhGias as $review) {
                if (!$review instanceof DanhGia || !$review->created_at) {
                    continue;
                }

                $events->push([
                    'kind' => 'review',
                    'title' => 'Danh gia ' . ((float) ($review->so_sao ?? 0)) . '/5',
                    'detail' => $review->nhan_xet
                        ? $this->truncateDashboardText($review->nhan_xet, 96)
                        : $bookingCode . ' da co review moi tu khach hang.',
                    'time_label' => $review->created_at->format('d/m/Y H:i'),
                    'tone' => ((float) ($review->so_sao ?? 0)) <= 2 ? 'warning' : 'success',
                    'sort_at' => $review->created_at->timestamp,
                    'booking_url' => $bookingUrl,
                ]);
            }
        }



        return $events
            ->sortByDesc('sort_at')
            ->take(14)
            ->map(function (array $event) {
                unset($event['sort_at']);

                return $event;
            })
            ->values()
            ->all();
    }

    private function getAssignableAdmins(): Collection
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->where('role', 'admin')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (User $admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ]);
    }

    private function formatCustomerSegmentLabel(string $segment): string
    {
        return match ($segment) {
            'needs_care' => 'Can cham soc',
            'vip' => 'VIP',
            'loyal' => 'Quay lai deu',
            'active_booking' => 'Dang co don',
            'churn_risk' => 'Nguy co roi bo',
            'new_customer' => 'Khach moi',
            default => 'On dinh',
        };
    }

    private function resolveCustomerSegmentTone(string $segment): string
    {
        return match ($segment) {
            'needs_care', 'churn_risk' => 'danger',
            'vip', 'loyal' => 'success',
            'active_booking' => 'info',
            'new_customer' => 'warning',
            default => 'muted',
        };
    }

    private function buildCustomerSegments(array $context): array
    {
        $segments = collect();

        if (($context['low_feedback_count'] ?? 0) > 0
            || ($context['overdue_follow_up_count'] ?? 0) > 0
            || ($context['cancel_with_reason_count'] ?? 0) > 0
        ) {
            $segments->push('needs_care');
        }

        if (($context['total_spent'] ?? 0) >= 2000000 || ($context['completed_booking_count'] ?? 0) >= 5) {
            $segments->push('vip');
        }

        if (($context['completed_booking_count'] ?? 0) >= 3
            && (($context['days_since_last_booking'] ?? 9999) <= 45)
        ) {
            $segments->push('loyal');
        }

        if (($context['active_booking_count'] ?? 0) > 0) {
            $segments->push('active_booking');
        }

        if (($context['completed_booking_count'] ?? 0) > 0
            && (($context['days_since_last_booking'] ?? 0) >= 45)
        ) {
            $segments->push('churn_risk');
        }

        if (($context['created_days'] ?? 9999) <= 30 && ($context['order_count'] ?? 0) <= 1) {
            $segments->push('new_customer');
        }

        if ($segments->isEmpty()) {
            $segments->push('standard');
        }

        return $segments
            ->unique()
            ->take(4)
            ->map(fn (string $segment) => [
                'code' => $segment,
                'label' => $this->formatCustomerSegmentLabel($segment),
                'tone' => $this->resolveCustomerSegmentTone($segment),
            ])
            ->values()
            ->all();
    }

    private function customerHasSegment(array $segments, string $segment): bool
    {
        return collect($segments)->contains(fn (array $item) => ($item['code'] ?? null) === $segment);
    }



    private function resolveCustomerFeedbackPriority(string $type, array $context = []): string
    {
        if ($type === 'low_rating') {
            $rating = (float) ($context['rating'] ?? 0);

            return match (true) {
                $rating <= 1 => 'high',
                $rating <= 2 => 'medium',
                default => 'low',
            };
        }

        $reasonCode = (string) ($context['reason_code'] ?? '');

        return in_array($reasonCode, [
            DonDatLich::CANCEL_REASON_KHONG_CO_THO_NAO_NHAN,
            DonDatLich::CANCEL_REASON_CHO_QUA_LAU,
        ], true) ? 'high' : 'medium';
    }

    private function formatCustomerFeedbackPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'high' => 'Uu tien cao',
            'medium' => 'Can xu ly',
            default => 'Theo doi',
        };
    }

    private function resolveCustomerFeedbackPriorityTone(string $priority): string
    {
        return match ($priority) {
            'high' => 'danger',
            'medium' => 'warning',
            default => 'info',
        };
    }

    private function formatCustomerFeedbackSourceKey(string $sourceType, int|string $sourceId): string
    {
        return $sourceType . '-' . $sourceId;
    }

    private function formatCustomerFeedbackStatusLabel(string $status): string
    {
        return match ($status) {
            'in_progress' => 'Dang xu ly',
            'resolved' => 'Da xu ly',
            default => 'Moi',
        };
    }

    private function resolveCustomerFeedbackStatusTone(string $status): string
    {
        return match ($status) {
            'in_progress' => 'warning',
            'resolved' => 'success',
            default => 'info',
        };
    }

    private function resolveCustomerFeedbackDueState(?Carbon $deadlineAt, string $status, Carbon $now): string
    {
        if ($status === 'resolved') {
            return 'resolved';
        }

        if (!$deadlineAt) {
            return 'no_deadline';
        }

        if ($deadlineAt->lt($now)) {
            return 'overdue';
        }

        if ($deadlineAt->isSameDay($now)) {
            return 'due_today';
        }

        return 'upcoming';
    }

    private function formatCustomerFeedbackDueStateLabel(string $dueState): string
    {
        return match ($dueState) {
            'overdue' => 'Qua han',
            'due_today' => 'Den han hom nay',
            'upcoming' => 'Sap den han',
            'resolved' => 'Da dong',
            default => 'Chua dat han',
        };
    }

    private function mapLowRatingFeedbackCase(DanhGia $review): array
    {
        $booking = $review->donDatLich;
        $customer = $booking?->khachHang;
        $worker = $booking?->tho ?? $review->nguoiBiDanhGia;
        $rating = (float) ($review->so_sao ?? 0);
        $priority = $this->resolveCustomerFeedbackPriority('low_rating', ['rating' => $rating]);
        $content = trim((string) ($review->nhan_xet ?: 'Khach hang de lai danh gia thap cho don nay.'));
        $locationLabel = $booking
            ? ($booking->loai_dat_lich === 'at_home'
                ? ($booking->dia_chi ?: 'Chua cap nhat dia chi xu ly')
                : 'Khach mang thiet bi den cua hang')
            : 'Chua co dia diem xu ly';

        return [
            'id' => $this->formatCustomerFeedbackSourceKey('low_rating', $review->id),
            'source_type' => 'low_rating',
            'source_id' => (int) $review->id,
            'type' => 'low_rating',
            'type_label' => 'Danh gia thap',
            'priority' => $priority,
            'priority_label' => $this->formatCustomerFeedbackPriorityLabel($priority),
            'priority_tone' => $this->resolveCustomerFeedbackPriorityTone($priority),
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name ?: 'Khach hang',
            'customer_phone' => $customer?->phone ?: null,
            'customer_url' => $customer ? '/admin/customers/' . $customer->id : null,
            'booking_id' => $booking?->id,
            'booking_code' => $booking
                ? $this->formatCustomerBookingCode($booking->id)
                : 'DG-' . str_pad((string) $review->id, 4, '0', STR_PAD_LEFT),
            'booking_url' => $booking ? '/customer/my-bookings/' . $booking->id : null,
            'worker_id' => $worker?->id,
            'worker_name' => $worker?->name ?: 'Chua gan tho',
            'service_label' => $booking ? $this->buildCustomerServiceLabel($booking) : 'Chua ro dich vu',
            'created_at' => optional($review->created_at)->toDateTimeString(),
            'created_label' => optional($review->created_at)->format('d/m/Y H:i') ?: 'Khong ro thoi diem',
            'summary' => $this->truncateDashboardText($content, 120),
            'content' => $content,
            'rating' => $rating,
            'location_label' => $locationLabel,
        ];
    }

    private function mapCancellationFeedbackCase(DonDatLich $booking): array
    {
        $customer = $booking->khachHang;
        $worker = $booking->tho;
        $reasonCode = (string) ($booking->ma_ly_do_huy ?? '');
        $priority = $this->resolveCustomerFeedbackPriority('cancellation', ['reason_code' => $reasonCode]);
        $content = trim((string) ($booking->ly_do_huy ?: 'Don da huy va can admin ra soat nguyen nhan.'));
        $locationLabel = $booking->loai_dat_lich === 'at_home'
            ? ($booking->dia_chi ?: 'Chua cap nhat dia chi xu ly')
            : 'Khach mang thiet bi den cua hang';

        return [
            'id' => $this->formatCustomerFeedbackSourceKey('cancellation', $booking->id),
            'source_type' => 'cancellation',
            'source_id' => (int) $booking->id,
            'type' => 'cancellation',
            'type_label' => 'Huy don',
            'priority' => $priority,
            'priority_label' => $this->formatCustomerFeedbackPriorityLabel($priority),
            'priority_tone' => $this->resolveCustomerFeedbackPriorityTone($priority),
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name ?: 'Khach hang',
            'customer_phone' => $customer?->phone ?: null,
            'customer_url' => $customer ? '/admin/customers/' . $customer->id : null,
            'booking_id' => $booking->id,
            'booking_code' => $this->formatCustomerBookingCode($booking->id),
            'booking_url' => '/customer/my-bookings/' . $booking->id,
            'worker_id' => $worker?->id,
            'worker_name' => $worker?->name ?: 'Chua co tho nhan',
            'service_label' => $this->buildCustomerServiceLabel($booking),
            'created_at' => optional($booking->updated_at)->toDateTimeString() ?: optional($booking->created_at)->toDateTimeString(),
            'created_label' => optional($booking->updated_at)->format('d/m/Y H:i')
                ?: (optional($booking->created_at)->format('d/m/Y H:i') ?: 'Khong ro thoi diem'),
            'summary' => $this->truncateDashboardText($content, 120),
            'content' => $content,
            'rating' => null,
            'location_label' => $locationLabel,
        ];
    }

    private function mapCustomerComplaintFeedbackCase(CustomerFeedbackCase $caseState): array
    {
        $booking = $caseState->booking;
        $customer = $caseState->customer ?? $booking?->khachHang;
        $worker = $caseState->worker ?? $booking?->tho;
        $snapshot = is_array($caseState->last_snapshot) ? $caseState->last_snapshot : [];
        $reasonCode = (string) ($snapshot['reason_code'] ?? '');
        $reasonLabel = (string) ($snapshot['reason_label'] ?? match ($reasonCode) {
            'loi_tai_phat' => 'Loi tai phat',
            'linh_kien_kem_chat_luong' => 'Linh kien thay the kem chat luong',
            default => 'Khieu nai khac',
        });
        $note = trim((string) ($snapshot['note'] ?? ''));
        $content = trim($reasonLabel . ($note !== '' ? '. ' . $note : ''));
        if ($content === '') {
            $content = 'Khach hang gui khieu nai cho don nay.';
        }

        $locationLabel = $booking
            ? ($booking->loai_dat_lich === 'at_home'
                ? ($booking->dia_chi ?: 'Chua cap nhat dia chi xu ly')
                : 'Khach mang thiet bi den cua hang')
            : 'Chua co dia diem xu ly';
        $priority = (string) ($caseState->priority ?: ($reasonCode === 'linh_kien_kem_chat_luong' ? 'high' : 'medium'));

        $case = [
            'id' => $this->formatCustomerFeedbackSourceKey('customer_complaint', (int) $caseState->source_id),
            'source_type' => 'customer_complaint',
            'source_id' => (int) $caseState->source_id,
            'type' => 'customer_complaint',
            'type_label' => 'Khieu nai',
            'priority' => $priority,
            'priority_label' => $this->formatCustomerFeedbackPriorityLabel($priority),
            'priority_tone' => $this->resolveCustomerFeedbackPriorityTone($priority),
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name ?: 'Khach hang',
            'customer_phone' => $customer?->phone ?: null,
            'customer_url' => $customer ? '/admin/customers/' . $customer->id : null,
            'booking_id' => $booking?->id ?: (int) $caseState->source_id,
            'booking_code' => $booking
                ? $this->formatCustomerBookingCode($booking->id)
                : ('KN-' . str_pad((string) $caseState->source_id, 4, '0', STR_PAD_LEFT)),
            'booking_url' => $booking ? '/customer/my-bookings/' . $booking->id : null,
            'worker_id' => $worker?->id,
            'worker_name' => $worker?->name ?: 'Chua gan tho',
            'service_label' => $booking ? $this->buildCustomerServiceLabel($booking) : 'Chua ro dich vu',
            'created_at' => optional($caseState->created_at)->toDateTimeString(),
            'created_label' => optional($caseState->created_at)->format('d/m/Y H:i') ?: 'Khong ro thoi diem',
            'summary' => $this->truncateDashboardText($content, 120),
            'content' => $content,
            'rating' => null,
            'location_label' => $locationLabel,
        ];

        return $this->applyCustomerFeedbackCaseState($case, $caseState);
    }

    private function applyCustomerFeedbackCaseState(array $case, ?CustomerFeedbackCase $caseState): array
    {
        $status = $caseState?->status ?: 'new';
        $priority = $caseState?->priority ?: $case['priority'];
        $assignedAdminName = $caseState?->assignedAdmin?->name;
        $deadlineAt = $caseState?->deadline_at;
        $dueState = $this->resolveCustomerFeedbackDueState($deadlineAt, $status, Carbon::now());

        $case['id'] = $this->formatCustomerFeedbackSourceKey($case['source_type'], $case['source_id']);
        $case['priority'] = $priority;
        $case['priority_label'] = $this->formatCustomerFeedbackPriorityLabel($priority);
        $case['priority_tone'] = $this->resolveCustomerFeedbackPriorityTone($priority);
        $case['status'] = $status;
        $case['status_label'] = $this->formatCustomerFeedbackStatusLabel($status);
        $case['status_tone'] = $this->resolveCustomerFeedbackStatusTone($status);
        $case['assigned_admin_id'] = $caseState?->assigned_admin_id;
        $case['assigned_admin_name'] = $assignedAdminName;
        $case['assigned_label'] = $caseState?->assigned_at
            ? optional($caseState->assigned_at)->format('d/m/Y H:i')
            : 'Chua nhan xu ly';
        $case['deadline_at'] = $deadlineAt?->toIso8601String();
        $case['deadline_label'] = $deadlineAt?->format('d/m/Y H:i');
        $case['due_state'] = $dueState;
        $case['due_state_label'] = $this->formatCustomerFeedbackDueStateLabel($dueState);
        $case['assignment_note'] = trim((string) ($caseState?->assignment_note ?? ''));
        $case['resolved_label'] = $caseState?->resolved_at
            ? optional($caseState->resolved_at)->format('d/m/Y H:i')
            : null;
        $case['resolution_note'] = $caseState?->resolution_note;

        return $case;
    }

    private function resolveCustomerFeedbackSourceCase(string $caseKey): array
    {
        if (!preg_match('/^([a-z_]+)-(\d+)$/', $caseKey, $matches)) {
            abort(404, 'Khong tim thay case phu hop.');
        }

        $sourceType = $matches[1];
        $sourceId = (int) $matches[2];

        return match ($sourceType) {
            'low_rating' => $this->mapLowRatingFeedbackCase(
                DanhGia::query()
                    ->with([
                        'donDatLich:id,khach_hang_id,tho_id,loai_dat_lich,dia_chi,trang_thai,created_at,updated_at',
                        'donDatLich.khachHang:id,name,phone',
                        'donDatLich.tho:id,name,phone',
                        'donDatLich.dichVus:id,ten_dich_vu',
                        'nguoiBiDanhGia:id,name,phone',
                    ])
                    ->findOrFail($sourceId)
            ),
            'customer_complaint' => $this->mapCustomerComplaintFeedbackCase(
                CustomerFeedbackCase::query()
                    ->with([
                        'customer:id,name,phone',
                        'worker:id,name,phone',
                        'booking:id,khach_hang_id,tho_id,loai_dat_lich,dia_chi,trang_thai,created_at,updated_at',
                        'booking.dichVus:id,ten_dich_vu',
                        'assignedAdmin:id,name',
                    ])
                    ->where('source_type', 'customer_complaint')
                    ->where('source_id', $sourceId)
                    ->firstOrFail()
            ),
            'cancellation' => $this->mapCancellationFeedbackCase(
                DonDatLich::query()
                    ->with([
                        'khachHang:id,name,phone',
                        'tho:id,name,phone',
                        'dichVus:id,ten_dich_vu',
                    ])
                    ->findOrFail($sourceId)
            ),
            default => abort(404, 'Khong tim thay case phu hop.'),
        };
    }

    public function toggleUserStatus(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong the thay doi trang thai cua admin.',
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => $user->is_active ? 'Da mo khoa tai khoan' : 'Da khoa tai khoan',
            'data' => $user,
        ]);
    }

    public function getWorkerProfiles(Request $request)
    {
        $approvalStatus = $request->query('approval_status');

        $query = HoSoTho::query()
            ->with([
                'user:id,name,email,phone,avatar,is_active,created_at',
                'user.dichVus:id,ten_dich_vu',
            ])
            ->latest('updated_at');

        if ($approvalStatus) {
            $query->where('trang_thai_duyet', $approvalStatus);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    public function getWorkerSchedulesOverview(Request $request)
    {
        $view = in_array((string) $request->query('view', 'day'), ['day', 'week'], true)
            ? (string) $request->query('view', 'day')
            : 'day';
        $preserveDate = $request->boolean('preserve_date');

        try {
            $anchorDate = Carbon::parse((string) $request->query('date', now()->toDateString()))->startOfDay();
        } catch (\Throwable $exception) {
            $anchorDate = now()->startOfDay();
        }

        $timeSlots = DonDatLich::fixedTimeSlots();
        $blockingStatuses = DonDatLich::scheduleBlockingStatuses();

        $workerProfiles = HoSoTho::query()
            ->with([
                'user:id,name,email,phone,address,avatar,is_active,created_at',
                'user.dichVus:id,ten_dich_vu',
            ])
            ->where('trang_thai_duyet', 'da_duyet')
            ->whereHas('user', function (Builder $query) {
                $query->where('role', 'worker');
            })
            ->get();

        $workerIds = $workerProfiles
            ->pluck('user_id')
            ->map(fn ($workerId): int => (int) $workerId)
            ->filter()
            ->values();

        $availableDates = $this->buildAdminWorkerScheduleAvailableDates($workerIds);

        if ($view === 'day' && !$preserveDate) {
            $resolvedAnchorDate = $this->resolveAdminWorkerScheduleAnchorDate(
                $anchorDate->toDateString(),
                $availableDates
            );

            if ($resolvedAnchorDate !== null) {
                $anchorDate = Carbon::parse($resolvedAnchorDate)->startOfDay();
            }
        }

        if ($view === 'day' && $preserveDate) {
            $requestedDate = $anchorDate->toDateString();
            $hasRequestedDate = $availableDates->contains(
                fn (array $option): bool => (string) ($option['date'] ?? '') === $requestedDate
            );

            if (!$hasRequestedDate) {
                $availableDates = $availableDates
                    ->push([
                        'date' => $requestedDate,
                        'label' => $this->adminWorkerScheduleWeekdayShortLabel($anchorDate) . ' ' . $anchorDate->format('d/m/Y'),
                        'full_label' => $this->adminWorkerScheduleWeekdayFullLabel($anchorDate) . ' ' . $anchorDate->format('d/m/Y'),
                        'booking_count' => 0,
                        'is_today' => $anchorDate->isToday(),
                    ])
                    ->sortBy('date')
                    ->values();
            }
        }

        $range = $this->resolveAdminWorkerScheduleRange($view, $anchorDate);

        $activeBookingsByWorker = DonDatLich::query()
            ->with(['dichVus:id,ten_dich_vu'])
            ->whereNotNull('tho_id')
            ->when(
                $workerIds->isNotEmpty(),
                fn ($query) => $query->whereIn('tho_id', $workerIds->all()),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->whereIn('trang_thai', $blockingStatuses)
            ->get()
            ->groupBy(fn (DonDatLich $booking) => (int) $booking->tho_id);

        $rangeBookingsByWorker = DonDatLich::query()
            ->with([
                'dichVus:id,ten_dich_vu',
                'khachHang:id,name,phone',
            ])
            ->whereNotNull('tho_id')
            ->when(
                $workerIds->isNotEmpty(),
                fn ($query) => $query->whereIn('tho_id', $workerIds->all()),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->whereBetween('ngay_hen', [$range['date_from'], $range['date_to']])
            ->orderBy('ngay_hen')
            ->orderBy('khung_gio_hen')
            ->get()
            ->groupBy(fn (DonDatLich $booking) => (int) $booking->tho_id);

        $workers = $workerProfiles
            ->map(function (HoSoTho $workerProfile) use ($activeBookingsByWorker, $rangeBookingsByWorker, $range, $timeSlots): array {
                $workerId = (int) $workerProfile->user_id;

                return $this->buildAdminWorkerScheduleSnapshot(
                    $workerProfile,
                    $activeBookingsByWorker->get($workerId, collect()),
                    $rangeBookingsByWorker->get($workerId, collect()),
                    $range,
                    $timeSlots
                );
            })
            ->sortBy(function (array $worker): string {
                return sprintf(
                    '%02d-%s',
                    (int) ($worker['sort_order'] ?? 99),
                    Str::lower((string) ($worker['name'] ?? ''))
                );
            })
            ->values()
            ->map(function (array $worker): array {
                unset($worker['sort_order']);

                return $worker;
            })
            ->values();

        $totalBusySlots = (int) $workers->sum('busy_slot_count');
        $totalFreeSlots = (int) $workers->sum('free_slot_count');
        $totalCapacitySlots = max(0, $totalBusySlots + $totalFreeSlots);

        return response()->json([
            'status' => 'success',
            'data' => [
                'meta' => [
                    'view' => $view,
                    'anchor_date' => $anchorDate->toDateString(),
                    'date_from' => $range['date_from'],
                    'date_to' => $range['date_to'],
                    'range_label' => $range['range_label'],
                    'day_count' => count($range['dates']),
                    'slot_count' => count($timeSlots),
                    'available_dates' => $availableDates->all(),
                    'time_slots' => collect($timeSlots)->map(fn (string $slot): array => [
                        'value' => $slot,
                        'label' => $this->formatAdminWorkerScheduleSlotLabel($slot),
                    ])->values()->all(),
                    'dates' => $range['dates'],
                    'updated_at' => now()->format('H:i'),
                ],
                'summary' => [
                    'total_workers' => $workers->count(),
                    'available_workers' => $workers->where('current_status.key', 'available')->count(),
                    'scheduled_workers' => $workers->where('current_status.key', 'scheduled')->count(),
                    'repairing_workers' => $workers->where('current_status.key', 'repairing')->count(),
                    'offline_workers' => $workers->where('current_status.key', 'offline')->count(),
                    'workers_with_free_slots' => $workers->filter(fn (array $worker): bool => (int) ($worker['free_slot_count'] ?? 0) > 0)->count(),
                    'fully_booked_workers' => $workers->filter(fn (array $worker): bool => (int) ($worker['capacity_slot_count'] ?? 0) > 0 && (int) ($worker['free_slot_count'] ?? 0) === 0)->count(),
                    'total_busy_slots' => $totalBusySlots,
                    'total_free_slots' => $totalFreeSlots,
                    'utilization_percent' => $totalCapacitySlots > 0
                        ? (int) round(($totalBusySlots / $totalCapacitySlots) * 100)
                        : 0,
                ],
                'workers' => $workers->all(),
            ],
        ]);
    }

    private function buildAdminWorkerScheduleAvailableDates(Collection $workerIds): Collection
    {
        if ($workerIds->isEmpty()) {
            return collect();
        }

        return DonDatLich::query()
            ->selectRaw('DATE(ngay_hen) as booking_date, COUNT(*) as booking_count')
            ->whereNotNull('tho_id')
            ->whereIn('tho_id', $workerIds->all())
            ->whereNotNull('ngay_hen')
            ->groupBy('booking_date')
            ->orderBy('booking_date')
            ->get()
            ->map(function ($row): array {
                $date = Carbon::parse((string) ($row->booking_date ?? now()->toDateString()))->startOfDay();

                return [
                    'date' => $date->toDateString(),
                    'label' => $this->adminWorkerScheduleWeekdayShortLabel($date) . ' ' . $date->format('d/m/Y'),
                    'full_label' => $this->adminWorkerScheduleWeekdayFullLabel($date) . ' ' . $date->format('d/m/Y'),
                    'booking_count' => (int) ($row->booking_count ?? 0),
                    'is_today' => $date->isToday(),
                ];
            })
            ->values();
    }

    private function resolveAdminWorkerScheduleAnchorDate(string $requestedDate, Collection $availableDates): ?string
    {
        if ($availableDates->isEmpty()) {
            return null;
        }

        $requestedOption = $availableDates->firstWhere('date', $requestedDate);
        if (is_array($requestedOption)) {
            return (string) ($requestedOption['date'] ?? $requestedDate);
        }

        $upcomingOption = $availableDates->first(
            fn (array $option): bool => (string) ($option['date'] ?? '') >= $requestedDate
        );
        if (is_array($upcomingOption)) {
            return (string) ($upcomingOption['date'] ?? $requestedDate);
        }

        $today = now()->toDateString();
        $todayOption = $availableDates->first(
            fn (array $option): bool => (string) ($option['date'] ?? '') >= $today
        );
        if (is_array($todayOption)) {
            return (string) ($todayOption['date'] ?? $requestedDate);
        }

        return (string) (($availableDates->last()['date'] ?? $requestedDate));
    }

    public function updateWorkerApproval(Request $request, string $userId)
    {
        $validator = Validator::make($request->all(), [
            'trang_thai_duyet' => 'required|in:cho_duyet,da_duyet,tu_choi',
            'ghi_chu_admin' => 'nullable|string|max:2000',
            'dang_hoat_dong' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $workerProfile = HoSoTho::query()
            ->where('user_id', $userId)
            ->first();

        if (!$workerProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay ho so tho',
            ], 404);
        }

        $approvalStatus = (string) $request->input('trang_thai_duyet');
        $isApproved = $approvalStatus === 'da_duyet';

        $workerProfile->update([
            'trang_thai_duyet' => $approvalStatus,
            'ghi_chu_admin' => trim((string) $request->input('ghi_chu_admin', '')) ?: null,
            'dang_hoat_dong' => $request->has('dang_hoat_dong')
                ? (bool) $request->boolean('dang_hoat_dong')
                : $isApproved,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $isApproved ? 'Da duyet ho so tho' : ($approvalStatus === 'tu_choi' ? 'Da tu choi ho so tho' : 'Da chuyen ho so ve cho duyet'),
            'data' => $workerProfile->fresh([
                'user:id,name,email,phone,avatar,is_active,created_at',
                'user.dichVus:id,ten_dich_vu',
            ]),
        ]);
    }

    private function resolveAdminWorkerScheduleRange(string $view, Carbon $anchorDate): array
    {
        $rangeStart = $view === 'day'
            ? $anchorDate->copy()
            : $anchorDate->copy()->startOfWeek(Carbon::MONDAY);
        $rangeEnd = $view === 'day'
            ? $anchorDate->copy()
            : $anchorDate->copy()->endOfWeek(Carbon::SUNDAY);

        $cursor = $rangeStart->copy();
        $dates = [];

        while ($cursor->lte($rangeEnd)) {
            $dates[] = [
                'date' => $cursor->toDateString(),
                'short_label' => $this->adminWorkerScheduleWeekdayShortLabel($cursor),
                'full_label' => $this->adminWorkerScheduleWeekdayFullLabel($cursor),
                'day_number' => $cursor->format('d'),
                'month_label' => 'Th' . $cursor->format('m'),
                'display_label' => $this->adminWorkerScheduleWeekdayShortLabel($cursor) . ' ' . $cursor->format('d/m'),
                'is_today' => $cursor->isToday(),
                'is_weekend' => in_array($cursor->dayOfWeekIso, [6, 7], true),
            ];

            $cursor->addDay();
        }

        $rangeLabel = $view === 'day'
            ? 'Ngay ' . $anchorDate->format('d/m/Y')
            : $rangeStart->format('d/m') . ' - ' . $rangeEnd->format('d/m/Y');

        return [
            'date_from' => $rangeStart->toDateString(),
            'date_to' => $rangeEnd->toDateString(),
            'dates' => $dates,
            'range_label' => $rangeLabel,
        ];
    }

    private function buildAdminWorkerScheduleSnapshot(
        HoSoTho $workerProfile,
        Collection $activeBookings,
        Collection $rangeBookings,
        array $range,
        array $timeSlots
    ): array {
        $worker = $workerProfile->user;
        $currentBooking = $this->resolveDashboardWorkerCurrentBooking($activeBookings);
        $currentStatus = $this->resolveDashboardWorkerMapStatus($worker, $currentBooking);
        $serviceIds = $worker?->dichVus?->pluck('id')
            ?->map(fn ($serviceId): int => (int) $serviceId)
            ?->filter()
            ?->values() ?? collect();
        $services = $worker?->dichVus?->pluck('ten_dich_vu')->filter()->values() ?? collect();
        $bookingsByDate = $rangeBookings->groupBy(
            fn (DonDatLich $booking) => optional($booking->ngay_hen)->toDateString() ?: (string) $booking->ngay_hen
        );

        $days = collect($range['dates'])
            ->map(function (array $dateMeta) use ($bookingsByDate, $currentStatus, $timeSlots): array {
                $date = (string) ($dateMeta['date'] ?? '');

                return $this->buildAdminWorkerScheduleDaySnapshot(
                    $dateMeta,
                    $bookingsByDate->get($date, collect()),
                    $timeSlots,
                    (string) ($currentStatus['key'] ?? 'available')
                );
            })
            ->values();

        $busySlotCount = (int) $days->sum('busy_count');
        $freeSlotCount = (int) $days->sum('free_count');
        $capacitySlotCount = max(0, $busySlotCount + $freeSlotCount);
        $reviewCount = (int) ($workerProfile->tong_so_danh_gia ?? 0);
        $ratingValue = (float) ($workerProfile->danh_gia_trung_binh ?? 0);
        $upcomingBookings = $days
            ->pluck('bookings')
            ->flatten(1)
            ->sortBy([
                ['date', 'asc'],
                ['slot', 'asc'],
            ])
            ->values();

        return [
            'id' => (int) ($worker?->id ?? 0),
            'name' => (string) ($worker?->name ?: 'Tho ky thuat'),
            'phone' => (string) ($worker?->phone ?: ''),
            'avatar' => (string) ($worker?->avatar ?: '/assets/images/worker2.png'),
            'service_ids' => $serviceIds->all(),
            'services' => $services->all(),
            'services_label' => $services->isNotEmpty()
                ? $services->take(3)->implode(', ') . ($services->count() > 3 ? ' +' . ($services->count() - 3) : '')
                : 'Chua gan nhom dich vu',
            'rating' => round($ratingValue, 1),
            'review_count' => $reviewCount,
            'rating_label' => $reviewCount > 0
                ? number_format($ratingValue, 1) . '/5 • ' . $reviewCount . ' danh gia'
                : 'Chua co danh gia',
            'current_status' => $currentStatus,
            'current_booking_label' => $currentBooking
                ? $this->formatDashboardBookingCode($currentBooking->id) . ' • ' . ($currentBooking->dichVus->pluck('ten_dich_vu')->filter()->implode(', ') ?: 'Don dang mo')
                : 'Chua co lich dang mo',
            'schedule_label' => $this->buildDashboardWorkerScheduleLabel($currentBooking),
            'area_label' => $this->extractCustomerArea($currentBooking?->dia_chi ?: $worker?->address) ?: 'Chua co khu vuc',
            'busy_slot_count' => $busySlotCount,
            'free_slot_count' => $freeSlotCount,
            'capacity_slot_count' => $capacitySlotCount,
            'utilization_percent' => $capacitySlotCount > 0
                ? (int) round(($busySlotCount / $capacitySlotCount) * 100)
                : 0,
            'next_free_slot_label' => $this->resolveAdminWorkerNextFreeSlotLabel($days),
            'upcoming_bookings' => $upcomingBookings->take(8)->values()->all(),
            'days' => $days->all(),
            'sort_order' => $this->resolveDashboardWorkerStatusOrder((string) ($currentStatus['key'] ?? 'offline')),
        ];
    }

    private function buildAdminWorkerScheduleDaySnapshot(
        array $dateMeta,
        Collection $bookingsForDate,
        array $timeSlots,
        string $workerStatusKey
    ): array {
        $slotMap = $bookingsForDate
            ->groupBy(fn (DonDatLich $booking) => DonDatLich::normalizeTimeSlot($booking->khung_gio_hen))
            ->map(fn (Collection $items) => $items
                ->sortBy(fn (DonDatLich $booking): string => sprintf(
                    '%02d-%s',
                    $this->resolveAdminWorkerScheduleBookingPriority((string) $booking->trang_thai),
                    optional($booking->updated_at)->format('YmdHis') ?? '00000000000000'
                ))
                ->first());

        $bookings = $bookingsForDate
            ->map(fn (DonDatLich $booking): array => $this->serializeAdminWorkerScheduleBooking($booking))
            ->sortBy([
                ['date', 'asc'],
                ['slot', 'asc'],
            ])
            ->values();

        $slots = collect($timeSlots)
            ->map(function (string $slot) use ($slotMap, $workerStatusKey): array {
                /** @var DonDatLich|null $booking */
                $booking = $slotMap->get($slot);

                if ($booking) {
                    return [
                        'slot' => $slot,
                        'label' => $this->formatAdminWorkerScheduleSlotLabel($slot),
                        'state' => $this->resolveAdminWorkerScheduleSlotState((string) $booking->trang_thai),
                        'booking' => $this->serializeAdminWorkerScheduleBooking($booking),
                    ];
                }

                return [
                    'slot' => $slot,
                    'label' => $this->formatAdminWorkerScheduleSlotLabel($slot),
                    'state' => $workerStatusKey === 'offline' ? 'offline' : 'free',
                    'booking' => null,
                ];
            })
            ->values();

        $busyCount = $slots->whereIn('state', ['busy', 'repairing', 'completed', 'cancelled'])->count();
        $freeCount = $slots->where('state', 'free')->count();
        $statusKey = $slots->contains(fn (array $slot): bool => $slot['state'] === 'repairing')
            ? 'repairing'
            : ($slots->contains(fn (array $slot): bool => in_array($slot['state'], ['busy', 'completed', 'cancelled'], true))
                ? 'scheduled'
                : ($workerStatusKey === 'offline' ? 'offline' : 'available'));

        return [
            ...$dateMeta,
            'status_key' => $statusKey,
            'busy_count' => $busyCount,
            'free_count' => $freeCount,
            'bookings' => $bookings->all(),
            'slots' => $slots->all(),
        ];
    }

    private function serializeAdminWorkerScheduleBooking(DonDatLich $booking): array
    {
        $costLabor = (float) ($booking->tien_cong ?? 0);
        $costParts = (float) ($booking->phi_linh_kien ?? 0);
        $costTravel = (float) ($booking->phi_di_lai ?? 0);
        $costTransport = (float) ($booking->tien_thue_xe ?? 0);
        $totalAmount = (float) ($booking->tong_tien ?? 0);

        if ($totalAmount <= 0) {
            $totalAmount = $costLabor + $costParts + $costTravel + $costTransport;
        }

        return [
            'id' => (int) $booking->id,
            'code' => $this->formatDashboardBookingCode($booking->id),
            'date' => $booking->ngay_hen?->toDateString(),
            'slot' => DonDatLich::normalizeTimeSlot($booking->khung_gio_hen),
            'slot_label' => $this->formatAdminWorkerScheduleSlotLabel((string) $booking->khung_gio_hen),
            'status' => (string) $booking->trang_thai,
            'status_label' => DonDatLich::statusLabel($booking->trang_thai),
            'service_ids' => $booking->resolveServiceIds(),
            'service_label' => $booking->dichVus->pluck('ten_dich_vu')->filter()->implode(', ') ?: 'Sua chua',
            'customer_name' => (string) ($booking->khachHang?->name ?: 'Khach hang'),
            'customer_phone' => (string) ($booking->khachHang?->phone ?: ''),
            'mode' => (string) ($booking->loai_dat_lich ?: 'at_store'),
            'mode_label' => ($booking->loai_dat_lich === 'at_store') ? 'Tại cửa hàng' : 'Tại nhà',
            'address' => (string) ($booking->dia_chi ?: ''),
            'problem_excerpt' => $this->truncateDashboardText((string) ($booking->mo_ta_van_de ?: 'Khách chưa để mô tả vấn đề.'), 120),
            'total_amount' => $totalAmount,
            'scheduled_at' => $booking->thoi_gian_hen?->toIso8601String(),
        ];
    }

    private function resolveAdminWorkerScheduleSlotState(string $status): string
    {
        return match ($status) {
            'dang_lam' => 'repairing',
            'da_xong' => 'completed',
            'da_huy' => 'cancelled',
            default => 'busy',
        };
    }

    private function resolveAdminWorkerScheduleBookingPriority(string $status): int
    {
        return match ($status) {
            'dang_lam' => 1,
            'da_xac_nhan', 'cho_xac_nhan', 'cho_hoan_thanh', 'cho_thanh_toan', DonDatLich::STATUS_CUSTOMER_UNREACHABLE => 2,
            'da_xong' => 3,
            'da_huy' => 4,
            default => 9,
        };
    }

    private function resolveAdminWorkerNextFreeSlotLabel(Collection $days): string
    {
        foreach ($days as $day) {
            foreach ((array) ($day['slots'] ?? []) as $slot) {
                if (($slot['state'] ?? null) === 'free') {
                    return (($day['display_label'] ?? $day['date'] ?? '') . ' • ' . ($slot['label'] ?? $slot['slot'] ?? ''));
                }
            }
        }

        return 'Khong con slot trong trong khung hien tai';
    }

    private function adminWorkerScheduleWeekdayShortLabel(Carbon $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'T2',
            2 => 'T3',
            3 => 'T4',
            4 => 'T5',
            5 => 'T6',
            6 => 'T7',
            default => 'CN',
        };
    }

    private function adminWorkerScheduleWeekdayFullLabel(Carbon $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'Thu 2',
            2 => 'Thu 3',
            3 => 'Thu 4',
            4 => 'Thu 5',
            5 => 'Thu 6',
            6 => 'Thu 7',
            default => 'Chu nhat',
        };
    }

    private function formatAdminWorkerScheduleSlotLabel(string $slot): string
    {
        return str_replace('-', ' - ', DonDatLich::normalizeTimeSlot($slot));
    }

    public function getAllBookings(Request $request)
    {
        $filters = $this->resolveAdminBookingFilters($request);
        $now = Carbon::now();
        $rows = $this->buildAdminBookingRows($filters, $now);
        $rows = $this->applyAdminBookingComputedFilters($rows, $filters);
        $rows = $this->sortAdminBookingRows($rows, $filters['sort_by'], $filters['sort_dir']);
        $summary = $this->buildAdminBookingSummary($rows);
        [$items, $pagination] = $this->paginateAdminBookingRows($rows, $filters['page'], $filters['per_page']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $items,
                'summary' => $summary,
                'pagination' => $pagination,
                'filters' => [
                    'search' => $filters['search'],
                    'status' => $filters['status'],
                    'worker_id' => $filters['worker_id'],
                    'service_id' => $filters['service_id'],
                    'payment' => $filters['payment'],
                    'mode' => $filters['mode'],
                    'priority' => $filters['priority'],
                    'sla' => $filters['sla'],
                    'date_from' => $filters['date_from'],
                    'date_to' => $filters['date_to'],
                    'view' => $filters['view'],
                    'sort_by' => $filters['sort_by'],
                    'sort_dir' => $filters['sort_dir'],
                    'status_options' => $this->adminBookingStatusOptions(),
                    'service_options' => $this->adminBookingServiceOptions(),
                    'worker_options' => $this->adminBookingWorkerOptions(),
                    'payment_options' => [
                        ['value' => '', 'label' => 'Tất cả thanh toán'],
                        ['value' => 'paid', 'label' => 'Đã thanh toán'],
                        ['value' => 'unpaid', 'label' => 'Chưa thanh toán'],
                        ['value' => 'failed', 'label' => 'Thanh toán lỗi'],
                    ],
                    'mode_options' => [
                        ['value' => '', 'label' => 'Tất cả hình thức'],
                        ['value' => 'at_home', 'label' => 'Tại nhà'],
                        ['value' => 'at_store', 'label' => 'Tại cửa hàng'],
                    ],
                    'priority_options' => [
                        ['value' => '', 'label' => 'Tất cả ưu tiên'],
                        ['value' => 'high', 'label' => 'Cao'],
                        ['value' => 'medium', 'label' => 'Trung bình'],
                        ['value' => 'low', 'label' => 'Thấp'],
                    ],
                    'sla_options' => [
                        ['value' => '', 'label' => 'Tất cả SLA'],
                        ['value' => 'overdue', 'label' => 'Quá hạn'],
                        ['value' => 'due_soon', 'label' => 'Sắp quá hạn'],
                        ['value' => 'on_track', 'label' => 'Đúng hạn'],
                        ['value' => 'closed', 'label' => 'Đã đóng'],
                    ],
                    'sort_options' => [
                        ['value' => 'created_at', 'label' => 'Tạo đơn'],
                        ['value' => 'scheduled_at', 'label' => 'Lịch hẹn'],
                        ['value' => 'priority', 'label' => 'Ưu tiên'],
                        ['value' => 'sla', 'label' => 'SLA'],
                        ['value' => 'updated_at', 'label' => 'Cập nhật gần nhất'],
                        ['value' => 'total_amount', 'label' => 'Tổng tiền'],
                    ],
                    'quick_views' => [
                        ['value' => 'all', 'label' => 'Tất cả'],
                        ['value' => 'overdue', 'label' => 'Đơn quá hạn'],
                        ['value' => 'unpaid', 'label' => 'Chờ thanh toán'],
                        ['value' => 'complaint', 'label' => 'Có khiếu nại'],
                        ['value' => 'contact_issue', 'label' => 'Không liên lạc được'],
                        ['value' => 'unassigned', 'label' => 'Chưa gán thợ'],
                    ],
                    'status_flow' => $this->adminBookingStatusFlow(),
                    'cancel_reason_options' => $this->adminBookingCancelReasonOptions(),
                    'time_slots' => $this->adminBookingTimeSlots(),
                ],
            ],
        ]);
    }

    public function getBookingDetail(string $id)
    {
        $booking = DonDatLich::query()
            ->with([
                'khachHang:id,name,phone,email,address',
                'tho:id,name,phone,email',
                'dichVus:id,ten_dich_vu',
                'thanhToans',
                'danhGias:id,don_dat_lich_id,so_sao,nhan_xet,nguoi_danh_gia_id,created_at,updated_at',
                'danhGias.nguoiDanhGia:id,name',
                'customerComplaintCase:id,source_type,source_id,customer_id,booking_id,worker_id,priority,status,assigned_admin_id,assigned_at,resolved_at,resolution_note,last_snapshot,created_at,updated_at',
                'customerComplaintCase.assignedAdmin:id,name',
                'workerContactIssueReporter:id,name',
            ])
            ->find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay don hang',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->serializeAdminBookingDetail($booking),
        ]);
    }

    public function assignBookingWorker(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'worker_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking = DonDatLich::query()
            ->with('dichVus:id,ten_dich_vu')
            ->find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay don hang',
            ], 404);
        }

        if (in_array((string) $booking->trang_thai, ['da_huy', 'da_xong', 'hoan_thanh'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Don da dong, khong the doi tho',
            ], 422);
        }

        $worker = User::query()
            ->with([
                'hoSoTho:user_id,trang_thai_duyet,dang_hoat_dong',
                'dichVus:id,ten_dich_vu',
            ])
            ->find($request->integer('worker_id'));

        if (!$worker || $worker->role !== 'worker') {
            return response()->json([
                'status' => 'error',
                'message' => 'Nhan su duoc chon khong phai tai khoan tho',
            ], 422);
        }

        if (
            !$worker->is_active
            || !$worker->hoSoTho
            || $worker->hoSoTho->trang_thai_duyet !== 'da_duyet'
            || !(bool) $worker->hoSoTho->dang_hoat_dong
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tho hien khong san sang nhan viec',
            ], 422);
        }

        if (!$worker->supportsServiceIds($booking->resolveServiceIds())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tho khong thuoc nhom dich vu cua don nay',
            ], 422);
        }

        $bookingDate = $booking->ngay_hen?->toDateString()
            ?? $booking->thoi_gian_hen?->toDateString();
        $timeSlot = DonDatLich::normalizeTimeSlot((string) $booking->khung_gio_hen);

        if ($bookingDate && $timeSlot !== '') {
            $hasConflict = DonDatLich::query()
                ->conflictsWithWorkerSchedule((int) $worker->id, $bookingDate, $timeSlot)
                ->where('id', '!=', $booking->id)
                ->exists();

            if ($hasConflict) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tho dang trung lich voi khung gio nay',
                ], 409);
            }
        }

        DB::transaction(function () use ($booking, $worker): void {
            $booking->tho_id = $worker->id;

            if ($booking->trang_thai === 'cho_xac_nhan') {
                $booking->trang_thai = 'da_xac_nhan';
            }

            $booking->save();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat tho phu trach',
            'data' => [
                'booking_id' => (int) $booking->id,
                'worker_id' => (int) $worker->id,
                'worker_name' => (string) $worker->name,
            ],
        ]);
    }

    public function updateBookingFinancials(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'tien_cong' => 'nullable|numeric|min:0',
            'phi_linh_kien' => 'nullable|numeric|min:0',
            'phi_di_lai' => 'nullable|numeric|min:0',
            'tien_thue_xe' => 'nullable|numeric|min:0',
            'ghi_chu_linh_kien' => 'nullable|string|max:1000',
            'chi_tiet_tien_cong' => 'nullable|array',
            'chi_tiet_tien_cong.*.noi_dung' => 'required_with:chi_tiet_tien_cong|string|max:255',
            'chi_tiet_tien_cong.*.so_tien' => 'required_with:chi_tiet_tien_cong|numeric|min:0',
            'chi_tiet_linh_kien' => 'nullable|array',
            'chi_tiet_linh_kien.*.noi_dung' => 'required_with:chi_tiet_linh_kien|string|max:255',
            'chi_tiet_linh_kien.*.so_tien' => 'required_with:chi_tiet_linh_kien|numeric|min:0',
            'chi_tiet_linh_kien.*.don_gia' => 'nullable|numeric|min:0',
            'chi_tiet_linh_kien.*.so_luong' => 'nullable|integer|min:1|max:999',
            'chi_tiet_linh_kien.*.bao_hanh_thang' => 'nullable|integer|min:0|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking = DonDatLich::query()->find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay don hang',
            ], 404);
        }

        if ((string) $booking->trang_thai === 'da_huy') {
            return response()->json([
                'status' => 'error',
                'message' => 'Don da huy, khong the cap nhat chi phi',
            ], 422);
        }

        $labor = max(0, (float) $request->input('tien_cong', $booking->tien_cong ?? 0));
        $parts = max(0, (float) $request->input('phi_linh_kien', $booking->phi_linh_kien ?? 0));
        $travel = max(0, (float) $request->input('phi_di_lai', $booking->phi_di_lai ?? 0));
        $transport = max(0, (float) $request->input('tien_thue_xe', $booking->tien_thue_xe ?? 0));

        $laborItemsInput = $request->input('chi_tiet_tien_cong');
        $laborItems = is_array($laborItemsInput)
            ? collect($laborItemsInput)->map(static function ($item): ?array {
                if (!is_array($item)) {
                    return null;
                }

                $description = trim((string) ($item['noi_dung'] ?? ''));
                $amount = max(0, (float) ($item['so_tien'] ?? 0));

                if ($description === '' && $amount <= 0) {
                    return null;
                }

                return [
                    'noi_dung' => $description !== '' ? $description : 'Tien cong sua chua',
                    'so_tien' => $amount,
                ];
            })->filter()->values()->all()
            : (is_array($booking->chi_tiet_tien_cong) ? array_values($booking->chi_tiet_tien_cong) : []);

        if ($labor > 0 && $laborItems === []) {
            $laborItems = [[
                'noi_dung' => 'Tien cong sua chua',
                'so_tien' => $labor,
            ]];
        }

        $partItemsInput = $request->input('chi_tiet_linh_kien');
        $partItems = is_array($partItemsInput)
            ? collect($partItemsInput)->map(static function ($item): ?array {
                if (!is_array($item)) {
                    return null;
                }

                $description = trim((string) ($item['noi_dung'] ?? ''));
                $quantity = max(1, (int) ($item['so_luong'] ?? 1));
                $lineTotal = max(0, (float) ($item['so_tien'] ?? 0));
                $unitPrice = array_key_exists('don_gia', $item) && $item['don_gia'] !== null
                    ? max(0, (float) $item['don_gia'])
                    : ($quantity > 0 ? $lineTotal / $quantity : $lineTotal);
                $warrantyMonths = array_key_exists('bao_hanh_thang', $item) && $item['bao_hanh_thang'] !== null && $item['bao_hanh_thang'] !== ''
                    ? max(0, (int) $item['bao_hanh_thang'])
                    : null;

                if ($description === '' && $lineTotal <= 0 && $unitPrice <= 0) {
                    return null;
                }

                return [
                    'noi_dung' => $description !== '' ? $description : 'Linh kien thay the',
                    'don_gia' => $unitPrice,
                    'so_luong' => $quantity,
                    'so_tien' => $lineTotal > 0 ? $lineTotal : ($unitPrice * $quantity),
                    'bao_hanh_thang' => $warrantyMonths,
                ];
            })->filter()->values()->all()
            : (is_array($booking->chi_tiet_linh_kien) ? array_values($booking->chi_tiet_linh_kien) : []);

        if ($parts > 0 && $partItems === []) {
            $partItems = [[
                'noi_dung' => 'Linh kien thay the',
                'don_gia' => $parts,
                'so_luong' => 1,
                'so_tien' => $parts,
                'bao_hanh_thang' => null,
            ]];
        }

        $booking->tien_cong = $labor;
        $booking->chi_tiet_tien_cong = $laborItems;
        $booking->phi_linh_kien = $parts;
        $booking->chi_tiet_linh_kien = $partItems;
        $booking->phi_di_lai = $travel;
        $booking->tien_thue_xe = $transport;
        $booking->ghi_chu_linh_kien = trim((string) $request->input('ghi_chu_linh_kien', $booking->ghi_chu_linh_kien ?? '')) ?: null;
        $booking->tong_tien = $labor + $parts + $travel + $transport;
        $booking->gia_da_cap_nhat = true;
        $booking->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat chi phi don hang',
            'data' => [
                'booking_id' => (int) $booking->id,
                'tien_cong' => (float) ($booking->tien_cong ?? 0),
                'phi_linh_kien' => (float) ($booking->phi_linh_kien ?? 0),
                'phi_di_lai' => (float) ($booking->phi_di_lai ?? 0),
                'tien_thue_xe' => (float) ($booking->tien_thue_xe ?? 0),
                'tong_tien' => (float) ($booking->tong_tien ?? 0),
            ],
        ]);
    }

    public function exportBookings(Request $request)
    {
        $filters = $this->resolveAdminBookingFilters($request);
        $now = Carbon::now();
        $rows = $this->buildAdminBookingRows($filters, $now);
        $rows = $this->applyAdminBookingComputedFilters($rows, $filters);
        $rows = $this->sortAdminBookingRows($rows, $filters['sort_by'], $filters['sort_dir']);

        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(static fn ($id) => (int) trim((string) $id))
            ->filter()
            ->unique()
            ->values();
        if ($ids->isNotEmpty()) {
            $rows = $rows->filter(fn (array $row) => $ids->contains((int) ($row['id'] ?? 0)))->values();
        }

        $filename = 'admin-bookings-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, [
                'Ma don',
                'Trang thai',
                'Uu tien',
                'SLA',
                'Khách hàng',
                'So dien thoai',
                'Dia chi',
                'Loai dat lich',
                'Dịch vụ',
                'Lịch hẹn',
                'Tho phu trach',
                'Tien cong',
                'Linh kien',
                'Phi di lai',
                'Tổng tiền',
                'Thanh toan',
                'Phuong thuc thanh toan',
                'Co khieu nai',
                'Qua han',
                'Tao don',
                'Cập nhật',
            ]);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['code'] ?? '',
                    $row['status_label'] ?? '',
                    $row['priority_label'] ?? '',
                    $row['sla_label'] ?? '',
                    $row['customer']['name'] ?? '',
                    $row['customer']['phone'] ?? '',
                    $row['customer']['address'] ?? '',
                    $row['mode_label'] ?? '',
                    $row['service_label'] ?? '',
                    $row['schedule']['label'] ?? '',
                    $row['worker']['name'] ?? '',
                    (float) ($row['costs']['labor'] ?? 0),
                    (float) ($row['costs']['parts'] ?? 0),
                    (float) ($row['costs']['travel'] ?? 0),
                    (float) ($row['costs']['total'] ?? 0),
                    $row['payment']['status_label'] ?? '',
                    $row['payment']['method_label'] ?? '',
                    (($row['flags']['has_complaint'] ?? false) ? 'co' : 'khong'),
                    (($row['flags']['is_overdue'] ?? false) ? 'co' : 'khong'),
                    $row['milestones']['created_label'] ?? '',
                    $row['milestones']['updated_label'] ?? '',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveAdminBookingFilters(Request $request): array
    {
        $sortBy = trim((string) $request->query('sort_by', 'created_at'));
        $allowedSortBy = ['created_at', 'scheduled_at', 'priority', 'sla', 'updated_at', 'total_amount'];
        if (!in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'created_at';
        }

        $sortDir = strtolower(trim((string) $request->query('sort_dir', 'desc')));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => trim((string) $request->query('status', '')),
            'worker_id' => trim((string) $request->query('worker_id', '')),
            'service_id' => trim((string) $request->query('service_id', '')),
            'payment' => trim((string) $request->query('payment', '')),
            'mode' => trim((string) $request->query('mode', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'sla' => trim((string) $request->query('sla', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'view' => trim((string) $request->query('view', 'all')) ?: 'all',
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'page' => max(1, (int) $request->query('page', 1)),
            'per_page' => min(100, max(10, (int) $request->query('per_page', 20))),
        ];
    }

    private function fetchAdminBookingCollection(array $filters): Collection
    {
        $query = DonDatLich::query()
            ->with([
                'khachHang:id,name,phone,address',
                'tho:id,name,phone',
                'dichVus:id,ten_dich_vu',
                'thanhToans',
                'customerComplaintCase:id,source_type,source_id,booking_id,priority,status,last_snapshot,assigned_admin_id,assigned_at,resolved_at,resolution_note,created_at,updated_at',
                'customerComplaintCase.assignedAdmin:id,name',
                'workerContactIssueReporter:id,name',
            ]);

        if ($filters['status'] !== '') {
            $query->where('trang_thai', $filters['status']);
        }

        if ($filters['worker_id'] !== '' && ctype_digit($filters['worker_id'])) {
            $query->where('tho_id', (int) $filters['worker_id']);
        }

        if ($filters['service_id'] !== '' && ctype_digit($filters['service_id'])) {
            $serviceId = (int) $filters['service_id'];
            $query->where(function (Builder $builder) use ($serviceId): void {
                $builder
                    ->whereHas('dichVus', fn (Builder $serviceQuery) => $serviceQuery->whereKey($serviceId))
                    ->orWhere('dich_vu_id', $serviceId);
            });
        }

        if ($filters['mode'] !== '') {
            $query->where('loai_dat_lich', $filters['mode']);
        }

        if ($filters['payment'] === 'paid') {
            $query->where('trang_thai_thanh_toan', true);
        } elseif ($filters['payment'] === 'unpaid') {
            $query->where('trang_thai_thanh_toan', false);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                if (ctype_digit($search)) {
                    $builder->orWhere('id', (int) $search);
                }

                $builder
                    ->orWhere('dia_chi', 'like', '%' . $search . '%')
                    ->orWhere('mo_ta_van_de', 'like', '%' . $search . '%')
                    ->orWhereHas('khachHang', function (Builder $customerQuery) use ($search): void {
                        $customerQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query->latest('created_at')->get();
    }

    private function buildAdminBookingRows(array $filters, Carbon $now): Collection
    {
        return $this->fetchAdminBookingCollection($filters)
            ->map(fn (DonDatLich $booking) => $this->serializeAdminBookingListRow($booking, $now))
            ->values();
    }

    private function applyAdminBookingComputedFilters(Collection $rows, array $filters): Collection
    {
        $filtered = $rows;

        if ($filters['date_from'] !== '') {
            $filtered = $filtered->filter(function (array $row) use ($filters): bool {
                $date = $row['schedule']['date']
                    ?: substr((string) ($row['milestones']['created_at'] ?? ''), 0, 10);
                if ($date === '') {
                    return false;
                }

                return $date >= $filters['date_from'];
            })->values();
        }

        if ($filters['date_to'] !== '') {
            $filtered = $filtered->filter(function (array $row) use ($filters): bool {
                $date = $row['schedule']['date']
                    ?: substr((string) ($row['milestones']['created_at'] ?? ''), 0, 10);
                if ($date === '') {
                    return false;
                }

                return $date <= $filters['date_to'];
            })->values();
        }

        if ($filters['priority'] !== '') {
            $filtered = $filtered->where('priority_key', $filters['priority'])->values();
        }

        if ($filters['sla'] !== '') {
            $filtered = $filtered->where('sla_state', $filters['sla'])->values();
        }

        if ($filters['payment'] === 'failed') {
            $filtered = $filtered->filter(fn (array $row): bool => (bool) ($row['flags']['payment_issue'] ?? false))->values();
        }

        $view = $filters['view'];
        if ($view === 'overdue') {
            $filtered = $filtered->where('sla_state', 'overdue')->values();
        } elseif ($view === 'unpaid') {
            $filtered = $filtered->filter(fn (array $row): bool => !(bool) ($row['payment']['is_paid'] ?? false))->values();
        } elseif ($view === 'complaint') {
            $filtered = $filtered->filter(fn (array $row): bool => (bool) ($row['flags']['has_complaint'] ?? false))->values();
        } elseif ($view === 'contact_issue') {
            $filtered = $filtered->filter(fn (array $row): bool => (bool) ($row['flags']['has_worker_contact_issue'] ?? false))->values();
        } elseif ($view === 'unassigned') {
            $filtered = $filtered->filter(fn (array $row): bool => (bool) ($row['flags']['is_unassigned'] ?? false))->values();
        }

        return $filtered->values();
    }

    private function sortAdminBookingRows(Collection $rows, string $sortBy, string $sortDir): Collection
    {
        $direction = $sortDir === 'asc' ? 1 : -1;
        $compareTimestamp = static fn (?string $left, ?string $right): int => (strtotime((string) $left) ?: 0) <=> (strtotime((string) $right) ?: 0);

        return $rows->sort(function (array $left, array $right) use ($sortBy, $direction, $compareTimestamp): int {
            $result = 0;

            if ($sortBy === 'scheduled_at') {
                $result = $compareTimestamp(
                    $left['schedule']['scheduled_at'] ?? $left['milestones']['created_at'] ?? null,
                    $right['schedule']['scheduled_at'] ?? $right['milestones']['created_at'] ?? null
                );
            } elseif ($sortBy === 'priority') {
                $result = ((int) ($left['priority_rank'] ?? 0)) <=> ((int) ($right['priority_rank'] ?? 0));
            } elseif ($sortBy === 'sla') {
                $result = ((int) ($left['sla_rank'] ?? 0)) <=> ((int) ($right['sla_rank'] ?? 0));
            } elseif ($sortBy === 'updated_at') {
                $result = $compareTimestamp($left['milestones']['updated_at'] ?? null, $right['milestones']['updated_at'] ?? null);
            } elseif ($sortBy === 'total_amount') {
                $result = ((float) ($left['costs']['total'] ?? 0)) <=> ((float) ($right['costs']['total'] ?? 0));
            } else {
                $result = $compareTimestamp($left['milestones']['created_at'] ?? null, $right['milestones']['created_at'] ?? null);
            }

            if ($result === 0) {
                $result = $compareTimestamp($left['milestones']['created_at'] ?? null, $right['milestones']['created_at'] ?? null);
            }

            return $result * $direction;
        })->values();
    }

    private function buildAdminBookingSummary(Collection $rows): array
    {
        $total = $rows->count();

        return [
            'total_orders' => $total,
            'overdue_count' => $rows->where('sla_state', 'overdue')->count(),
            'due_soon_count' => $rows->where('sla_state', 'due_soon')->count(),
            'on_track_count' => $rows->where('sla_state', 'on_track')->count(),
            'unpaid_count' => $rows->filter(fn (array $row): bool => !(bool) ($row['payment']['is_paid'] ?? false))->count(),
            'payment_issue_count' => $rows->filter(fn (array $row): bool => (bool) ($row['flags']['payment_issue'] ?? false))->count(),
            'complaint_count' => $rows->filter(fn (array $row): bool => (bool) ($row['flags']['has_complaint'] ?? false))->count(),
            'contact_issue_count' => $rows->filter(fn (array $row): bool => (bool) ($row['flags']['has_worker_contact_issue'] ?? false))->count(),
            'unassigned_count' => $rows->filter(fn (array $row): bool => (bool) ($row['flags']['is_unassigned'] ?? false))->count(),
            'filtered_count' => $total,
        ];
    }

    private function paginateAdminBookingRows(Collection $rows, int $page, int $perPage): array
    {
        $total = $rows->count();
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $safePage = min(max(1, $page), $lastPage);
        $offset = ($safePage - 1) * $perPage;
        $items = $rows->slice($offset, $perPage)->values()->all();

        return [
            $items,
            [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $safePage,
                'last_page' => $lastPage,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }

    private function resolveAdminBookingScheduleAt(DonDatLich $booking): ?Carbon
    {
        if ($booking->thoi_gian_hen) {
            return $booking->thoi_gian_hen->copy();
        }

        if ($booking->ngay_hen && $booking->khung_gio_hen) {
            $slotStart = trim(explode('-', (string) $booking->khung_gio_hen)[0] ?? '');
            if ($slotStart !== '') {
                try {
                    return Carbon::createFromFormat(
                        'Y-m-d H:i',
                        $booking->ngay_hen->toDateString() . ' ' . $slotStart,
                        config('app.timezone') ?: 'Asia/Ho_Chi_Minh'
                    );
                } catch (\Throwable) {
                    return $booking->ngay_hen->copy()->startOfDay();
                }
            }

            return $booking->ngay_hen->copy()->startOfDay();
        }

        if ($booking->ngay_hen) {
            return $booking->ngay_hen->copy()->startOfDay();
        }

        return $booking->created_at?->copy();
    }

    private function resolveAdminBookingSlaState(DonDatLich $booking, ?Carbon $scheduleAt, Carbon $now): string
    {
        if (in_array((string) $booking->trang_thai, ['da_huy', 'da_xong', 'hoan_thanh'], true)) {
            return 'closed';
        }

        if ($scheduleAt === null) {
            return 'on_track';
        }

        if ($scheduleAt->lt($now)) {
            return 'overdue';
        }

        if ($scheduleAt->diffInMinutes($now) <= 120) {
            return 'due_soon';
        }

        return 'on_track';
    }

    private function resolveAdminBookingSlaLabel(string $slaState): string
    {
        return match ($slaState) {
            'overdue' => 'Quá hạn',
            'due_soon' => 'Sắp quá hạn',
            'closed' => 'Đã đóng',
            default => 'Đúng hạn',
        };
    }

    private function resolveAdminBookingSlaTone(string $slaState): string
    {
        return match ($slaState) {
            'overdue' => 'danger',
            'due_soon' => 'warning',
            'closed' => 'muted',
            default => 'success',
        };
    }

    private function resolveAdminBookingSlaRank(string $slaState): int
    {
        return match ($slaState) {
            'overdue' => 4,
            'due_soon' => 3,
            'on_track' => 2,
            default => 1,
        };
    }

    private function resolveAdminBookingPriorityKey(
        string $slaState,
        bool $hasComplaint,
        bool $hasWorkerContactIssue,
        bool $paymentIssue,
        bool $isUnassigned,
        bool $isPaid
    ): string {
        if ($hasComplaint || $hasWorkerContactIssue || $slaState === 'overdue') {
            return 'high';
        }

        if ($paymentIssue || $slaState === 'due_soon' || $isUnassigned || !$isPaid) {
            return 'medium';
        }

        return 'low';
    }

    private function resolveAdminBookingPriorityMeta(string $priorityKey): array
    {
        return match ($priorityKey) {
            'high' => ['Cao', 'danger', 3],
            'medium' => ['Trung bình', 'warning', 2],
            default => ['Thấp', 'success', 1],
        };
    }

    private function resolveAdminBookingPaymentIssue(string $latestTransactionStatus, bool $isPaid, string $bookingStatus): bool
    {
        if ($isPaid) {
            return false;
        }

        if (in_array($latestTransactionStatus, ['failed', 'error', 'cancelled', 'canceled', 'that_bai', 'huy'], true)) {
            return true;
        }

        return in_array($bookingStatus, ['cho_thanh_toan'], true);
    }

    private function resolveAdminBookingPaymentStatusTone(string $status): string
    {
        $status = Str::lower(trim($status));

        if (in_array($status, ['success', 'paid', 'thanh_cong'], true)) {
            return 'success';
        }

        if (in_array($status, ['failed', 'error', 'cancelled', 'canceled', 'that_bai', 'huy'], true)) {
            return 'danger';
        }

        return 'info';
    }

    private function normalizeMediaList(mixed $value): array
    {
        $items = [];

        if (is_array($value)) {
            $items = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $items = $decoded;
            } else {
                $items = [$value];
            }
        }

        return collect($items)
            ->map(function ($item) {
                $path = trim((string) $item);
                if ($path === '') {
                    return null;
                }

                if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
                    return $path;
                }

                if (Str::startsWith($path, '/storage/')) {
                    return $path;
                }

                if (Str::startsWith($path, 'storage/')) {
                    return '/' . $path;
                }

                return '/storage/' . ltrim($path, '/');
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveAdminComplaintReasonLabel(?string $reasonCode): string
    {
        return match ((string) $reasonCode) {
            'loi_tai_phat' => 'Lỗi tái phát',
            'linh_kien_kem_chat_luong' => 'Linh kiện thay thế kém chất lượng',
            default => 'Khiếu nại khác',
        };
    }

    private function adminBookingStatusOptions(): array
    {
        $items = [['value' => '', 'label' => 'Tất cả trạng thái']];

        foreach (DonDatLich::statusLabels() as $value => $unusedLabel) {
            $items[] = [
                'value' => (string) $value,
                'label' => $this->formatCustomerBookingStatusLabel((string) $value),
            ];
        }

        return $items;
    }

    private function adminBookingServiceOptions(): array
    {
        return DanhMucDichVu::query()
            ->orderBy('ten_dich_vu')
            ->get(['id', 'ten_dich_vu'])
            ->map(fn (DanhMucDichVu $service) => [
                'id' => (int) $service->id,
                'name' => (string) ($service->ten_dich_vu ?? 'Dịch vụ'),
            ])
            ->values()
            ->all();
    }

    private function adminBookingWorkerOptions(?DonDatLich $booking = null): array
    {
        $requiredServiceIds = $booking ? $booking->resolveServiceIds() : [];

        return User::query()
            ->with(['hoSoTho:user_id,trang_thai_duyet,dang_hoat_dong', 'dichVus:id,ten_dich_vu'])
            ->where('role', 'worker')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(function (User $worker) use ($requiredServiceIds): bool {
                if ($worker->hoSoTho?->trang_thai_duyet !== 'da_duyet' || !(bool) ($worker->hoSoTho?->dang_hoat_dong ?? false)) {
                    return false;
                }

                if ($requiredServiceIds === []) {
                    return true;
                }

                return $worker->supportsServiceIds($requiredServiceIds);
            })
            ->map(fn (User $worker) => [
                'id' => (int) $worker->id,
                'name' => (string) $worker->name,
                'phone' => (string) ($worker->phone ?? ''),
            ])
            ->values()
            ->all();
    }

    private function adminBookingStatusFlow(): array
    {
        return [
            ['value' => 'da_xac_nhan', 'label' => 'Đã xác nhận'],
            ['value' => DonDatLich::STATUS_CUSTOMER_UNREACHABLE, 'label' => 'Không liên lạc được với khách hàng'],
            ['value' => 'dang_lam', 'label' => 'Đang làm'],
            ['value' => 'cho_hoan_thanh', 'label' => 'Chờ hoàn thành COD'],
            ['value' => 'cho_thanh_toan', 'label' => 'Chờ thanh toán'],
            ['value' => 'da_xong', 'label' => 'Đã hoàn tất'],
            ['value' => 'da_huy', 'label' => 'Đã hủy'],
        ];
    }
    private function adminBookingCancelReasonOptions(): array
    {
        return collect(DonDatLich::cancelReasonLabels())
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    private function adminBookingTimeSlots(): array
    {
        return [
            '08:00-10:00',
            '10:00-12:00',
            '12:00-14:00',
            '14:00-17:00',
        ];
    }

    private function serializeAdminBookingContactIssue(DonDatLich $booking): ?array
    {
        if (!$booking->hasWorkerContactIssue()) {
            return null;
        }

        $reporter = $booking->workerContactIssueReporter;
        $isOpen = $booking->hasOpenWorkerContactIssue();

        return [
            'is_reported' => true,
            'is_open' => $isOpen,
            'status_key' => $isOpen ? DonDatLich::STATUS_CUSTOMER_UNREACHABLE : 'resolved',
            'status_label' => $isOpen ? 'Dang cho admin ho tro' : 'Da dong',
            'reported_at' => optional($booking->worker_contact_issue_reported_at)->toIso8601String(),
            'reported_label' => optional($booking->worker_contact_issue_reported_at)->format('d/m/Y H:i'),
            'resolved_at' => optional($booking->worker_contact_issue_resolved_at)->toIso8601String(),
            'resolved_label' => optional($booking->worker_contact_issue_resolved_at)->format('d/m/Y H:i'),
            'reporter_name' => (string) ($booking->worker_contact_issue_reporter_name ?: $reporter?->name ?: $booking->tho?->name ?: ''),
            'called_phone' => (string) ($booking->worker_contact_issue_called_phone ?? ''),
            'note' => (string) ($booking->worker_contact_issue_note ?? ''),
            'reported_by' => [
                'id' => $booking->worker_contact_issue_reported_by
                    ? (int) $booking->worker_contact_issue_reported_by
                    : null,
                'name' => (string) ($reporter?->name ?: $booking->tho?->name ?: ''),
            ],
        ];
    }
    private function serializeAdminBookingListRow(DonDatLich $booking, Carbon $now): array
    {
        $serviceNames = $booking->dichVus->pluck('ten_dich_vu')->filter()->values();
        if ($serviceNames->isEmpty() && !empty($booking->dich_vu_id)) {
            $service = DanhMucDichVu::query()->find((int) $booking->dich_vu_id);
            if ($service?->ten_dich_vu) {
                $serviceNames->push($service->ten_dich_vu);
            }
        }

        $serviceLabel = $serviceNames->implode(', ') ?: 'Chưa xác định dịch vụ';
        $scheduleAt = $this->resolveAdminBookingScheduleAt($booking);
        $scheduleDate = $scheduleAt?->toDateString();
        $scheduleLabel = $this->formatCustomerScheduleLabel($booking);
        $slaState = $this->resolveAdminBookingSlaState($booking, $scheduleAt, $now);
        $slaLabel = $this->resolveAdminBookingSlaLabel($slaState);
        $slaTone = $this->resolveAdminBookingSlaTone($slaState);
        $paymentMethod = (string) ($booking->phuong_thuc_thanh_toan ?: 'cod');
        $paymentMethodLabel = $this->formatCustomerPaymentMethodLabel($paymentMethod);
        $isPaid = (bool) ($booking->trang_thai_thanh_toan ?? false);
        $latestPayment = $booking->thanhToans->sortByDesc(fn ($payment) => optional($payment->created_at)->timestamp ?? 0)->first();
        $latestPaymentStatus = Str::lower((string) ($latestPayment?->trang_thai ?? ''));
        $paymentIssue = $this->resolveAdminBookingPaymentIssue($latestPaymentStatus, $isPaid, (string) $booking->trang_thai);
        $complaintCase = $booking->customerComplaintCase;
        $contactIssue = $this->serializeAdminBookingContactIssue($booking);
        $hasComplaint = $complaintCase !== null;
        $hasWorkerContactIssue = (bool) ($contactIssue['is_open'] ?? false);
        $priorityKey = $this->resolveAdminBookingPriorityKey(
            $slaState,
            $hasComplaint,
            $hasWorkerContactIssue,
            $paymentIssue,
            $booking->tho_id === null,
            $isPaid
        );
        [$priorityLabel, $priorityTone, $priorityRank] = $this->resolveAdminBookingPriorityMeta($priorityKey);
        $statusTone = $this->resolveCustomerBookingTone($booking->trang_thai);
        $statusLabel = $this->formatCustomerBookingStatusLabel($booking->trang_thai);
        $costLabor = (float) ($booking->tien_cong ?? 0);
        $costParts = (float) ($booking->phi_linh_kien ?? 0);
        $costTravel = (float) ($booking->phi_di_lai ?? 0);
        $costTransport = (float) ($booking->tien_thue_xe ?? 0);
        $totalAmount = (float) ($booking->tong_tien ?? 0);
        if ($totalAmount <= 0) {
            $totalAmount = $costLabor + $costParts + $costTravel + $costTransport;
        }

        $beforeImages = $this->normalizeMediaList($booking->hinh_anh_mo_ta);
        $beforeVideos = $this->normalizeMediaList($booking->video_mo_ta);
        $afterImages = $this->normalizeMediaList($booking->hinh_anh_ket_qua);
        $afterVideos = $this->normalizeMediaList($booking->video_ket_qua);
        $mediaCountTotal = count($beforeImages) + count($beforeVideos) + count($afterImages) + count($afterVideos);

        $complaintSnapshot = is_array($complaintCase?->last_snapshot) ? $complaintCase->last_snapshot : [];
        $complaintReasonCode = (string) ($complaintSnapshot['reason_code'] ?? '');
        $complaintReasonLabel = (string) ($complaintSnapshot['reason_label'] ?? $this->resolveAdminComplaintReasonLabel($complaintReasonCode));

        $isOverdue = $slaState === 'overdue';
        $mode = (string) ($booking->loai_dat_lich ?: 'at_home');
        $modeLabel = $mode === 'at_store' ? 'Tại cửa hàng' : 'Tại nhà';
        $address = $mode === 'at_home'
            ? ((string) ($booking->dia_chi ?: ($booking->khachHang?->address ?: 'Chưa cập nhật địa chỉ')))
            : 'Khách mang thiết bị đến cửa hàng';

        return [
            'id' => (int) $booking->id,
            'code' => $this->formatCustomerBookingCode($booking->id),
            'status_key' => (string) ($booking->trang_thai ?: ''),
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'priority_key' => $priorityKey,
            'priority_label' => $priorityLabel,
            'priority_tone' => $priorityTone,
            'priority_rank' => $priorityRank,
            'sla_state' => $slaState,
            'sla_label' => $slaLabel,
            'sla_tone' => $slaTone,
            'sla_rank' => $this->resolveAdminBookingSlaRank($slaState),
            'mode' => $mode,
            'mode_label' => $modeLabel,
            'customer' => [
                'id' => (int) ($booking->khachHang?->id ?? 0),
                'name' => (string) ($booking->khachHang?->name ?: 'Khách hàng'),
                'phone' => (string) ($booking->khachHang?->phone ?: ''),
                'address' => $address,
            ],
            'service_label' => $serviceLabel,
            'service_names' => $serviceNames->values()->all(),
            'service_count' => $serviceNames->count(),
            'problem_excerpt' => $this->truncateDashboardText((string) ($booking->mo_ta_van_de ?: 'Khách chưa để mô tả vấn đề.'), 108),
            'worker' => [
                'id' => $booking->tho?->id ? (int) $booking->tho->id : null,
                'name' => $booking->tho?->name ? (string) $booking->tho->name : 'Chưa phân công',
                'phone' => (string) ($booking->tho?->phone ?: ''),
            ],
            'schedule' => [
                'date' => $scheduleDate,
                'time_slot' => (string) ($booking->khung_gio_hen ?: ''),
                'scheduled_at' => $scheduleAt?->toIso8601String(),
                'label' => $scheduleLabel,
            ],
            'costs' => [
                'labor' => $costLabor,
                'parts' => $costParts,
                'travel' => $costTravel,
                'transport' => $costTransport,
                'total' => $totalAmount,
            ],
            'payment' => [
                'is_paid' => $isPaid,
                'status_label' => $isPaid ? 'Đã thanh toán' : 'Chưa thanh toán',
                'status_tone' => $isPaid ? 'success' : 'warning',
                'method' => $paymentMethod,
                'method_label' => $paymentMethodLabel,
                'latest_transaction_status' => $latestPaymentStatus,
                'latest_transaction_label' => $latestPayment?->created_at
                    ? optional($latestPayment->created_at)->format('d/m/Y H:i')
                    : null,
            ],
            'media' => [
                'before_images' => $beforeImages,
                'before_videos' => $beforeVideos,
                'after_images' => $afterImages,
                'after_videos' => $afterVideos,
                'total' => $mediaCountTotal,
            ],
            'flags' => [
                'is_overdue' => $isOverdue,
                'payment_issue' => $paymentIssue,
                'has_complaint' => $hasComplaint,
                'has_worker_contact_issue' => $hasWorkerContactIssue,
                'is_unassigned' => $booking->tho_id === null,
            ],
            'contact_issue' => $contactIssue,
            'complaint' => [
                'has_complaint' => $hasComplaint,
                'status' => (string) ($complaintCase?->status ?: ''),
                'priority' => (string) ($complaintCase?->priority ?: ''),
                'reason_code' => $complaintReasonCode,
                'reason_label' => $complaintReasonLabel,
                'created_at' => optional($complaintCase?->created_at)->toIso8601String(),
                'created_label' => optional($complaintCase?->created_at)->format('d/m/Y H:i'),
            ],
            'milestones' => [
                'created_at' => optional($booking->created_at)->toIso8601String(),
                'created_label' => optional($booking->created_at)->format('d/m/Y H:i'),
                'updated_at' => optional($booking->updated_at)->toIso8601String(),
                'updated_label' => optional($booking->updated_at)->format('d/m/Y H:i'),
                'completed_at' => optional($booking->thoi_gian_hoan_thanh)->toIso8601String(),
                'completed_label' => optional($booking->thoi_gian_hoan_thanh)->format('d/m/Y H:i'),
                'cancelled_at' => $booking->trang_thai === 'da_huy'
                    ? optional($booking->updated_at)->toIso8601String()
                    : null,
                'cancelled_label' => $booking->trang_thai === 'da_huy'
                    ? optional($booking->updated_at)->format('d/m/Y H:i')
                    : null,
            ],
        ];
    }

    private function serializeAdminBookingDetail(DonDatLich $booking): array
    {
        $base = $this->serializeAdminBookingListRow($booking, Carbon::now());
        $complaintCase = $booking->customerComplaintCase;
        $complaintSnapshot = is_array($complaintCase?->last_snapshot) ? $complaintCase->last_snapshot : [];
        $paymentHistory = $booking->thanhToans
            ->sortByDesc(fn (ThanhToan $payment) => optional($payment->created_at)->timestamp ?? 0)
            ->map(function (ThanhToan $payment): array {
                return [
                    'id' => (int) $payment->id,
                    'amount' => (float) ($payment->so_tien ?? 0),
                    'method' => (string) ($payment->phuong_thuc ?? ''),
                    'method_label' => $this->formatCustomerPaymentMethodLabel((string) ($payment->phuong_thuc ?? '')),
                    'status' => (string) ($payment->trang_thai ?? ''),
                    'transaction_code' => (string) ($payment->ma_giao_dich ?? ''),
                    'created_at' => optional($payment->created_at)->toIso8601String(),
                    'created_label' => optional($payment->created_at)->format('d/m/Y H:i'),
                ];
            })
            ->values();
        $reviewHistory = $booking->danhGias
            ->sortByDesc(fn ($review) => optional($review->created_at)->timestamp ?? 0)
            ->map(function ($review): array {
                return [
                    'id' => (int) $review->id,
                    'rating' => (float) ($review->so_sao ?? 0),
                    'comment' => (string) ($review->nhan_xet ?? ''),
                    'author' => (string) ($review->nguoiDanhGia?->name ?? 'Khách hàng'),
                    'created_at' => optional($review->created_at)->toIso8601String(),
                    'created_label' => optional($review->created_at)->format('d/m/Y H:i'),
                ];
            })
            ->values();
        $timeline = $this->buildAdminBookingTimeline($booking, $base);
        $actionHistory = $this->buildAdminBookingActionHistory($booking, $base, $paymentHistory, $complaintCase, $reviewHistory);

        return array_merge($base, [
            'problem_description' => (string) ($booking->mo_ta_van_de ?? ''),
            'technical_note' => (string) ($booking->giai_phap ?? ''),
            'cancel_reason_code' => (string) ($booking->ma_ly_do_huy ?? ''),
            'cancel_reason_label' => DonDatLich::cancelReasonLabel($booking->ma_ly_do_huy),
            'cancel_note' => (string) ($booking->ly_do_huy ?? ''),
            'cost_details' => [
                'labor_items' => is_array($booking->chi_tiet_tien_cong) ? $booking->chi_tiet_tien_cong : [],
                'part_items' => is_array($booking->chi_tiet_linh_kien) ? $booking->chi_tiet_linh_kien : [],
                'part_note' => (string) ($booking->ghi_chu_linh_kien ?? ''),
            ],
            'media_gallery' => [
                'before_images' => $this->normalizeMediaList($booking->hinh_anh_mo_ta),
                'before_videos' => $this->normalizeMediaList($booking->video_mo_ta),
                'after_images' => $this->normalizeMediaList($booking->hinh_anh_ket_qua),
                'after_videos' => $this->normalizeMediaList($booking->video_ket_qua),
            ],
            'contact_issue' => $base['contact_issue'] ?? null,
            'timeline' => $timeline,
            'action_history' => $actionHistory,
            'complaint_detail' => $complaintCase ? [
                'id' => (int) $complaintCase->id,
                'status' => (string) ($complaintCase->status ?? 'new'),
                'priority' => (string) ($complaintCase->priority ?? 'medium'),
                'reason_code' => (string) ($complaintSnapshot['reason_code'] ?? ''),
                'reason_label' => (string) ($complaintSnapshot['reason_label'] ?? $this->resolveAdminComplaintReasonLabel((string) ($complaintSnapshot['reason_code'] ?? ''))),
                'note' => (string) ($complaintSnapshot['note'] ?? ''),
                'images' => array_values(array_filter((array) ($complaintSnapshot['images'] ?? []))),
                'video' => (string) ($complaintSnapshot['video'] ?? ''),
                'assigned_admin' => (string) ($complaintCase->assignedAdmin?->name ?? ''),
                'assigned_at' => optional($complaintCase->assigned_at)->toIso8601String(),
                'assigned_label' => optional($complaintCase->assigned_at)->format('d/m/Y H:i'),
                'resolved_at' => optional($complaintCase->resolved_at)->toIso8601String(),
                'resolved_label' => optional($complaintCase->resolved_at)->format('d/m/Y H:i'),
                'resolution_note' => (string) ($complaintCase->resolution_note ?? ''),
                'created_at' => optional($complaintCase->created_at)->toIso8601String(),
                'created_label' => optional($complaintCase->created_at)->format('d/m/Y H:i'),
            ] : null,
            'payment_history' => $paymentHistory,
            'review_history' => $reviewHistory,
            'action_options' => [
                'status_flow' => $this->adminBookingStatusFlow(),
                'cancel_reason_options' => $this->adminBookingCancelReasonOptions(),
                'time_slots' => $this->adminBookingTimeSlots(),
                'worker_options' => $this->adminBookingWorkerOptions($booking),
                'complaint_url' => '/admin/customer-feedback?search=' . urlencode($this->formatCustomerBookingCode($booking->id)),
            ],
        ]);
    }

    private function buildAdminBookingTimeline(DonDatLich $booking, array $base): array
    {
        $status = (string) ($booking->trang_thai ?? '');
        $createdAt = $base['milestones']['created_at'] ?? null;
        $updatedAt = $base['milestones']['updated_at'] ?? null;
        $completedAt = $base['milestones']['completed_at'] ?? null;
        $cancelledAt = $base['milestones']['cancelled_at'] ?? null;
        $isAccepted = in_array($status, ['da_xac_nhan', 'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan', 'da_xong', 'hoan_thanh'], true);
        $isStarted = in_array($status, ['dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan', 'da_xong', 'hoan_thanh'], true);
        $contactIssue = $base['contact_issue'] ?? null;

        $timeline = [
            [
                'key' => 'created',
                'title' => 'Tạo đơn',
                'time' => $createdAt,
                'time_label' => $base['milestones']['created_label'] ?? '',
                'state' => 'done',
                'note' => 'Khách hàng đã tạo đơn trên hệ thống',
            ],
            [
                'key' => 'accepted',
                'title' => 'Xác nhận đơn',
                'time' => $isAccepted ? $updatedAt : null,
                'time_label' => $isAccepted ? ($base['milestones']['updated_label'] ?? '') : '',
                'state' => $isAccepted ? 'done' : 'pending',
                'note' => $isAccepted
                    ? (($base['worker']['name'] ?? 'Thợ') . ' đã được gán cho đơn này')
                    : 'Chưa có thợ nhận hoặc chưa xác nhận',
                'estimated' => $isAccepted && !$booking->thoi_gian_hoan_thanh && $status !== 'da_huy',
            ],
            [
                'key' => 'in_progress',
                'title' => 'Đang xử lý',
                'time' => $isStarted ? $updatedAt : null,
                'time_label' => $isStarted ? ($base['milestones']['updated_label'] ?? '') : '',
                'state' => $isStarted ? 'done' : 'pending',
                'note' => $isStarted ? 'Đơn đã vào luồng xử lý kỹ thuật' : 'Chưa bắt đầu xử lý',
                'estimated' => $isStarted && !$booking->thoi_gian_hoan_thanh && $status !== 'da_huy',
            ],
            [
                'key' => 'completed',
                'title' => 'Hoàn tất',
                'time' => $completedAt,
                'time_label' => $base['milestones']['completed_label'] ?? '',
                'state' => $completedAt ? 'done' : 'pending',
                'note' => $completedAt ? 'Đơn đã hoàn tất' : 'Chưa hoàn tất',
            ],
            [
                'key' => 'cancelled',
                'title' => 'Hủy đơn',
                'time' => $cancelledAt,
                'time_label' => $base['milestones']['cancelled_label'] ?? '',
                'state' => $cancelledAt ? 'done' : 'hidden',
                'note' => $cancelledAt ? ((string) ($booking->ly_do_huy ?: 'Đơn đã bị hủy')) : '',
            ],
        ];

        if (is_array($contactIssue) && ($contactIssue['is_reported'] ?? false)) {
            array_splice($timeline, 2, 0, [[
                'key' => 'worker_contact_issue',
                'title' => 'Không liên lạc được',
                'time' => $contactIssue['reported_at'] ?? null,
                'time_label' => $contactIssue['reported_label'] ?? '',
                'state' => 'done',
                'note' => ($contactIssue['is_open'] ?? false)
                    ? 'admin sẽ liên hệ hỗ trợ sau.'
                    : 'Báo cáo liên hệ đã được đóng.',
            ]]);
        }

        return $timeline;
    }

    private function buildAdminBookingActionHistory(
        DonDatLich $booking,
        array $base,
        Collection $paymentHistory,
        ?CustomerFeedbackCase $complaintCase,
        Collection $reviewHistory
    ): array {
        $events = collect();

        if (!empty($base['milestones']['created_at'])) {
            $events->push([
                'time' => $base['milestones']['created_at'],
                'time_label' => $base['milestones']['created_label'] ?? '',
                'title' => 'Tạo đơn',
                'detail' => 'Hệ thống ghi nhận đơn ' . ($base['code'] ?? ''),
                'tone' => 'info',
                'actor' => 'Khách hàng',
            ]);
        }

        $contactIssue = $base['contact_issue'] ?? null;
        if (is_array($contactIssue) && ($contactIssue['is_reported'] ?? false)) {
            $events->push([
                'time' => $contactIssue['reported_at'] ?? null,
                'time_label' => $contactIssue['reported_label'] ?? '',
                'title' => 'Thợ báo không liên lạc được',
                'detail' => (string) ($contactIssue['note'] ?: 'Thợ báo không liên lạc được với khách hàng.'),
                'tone' => ($contactIssue['is_open'] ?? false) ? 'danger' : 'info',
                'actor' => (string) ($contactIssue['reported_by']['name'] ?? 'Thợ'),
            ]);

            if (!empty($contactIssue['resolved_at'])) {
                $events->push([
                    'time' => $contactIssue['resolved_at'] ?? null,
                    'time_label' => $contactIssue['resolved_label'] ?? '',
                    'title' => 'Dong bao cao lien he',
                    'detail' => 'Booking da roi khoi trang thai cho lien he.',
                    'tone' => 'success',
                    'actor' => 'He thong',
                ]);
            }
        }

        if ($booking->trang_thai === 'da_huy' && !empty($base['milestones']['cancelled_at'])) {
            $events->push([
                'time' => $base['milestones']['cancelled_at'],
                'time_label' => $base['milestones']['cancelled_label'] ?? '',
                'title' => 'Đơn bị hủy',
                'detail' => (string) ($booking->ly_do_huy ?: 'Đơn đã bị hủy trên hệ thống'),
                'tone' => 'danger',
                'actor' => 'Hệ thống',
            ]);
        }

        if (!empty($base['milestones']['completed_at'])) {
            $events->push([
                'time' => $base['milestones']['completed_at'],
                'time_label' => $base['milestones']['completed_label'] ?? '',
                'title' => 'Hoàn tất đơn',
                'detail' => 'Đơn đã được cập nhật trạng thái hoàn tất',
                'tone' => 'success',
                'actor' => 'Hệ thống',
            ]);
        }

        foreach ($paymentHistory as $payment) {
            $events->push([
                'time' => $payment['created_at'] ?? null,
                'time_label' => $payment['created_label'] ?? '',
                'title' => 'Thanh toán',
                'detail' => ($payment['method_label'] ?? 'Khác')
                    . ' - '
                    . ((string) ($payment['status'] ?? ''))
                    . ' - '
                    . number_format((float) ($payment['amount'] ?? 0), 0, ',', '.')
                    . ' đ',
                'tone' => $this->resolveAdminBookingPaymentStatusTone((string) ($payment['status'] ?? '')),
                'actor' => 'Giao dịch',
            ]);
        }

        if ($complaintCase) {
            $snapshot = is_array($complaintCase->last_snapshot) ? $complaintCase->last_snapshot : [];
            $reasonLabel = (string) ($snapshot['reason_label'] ?? $this->resolveAdminComplaintReasonLabel((string) ($snapshot['reason_code'] ?? '')));

            $events->push([
                'time' => optional($complaintCase->created_at)->toIso8601String(),
                'time_label' => optional($complaintCase->created_at)->format('d/m/Y H:i'),
                'title' => 'Khiếu nại',
                'detail' => 'Khách tạo khiếu nại: ' . $reasonLabel,
                'tone' => 'warning',
                'actor' => 'Khách hàng',
            ]);

                if ($complaintCase->assigned_at) {
                $events->push([
                    'time' => optional($complaintCase->assigned_at)->toIso8601String(),
                    'time_label' => optional($complaintCase->assigned_at)->format('d/m/Y H:i'),
                    'title' => 'Nhận xử lý khiếu nại',
                    'detail' => 'Case được tiếp nhận bởi ' . ($complaintCase->assignedAdmin?->name ?: 'Admin'),
                    'tone' => 'info',
                    'actor' => $complaintCase->assignedAdmin?->name ?: 'Admin',
                ]);
            }

            if ($complaintCase->resolved_at) {
                $events->push([
                    'time' => optional($complaintCase->resolved_at)->toIso8601String(),
                    'time_label' => optional($complaintCase->resolved_at)->format('d/m/Y H:i'),
                    'title' => 'Đóng khiếu nại',
                    'detail' => (string) ($complaintCase->resolution_note ?: 'Đã đánh dấu xử lý'),
                    'tone' => 'success',
                    'actor' => $complaintCase->assignedAdmin?->name ?: 'Admin',
                ]);
            }
        }

        foreach ($reviewHistory as $review) {
            $events->push([
                'time' => $review['created_at'] ?? null,
                'time_label' => $review['created_label'] ?? '',
                'title' => 'Đánh giá đơn',
                'detail' => 'Số sao: ' . ((float) ($review['rating'] ?? 0)) . '/5'
                    . ((string) ($review['comment'] ?? '') !== '' ? (' - ' . (string) ($review['comment'] ?? '')) : ''),
                'tone' => ((float) ($review['rating'] ?? 0)) <= 2 ? 'warning' : 'success',
                'actor' => (string) ($review['author'] ?? 'Khách hàng'),
            ]);
        }

        return $events
            ->filter(fn (array $event): bool => !empty($event['time']))
            ->sortByDesc(fn (array $event): int => strtotime((string) ($event['time'] ?? '')) ?: 0)
            ->values()
            ->all();
    }

    public function getServices(Request $request)
    {
        $status = $request->query('status');

        $query = DanhMucDichVu::query()->orderByDesc('id');

        if ($status !== null && $status !== '') {
            $query->where('trang_thai', (int) $status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    public function storeService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ten_dich_vu' => 'required|string|max:255|unique:danh_muc_dich_vu,ten_dich_vu',
            'mo_ta' => 'nullable|string',
            'hinh_anh' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'trang_thai' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $service = DanhMucDichVu::query()->create([
            'ten_dich_vu' => trim((string) $request->input('ten_dich_vu')),
            'mo_ta' => trim((string) $request->input('mo_ta', '')) ?: null,
            'hinh_anh' => $request->hasFile('hinh_anh')
                ? $this->storeServiceImage($request->file('hinh_anh'))
                : null,
            'trang_thai' => $request->has('trang_thai') ? (int) $request->boolean('trang_thai') : 1,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Da them dich vu',
            'data' => $service,
        ], 201);
    }

    public function updateService(Request $request, string $id)
    {
        $service = DanhMucDichVu::query()->find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay dich vu',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'ten_dich_vu' => 'required|string|max:255|unique:danh_muc_dich_vu,ten_dich_vu,' . $service->id,
            'mo_ta' => 'nullable|string',
            'hinh_anh' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'remove_image' => 'nullable|boolean',
            'trang_thai' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $imagePath = $service->getRawOriginal('hinh_anh');

        if ($request->boolean('remove_image')) {
            $this->deleteStoredServiceImage($imagePath);
            $imagePath = null;
        }

        if ($request->hasFile('hinh_anh')) {
            $this->deleteStoredServiceImage($imagePath);
            $imagePath = $this->storeServiceImage($request->file('hinh_anh'));
        }

        $service->update([
            'ten_dich_vu' => trim((string) $request->input('ten_dich_vu')),
            'mo_ta' => trim((string) $request->input('mo_ta', '')) ?: null,
            'hinh_anh' => $imagePath,
            'trang_thai' => $request->has('trang_thai') ? (int) $request->boolean('trang_thai') : (int) $service->trang_thai,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat dich vu',
            'data' => $service->fresh(),
        ]);
    }

    public function destroyService(string $id)
    {
        $service = DanhMucDichVu::query()->find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay dich vu',
            ], 404);
        }

        $hasBookings = Schema::hasTable('don_dat_lich_dich_vu')
            ? DB::table('don_dat_lich_dich_vu')
                ->where('dich_vu_id', $service->id)
                ->exists()
            : false;
        $hasPosts = Schema::hasTable('bai_dang')
            ? DB::table('bai_dang')
                ->where('dich_vu_id', $service->id)
                ->exists()
            : false;

        if ($hasBookings || $hasPosts) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dich vu dang duoc su dung trong he thong. Hay an dich vu thay vi xoa.',
            ], 409);
        }

        $serviceImagePath = $service->getRawOriginal('hinh_anh');
        $partImagePaths = LinhKien::query()
            ->where('dich_vu_id', $service->id)
            ->pluck('hinh_anh')
            ->filter(static fn ($path) => trim((string) $path) !== '')
            ->unique()
            ->values();

        try {
            $service->delete();
        } catch (QueryException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong the xoa dich vu vi du lieu nay dang duoc lien ket.',
            ], 409);
        }

        $this->deleteStoredServiceImage($serviceImagePath);
        $partImagePaths->each(fn ($path) => $this->deleteStoredPartImage($path));

        return response()->json([
            'status' => 'success',
            'message' => 'Da xoa dich vu',
        ]);
    }

    public function getParts(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $serviceId = trim((string) $request->query('service_id', ''));

        $query = LinhKien::query()
            ->with('dichVu:id,ten_dich_vu,trang_thai')
            ->when($serviceId !== '' && ctype_digit($serviceId), function (Builder $builder) use ($serviceId) {
                $builder->where('dich_vu_id', (int) $serviceId);
            })
            ->when($search !== '', function (Builder $builder) use ($search) {
                $builder->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('ten_linh_kien', 'like', '%' . $search . '%')
                        ->orWhereHas('dichVu', function (Builder $serviceQuery) use ($search) {
                            $serviceQuery->where('ten_dich_vu', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('id');

        $parts = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $parts
                    ->map(fn (LinhKien $part) => $this->serializeAdminPart($part))
                    ->values(),
                'summary' => [
                    'total' => $parts->count(),
                    'priced' => $parts->filter(fn (LinhKien $part) => (float) ($part->gia ?? 0) > 0)->count(),
                ],
                'service_options' => $this->getAdminCatalogServiceOptions(),
            ],
        ]);
    }

    public function storePart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dich_vu_id' => 'required|integer|exists:danh_muc_dich_vu,id',
            'ten_linh_kien' => [
                'required',
                'string',
                'max:255',
                Rule::unique('linh_kien', 'ten_linh_kien')->where(function ($query) use ($request) {
                    return $query->where('dich_vu_id', $request->integer('dich_vu_id'));
                }),
            ],
            'gia' => 'nullable|numeric|min:0',
            'so_luong_ton_kho' => 'nullable|integer|min:0|max:1000000',
            'han_su_dung' => 'nullable|date',
            'hinh_anh' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu linh kien khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $part = LinhKien::query()->create([
            'dich_vu_id' => $request->integer('dich_vu_id'),
            'ten_linh_kien' => trim((string) $request->input('ten_linh_kien')),
            'gia' => $request->filled('gia') ? (float) $request->input('gia') : null,
            'so_luong_ton_kho' => max(0, (int) $request->input('so_luong_ton_kho', 0)),
            'han_su_dung' => $request->filled('han_su_dung')
                ? $request->date('han_su_dung')?->toDateString()
                : null,
            'hinh_anh' => $request->hasFile('hinh_anh')
                ? $this->storePartImage($request->file('hinh_anh'))
                : null,
        ]);

        $part->load('dichVu:id,ten_dich_vu,trang_thai');

        return response()->json([
            'status' => 'success',
            'message' => 'Da them linh kien',
            'data' => $this->serializeAdminPart($part),
        ], 201);
    }

    public function updatePart(Request $request, string $id)
    {
        $part = LinhKien::query()->with('dichVu:id,ten_dich_vu,trang_thai')->find($id);

        if (!$part) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay linh kien',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'dich_vu_id' => 'required|integer|exists:danh_muc_dich_vu,id',
            'ten_linh_kien' => [
                'required',
                'string',
                'max:255',
                Rule::unique('linh_kien', 'ten_linh_kien')
                    ->ignore($part->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('dich_vu_id', $request->integer('dich_vu_id'));
                    }),
            ],
            'gia' => 'nullable|numeric|min:0',
            'so_luong_ton_kho' => 'nullable|integer|min:0|max:1000000',
            'han_su_dung' => 'nullable|date',
            'hinh_anh' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'remove_image' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu linh kien khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $imagePath = $part->getRawOriginal('hinh_anh');

        if ($request->boolean('remove_image')) {
            $this->deleteStoredPartImage($imagePath);
            $imagePath = null;
        }

        if ($request->hasFile('hinh_anh')) {
            $this->deleteStoredPartImage($imagePath);
            $imagePath = $this->storePartImage($request->file('hinh_anh'));
        }

        $part->update([
            'dich_vu_id' => $request->integer('dich_vu_id'),
            'ten_linh_kien' => trim((string) $request->input('ten_linh_kien')),
            'gia' => $request->filled('gia') ? (float) $request->input('gia') : null,
            'so_luong_ton_kho' => max(0, (int) $request->input('so_luong_ton_kho', 0)),
            'han_su_dung' => $request->filled('han_su_dung')
                ? $request->date('han_su_dung')?->toDateString()
                : null,
            'hinh_anh' => $imagePath,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat linh kien',
            'data' => $this->serializeAdminPart($part->fresh(['dichVu:id,ten_dich_vu,trang_thai'])),
        ]);
    }

    public function destroyPart(string $id)
    {
        $part = LinhKien::query()->find($id);

        if (!$part) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay linh kien',
            ], 404);
        }

        $this->deleteStoredPartImage($part->getRawOriginal('hinh_anh'));
        $part->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Da xoa linh kien',
        ]);
    }

    public function getSymptoms(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $serviceId = trim((string) $request->query('service_id', ''));

        $query = TrieuChung::query()
            ->with([
                'dichVu:id,ten_dich_vu,trang_thai',
                'nguyenNhans:id,ten_nguyen_nhan',
            ])
            ->when($serviceId !== '' && ctype_digit($serviceId), function (Builder $builder) use ($serviceId) {
                $builder->where('dich_vu_id', (int) $serviceId);
            })
            ->when($search !== '', function (Builder $builder) use ($search) {
                $builder->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('ten_trieu_chung', 'like', '%' . $search . '%')
                        ->orWhereHas('nguyenNhans', function (Builder $causeQuery) use ($search) {
                            $causeQuery->where('ten_nguyen_nhan', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('dichVu', function (Builder $serviceQuery) use ($search) {
                            $serviceQuery->where('ten_dich_vu', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('id');

        $symptoms = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $symptoms
                    ->map(fn (TrieuChung $symptom) => $this->serializeAdminSymptom($symptom))
                    ->values(),
                'summary' => [
                    'total' => $symptoms->count(),
                    'linked_causes' => $symptoms->sum(fn (TrieuChung $symptom) => $symptom->nguyenNhans->count()),
                ],
                'service_options' => $this->getAdminCatalogServiceOptions(),
            ],
        ]);
    }

    public function storeSymptom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dich_vu_id' => 'required|integer|exists:danh_muc_dich_vu,id',
            'ten_trieu_chung' => [
                'required',
                'string',
                'max:255',
                Rule::unique('trieu_chung', 'ten_trieu_chung')->where(function ($query) use ($request) {
                    return $query->where('dich_vu_id', $request->integer('dich_vu_id'));
                }),
            ],
            'nguyen_nhans_text' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu trieu chung khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $symptom = TrieuChung::query()->create([
            'dich_vu_id' => $request->integer('dich_vu_id'),
            'ten_trieu_chung' => trim((string) $request->input('ten_trieu_chung')),
        ]);

        if ($request->exists('nguyen_nhans_text')) {
            $this->syncSymptomCausesFromText($symptom, (string) $request->input('nguyen_nhans_text', ''));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Da them trieu chung',
            'data' => $this->serializeAdminSymptom($symptom->fresh([
                'dichVu:id,ten_dich_vu,trang_thai',
                'nguyenNhans:id,ten_nguyen_nhan',
            ])),
        ], 201);
    }

    public function updateSymptom(Request $request, string $id)
    {
        $symptom = TrieuChung::query()->with([
            'dichVu:id,ten_dich_vu,trang_thai',
            'nguyenNhans:id,ten_nguyen_nhan',
        ])->find($id);

        if (!$symptom) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay trieu chung',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'dich_vu_id' => 'required|integer|exists:danh_muc_dich_vu,id',
            'ten_trieu_chung' => [
                'required',
                'string',
                'max:255',
                Rule::unique('trieu_chung', 'ten_trieu_chung')
                    ->ignore($symptom->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('dich_vu_id', $request->integer('dich_vu_id'));
                    }),
            ],
            'nguyen_nhans_text' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu trieu chung khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $symptom->update([
            'dich_vu_id' => $request->integer('dich_vu_id'),
            'ten_trieu_chung' => trim((string) $request->input('ten_trieu_chung')),
        ]);

        if ($request->exists('nguyen_nhans_text')) {
            $this->syncSymptomCausesFromText($symptom, (string) $request->input('nguyen_nhans_text', ''));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat trieu chung',
            'data' => $this->serializeAdminSymptom($symptom->fresh([
                'dichVu:id,ten_dich_vu,trang_thai',
                'nguyenNhans:id,ten_nguyen_nhan',
            ])),
        ]);
    }

    public function destroySymptom(string $id)
    {
        $symptom = TrieuChung::query()->find($id);

        if (!$symptom) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay trieu chung',
            ], 404);
        }

        $symptom->nguyenNhans()->detach();
        $symptom->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Da xoa trieu chung',
        ]);
    }

    public function getRepairKnowledgeTree(Request $request)
    {
        $services = DanhMucDichVu::query()
            ->select(['id', 'ten_dich_vu', 'trang_thai'])
            ->with([
                'trieuChungs' => function ($symptomQuery) {
                    $symptomQuery
                        ->select(['trieu_chung.id', 'trieu_chung.dich_vu_id', 'trieu_chung.ten_trieu_chung', 'trieu_chung.updated_at'])
                        ->with([
                            'nguyenNhans' => function ($causeQuery) {
                                $causeQuery
                                    ->select(['nguyen_nhan.id', 'nguyen_nhan.ten_nguyen_nhan', 'nguyen_nhan.updated_at'])
                                    ->with([
                                        'trieuChungs:id,dich_vu_id,ten_trieu_chung',
                                        'trieuChungs.dichVu:id,ten_dich_vu,trang_thai',
                                        'huongXuLys:id,nguyen_nhan_id,ten_huong_xu_ly,gia_tham_khao,mo_ta_cong_viec,updated_at',
                                    ])
                                    ->orderBy('ten_nguyen_nhan');
                            },
                        ])
                        ->orderBy('ten_trieu_chung');
                },
            ])
            ->orderBy('ten_dich_vu')
            ->get();

        $symptoms = $services
            ->flatMap(fn (DanhMucDichVu $service) => $service->trieuChungs ?? collect())
            ->values();
        $causes = $symptoms
            ->flatMap(fn (TrieuChung $symptom) => $symptom->nguyenNhans ?? collect())
            ->unique('id')
            ->values();
        $resolutions = $causes
            ->flatMap(fn (NguyenNhan $cause) => $cause->huongXuLys ?? collect())
            ->unique('id')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $services
                    ->map(fn (DanhMucDichVu $service) => $this->serializeRepairKnowledgeService($service))
                    ->values(),
                'summary' => [
                    'services' => $services->count(),
                    'symptoms' => $symptoms->count(),
                    'causes' => $causes->count(),
                    'resolutions' => $resolutions->count(),
                ],
                'service_options' => $this->getAdminCatalogServiceOptions(),
                'symptom_options' => $this->getAdminSymptomOptions(),
                'cause_options' => $this->getAdminCauseOptions(),
            ],
        ]);
    }


    public function storeWorker(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15|unique:users,phone',
            'password' => 'required|string|min:6',
            'cccd' => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
            'kinh_nghiem' => 'nullable|string',
            'dich_vu_ids' => 'nullable|array',
            'dich_vu_ids.*' => 'integer|exists:danh_muc_dich_vu,id',
            'avatar' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors(),
            ], 422);
        }

        return DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt($request->password),
                'address' => $request->address,
                'role' => 'worker',
                'is_active' => true,
                'phone_verified_at' => now(),
            ]);

            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                if ($file instanceof UploadedFile) {
                    $this->storeWorkerAvatar($user, $file);
                }
            }

            $this->upsertWorkerProfile($user, [
                'cccd' => $request->cccd,
                'kinh_nghiem' => $request->kinh_nghiem,
            ], true);

            $user->dichVus()->sync($this->normalizeWorkerServiceIds($request->input('dich_vu_ids', [])));
            $this->syncWorkerKnowledge($user->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Đã tạo tài khoản thợ thành công',
                'data' => $user->load('hoSoTho', 'dichVus'),
            ], 201);
        });
    }

    public function getWorkerDetail(string $userId)
    {
        $worker = User::query()
            ->with(['hoSoTho', 'dichVus:id,ten_dich_vu'])
            ->where('role', 'worker')
            ->findOrFail($userId);

        return response()->json([
            'status' => 'success',
            'data' => $worker,
        ]);
    }

    public function updateWorker(Request $request, string $userId)
    {
        $worker = User::query()
            ->where('role', 'worker')
            ->findOrFail($userId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $worker->id,
            'phone' => 'required|string|max:15|unique:users,phone,' . $worker->id,
            'password' => 'nullable|string|min:6',
            'cccd' => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
            'kinh_nghiem' => 'nullable|string',
            'dich_vu_ids' => 'nullable|array',
            'dich_vu_ids.*' => 'integer|exists:danh_muc_dich_vu,id',
            'is_active' => 'boolean',
            'avatar' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors(),
            ], 422);
        }

        return DB::transaction(function () use ($request, $worker) {
            $isActive = $request->boolean('is_active', true);
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => $isActive,
            ];

            if ($request->filled('password')) {
                $userData['password'] = bcrypt($request->password);
            }

            $worker->update($userData);

            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                if ($file instanceof UploadedFile) {
                    $this->storeWorkerAvatar($worker, $file);
                }
            }

            $this->upsertWorkerProfile($worker, [
                'cccd' => $request->cccd,
                'kinh_nghiem' => $request->kinh_nghiem,
            ], $isActive);

            $worker->dichVus()->sync($this->normalizeWorkerServiceIds($request->input('dich_vu_ids', [])));
            $this->syncWorkerKnowledge($worker->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Đã cập nhật thông tin thợ thành công',
                'data' => $worker->load('hoSoTho', 'dichVus'),
            ]);
        });
    }

    public function destroyWorker(string $userId)
    {
        $worker = User::query()
            ->where('role', 'worker')
            ->find($userId);

        if (!$worker) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không tìm thấy tài khoản thợ',
            ], 404);
        }

        if ($worker->donDatLichAsTho()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể xóa thợ này vì đã có đơn đặt lịch.',
            ], 400);
        }

        return DB::transaction(function () use ($worker) {
            if ($worker->hoSoTho) {
                $worker->hoSoTho()->delete();
            }
            $worker->dichVus()->detach();
            $worker->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Đã xóa tài khoản thợ thành công',
            ]);
        });
    }

    public function storeCause(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ten_nguyen_nhan' => 'required|string|max:255',
            'symptom_ids' => 'required|array|min:1',
            'symptom_ids.*' => 'integer|exists:trieu_chung,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu nguyen nhan khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $symptomIds = collect($request->input('symptom_ids', []))
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $causeName = trim((string) $request->input('ten_nguyen_nhan'));
        $lookup = TextNormalizer::normalize($causeName);

        $cause = DB::transaction(function () use ($causeName, $lookup, $symptomIds) {
            $existingCause = NguyenNhan::query()
                ->get()
                ->first(fn (NguyenNhan $cause) => TextNormalizer::normalize((string) $cause->ten_nguyen_nhan) === $lookup);

            $cause = $existingCause ?: NguyenNhan::query()->create([
                'ten_nguyen_nhan' => $causeName,
            ]);

            $cause->trieuChungs()->syncWithoutDetaching($symptomIds);

            return $cause;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Da luu nguyen nhan',
            'data' => $this->serializeAdminCause($cause->fresh([
                'trieuChungs:id,dich_vu_id,ten_trieu_chung',
                'trieuChungs.dichVu:id,ten_dich_vu,trang_thai',
                'huongXuLys:id,nguyen_nhan_id,ten_huong_xu_ly,gia_tham_khao,mo_ta_cong_viec,updated_at',
            ])),
        ], 201);
    }

    public function updateCause(Request $request, string $id)
    {
        $cause = NguyenNhan::query()->with([
            'trieuChungs:id,dich_vu_id,ten_trieu_chung',
            'trieuChungs.dichVu:id,ten_dich_vu,trang_thai',
            'huongXuLys:id,nguyen_nhan_id,ten_huong_xu_ly,gia_tham_khao,mo_ta_cong_viec,updated_at',
        ])->find($id);

        if (!$cause) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay nguyen nhan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'ten_nguyen_nhan' => [
                'required',
                'string',
                'max:255',
                Rule::unique('nguyen_nhan', 'ten_nguyen_nhan')->ignore($cause->id),
            ],
            'symptom_ids' => 'required|array|min:1',
            'symptom_ids.*' => 'integer|exists:trieu_chung,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu nguyen nhan khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $symptomIds = collect($request->input('symptom_ids', []))
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existingSymptomIds = $cause->trieuChungs()->pluck('trieu_chung.id')->map(fn ($id) => (int) $id)->all();

        DB::transaction(function () use ($request, $cause, $symptomIds) {
            $cause->update([
                'ten_nguyen_nhan' => trim((string) $request->input('ten_nguyen_nhan')),
            ]);

            $cause->trieuChungs()->sync($symptomIds);
        });

        app(\App\Services\Chat\AiKnowledgeSyncService::class)->syncRepairCatalogByIds(array_values(array_unique(array_merge(
            $existingSymptomIds,
            $symptomIds
        ))));

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat nguyen nhan',
            'data' => $this->serializeAdminCause($cause->fresh([
                'trieuChungs:id,dich_vu_id,ten_trieu_chung',
                'trieuChungs.dichVu:id,ten_dich_vu,trang_thai',
                'huongXuLys:id,nguyen_nhan_id,ten_huong_xu_ly,gia_tham_khao,mo_ta_cong_viec,updated_at',
            ])),
        ]);
    }

    public function destroyCause(string $id)
    {
        $cause = NguyenNhan::query()->withCount(['trieuChungs', 'huongXuLys'])->find($id);

        if (!$cause) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay nguyen nhan',
            ], 404);
        }

        $cause->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Da xoa nguyen nhan va cac huong xu ly lien quan',
            'data' => [
                'linked_symptoms' => (int) $cause->trieu_chungs_count,
                'linked_resolutions' => (int) $cause->huong_xu_lys_count,
            ],
        ]);
    }

    public function getResolutions(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $serviceId = trim((string) $request->query('service_id', ''));
        $causeId = trim((string) $request->query('cause_id', ''));

        $query = HuongXuLy::query()
            ->with([
                'nguyenNhan:id,ten_nguyen_nhan',
                'nguyenNhan.trieuChungs:id,dich_vu_id,ten_trieu_chung',
                'nguyenNhan.trieuChungs.dichVu:id,ten_dich_vu,trang_thai',
            ])
            ->when($causeId !== '' && ctype_digit($causeId), function (Builder $builder) use ($causeId) {
                $builder->where('nguyen_nhan_id', (int) $causeId);
            })
            ->when($serviceId !== '' && ctype_digit($serviceId), function (Builder $builder) use ($serviceId) {
                $builder->whereHas('nguyenNhan.trieuChungs', function (Builder $symptomQuery) use ($serviceId) {
                    $symptomQuery->where('trieu_chung.dich_vu_id', (int) $serviceId);
                });
            })
            ->when($search !== '', function (Builder $builder) use ($search) {
                $builder->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('ten_huong_xu_ly', 'like', '%' . $search . '%')
                        ->orWhere('mo_ta_cong_viec', 'like', '%' . $search . '%')
                        ->orWhereHas('nguyenNhan', function (Builder $causeQuery) use ($search) {
                            $causeQuery->where('ten_nguyen_nhan', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('nguyenNhan.trieuChungs', function (Builder $symptomQuery) use ($search) {
                            $symptomQuery->where('ten_trieu_chung', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('id');

        $resolutions = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $resolutions
                    ->map(fn (HuongXuLy $resolution) => $this->serializeAdminResolution($resolution))
                    ->values(),
                'summary' => [
                    'total' => $resolutions->count(),
                    'priced' => $resolutions->filter(fn (HuongXuLy $resolution) => (float) ($resolution->gia_tham_khao ?? 0) > 0)->count(),
                ],
                'service_options' => $this->getAdminCatalogServiceOptions(),
                'cause_options' => $this->getAdminCauseOptions(),
            ],
        ]);
    }

    public function storeResolution(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nguyen_nhan_id' => 'required|integer|exists:nguyen_nhan,id',
            'ten_huong_xu_ly' => [
                'required',
                'string',
                'max:255',
                Rule::unique('huong_xu_ly', 'ten_huong_xu_ly')->where(function ($query) use ($request) {
                    return $query->where('nguyen_nhan_id', $request->integer('nguyen_nhan_id'));
                }),
            ],
            'gia_tham_khao' => 'nullable|numeric|min:0',
            'mo_ta_cong_viec' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu huong xu ly khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resolution = HuongXuLy::query()->create([
            'nguyen_nhan_id' => $request->integer('nguyen_nhan_id'),
            'ten_huong_xu_ly' => trim((string) $request->input('ten_huong_xu_ly')),
            'gia_tham_khao' => $request->filled('gia_tham_khao') ? (float) $request->input('gia_tham_khao') : null,
            'mo_ta_cong_viec' => trim((string) $request->input('mo_ta_cong_viec', '')) ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Da them huong xu ly',
            'data' => $this->serializeAdminResolution($resolution->fresh([
                'nguyenNhan:id,ten_nguyen_nhan',
                'nguyenNhan.trieuChungs:id,dich_vu_id,ten_trieu_chung',
                'nguyenNhan.trieuChungs.dichVu:id,ten_dich_vu,trang_thai',
            ])),
        ], 201);
    }

    public function updateResolution(Request $request, string $id)
    {
        $resolution = HuongXuLy::query()->with([
            'nguyenNhan:id,ten_nguyen_nhan',
            'nguyenNhan.trieuChungs:id,dich_vu_id,ten_trieu_chung',
            'nguyenNhan.trieuChungs.dichVu:id,ten_dich_vu,trang_thai',
        ])->find($id);

        if (!$resolution) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay huong xu ly',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nguyen_nhan_id' => 'required|integer|exists:nguyen_nhan,id',
            'ten_huong_xu_ly' => [
                'required',
                'string',
                'max:255',
                Rule::unique('huong_xu_ly', 'ten_huong_xu_ly')
                    ->ignore($resolution->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('nguyen_nhan_id', $request->integer('nguyen_nhan_id'));
                    }),
            ],
            'gia_tham_khao' => 'nullable|numeric|min:0',
            'mo_ta_cong_viec' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu huong xu ly khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resolution->update([
            'nguyen_nhan_id' => $request->integer('nguyen_nhan_id'),
            'ten_huong_xu_ly' => trim((string) $request->input('ten_huong_xu_ly')),
            'gia_tham_khao' => $request->filled('gia_tham_khao') ? (float) $request->input('gia_tham_khao') : null,
            'mo_ta_cong_viec' => trim((string) $request->input('mo_ta_cong_viec', '')) ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat huong xu ly',
            'data' => $this->serializeAdminResolution($resolution->fresh([
                'nguyenNhan:id,ten_nguyen_nhan',
                'nguyenNhan.trieuChungs:id,dich_vu_id,ten_trieu_chung',
                'nguyenNhan.trieuChungs.dichVu:id,ten_dich_vu,trang_thai',
            ])),
        ]);
    }

    public function destroyResolution(string $id)
    {
        $resolution = HuongXuLy::query()->find($id);

        if (!$resolution) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay huong xu ly',
            ], 404);
        }

        $resolution->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Da xoa huong xu ly',
        ]);
    }

    public function getAssistantSoulConfig(AssistantSoulConfigService $assistantSoulConfigService)
    {
        return response()->json([
            'status' => 'success',
            'data' => $assistantSoulConfigService->getEditorState(),
        ]);
    }

    public function getAiKnowledgeItems(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'q' => 'nullable|string|max:500',
            'source_type' => 'nullable|in:booking_case,service_catalog',
            'service_id' => 'nullable|integer|min:1',
            'source_id' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'min_quality_score' => 'nullable|numeric|min:0|max:1',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'sort' => 'nullable|in:latest,quality,rating',
            'direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $perPage = (int) ($request->query('per_page', 20));
        $paginator = $this->buildAiKnowledgeQuery($request)
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'status' => 'success',
            'data' => array_map(
                fn (AiKnowledgeItem $item): array => $this->transformAiKnowledgeRecord($item),
                $paginator->items()
            ),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function getAiKnowledgeItem(string $id)
    {
        $item = AiKnowledgeItem::query()
            ->with('primaryService:id,ten_dich_vu')
            ->find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay ban ghi AI knowledge',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->transformAiKnowledgeRecord($item),
        ]);
    }

    public function syncAiKnowledge(Request $request, AiKnowledgeSyncService $syncService)
    {
        $validator = Validator::make($request->all(), [
            'source' => 'nullable|in:all,booking_case,service_catalog',
            'id' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $source = (string) $request->input('source', 'all');
        $sourceId = $request->filled('id') ? (int) $request->input('id') : null;
        $result = $syncService->sync($source, $sourceId);

        return response()->json([
            'status' => 'success',
            'message' => 'Da dong bo AI knowledge',
            'data' => [
                'source' => $source,
                'source_id' => $sourceId,
                'result' => $result,
                'total_items' => AiKnowledgeItem::query()->count(),
            ],
        ]);
    }

    public function exportAiKnowledge(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'q' => 'nullable|string|max:500',
            'source_type' => 'nullable|in:booking_case,service_catalog',
            'service_id' => 'nullable|integer|min:1',
            'source_id' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'min_quality_score' => 'nullable|numeric|min:0|max:1',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'sort' => 'nullable|in:latest,quality,rating',
            'direction' => 'nullable|in:asc,desc',
            'format' => 'required|in:jsonl,csv',
            'profile' => 'nullable|in:records,finetune',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $format = (string) $request->query('format');
        $profile = (string) $request->query('profile', 'records');
        $items = $this->buildAiKnowledgeQuery($request)->get();
        $timestamp = now()->format('Ymd_His');
        $extension = $format === 'jsonl' ? 'jsonl' : 'csv';
        $filename = "ai_knowledge_{$profile}_{$timestamp}.{$extension}";

        if ($format === 'jsonl') {
            return response()->streamDownload(function () use ($items, $profile): void {
                foreach ($items as $item) {
                    $payload = $profile === 'finetune'
                        ? $this->transformAiKnowledgeFineTuneRecord($item)
                        : $this->transformAiKnowledgeRecord($item);

                    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
                }
            }, $filename, [
                'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            ]);
        }

        return response()->streamDownload(function () use ($items, $profile): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");

            if ($profile === 'finetune') {
                fputcsv($handle, ['source_key', 'service_name', 'prompt', 'completion', 'quality_score', 'rating_avg']);
                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item->source_key,
                        $item->service_name,
                        $this->buildAiKnowledgeTrainingPrompt($item),
                        $this->buildAiKnowledgeTrainingCompletion($item),
                        $item->quality_score,
                        $item->rating_avg,
                    ]);
                }
            } else {
                fputcsv($handle, [
                    'id',
                    'source_key',
                    'source_type',
                    'source_id',
                    'primary_service_id',
                    'service_name',
                    'title',
                    'symptom_text',
                    'cause_text',
                    'solution_text',
                    'price_context',
                    'rating_avg',
                    'quality_score',
                    'is_active',
                    'published_at',
                    'training_prompt',
                    'training_completion',
                    'content',
                    'metadata_json',
                ]);

                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item->id,
                        $item->source_key,
                        $item->source_type,
                        $item->source_id,
                        $item->primary_service_id,
                        $item->service_name,
                        $item->title,
                        $item->symptom_text,
                        $item->cause_text,
                        $item->solution_text,
                        $item->price_context,
                        $item->rating_avg,
                        $item->quality_score,
                        $item->is_active ? 1 : 0,
                        optional($item->published_at)->toDateTimeString(),
                        $this->buildAiKnowledgeTrainingPrompt($item),
                        $this->buildAiKnowledgeTrainingCompletion($item),
                        $item->content,
                        json_encode($item->metadata ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function updateAssistantSoulConfig(Request $request, AssistantSoulConfigService $assistantSoulConfigService)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:5000',
            'identity_rules' => 'required|array|min:1',
            'identity_rules.*' => 'required|string|max:1000',
            'required_rules' => 'required|array|min:1',
            'required_rules.*' => 'required|string|max:2000',
            'response_goals' => 'required|array|min:1',
            'response_goals.*' => 'required|string|max:2000',
            'assistant_text_order' => 'required|array|min:1',
            'assistant_text_order.*' => 'required|string|max:1000',
            'json_keys' => 'required|array|min:1',
            'json_keys.*' => 'required|string|max:255',
            'output_style' => 'required|string|max:2000',
            'service_process' => 'required|array|min:1',
            'service_process.*' => 'required|string|max:1000',
            'emergency_keywords' => 'required|array|min:1',
            'emergency_keywords.*' => 'required|string|max:255',
            'emergency_response' => 'required|array',
            'emergency_response.fallback_price_line' => 'required|string|max:1000',
            'emergency_response.price_line_template' => 'required|string|max:1000',
            'emergency_response.lines' => 'required|array|min:1',
            'emergency_response.lines.*' => 'required|string|max:1500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $config = $this->normalizeAssistantSoulPayload($validator->validated());
        $assistantSoulConfigService->updateConfig($config, $request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat ASSISTANT SOUL',
            'data' => $assistantSoulConfigService->getEditorState(),
        ]);
    }

    public function resetAssistantSoulConfig(AssistantSoulConfigService $assistantSoulConfigService)
    {
        $assistantSoulConfigService->resetConfig();

        return response()->json([
            'status' => 'success',
            'message' => 'Da khoi phuc cau hinh mac dinh',
            'data' => $assistantSoulConfigService->getEditorState(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeAssistantSoulPayload(array $payload): array
    {
        return [
            'name' => trim((string) $payload['name']),
            'role' => trim((string) $payload['role']),
            'identity_rules' => $this->normalizeStringList($payload['identity_rules'] ?? []),
            'required_rules' => $this->normalizeStringList($payload['required_rules'] ?? []),
            'response_goals' => $this->normalizeStringList($payload['response_goals'] ?? []),
            'assistant_text_order' => $this->normalizeStringList($payload['assistant_text_order'] ?? []),
            'json_keys' => $this->normalizeStringList($payload['json_keys'] ?? []),
            'output_style' => trim((string) $payload['output_style']),
            'service_process' => $this->normalizeStringList($payload['service_process'] ?? []),
            'emergency_keywords' => $this->normalizeStringList($payload['emergency_keywords'] ?? []),
            'emergency_response' => [
                'fallback_price_line' => trim((string) data_get($payload, 'emergency_response.fallback_price_line', '')),
                'price_line_template' => trim((string) data_get($payload, 'emergency_response.price_line_template', '')),
                'lines' => $this->normalizeStringList(data_get($payload, 'emergency_response.lines', [])),
            ],
        ];
    }

    private function buildAiKnowledgeQuery(Request $request): Builder
    {
        $query = AiKnowledgeItem::query()
            ->with('primaryService:id,ten_dich_vu');

        $keyword = trim((string) $request->query('q', ''));
        $normalizedKeyword = TextNormalizer::normalize($keyword);
        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword, $normalizedKeyword): void {
                $builder->where('source_key', 'like', "%{$keyword}%")
                    ->orWhere('service_name', 'like', "%{$keyword}%")
                    ->orWhere('title', 'like', "%{$keyword}%")
                    ->orWhere('symptom_text', 'like', "%{$keyword}%")
                    ->orWhere('cause_text', 'like', "%{$keyword}%")
                    ->orWhere('solution_text', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%");

                if ($normalizedKeyword !== '') {
                    $builder->orWhere('normalized_content', 'like', "%{$normalizedKeyword}%");
                }
            });
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', (string) $request->query('source_type'));
        }

        if ($request->filled('service_id')) {
            $query->where('primary_service_id', (int) $request->query('service_id'));
        }

        if ($request->filled('source_id')) {
            $query->where('source_id', (int) $request->query('source_id'));
        }

        if ($request->has('is_active') && $request->query('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('min_quality_score')) {
            $query->where('quality_score', '>=', (float) $request->query('min_quality_score'));
        }

        if ($request->filled('min_rating')) {
            $query->where('rating_avg', '>=', (float) $request->query('min_rating'));
        }

        $sort = (string) $request->query('sort', 'latest');
        $direction = (string) $request->query('direction', 'desc');
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        if ($sort === 'quality') {
            $query->orderBy('quality_score', $direction)->orderBy('published_at', 'desc');
        } elseif ($sort === 'rating') {
            $query->orderBy('rating_avg', $direction)->orderBy('quality_score', 'desc');
        } else {
            $query->orderBy('published_at', $direction)->orderBy('updated_at', 'desc');
        }

        return $query;
    }

    private function transformAiKnowledgeRecord(AiKnowledgeItem $item): array
    {
        return [
            'id' => $item->id,
            'source_key' => $item->source_key,
            'source_type' => $item->source_type,
            'source_id' => $item->source_id,
            'primary_service_id' => $item->primary_service_id,
            'service_name' => $item->service_name,
            'title' => $item->title,
            'symptom_text' => $item->symptom_text,
            'cause_text' => $item->cause_text,
            'solution_text' => $item->solution_text,
            'price_context' => $item->price_context,
            'rating_avg' => $item->rating_avg,
            'quality_score' => $item->quality_score,
            'is_active' => $item->is_active,
            'published_at' => optional($item->published_at)->toISOString(),
            'primary_service' => $item->primaryService
                ? [
                    'id' => $item->primaryService->id,
                    'ten_dich_vu' => $item->primaryService->ten_dich_vu,
                ]
                : null,
            'training_prompt' => $this->buildAiKnowledgeTrainingPrompt($item),
            'training_completion' => $this->buildAiKnowledgeTrainingCompletion($item),
            'content' => $item->content,
            'metadata' => $item->metadata ?? [],
        ];
    }

    private function transformAiKnowledgeFineTuneRecord(AiKnowledgeItem $item): array
    {
        return [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Ban la tro ly ky thuat sua chua gia dung. Tra loi ngan gon, ro nguyen nhan, giai phap va chi phi tham khao neu co.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildAiKnowledgeTrainingPrompt($item),
                ],
                [
                    'role' => 'assistant',
                    'content' => $this->buildAiKnowledgeTrainingCompletion($item),
                ],
            ],
            'metadata' => [
                'source_key' => $item->source_key,
                'source_type' => $item->source_type,
                'service_name' => $item->service_name,
                'quality_score' => (float) $item->quality_score,
                'rating_avg' => $item->rating_avg !== null ? (float) $item->rating_avg : null,
            ],
        ];
    }

    private function buildAiKnowledgeTrainingPrompt(AiKnowledgeItem $item): string
    {
        $lines = array_filter([
            $item->service_name ? 'Dich vu: ' . $item->service_name : null,
            $item->symptom_text ? 'Trieu chung: ' . $item->symptom_text : null,
            !$item->symptom_text && $item->title ? 'Tinh huong: ' . $item->title : null,
        ]);

        return implode("\n", $lines);
    }

    private function buildAiKnowledgeTrainingCompletion(AiKnowledgeItem $item): string
    {
        $lines = array_filter([
            $item->cause_text ? 'Nguyen nhan du kien: ' . $item->cause_text : null,
            $item->solution_text ? 'Giai phap de xuat: ' . $item->solution_text : null,
            $item->price_context ? 'Chi phi tham khao: ' . $item->price_context : null,
            !$item->cause_text && !$item->solution_text && $item->content ? $item->content : null,
        ]);

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function streamCsvDownload(string $filename, array $headers, array $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function getAdminCatalogServiceOptions(): Collection
    {
        return DanhMucDichVu::query()
            ->select(['id', 'ten_dich_vu', 'trang_thai'])
            ->orderBy('ten_dich_vu')
            ->get()
            ->map(fn (DanhMucDichVu $service) => [
                'id' => $service->id,
                'ten_dich_vu' => $service->ten_dich_vu,
                'trang_thai' => (int) $service->trang_thai,
            ])
            ->values();
    }

    private function getAdminSymptomOptions(): Collection
    {
        return TrieuChung::query()
            ->with([
                'dichVu:id,ten_dich_vu,trang_thai',
            ])
            ->orderBy('ten_trieu_chung')
            ->get()
            ->map(fn (TrieuChung $symptom) => [
                'id' => $symptom->id,
                'dich_vu_id' => $symptom->dich_vu_id,
                'service_name' => $symptom->dichVu?->ten_dich_vu,
                'ten_trieu_chung' => $symptom->ten_trieu_chung,
                'label' => trim(($symptom->dichVu?->ten_dich_vu ? $symptom->dichVu->ten_dich_vu . ' · ' : '') . $symptom->ten_trieu_chung),
            ])
            ->values();
    }

    private function serializeRepairKnowledgeService(DanhMucDichVu $service): array
    {
        $symptoms = ($service->trieuChungs ?? collect())
            ->sortBy(fn (TrieuChung $symptom) => trim((string) $symptom->ten_trieu_chung))
            ->values();
        $causes = $symptoms
            ->flatMap(fn (TrieuChung $symptom) => $symptom->nguyenNhans ?? collect())
            ->unique('id')
            ->values();
        $resolutions = $causes
            ->flatMap(fn (NguyenNhan $cause) => $cause->huongXuLys ?? collect())
            ->unique('id')
            ->values();

        return [
            'id' => $service->id,
            'ten_dich_vu' => $service->ten_dich_vu,
            'trang_thai' => (int) $service->trang_thai,
            'symptom_count' => $symptoms->count(),
            'cause_count' => $causes->count(),
            'resolution_count' => $resolutions->count(),
            'symptoms' => $symptoms
                ->map(function (TrieuChung $symptom) {
                    $causes = ($symptom->nguyenNhans ?? collect())
                        ->sortBy(fn (NguyenNhan $cause) => trim((string) $cause->ten_nguyen_nhan))
                        ->values();
                    $resolutionCount = $causes
                        ->flatMap(fn (NguyenNhan $cause) => $cause->huongXuLys ?? collect())
                        ->unique('id')
                        ->count();

                    return array_merge($this->serializeAdminSymptom($symptom), [
                        'resolution_count' => $resolutionCount,
                        'causes' => $causes
                            ->map(function (NguyenNhan $cause) {
                                $resolutions = ($cause->huongXuLys ?? collect())
                                    ->sortBy(fn (HuongXuLy $resolution) => trim((string) $resolution->ten_huong_xu_ly))
                                    ->values()
                                    ->map(fn (HuongXuLy $resolution) => $this->serializeAdminResolution($resolution))
                                    ->values();

                                return array_merge($this->serializeAdminCause($cause), [
                                    'resolutions' => $resolutions->all(),
                                ]);
                            })
                            ->values()
                            ->all(),
                    ]);
                })
                ->values()
                ->all(),
        ];
    }

    private function serializeAdminPart(LinhKien $part): array
    {
        $expiryDate = $part->han_su_dung;
        $expiryLabel = $expiryDate?->format('d/m/Y');
        $expiryState = 'none';

        if ($expiryDate) {
            $today = Carbon::today();
            $warningThreshold = $today->copy()->addMonthsNoOverflow(6);

            if ($expiryDate->lt($today)) {
                $expiryState = 'expired';
            } elseif ($expiryDate->lte($warningThreshold)) {
                $expiryState = 'expiring_soon';
            } else {
                $expiryState = 'active';
            }
        }

        return [
            'id' => $part->id,
            'dich_vu_id' => $part->dich_vu_id,
            'service_name' => $part->dichVu?->ten_dich_vu,
            'ten_linh_kien' => $part->ten_linh_kien,
            'hinh_anh' => $part->hinh_anh,
            'gia' => $part->gia !== null ? (float) $part->gia : null,
            'gia_label' => $part->gia !== null ? number_format((float) $part->gia, 0, ',', '.') . ' đ' : 'Chua cap nhat',
            'so_luong_ton_kho' => (int) ($part->so_luong_ton_kho ?? 0),
            'so_luong_ton_kho_label' => number_format((int) ($part->so_luong_ton_kho ?? 0), 0, ',', '.'),
            'han_su_dung' => $expiryDate?->toDateString(),
            'han_su_dung_label' => $expiryLabel ?: 'Khong co',
            'han_su_dung_state' => $expiryState,
            'updated_at' => optional($part->updated_at)->toIso8601String(),
            'updated_label' => optional($part->updated_at)->format('d/m/Y H:i'),
        ];
    }

    private function serializeAdminSymptom(TrieuChung $symptom): array
    {
        $causes = $symptom->relationLoaded('nguyenNhans')
            ? $symptom->nguyenNhans
            : $symptom->nguyenNhans()->get(['nguyen_nhan.id', 'ten_nguyen_nhan']);

        $causeNames = $causes
            ->map(fn (NguyenNhan $cause) => trim((string) $cause->ten_nguyen_nhan))
            ->filter()
            ->sort()
            ->values();

        return [
            'id' => $symptom->id,
            'dich_vu_id' => $symptom->dich_vu_id,
            'service_name' => $symptom->dichVu?->ten_dich_vu,
            'ten_trieu_chung' => $symptom->ten_trieu_chung,
            'nguyen_nhan_count' => $causeNames->count(),
            'nguyen_nhan_names' => $causeNames->all(),
            'nguyen_nhans_text' => $causeNames->implode(PHP_EOL),
            'updated_at' => optional($symptom->updated_at)->toIso8601String(),
            'updated_label' => optional($symptom->updated_at)->format('d/m/Y H:i'),
        ];
    }

    private function serializeAdminCause(NguyenNhan $cause): array
    {
        $symptoms = $cause->relationLoaded('trieuChungs')
            ? $cause->trieuChungs
            : $cause->trieuChungs()->with([
                'dichVu:id,ten_dich_vu,trang_thai',
            ])->get(['trieu_chung.id', 'trieu_chung.dich_vu_id', 'trieu_chung.ten_trieu_chung']);
        $resolutions = $cause->relationLoaded('huongXuLys')
            ? $cause->huongXuLys
            : $cause->huongXuLys()->get(['id', 'nguyen_nhan_id', 'ten_huong_xu_ly', 'gia_tham_khao', 'mo_ta_cong_viec', 'updated_at']);

        $serviceIds = $symptoms
            ->pluck('dich_vu_id')
            ->filter()
            ->unique()
            ->values();
        $serviceNames = $symptoms
            ->map(fn (TrieuChung $symptom) => $symptom->dichVu?->ten_dich_vu)
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $symptomIds = $symptoms
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();
        $symptomNames = $symptoms
            ->map(fn (TrieuChung $symptom) => trim((string) $symptom->ten_trieu_chung))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'id' => $cause->id,
            'ten_nguyen_nhan' => $cause->ten_nguyen_nhan,
            'service_ids' => $serviceIds->all(),
            'service_names' => $serviceNames->all(),
            'symptom_ids' => $symptomIds->all(),
            'symptom_names' => $symptomNames->all(),
            'linked_symptom_count' => $symptomIds->count(),
            'resolution_count' => $resolutions->count(),
            'updated_at' => optional($cause->updated_at)->toIso8601String(),
            'updated_label' => optional($cause->updated_at)->format('d/m/Y H:i'),
        ];
    }

    private function serializeAdminResolution(HuongXuLy $resolution): array
    {
        $cause = $resolution->relationLoaded('nguyenNhan')
            ? $resolution->nguyenNhan
            : $resolution->nguyenNhan()->with([
                'trieuChungs:id,dich_vu_id,ten_trieu_chung',
                'trieuChungs.dichVu:id,ten_dich_vu,trang_thai',
            ])->first();

        $symptoms = $cause?->relationLoaded('trieuChungs')
            ? $cause->trieuChungs
            : collect();
        $serviceNames = $symptoms
            ->map(fn (TrieuChung $symptom) => $symptom->dichVu?->ten_dich_vu)
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $serviceIds = $symptoms
            ->pluck('dich_vu_id')
            ->filter()
            ->unique()
            ->values();
        $symptomNames = $symptoms
            ->map(fn (TrieuChung $symptom) => trim((string) $symptom->ten_trieu_chung))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'id' => $resolution->id,
            'nguyen_nhan_id' => $resolution->nguyen_nhan_id,
            'cause_name' => $cause?->ten_nguyen_nhan,
            'service_ids' => $serviceIds->all(),
            'service_names' => $serviceNames->all(),
            'primary_service_id' => $serviceIds->first(),
            'ten_huong_xu_ly' => $resolution->ten_huong_xu_ly,
            'gia_tham_khao' => $resolution->gia_tham_khao !== null ? (float) $resolution->gia_tham_khao : null,
            'gia_label' => $resolution->gia_tham_khao !== null
                ? number_format((float) $resolution->gia_tham_khao, 0, ',', '.') . ' đ'
                : 'Chua cap nhat',
            'mo_ta_cong_viec' => $resolution->mo_ta_cong_viec,
            'symptom_names' => $symptomNames->all(),
            'updated_at' => optional($resolution->updated_at)->toIso8601String(),
            'updated_label' => optional($resolution->updated_at)->format('d/m/Y H:i'),
        ];
    }

    private function getAdminCauseOptions(): Collection
    {
        return NguyenNhan::query()
            ->with([
                'trieuChungs:id,dich_vu_id,ten_trieu_chung',
                'trieuChungs.dichVu:id,ten_dich_vu,trang_thai',
            ])
            ->orderBy('ten_nguyen_nhan')
            ->get()
            ->map(fn (NguyenNhan $cause) => $this->serializeAdminCause($cause))
            ->values();
    }

    private function syncSymptomCausesFromText(TrieuChung $symptom, string $rawText): void
    {
        $causeNames = $this->normalizeCauseNamesFromText($rawText);

        if ($causeNames === []) {
            $symptom->nguyenNhans()->sync([]);
            app(\App\Services\Chat\AiKnowledgeSyncService::class)->syncSourceRecord('repair_catalog', $symptom->id);

            return;
        }

        $existingCauses = NguyenNhan::query()
            ->get()
            ->keyBy(fn (NguyenNhan $cause) => TextNormalizer::normalize((string) $cause->ten_nguyen_nhan));

        $causeIds = [];

        foreach ($causeNames as $causeName) {
            $lookup = TextNormalizer::normalize($causeName);
            $cause = $existingCauses->get($lookup);

            if (!$cause) {
                $cause = NguyenNhan::query()->create([
                    'ten_nguyen_nhan' => $causeName,
                ]);

                $existingCauses->put($lookup, $cause);
            }

            $causeIds[] = $cause->id;
        }

        $symptom->nguyenNhans()->sync(array_values(array_unique($causeIds)));
        app(\App\Services\Chat\AiKnowledgeSyncService::class)->syncSourceRecord('repair_catalog', $symptom->id);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeCauseNamesFromText(string $rawText): array
    {
        $segments = preg_split('/[\r\n,;]+/u', $rawText) ?: [];
        $seen = [];
        $normalized = [];

        foreach ($segments as $segment) {
            $value = preg_replace('/\s+/u', ' ', trim((string) $segment)) ?? '';

            if ($value === '') {
                continue;
            }

            $lookup = TextNormalizer::normalize($value);

            if ($lookup === '' || isset($seen[$lookup])) {
                continue;
            }

            $seen[$lookup] = true;
            $normalized[] = $value;
        }

        return $normalized;
    }

    /**
     * @param  mixed  $items
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item): string {
            return trim((string) $item);
        }, $items), static fn (string $item): bool => $item !== ''));
    }

    private function storeServiceImage(UploadedFile $file): string
    {
        return $this->storeCatalogImage($file, 'services');
    }

    private function storePartImage(UploadedFile $file): string
    {
        return $this->storeCatalogImage($file, 'parts');
    }

    private function storeCatalogImage(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    private function deleteStoredServiceImage(?string $imagePath): void
    {
        $this->deleteStoredCatalogImage($imagePath);
    }

    private function deleteStoredPartImage(?string $imagePath): void
    {
        $this->deleteStoredCatalogImage($imagePath);
    }

    private function upsertWorkerProfile(User $worker, array $attributes, bool $isActive): HoSoTho
    {
        $profile = $worker->hoSoTho()->firstOrNew(['user_id' => $worker->id]);

        if (!$profile->exists) {
            $profile->fill([
                'trang_thai_duyet' => 'da_duyet',
                'dang_hoat_dong' => $isActive,
                'trang_thai_hoat_dong' => $isActive ? 'dang_hoat_dong' : 'tam_khoa',
            ]);
        }

        $profile->fill($attributes);
        $profile->save();

        return $profile;
    }

    private function normalizeWorkerServiceIds(array $serviceIds): array
    {
        return collect($serviceIds)
            ->filter(static fn ($serviceId) => $serviceId !== null && $serviceId !== '')
            ->map(static fn ($serviceId) => (int) $serviceId)
            ->filter(static fn (int $serviceId) => $serviceId > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function storeWorkerAvatar(User $worker, UploadedFile $file): void
    {
        $this->deleteStoredCatalogImage($worker->avatar);

        $fileName = 'worker_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('avatars', $fileName, 'public');

        $worker->update(['avatar' => '/storage/' . $path]);
    }

    private function syncWorkerKnowledge(int $workerId): void
    {
        if (!Schema::hasTable('ai_knowledge_items')) {
            return;
        }

        app(AiKnowledgeSyncService::class)->syncSourceRecord('worker_profile', $workerId);
    }

    private function deleteStoredCatalogImage(?string $imagePath): void
    {
        $imagePath = trim((string) $imagePath);

        if ($imagePath === '') {
            return;
        }

        if (Str::startsWith($imagePath, ['http://', 'https://', 'data:'])) {
            $storagePrefix = rtrim(asset('storage'), '/');

            if (!Str::startsWith($imagePath, $storagePrefix . '/')) {
                return;
            }

            $imagePath = Str::after($imagePath, $storagePrefix . '/');
        }

        if (Str::startsWith($imagePath, '/storage/')) {
            $imagePath = Str::after($imagePath, '/storage/');
        } elseif (Str::startsWith($imagePath, 'storage/')) {
            $imagePath = Str::after($imagePath, 'storage/');
        }

        if ($imagePath !== '' && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }
}

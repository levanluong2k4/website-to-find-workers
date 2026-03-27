<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiKnowledgeItem;
use App\Models\DanhMucDichVu;
use App\Models\DanhGia;
use App\Models\DonDatLich;
use App\Models\HoSoTho;
use App\Models\User;
use App\Services\Chat\AssistantSoulConfigService;
use App\Services\Chat\AiKnowledgeSyncService;
use App\Services\Chat\TextNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

        $trendStart = Carbon::today()->subDays(6)->startOfDay();
        $trendRaw = DonDatLich::query()
            ->whereIn('trang_thai', $completedStatuses)
            ->whereBetween(
                DB::raw('COALESCE(thoi_gian_hoan_thanh, created_at)'),
                [$trendStart, $range['today_end']]
            )
            ->selectRaw('DATE(COALESCE(thoi_gian_hoan_thanh, created_at)) as report_date, SUM(tong_tien) as total')
            ->groupBy('report_date')
            ->pluck('total', 'report_date');

        $trend = collect(range(0, 6))->map(function (int $offset) use ($trendStart, $trendRaw) {
            $day = $trendStart->copy()->addDays($offset);

            return [
                'label' => $this->dashboardDayLabel($day),
                'date' => $day->toDateString(),
                'value' => (float) ($trendRaw[$day->toDateString()] ?? 0),
            ];
        })->values();

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

    private function resolveDashboardRange(?string $period): array
    {
        $normalizedPeriod = in_array($period, ['today', '7d', '30d'], true) ? $period : '7d';
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        return match ($normalizedPeriod) {
            'today' => [
                'period' => 'today',
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

    public function getUsers(Request $request)
    {
        $role = $request->query('role');

        $query = User::query()
            ->with([
                'hoSoTho',
                'dichVus:id,ten_dich_vu',
            ])
            ->where('role', '!=', 'admin');

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->orderByDesc('created_at')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
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

    public function getAllBookings(Request $request)
    {
        $status = $request->query('status');

        $query = DonDatLich::with([
            'khachHang:id,name,phone',
            'tho:id,name,phone',
            'dichVus:id,ten_dich_vu',
        ]);

        if ($status) {
            $query->where('trang_thai', $status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderByDesc('created_at')->get(),
        ]);
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

        $service->update([
            'trang_thai' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Da xoa dich vu',
            'data' => $service->fresh(),
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
        return $file->store('services', 'public');
    }

    private function deleteStoredServiceImage(?string $imagePath): void
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\DonDatLich;
use App\Models\LichSuGiaoDich;
use App\Models\User;
use App\Models\ViDienTu;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminRevenueController extends Controller
{
    public function index(Request $request)
    {
        $period  = $request->input('period', '30d');
        [$from, $to] = $this->resolvePeriod($period, $request);

        $taxRate = (float) (AppSetting::where('key', 'ty_le_thue_nha_nuoc')->value('value') ?? 10);
        $feeRate = (float) (AppSetting::where('key', 'ty_le_phi_nen_tang')->value('value') ?? 20);
        $netRate = max(0, 100 - $taxRate - $feeRate);

        // ------- KPIs -------
        $earningsQuery = DonDatLich::query()
            ->whereIn('trang_thai', ['da_xong', 'hoan_thanh']);
        if ($from) $earningsQuery->where('updated_at', '>=', $from);
        if ($to)   $earningsQuery->where('updated_at', '<=', $to);

        $tongDoanhThuGop = (float) $earningsQuery->sum('tong_tien');

        $tongThue       = round($tongDoanhThuGop * $taxRate / 100);
        $tongPhiNenTang = round($tongDoanhThuGop * $feeRate / 100);
        $tongLuongTho   = round($tongDoanhThuGop * $netRate / 100);

        $withdrawQuery = LichSuGiaoDich::query()
            ->where('loai_giao_dich', 'rut_tien')
            ->where('trang_thai', 'thanh_cong');
        if ($from) $withdrawQuery->where('created_at', '>=', $from);
        if ($to)   $withdrawQuery->where('created_at', '<=', $to);
        $tongDaRut = abs((float) $withdrawQuery->sum('so_tien'));

        // ------- Chart: revenue by day -------
        $chartQuery = DonDatLich::query()
            ->whereIn('trang_thai', ['da_xong', 'hoan_thanh'])
            ->selectRaw('DATE(updated_at) as ngay, SUM(tong_tien) as tong')
            ->groupBy('ngay')
            ->orderBy('ngay');
        if ($from) $chartQuery->where('updated_at', '>=', $from);
        if ($to)   $chartQuery->where('updated_at', '<=', $to);
        $chartData = $chartQuery->get()->map(fn($r) => [
            'ngay'   => $r->ngay,
            'gop'    => (float) $r->tong,
            'thue'   => round($r->tong * $taxRate / 100),
            'phi'    => round($r->tong * $feeRate / 100),
            'luong'  => round($r->tong * $netRate / 100),
        ]);

        // ------- Top workers -------
        $topWorkers = DonDatLich::query()
            ->whereIn('don_dat_lich.trang_thai', ['da_xong', 'hoan_thanh'])
            ->join('users', 'users.id', '=', 'don_dat_lich.tho_id')
            ->selectRaw('users.id, users.name, users.avatar, SUM(don_dat_lich.tong_tien) as tong_gop, COUNT(don_dat_lich.id) as so_don')
            ->groupBy('users.id', 'users.name', 'users.avatar')
            ->orderByDesc('tong_gop')
            ->limit(10)
            ->when($from, fn($q) => $q->where('don_dat_lich.updated_at', '>=', $from))
            ->when($to,   fn($q) => $q->where('don_dat_lich.updated_at', '<=', $to))
            ->get()
            ->map(fn($w) => [
                'id'         => $w->id,
                'name'       => $w->name,
                'avatar'     => $w->avatar,
                'so_don'     => (int) $w->so_don,
                'tong_gop'   => (float) $w->tong_gop,
                'luong_thuc' => round($w->tong_gop * $netRate / 100),
            ]);

        // ------- Worker salary table -------
        $salaryTable = DonDatLich::query()
            ->whereIn('don_dat_lich.trang_thai', ['da_xong', 'hoan_thanh'])
            ->join('users', 'users.id', '=', 'don_dat_lich.tho_id')
            ->leftJoin('vi_dien_tu', 'vi_dien_tu.ma_tho', '=', 'users.id')
            ->selectRaw('
                users.id,
                users.name,
                users.phone,
                MAX(vi_dien_tu.so_du) as so_du,
                COUNT(don_dat_lich.id) as so_don,
                SUM(don_dat_lich.tong_tien) as tong_gop
            ')
            ->groupBy('users.id', 'users.name', 'users.phone')
            ->orderByDesc('tong_gop')
            ->when($from, fn($q) => $q->where('don_dat_lich.updated_at', '>=', $from))
            ->when($to,   fn($q) => $q->where('don_dat_lich.updated_at', '<=', $to))
            ->get()
            ->map(function ($w) use ($taxRate, $feeRate, $netRate) {
                $gop   = (float) $w->tong_gop;
                $thue  = round($gop * $taxRate / 100);
                $phi   = round($gop * $feeRate / 100);
                $luong = round($gop * $netRate / 100);

                // total withdrawn all time for this worker
                $daRut = LichSuGiaoDich::query()
                    ->join('vi_dien_tu', 'vi_dien_tu.id', '=', 'lich_su_giao_dichs.ma_vi')
                    ->where('vi_dien_tu.ma_tho', $w->id)
                    ->where('lich_su_giao_dichs.loai_giao_dich', 'rut_tien')
                    ->where('lich_su_giao_dichs.trang_thai', 'thanh_cong')
                    ->sum(DB::raw('ABS(lich_su_giao_dichs.so_tien)'));

                $coPendingRut = LichSuGiaoDich::query()
                    ->join('vi_dien_tu', 'vi_dien_tu.id', '=', 'lich_su_giao_dichs.ma_vi')
                    ->where('vi_dien_tu.ma_tho', $w->id)
                    ->where('lich_su_giao_dichs.loai_giao_dich', 'rut_tien')
                    ->where('lich_su_giao_dichs.trang_thai', 'dang_xu_ly')
                    ->exists();

                return [
                    'id'           => $w->id,
                    'name'         => $w->name,
                    'phone'        => $w->phone,
                    'so_don'       => (int) $w->so_don,
                    'tong_gop'     => $gop,
                    'thue'         => $thue,
                    'phi_nen_tang' => $phi,
                    'luong_thuc'   => $luong,
                    'so_du_vi'     => (float) $w->so_du,
                    'da_rut'       => (float) $daRut,
                    'co_pending'   => $coPendingRut,
                ];
            });

        // ------- Orders History (Paginated) -------
        $ordersPage = (int) $request->input('order_page', 1);
        $ordersQuery = DonDatLich::query()
            ->whereIn('don_dat_lich.trang_thai', ['da_xong', 'hoan_thanh'])
            ->join('users as tho', 'tho.id', '=', 'don_dat_lich.tho_id')
            ->join('users as kh', 'kh.id', '=', 'don_dat_lich.khach_hang_id')
            ->select(
                'don_dat_lich.id',
                'don_dat_lich.tong_tien',
                'don_dat_lich.updated_at',
                'tho.name as tho_name',
                'tho.id as tho_id',
                'kh.name as khach_hang'
            )
            ->orderByDesc('don_dat_lich.updated_at')
            ->when($from, fn($q) => $q->where('don_dat_lich.updated_at', '>=', $from))
            ->when($to,   fn($q) => $q->where('don_dat_lich.updated_at', '<=', $to));
            
        $paginatedOrders = $ordersQuery->paginate(10, ['*'], 'order_page', $ordersPage);
        
        $ordersList = collect($paginatedOrders->items())->map(function($o) use ($taxRate, $feeRate, $netRate) {
            $gop = (float) $o->tong_tien;
            return [
                'id' => $o->id,
                'khach_hang' => $o->khach_hang,
                'tho_id' => $o->tho_id,
                'tho_name' => $o->tho_name,
                'ngay' => Carbon::parse($o->updated_at)->format('d/m/Y H:i'),
                'tong_gop' => $gop,
                'thue' => round($gop * $taxRate / 100),
                'phi' => round($gop * $feeRate / 100),
                'luong' => round($gop * $netRate / 100)
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'period'         => $period,
                'wage_config'    => ['tax_rate' => $taxRate, 'fee_rate' => $feeRate, 'net_rate' => $netRate],
                'kpis'           => [
                    'tong_doanh_thu_gop' => $tongDoanhThuGop,
                    'tong_thue'          => $tongThue,
                    'tong_phi_nen_tang'  => $tongPhiNenTang,
                    'tong_luong_tho'     => $tongLuongTho,
                    'tong_da_rut'        => $tongDaRut,
                    'so_tho_hoat_dong'   => $salaryTable->count(),
                ],
                'chart'          => $chartData,
                'top_workers'    => $topWorkers,
                'salary_table'   => $salaryTable,
                'orders'         => [
                    'data' => $ordersList,
                    'current_page' => $paginatedOrders->currentPage(),
                    'last_page' => $paginatedOrders->lastPage(),
                    'total' => $paginatedOrders->total()
                ]
            ],
        ]);
    }

    public function export(Request $request)
    {
        $period  = $request->input('period', '30d');
        [$from, $to] = $this->resolvePeriod($period, $request);

        $taxRate = (float) (AppSetting::where('key', 'ty_le_thue_nha_nuoc')->value('value') ?? 10);
        $feeRate = (float) (AppSetting::where('key', 'ty_le_phi_nen_tang')->value('value') ?? 20);
        $netRate = max(0, 100 - $taxRate - $feeRate);

        $orders = DonDatLich::query()
            ->whereIn('don_dat_lich.trang_thai', ['da_xong', 'hoan_thanh'])
            ->join('users as tho', 'tho.id', '=', 'don_dat_lich.tho_id')
            ->join('users as kh', 'kh.id', '=', 'don_dat_lich.khach_hang_id')
            ->select(
                'don_dat_lich.id',
                'don_dat_lich.tong_tien',
                'don_dat_lich.updated_at',
                'tho.name as tho_name',
                'tho.id as tho_id',
                'kh.name as khach_hang'
            )
            ->orderByDesc('don_dat_lich.updated_at')
            ->when($from, fn($q) => $q->where('don_dat_lich.updated_at', '>=', $from))
            ->when($to,   fn($q) => $q->where('don_dat_lich.updated_at', '<=', $to))
            ->get();

        $filename = 'chi_tiet_doanh_thu_' . Carbon::now()->format('Ymd_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        
        $callback = function () use ($orders, $taxRate, $feeRate, $netRate, $period, $from, $to) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            
            // Metadata
            fputcsv($file, ['THỐNG KÊ DOANH THU ĐƠN ĐẶT LỊCH']);
            fputcsv($file, ['Thời gian xuất báo cáo:', Carbon::now()->format('d/m/Y H:i:s')]);
            
            $kyBaoCao = $period;
            if ($period === 'custom' || ($from && $to)) {
                $fDate = $from ? Carbon::parse($from)->format('d/m/Y') : '...';
                $tDate = $to ? Carbon::parse($to)->format('d/m/Y') : '...';
                $kyBaoCao = "Từ ngày $fDate đến ngày $tDate";
            }
            fputcsv($file, ['Kỳ báo cáo:', $kyBaoCao]);
            fputcsv($file, []); // empty line

            fputcsv($file, [
                'Mã Đơn',
                'Thời Gian Hoàn Thành',
                'Khách Hàng',
                'ID Thợ',
                'Tên Thợ',
                'Doanh Thu Gộp',
                'Thuế Nhà Nước (' . $taxRate . '%)',
                'Phí Nền Tảng (' . $feeRate . '%)',
                'Thợ Thực Nhận (' . $netRate . '%)'
            ]);

            $tongGop = 0;
            $tongThue = 0;
            $tongPhi = 0;
            $tongThucNhan = 0;
            
            $fmt = fn($n) => number_format(round($n), 0, ',', '.') . 'đ';

            foreach ($orders as $o) {
                $gop   = (float) $o->tong_tien;
                $thue  = round($gop * $taxRate / 100);
                $phi   = round($gop * $feeRate / 100);
                $luong = round($gop * $netRate / 100);

                $tongGop += $gop;
                $tongThue += $thue;
                $tongPhi += $phi;
                $tongThucNhan += $luong;

                fputcsv($file, [
                    $o->id,
                    Carbon::parse($o->updated_at)->format('d/m/Y H:i'),
                    $o->khach_hang,
                    $o->tho_id,
                    $o->tho_name,
                    $fmt($gop),
                    $fmt($thue),
                    $fmt($phi),
                    $fmt($luong)
                ]);
            }
            
            fputcsv($file, []);
            fputcsv($file, [
                'TỔNG CỘNG',
                '',
                '',
                '',
                '',
                $fmt($tongGop),
                $fmt($tongThue),
                $fmt($tongPhi),
                $fmt($tongThucNhan)
            ]);

            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function withdrawals(Request $request)
    {
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);
        $status  = $request->input('status');   // dang_xu_ly | thanh_cong | that_bai | all
        $search  = trim($request->input('search', ''));

        $query = LichSuGiaoDich::query()
            ->where('lich_su_giao_dichs.loai_giao_dich', 'rut_tien')
            ->join('vi_dien_tu', 'vi_dien_tu.id', '=', 'lich_su_giao_dichs.ma_vi')
            ->join('users', 'users.id', '=', 'vi_dien_tu.ma_tho')
            ->select(
                'lich_su_giao_dichs.id',
                DB::raw('ABS(lich_su_giao_dichs.so_tien) as so_tien'),
                'lich_su_giao_dichs.trang_thai',
                'lich_su_giao_dichs.created_at',
                'users.id as user_id',
                'users.name as ten_tho',
                'users.phone as sdt',
                'vi_dien_tu.so_du as so_du_vi'
            )
            ->orderByDesc('lich_su_giao_dichs.created_at');

        if ($status && $status !== 'all') {
            $query->where('lich_su_giao_dichs.trang_thai', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%$search%")
                  ->orWhere('users.phone', 'like', "%$search%");
            });
        }

        $total   = $query->count();
        $records = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        // summary counts
        $counts = LichSuGiaoDich::query()
            ->where('loai_giao_dich', 'rut_tien')
            ->selectRaw('trang_thai, COUNT(*) as cnt, SUM(ABS(so_tien)) as tong')
            ->groupBy('trang_thai')
            ->get()
            ->keyBy('trang_thai');

        return response()->json([
            'status' => 'success',
            'data' => [
                'records'  => $records,
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'summary'  => [
                    'dang_xu_ly'  => ['cnt' => (int)($counts['dang_xu_ly']->cnt ?? 0),  'tong' => (float)($counts['dang_xu_ly']->tong ?? 0)],
                    'thanh_cong'  => ['cnt' => (int)($counts['thanh_cong']->cnt ?? 0),  'tong' => (float)($counts['thanh_cong']->tong ?? 0)],
                    'that_bai'    => ['cnt' => (int)($counts['that_bai']->cnt ?? 0),     'tong' => (float)($counts['that_bai']->tong ?? 0)],
                ],
            ],
        ]);
    }

    // -------------------------------------------------------
    private function resolvePeriod(string $period, Request $request): array
    {
        $now = Carbon::now();
        return match ($period) {
            'today'      => [Carbon::today(),                                               Carbon::now()],
            '7d'         => [$now->copy()->subDays(6)->startOfDay(),                        $now],
            '30d'        => [$now->copy()->subDays(29)->startOfDay(),                       $now],
            'month'      => [Carbon::now()->startOfMonth(),                                 $now],
            'prev-month' => [Carbon::now()->subMonth()->startOfMonth(),                     Carbon::now()->subMonth()->endOfMonth()],
            'all'        => [null, null],
            'custom'     => [
                $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : null,
                $request->filled('to')   ? Carbon::parse($request->input('to'))->endOfDay()   : null,
            ],
            default      => [$now->copy()->subDays(29)->startOfDay(), $now],
        };
    }
}

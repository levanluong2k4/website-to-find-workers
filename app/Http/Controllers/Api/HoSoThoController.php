<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HoSoTho\UpdateHoSoThoRequest;
use App\Models\DonDatLich;
use App\Models\HoSoTho;
use Illuminate\Http\Request;

class HoSoThoController extends Controller
{
    public function index(Request $request)
    {
        $query = HoSoTho::with('user:id,name,email,avatar,phone,address', 'user.dichVus:id,ten_dich_vu')
            ->where('trang_thai_duyet', 'da_duyet')
            ->where('dang_hoat_dong', true);

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('dichVus', function ($q2) use ($keyword) {
                        $q2->where('ten_dich_vu', 'LIKE', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->category_id;
            $query->whereHas('user.dichVus', function ($q) use ($categoryId) {
                $q->where('dich_vu_id', $categoryId);
            });
        }

        $serviceIds = $request->input('service_ids', []);
        if (is_string($serviceIds)) {
            $serviceIds = array_filter(explode(',', $serviceIds));
        }

        $serviceIds = collect($serviceIds)
            ->filter(static fn ($value) => $value !== null && $value !== '')
            ->map(static fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        foreach ($serviceIds as $serviceId) {
            $query->whereHas('user.dichVus', function ($serviceQuery) use ($serviceId) {
                $serviceQuery->whereKey($serviceId);
            });
        }

        if ($request->filled('province')) {
            $province = $request->province;
            $query->whereHas('user', function ($q) use ($province) {
                $q->where('address', 'LIKE', "%{$province}%");
            });
        }

        $latitude = $request->lat;
        $longitude = $request->lng;

        if ($latitude && $longitude) {
            $storeLat = 12.2618;
            $storeLng = 109.1995;
            $haversine = "(6371 * acos(cos(radians($latitude)) * cos(radians({$storeLat})) * cos(radians({$storeLng}) - radians($longitude)) + sin(radians($latitude)) * sin(radians({$storeLat}))))";
            $query->selectRaw("ho_so_tho.*, {$haversine} AS distance");
        } else {
            $query->select('ho_so_tho.*');
        }

        if ($request->filled('ngay_hen') && $request->filled('khung_gio_hen')) {
            $ngayHen = $request->ngay_hen;
            $khungGioHen = DonDatLich::normalizeTimeSlot($request->khung_gio_hen);

            $query->whereDoesntHave('user.donDatLichAsTho', function ($q) use ($ngayHen, $khungGioHen) {
                $q->whereDate('ngay_hen', $ngayHen)
                    ->whereRaw("REPLACE(khung_gio_hen, ' ', '') = ?", [$khungGioHen])
                    ->whereIn('trang_thai', DonDatLich::scheduleBlockingStatuses());
            });
        }

        $query->orderByRaw("
            CASE trang_thai_hoat_dong
                WHEN 'dang_hoat_dong' THEN 1
                WHEN 'dang_ban' THEN 2
                WHEN 'ngung_hoat_dong' THEN 3
                WHEN 'tam_khoa' THEN 4
                ELSE 5
            END ASC
        ");

        if ($request->sort === 'nearest' && $latitude && $longitude) {
            $query->orderBy('distance', 'ASC');
        } elseif ($request->sort === 'rating') {
            $query->orderBy('danh_gia_trung_binh', 'DESC');
            $query->orderBy('tong_so_danh_gia', 'DESC');
        } else {
            $query->orderBy('tong_so_danh_gia', 'DESC');
            $query->orderBy('danh_gia_trung_binh', 'DESC');
        }

        return response()->json($query->paginate(15));
    }

    public function show(string $id)
    {
        $hoSo = HoSoTho::with([
            'user:id,name,email,avatar,phone,address',
            'user.dichVus',
            'user.danhGiasNhan.nguoiDanhGia:id,name,avatar',
        ])->where('user_id', $id)->first();

        if (!$hoSo) {
            return response()->json(['message' => 'Khong tim thay ho so tho'], 404);
        }

        return response()->json($hoSo);
    }

    public function busySlots(Request $request, string $id)
    {
        $hoSo = HoSoTho::where('user_id', $id)->first();

        if (!$hoSo) {
            return response()->json(['message' => 'Khong tim thay ho so tho'], 404);
        }

        $dateFrom = \Illuminate\Support\Carbon::parse($request->query('date_from', now()->toDateString()))->toDateString();
        $dateTo = \Illuminate\Support\Carbon::parse($request->query('date_to', now()->addDays(6)->toDateString()))->toDateString();

        if ($dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $busySlots = DonDatLich::query()
            ->select(['ngay_hen', 'khung_gio_hen'])
            ->where('tho_id', (int) $id)
            ->whereBetween('ngay_hen', [$dateFrom, $dateTo])
            ->whereIn('trang_thai', DonDatLich::scheduleBlockingStatuses())
            ->orderBy('ngay_hen')
            ->get()
            ->groupBy(fn (DonDatLich $booking) => optional($booking->ngay_hen)->toDateString() ?: (string) $booking->ngay_hen)
            ->map(fn ($items) => $items
                ->map(fn (DonDatLich $booking) => DonDatLich::normalizeTimeSlot($booking->khung_gio_hen))
                ->filter()
                ->unique()
                ->values()
                ->all()
            );

        return response()->json([
            'worker_id' => (int) $id,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'busy_slots' => $busySlots,
        ]);
    }

    public function update(UpdateHoSoThoRequest $request)
    {
        $user = $request->user();
        $hoSo = $user->hoSoTho;

        if (!$hoSo) {
            if ($user->role === 'admin') {
                $hoSo = HoSoTho::create([
                    'user_id' => $user->id,
                    'cccd' => 'ADMIN_PROFILE_' . $user->id,
                    'trang_thai_duyet' => 'da_duyet',
                    'dang_hoat_dong' => true,
                ]);
            } else {
                return response()->json(['message' => 'Khong tim thay ho so tho'], 404);
            }
        }

        $validated = $request->validated();
        $hasAvailabilityInput = $request->has('dang_hoat_dong');
        $nextAvailability = $hasAvailabilityInput
            ? (bool) $request->boolean('dang_hoat_dong')
            : (bool) $hoSo->dang_hoat_dong;
        $nextOperationalStatus = $hoSo->trang_thai_hoat_dong;

        if ($hasAvailabilityInput && $hoSo->trang_thai_hoat_dong !== 'tam_khoa') {
            $nextOperationalStatus = $nextAvailability ? 'dang_hoat_dong' : 'ngung_hoat_dong';
        }

        $hoSo->update([
            'cccd' => $validated['cccd'] ?? $hoSo->cccd,
            'kinh_nghiem' => $validated['kinh_nghiem'] ?? $hoSo->kinh_nghiem,
            'chung_chi' => $validated['chung_chi'] ?? $hoSo->chung_chi,
            'bang_gia_tham_khao' => $validated['bang_gia_tham_khao'] ?? $hoSo->bang_gia_tham_khao,
            'vi_do' => $validated['vi_do'] ?? $hoSo->vi_do,
            'kinh_do' => $validated['kinh_do'] ?? $hoSo->kinh_do,
            'ban_kinh_phuc_vu' => $validated['ban_kinh_phuc_vu'] ?? $hoSo->ban_kinh_phuc_vu,
            'dang_hoat_dong' => $nextAvailability,
            'trang_thai_hoat_dong' => $nextOperationalStatus,
        ]);

        if (isset($validated['dich_vu_ids'])) {
            $user->dichVus()->sync($validated['dich_vu_ids']);
        }

        $hoSo->load('user.dichVus');

        return response()->json([
            'message' => 'Cap nhat ho so thanh cong',
            'data' => $hoSo,
        ]);
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['worker', 'admin'], true)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $now = \Carbon\Carbon::now();
        $thisMonth = $now->month;
        $thisYear = $now->year;

        $completedBookings = \App\Models\DonDatLich::where('tho_id', $user->id)
            ->where('trang_thai', 'da_xong');

        $tongDoanhThu = (clone $completedBookings)->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(phi_di_lai, 0) + COALESCE(phi_linh_kien, 0)'));

        $doanhThuThangNay = (clone $completedBookings)
            ->whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(phi_di_lai, 0) + COALESCE(phi_linh_kien, 0)'));

        $soDonHoanThanhThangNay = (clone $completedBookings)
            ->whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->count();

        $soDonHuyThangNay = \App\Models\DonDatLich::where('tho_id', $user->id)
            ->where('trang_thai', 'da_huy')
            ->whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->count();

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateStr = $date->format('Y-m-d');

            $dayRevenue = \App\Models\DonDatLich::where('tho_id', $user->id)
                ->where('trang_thai', 'da_xong')
                ->whereDate('created_at', $dateStr)
                ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(phi_di_lai, 0) + COALESCE(phi_linh_kien, 0)'));

            $chartData[] = [
                'date' => $date->format('d/m'),
                'revenue' => (float) $dayRevenue,
            ];
        }

        return response()->json([
            'tong_doanh_thu' => $tongDoanhThu,
            'doanh_thu_thang_nay' => $doanhThuThangNay,
            'don_hoan_thanh_thang_nay' => $soDonHoanThanhThangNay,
            'don_huy_thang_nay' => $soDonHuyThangNay,
            'chart_data' => $chartData,
        ]);
    }
}

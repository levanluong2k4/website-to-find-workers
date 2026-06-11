<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\DonDatLich;
use App\Models\LinhKien;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MockDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Create 5 Customers
        $customerNames = [
            'Mai Tiến Dũng',
            'Nguyễn Thị Hoa',
            'Lê Văn Luyện',
            'Trần Đại Quang',
            'Phạm Văn Đồng'
        ];
        
        $customers = [];
        foreach ($customerNames as $name) {
            $baseEmail = strtolower(str_replace(' ', '', Str::ascii($name))) . '@gmail.com';
            $email = $baseEmail;
            $counter = 1;
            while (User::where('email', $email)->exists()) {
                $email = str_replace('@gmail.com', $counter . '@gmail.com', $baseEmail);
                $counter++;
            }
            
            $phone = '0' . rand(100000000, 999999999);
            
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('123456'),
                'phone' => $phone,
                'role' => 'customer',
                'is_active' => 1,
            ]);
            $customers[] = $user;
        }

        $this->command->info("Đã tạo 5 khách hàng thành công.");

        // Store location assumption for Lat/Lng near store (Nha Trang)
        $storeLat = 12.2388;
        $storeLng = 109.1967;

        // Workers
        $workers = User::where('role', 'worker')->get();
        if($workers->isEmpty()) {
            $this->command->info("Không có thợ nào trong DB, sẽ bỏ qua gán thợ.");
        }

        $linhKiens = LinhKien::inRandomOrder()->limit(10)->get();

        $khungGioHenList = [
            '08:00 - 10:00',
            '10:00 - 12:00',
            '13:00 - 15:00',
            '15:00 - 17:00'
        ];

        $dichVus = \Illuminate\Support\Facades\DB::table('danh_muc_dich_vu')->get();

        // 2. Create 20 orders
        for ($i = 0; $i < 20; $i++) {
            $customer = $customers[array_rand($customers)];
            $baseTime = Carbon::now()->subDays(rand(1, 5))->setTime(rand(6, 18), rand(0, 59));
            $ngayHen = $baseTime->copy()->addDays(rand(1, 2));
            $khungGio = $khungGioHenList[array_rand($khungGioHenList)];
            
            $startTimeStr = trim(explode('-', $khungGio)[0]);
            $endTimeStr = trim(explode('-', $khungGio)[1]);
            
            $thoiGianHen = Carbon::parse($ngayHen->format('Y-m-d') . ' ' . $startTimeStr);
            $thoiGianHoanThanh = Carbon::parse($ngayHen->format('Y-m-d') . ' ' . $endTimeStr);
            
            // Random around Nha Trang (roughly 5km is ~0.045 degrees)
            $lat = $storeLat + (rand(-450, 450) / 10000);
            $lng = $storeLng + (rand(-450, 450) / 10000);

            // 15 completed orders, 5 pending
            $isCompleted = $i < 15;
            $status = $isCompleted ? 'da_xong' : 'cho_xac_nhan';
            
            // 10 cash, 5 online for completed
            // If i < 10 -> cash, i < 15 -> online
            $paymentMethod = $i < 10 ? 'cod' : 'transfer';
            if (!$isCompleted) {
                $paymentMethod = 'cod';
            }
            
            $thoId = null;
            if ($isCompleted && $workers->isNotEmpty()) {
                // random worker
                $thoId = $workers->random()->id;
            }
            
            $tienCong = $isCompleted ? rand(50, 500) * 1000 : 0;
            $chiTietLinhKien = [];
            $phiLinhKien = 0;

            if ($isCompleted && $linhKiens->isNotEmpty()) {
                $numLinhKien = rand(1, 3);
                $selectedLK = $linhKiens->random($numLinhKien);
                foreach ($selectedLK as $lk) {
                    $qty = rand(1, 2);
                    $chiTietLinhKien[] = [
                        'id' => $lk->id,
                        'ten_linh_kien' => $lk->ten_linh_kien,
                        'gia' => $lk->gia,
                        'so_luong' => $qty,
                        'thanh_tien' => $lk->gia * $qty
                    ];
                    $phiLinhKien += $lk->gia * $qty;
                }
            }

            $tongTien = $tienCong + $phiLinhKien;

            $orderId = \Illuminate\Support\Facades\DB::table('don_dat_lich')->insertGetId([
                'khach_hang_id' => $customer->id,
                'tho_id' => $thoId,
                'loai_dat_lich' => 'at_home',
                'ngay_hen' => $ngayHen->format('Y-m-d'),
                'khung_gio_hen' => $khungGio,
                'dia_chi' => 'Đường ngẫu nhiên số ' . rand(1, 100) . ', Nha Trang',
                'vi_do' => $lat,
                'kinh_do' => $lng,
                'mo_ta_van_de' => 'Máy gặp sự cố ngẫu nhiên loại ' . $i,
                'trang_thai' => $status,
                'tien_cong' => $tienCong,
                'chi_tiet_tien_cong' => $isCompleted ? json_encode([['ten' => 'Công kiểm tra sửa chữa', 'gia' => $tienCong]]) : null,
                'phi_linh_kien' => $phiLinhKien,
                'chi_tiet_linh_kien' => $isCompleted ? json_encode($chiTietLinhKien) : null,
                'tong_tien' => $tongTien,
                'phuong_thuc_thanh_toan' => $paymentMethod,
                'trang_thai_thanh_toan' => $isCompleted ? 1 : 0,
                'thoi_gian_bat_dau_sua' => $isCompleted ? $thoiGianHen : null,
                'thoi_gian_hoan_thanh' => $isCompleted ? $thoiGianHoanThanh : null,
                'thoi_gian_hen' => $thoiGianHen,
                'created_at' => $baseTime,
                'updated_at' => $isCompleted ? $thoiGianHoanThanh : Carbon::now(),
            ]);

            if ($dichVus->isNotEmpty()) {
                $dv = $dichVus->random();
                \Illuminate\Support\Facades\DB::table('don_dat_lich_dich_vu')->insert([
                    'don_dat_lich_id' => $orderId,
                    'dich_vu_id' => $dv->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            if ($isCompleted) {
                \Illuminate\Support\Facades\DB::table('thanh_toan')->insert([
                    'don_dat_lich_id' => $orderId,
                    'so_tien' => $tongTien,
                    'phuong_thuc' => $paymentMethod == 'cod' ? 'cash' : 'vnpay',
                    'ma_giao_dich' => 'TXN' . rand(100000, 999999),
                    'trang_thai' => 'success',
                    'created_at' => $thoiGianHoanThanh,
                    'updated_at' => $thoiGianHoanThanh,
                ]);
            }
        }

        $this->command->info("Đã tạo 20 đơn đặt lịch (15 hoàn thành, 5 chờ) thành công.");
    }
}

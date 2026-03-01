<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('vi_VN');

        // Danh sách trạng thái
        $statuses = ['dang_hoat_dong', 'dang_hoat_dong', 'dang_ban', 'ngung_hoat_dong'];

        // Danh sách tỉnh thành giả lập
        $provinces = ['Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Cần Thơ', 'Hải Phòng'];

        // Lấy tất cả ID danh mục dịch vụ hiện có để gán ngẫu nhiên
        $dichVuIds = \App\Models\DanhMucDichVu::pluck('id')->toArray();
        if (empty($dichVuIds)) {
            // Nếu DB chưa có dịch vụ nào, tạo mock 5 dịch vụ
            for ($i = 1; $i <= 5; $i++) {
                $dichVu = \App\Models\DanhMucDichVu::create([
                    'ten_danh_muc' => "Dịch vụ $i",
                    'mo_ta' => "Mô tả $i",
                    'the_loai' => 'sua_chua'
                ]);
                $dichVuIds[] = $dichVu->id;
            }
        }

        // Tạo 20 thợ
        for ($i = 0; $i < 20; $i++) {
            // Bước 1: Tạo User
            $province = $faker->randomElement($provinces);
            $user = \App\Models\User::create([
                'name' => 'Thợ ' . $faker->name,
                'email' => "tho_{$i}_" . time() . "@findworker.vn",
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                'phone' => $faker->numerify('09########'),
                'address' => $faker->streetAddress . ", " . $province,
                'avatar' => null, // Có thể để null, frontend dùng ảnh /assets/images/customer.png
                'role' => 'worker',
                'is_active' => true,
            ]);

            // Toạ độ giả lập (quanh Việt Nam, tập trung HN/HCM)
            // HCM: ~10.762622, 106.660172 (+- 0.5)
            // HN: ~21.027764, 105.834160 (+- 0.5)
            $isHCM = $faker->boolean(60);
            $lat = $isHCM ? $faker->latitude(10.2, 11.2) : $faker->latitude(20.5, 21.5);
            $lng = $isHCM ? $faker->longitude(106.1, 107.1) : $faker->longitude(105.3, 106.3);

            // Bước 2: Tạo HoSoTho
            $hoSoTho = \App\Models\HoSoTho::create([
                'user_id' => $user->id,
                'cccd' => $faker->numerify('079###########'),
                'kinh_nghiem' => $faker->numberBetween(1, 15) . ' năm kinh nghiệm sửa chữa điện lạnh',
                'chung_chi' => 'Chứng chỉ nghề Bậc ' . $faker->numberBetween(3, 7),
                'bang_gia_tham_khao' => $faker->numberBetween(150, 500) . '.000 VNĐ / Lần kiểm tra',
                'vi_do' => $lat,
                'kinh_do' => $lng,
                'ban_kinh_phuc_vu' => $faker->numberBetween(10, 50), // km
                'trang_thai_duyet' => 'da_duyet',
                'dang_hoat_dong' => true,
                'trang_thai_hoat_dong' => $faker->randomElement($statuses),
                'danh_gia_trung_binh' => $faker->randomFloat(1, 3.5, 5.0),
                'tong_so_danh_gia' => $faker->numberBetween(5, 500),
            ]);

            // Bước 3: Gắn Dịch Vụ
            $numServices = min(count($dichVuIds), $faker->numberBetween(1, 3));
            $randomServices = $faker->randomElements($dichVuIds, $numServices);
            $user->dichVus()->attach($randomServices);
        }
    }
}

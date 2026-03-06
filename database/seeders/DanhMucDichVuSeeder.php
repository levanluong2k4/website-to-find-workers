<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\DanhMucDichVu;

class DanhMucDichVuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $danhMucs = [
            [
                'ten_dich_vu' => 'Sửa điều hòa (Máy lạnh)',
                'mo_ta' => 'Sửa chữa, bảo dưỡng, bơm ga điều hòa treo tường, âm trần,...',
                'hinh_anh' => 'ac_unit',
                'trang_thai' => true
            ],
            [
                'ten_dich_vu' => 'Sửa tủ lạnh',
                'mo_ta' => 'Sửa tủ lạnh không đông đá, chảy nước, kêu to, hỏng block,...',
                'hinh_anh' => 'kitchen',
                'trang_thai' => true
            ],
            [
                'ten_dich_vu' => 'Sửa máy giặt',
                'mo_ta' => 'Sửa máy giặt lồng ngang, lồng đứng, không vắt, báo lỗi bo mạch,...',
                'hinh_anh' => 'local_laundry_service',
                'trang_thai' => true
            ],
            [
                'ten_dich_vu' => 'Sửa quạt điện',
                'mo_ta' => 'Sửa quạt trần, quạt bàn, quạt hơi nước không quay, đứt dây,...',
                'hinh_anh' => 'mode_fan',
                'trang_thai' => true
            ],
            [
                'ten_dich_vu' => 'Sửa máy nước nóng',
                'mo_ta' => 'Sửa bình nóng lạnh không ra nước nóng, rò điện, hỏng sợi đốt,...',
                'hinh_anh' => 'water_damage',
                'trang_thai' => true
            ],
            [
                'ten_dich_vu' => 'Sửa lò vi sóng',
                'mo_ta' => 'Sửa lò vi sóng không nóng, đĩa không quay, hỏng phím bấm,...',
                'hinh_anh' => 'microwave',
                'trang_thai' => true
            ],
            [
                'ten_dich_vu' => 'Sửa tivi',
                'mo_ta' => 'Sửa tivi mất nguồn, tối nửa màn hình, hỏng LED, lỗi board mạch,...',
                'hinh_anh' => 'tv',
                'trang_thai' => true
            ],
            [
                'ten_dich_vu' => 'Điện nước dân dụng',
                'mo_ta' => 'Xử lý chập điện, mất điện cục bộ, rò rỉ ống nước, thay bóng đèn,...',
                'hinh_anh' => 'plumbing',
                'trang_thai' => true
            ]
        ];

        foreach ($danhMucs as $dm) {
            DanhMucDichVu::updateOrCreate(
                ['ten_dich_vu' => $dm['ten_dich_vu']],
                $dm
            );
        }
    }
}

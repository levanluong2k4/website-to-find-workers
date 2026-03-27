<?php

namespace Database\Seeders;

use App\Models\DanhMucDichVu;
use App\Models\LinhKien;
use Illuminate\Database\Seeder;

class LinhKienSeeder extends Seeder
{
    public function run(): void
    {
        $service = DanhMucDichVu::query()->updateOrCreate(
            ['ten_dich_vu' => 'Sửa điều hòa (Máy lạnh)'],
            [
                'mo_ta' => 'Sửa chữa, bảo dưỡng, bơm ga điều hòa treo tường, âm trần,...',
                'hinh_anh' => 'ac_unit',
                'trang_thai' => true,
            ]
        );

        foreach ($this->airConditionerParts() as $part) {
            LinhKien::query()->updateOrCreate(
                [
                    'dich_vu_id' => $service->id,
                    'ten_linh_kien' => $part['ten_linh_kien'],
                ],
                [
                    'hinh_anh' => $part['hinh_anh'],
                    'gia' => $part['gia'],
                ]
            );
        }
    }

    private function airConditionerParts(): array
    {
        return array_map(function (array $row): array {
            return [
                'ten_linh_kien' => $row[0],
                'hinh_anh' => $this->normalizeImage($row[1] ?? null),
                'gia' => $this->normalizePrice($row[2] ?? null),
            ];
        }, [
            ['( SP1876 ) Bo Mắt Nhận LG Mẫu 6', 'Không có dữ liệu', '330,000'],
            ['( SP1875 ) Hall Đếm , Mạch Xung Của Quạt AC Dàn Lạnh LG 7 Dây', 'Không có dữ liệu', '130,000'],
            ['( SP1870 ) Bộ 2 Con Sensor Dàn Lạnh Vàng Đen Dùng Chung Nhiều Hãng', 'Không có dữ liệu', '130,000'],
            ['( SP1869 ) Mắt Dò Chuyển Động - Bo Nano', 'Không có dữ liệu', 'Xem Bên Dưới'],
            ['( SP1868 ) Bo Nóng Samsung Mã DB92 02867', 'Không có dữ liệu', '2,670,000'],
            ['( SP1867 ) Bo Nóng Samsung Mã DB92 02866', 'Không có dữ liệu', '970,000'],
            ['( SP1865 ) Bo Mắt Nhận Beko Và Dùng Chung Nhiều Hãng Mẫu 03', 'Không có dữ liệu', '390,000'],
            ['( SP1863 ) Bo Dàn Lạnh Fujitsu Mẫu 02 Rắc Quạt Lớn', 'Không có dữ liệu', '880,000'],
            ['( SP1862 ) Quạt Dàn Lạnh Mitsubishi Electric 30W', 'Không có dữ liệu', '530,000'],
            ['( SP1860 ) Bo Dàn Nóng Máy Lạnh Inverter Đời Cũ 1 Bo Chạy IPM STK621-033', 'Không có dữ liệu', '640,000'],
            ['( SP1859 ) Bộ Bo Dàn Nóng Máy Lạnh Inverter Đời Cũ 2 Bo Chạy IPM STK621-043', 'Không có dữ liệu', '640,000'],
            ['( SP1857 ) Bo Dàn Lạnh Dùng Quạt DC 3 Dây Dùng Chung Nhiều Hãng Mẫu Số 19', 'Không có dữ liệu', '790,000'],
            ['( SP1856 ) Quạt Dàn Lạnh Casper Loại 3 Dây DC', 'Không có dữ liệu', '690,000'],
            ['( SP1854 ) Bo Nóng Samsung 2 Tụ Vuông Xanh', 'Không có dữ liệu', '780,000'],
            ['C-1RZ107H1AG', 'Không có dữ liệu', '1,780,000'],
            ['1YC20JXD / 1YC20HXD', 'Không có dữ liệu', '1,130,000'],
            ['9GS075XBA21 / 9GS075XDA21', 'Không có dữ liệu', '1,130,000'],
            ['1YC36DXD', 'Không có dữ liệu', '1,880,000'],
            ['9RS120XBA21', 'Không có dữ liệu', '1,830,000'],
            ['DST102MAA', 'Không có dữ liệu', '1,030,000'],
            ['1YC15AXD', 'Không có dữ liệu', '980,000'],
            ['GSD088RKQA6J', 'Không có dữ liệu', '1,030,000'],
            ['35A25LY', 'Không có dữ liệu', '980,000'],
            ['ASN98D32UEZ', 'Không có dữ liệu', '1,050,000'],
            ['GS089MAA', 'Không có dữ liệu', '1,130,000'],
            ['( SP1842 ) Bộ Bo Lạnh Mitsubishi Electric Inverter Quạt DC', 'Không có dữ liệu', '580,000'],
            ['( SP1841 ) Mắt Nhận Gree , Aqua 2 Bẹ Cáp Dùng Chung Tất Cả Các Mắt 2 Bẹ Cáp Dài', 'Không có dữ liệu', '160,000'],
            ['( SP1840 ) Motor Đảo Gió Gree , Aqua', 'Không có dữ liệu', '140,000'],
            ['( SP1839 ) Quạt Dàn Lạnh FN20J Hãng Gree , Aqua Và Chung Nhiều Hãng Khác 20W', 'Không có dữ liệu', '390,000'],
            ['( SP1173B ) Bo Điều Hòa Aqua , Gree Không Inverter 2 Chiều', 'Không có dữ liệu', '530,000'],
            ['( SP1830 ) Bộ 3 Con Sensor Dàn Nóng Điều Hòa Gree Aqua Electrolux', 'Không có dữ liệu', '150,000'],
            ['( SP1829 ) Đoạn Dây Kết Nối Bo Mắt Nhận LG', 'Không có dữ liệu', '140,000'],
            ['( SP1826 ) Bo Nóng Toshiba Dài Quạt DC 3 Sensor Gas R32 Máy 1.0Hp Và 1.5Hp', 'Không có dữ liệu', '1,490,000'],
            ['( SP1821 ) Bo Dàn Lạnh Inverter Dùng Chung Nhiều Hãng Mẫu Số 18', 'Không có dữ liệu', '780,000'],
            ['( SP1820 ) Bo Dàn Lạnh Máy Inverter Dùng Chung Nhiều Hãng ( Mẫu 1 Nguồn Rung )', 'Không có dữ liệu', '480,000'],
            ['( SP1819 ) Bo Nóng Toshiba Dài Quạt DC 4 Sensor Gas R32 Máy 1.0Hp Và 1.5Hp', 'Không có dữ liệu', '1,490,000'],
            ['( SP1818 ) Quạt Dàn Lạnh DC Aqua Sanyo', 'Không có dữ liệu', '680,000'],
            ['( SP1817 ) Quạt Dàn Lạnh DC 35Vol', 'Không có dữ liệu', '550,000'],
            ['( SP1816 ) Bo Dàn Nóng Dùng Chung Nhiều Hãng Mẫu Số 18 Quạt AC', 'Không có dữ liệu', '1,480,000'],
            ['( SP1811 ) Quạt Dàn Nóng LG Loại DC 3 Dây', 'Không có dữ liệu', '960,000'],
            ['( SP1810 ) Quạt Điều Hòa B-SWZ150A8 Công Suất 150W', 'Không có dữ liệu', 'Hàng Có Sẵn'],
            ['( SP1809 ) Quạt Điều Hòa B-FN35C-ZL Công Suất 35W', 'Không có dữ liệu', 'Hàng Có Sẵn'],
            ['( SP1805 ) Bo Dàn Nóng Dùng Chung Nhiều Hãng Mẫu Số 17 Quạt AC', 'Không có dữ liệu', '1,280,000'],
            ['( SP1803 ) Bo Mắt Nhận Điều Hòa Dùng Chung Nhiều Hãng Mẫu 2 Rắc 13 Chân', 'Không có dữ liệu', '480,000'],
            ['( SP1802 ) Bo Dàn Lạnh Máy Không Inverter Dùng Chung Nhiều Hãng Mẫu Số 16', 'Không có dữ liệu', '750,000'],
            ['( SP1801 ) Bo Dàn Nóng Dùng Chung Nhiều Hãng Mẫu Số 16 Quạt DC', 'Không có dữ liệu', '1,530,000'],
            ['( SP1800 ) Quạt Dàn Lạnh Điều Hòa 28W Dùng Chung Nhiều Hãng', 'Không có dữ liệu', '780,000'],
            ['( SP1799 ) Bo Dàn Lạnh Dùng Chung Nhiều Hãng Mẫu Số 15', 'Không có dữ liệu', '1,180,000'],
            ['( SP1792 ) Bo Lạnh Quạt DC Model Dòng XKH-8', 'Không có dữ liệu', '880,000'],
            ['( SP1790 ) Bo Nóng Quạt DC 3 Dây Hãng Gree , Aqua Và Nhiều Hãng Khác', 'Không có dữ liệu', '1,560,000'],
            ['( SP1785 ) Quạt Mã 4681A20168A - EAU32165801', 'Không có dữ liệu', '930,000'],
            ['( SP1784 ) Bo Dàn Lạnh Sharp Inverter 2.0Hp Và 2.5Hp', 'Không có dữ liệu', '950,000'],
            ['( SP1783 ) Bo Dàn Nóng Sharp 2.0Hp Và 2.5Hp R32 Loại Bo Dùng 5 Sensor', 'Không có dữ liệu', '1,980,000'],
            ['( SP1778 ) Quạt Dàn Lạnh DC 5 Dây 21W - 30W - 38W Dùng Cho Điều Hòa Hitachi', 'Không có dữ liệu', '690,000'],
            ['( SP1776 ) Quạt Dàn Nóng AC220V 25W Quay Thuận', 'Không có dữ liệu', '580,000'],
        ]);
    }

    private function normalizeImage(?string $value): ?string
    {
        $normalized = trim((string) $value);

        if ($normalized === '' || mb_strtolower($normalized) === 'không có dữ liệu') {
            return null;
        }

        return $normalized;
    }

    private function normalizePrice(?string $value): ?float
    {
        $normalized = trim((string) $value);

        if ($normalized === '' || !preg_match('/\d/', $normalized)) {
            return null;
        }

        $normalized = preg_replace('/[^\d]/', '', $normalized);

        return $normalized === '' ? null : (float) $normalized;
    }
}

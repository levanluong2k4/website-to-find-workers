<?php

namespace Database\Seeders;

use App\Models\HuongXuLy;
use App\Models\NguyenNhan;
use App\Models\TrieuChung;
use Illuminate\Database\Seeder;

class TvRepairKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceId = 12; // Sửa Tivi

        // 1. Trieu Chung (Symptoms)
        $symptoms = [
            1 => 'Tivi không lên nguồn, bật không lên, hoặc đèn báo nguồn chập chờn',
            2 => 'Tivi có tín hiệu bật nhưng màn hình tối đen, tối hình, mờ (nhìn kỹ mới thấy)',
            3 => 'Tivi có tiếng nhưng không hiển thị hình ảnh, hoặc nửa sáng nửa tối',
            4 => 'Tivi tự động bật tắt liên tục, chạy 2-5 giây rồi tự tắt, treo logo',
            5 => 'Màn hình bị sọc dọc, sọc ngang, có hạt mưa, hạt mè hoặc đốm bầm',
            6 => 'Màn hình xuất hiện nốt đen to, điểm chết, hoặc bị trắng sáng toàn bộ',
            7 => 'Màu sắc tivi bị thay đổi, vàng ố, vết ẩm trên diện rộng',
            8 => 'Tivi phát tiếng kêu to bất thường, loa bị rè, méo tiếng, lúc to lúc nhỏ',
            9 => 'Tivi không nhận tất cả các kênh, mất kênh, thiếu kênh',
            10 => 'Tivi không nhận tín hiệu từ remote, kính 3D không hoạt động',
            11 => 'Hình ảnh và âm thanh không khớp, hình bị giãn, không đầy màn hình',
            12 => 'Lỗi phần mềm: Không gõ được số, sai phông phụ đề, hiện quảng cáo liên tục',
            13 => 'Tivi Sony nháy đèn đỏ báo lỗi (Nháy 1, 2, 6, 8, 13 lần)',
            14 => 'Tivi Samsung báo mã lỗi mạng/Smart Hub (0-1, 7-1, 012, 102, 105, 107, 118...)',
        ];

        $symptomIds = [];
        foreach ($symptoms as $index => $name) {
            $symptomIds[$index] = TrieuChung::updateOrCreate(
                ['dich_vu_id' => $serviceId, 'ten_trieu_chung' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            )->id;
        }

        // 2. Nguyen Nhan (Causes)
        $causes = [
            1 => 'Lỗi Bo cấp nguồn (Power board): Bị sét đánh, vô nước, oxy hóa, chập cháy cầu chì',
            2 => 'Lỗi Bo cao áp: Chạm mạch, hư hỏng hệ thống cao áp khiến đèn không sáng',
            3 => 'Lỗi Bo mạch chính (Mainboard) / Bo xử lý: Chip xử lý lỗi, hỏng vi mạch',
            4 => 'Lỗi Màn hình (Panel) / Đèn hình: Va đập vật lý gây vỡ, hỏng đèn nền',
            5 => 'Lỗi cáp kết nối (Cap tivi): Lỏng, đứt, oxy hóa lớp keo dẫn điện từ Mainboard lên màn hình',
            6 => 'Lỗi Bo Tcom hoặc mạch LVDS điều khiển xuất hình ảnh',
            7 => 'Lỗi phần cứng âm thanh: Màng loa rách, đứt/chập cuộn dây loa, bụi bám màng loa',
            8 => 'Lỗi cổng tín hiệu ngoại vi: Dây cáp HDMI/AV lỏng, gãy, rỉ sét hoặc kẹt ăng-ten',
            9 => 'Lỗi nguồn điện/Nhiễu sóng: Điện áp chập chờn, đặt gần loa wifi/điện thoại gây nhiễu',
            10 => 'Lỗi mạng/Smart Hub: Đường truyền kém, cấu hình IP/DNS sai, lỗi kết nối máy chủ',
            11 => 'Lỗi phần mềm/Cài đặt: Sai chế độ âm thanh/hình ảnh, lỗi phông chữ, chưa update Firmware',
            12 => 'Lỗi Remote / Mắt hồng ngoại: Hết pin, cửa sổ truyền tín hiệu bị bẩn, hỏng mắt nhận',
            13 => 'Lỗi cập nhật phần mềm thất bại, xung đột ứng dụng hoặc tràn bộ nhớ tivi',
        ];

        $causeIds = [];
        foreach ($causes as $index => $name) {
            $causeIds[$index] = NguyenNhan::updateOrCreate(
                ['ten_nguyen_nhan' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            )->id;
        }

        // 3. Huong Xu Ly (Resolutions)
        $resolutions = [
            ['cause_index' => 1, 'ten' => 'Kiểm tra dây nguồn và thay cầu chì', 'desc' => 'Rút dây nguồn 5 phút, kiểm tra ổ điện, dây điện có gấp khúc đứt gãy không. Cắm lại hoặc thay cầu chì.', 'price' => null],
            ['cause_index' => 1, 'ten' => 'Sửa chữa hoặc thay Bo mạch nguồn', 'desc' => 'Sửa chữa linh kiện chập cháy trên bo hoặc thay bo nguồn mới. Khắc phục triệt để Tivi Sony nháy đèn đỏ 2 lần, 8 lần.', 'price' => 400000.00],
            ['cause_index' => 2, 'ten' => 'Sửa chữa hoặc thay Bo cao áp', 'desc' => 'Thợ tháo rời tivi thay thế hệ thống cao áp chạm mạch. Khắc phục tivi có tiếng không hình, màn nửa sáng nửa tối, Tivi Sony nháy đèn 6 lần.', 'price' => null],
            ['cause_index' => 3, 'ten' => 'Sửa chữa hoặc thay Mainboard (Bo mạch chính)', 'desc' => 'Kiểm tra và sửa chữa chip xử lý hoặc thay Mainboard mới. Khắc phục lỗi Tivi Sony nháy đèn 1 lần, treo logo.', 'price' => 800000.00],
            ['cause_index' => 4, 'ten' => 'Xử lý điểm chết/vết bẩn màn hình', 'desc' => 'Lấy khăn mềm lau nhẹ vào điểm đen để xác định là vết bẩn hay điểm chết thật sự. Tránh đè mạnh gây hỏng panel.', 'price' => null],
            ['cause_index' => 4, 'ten' => 'Thay màn hình Tivi mới', 'desc' => 'Thay thế Panel màn hình do nứt vỡ, đốm đen to.', 'price' => 1200000.00],
            ['cause_index' => 5, 'ten' => 'Cắm lại hoặc thay Dây cáp Tivi (Cap màn hình)', 'desc' => 'Tháo máy, vệ sinh rắc cắm, cắm chặt lại cáp kết nối từ main lên màn hình, hoặc thay cáp mới do lớp keo bị oxy hóa. Khắc phục lỗi sọc dọc màn hình.', 'price' => null],
            ['cause_index' => 6, 'ten' => 'Thay Bo Tcom / Mạch LVDS', 'desc' => 'Thay mạch LVDS điều khiển hình ảnh. Khắc phục lỗi tivi trắng sáng toàn bộ màn hình, nhòe màu, sọc ngang dọc hoặc Tivi Sony nháy đèn 13 lần.', 'price' => null],
            ['cause_index' => 7, 'ten' => 'Vệ sinh khe loa', 'desc' => 'Dùng khăn khô mềm vệ sinh bụi bẩn bám ở khe loa (tuyệt đối không dùng nước ẩm).', 'price' => null],
            ['cause_index' => 7, 'ten' => 'Sửa mạch âm thanh hoặc Thay Loa tivi', 'desc' => 'Thay loa mới nếu màng loa rách, đứt cuộn dây, hoặc sửa IC âm thanh.', 'price' => 300000.00],
            ['cause_index' => 8, 'ten' => 'Kiểm tra ăng-ten, thay cổng HDMI/AV', 'desc' => 'Cắm chặt lại dây cáp HDMI/AV, đổi nguồn phát khác. Xử lý lỗi tivi không nhận tín hiệu.', 'price' => 250000.00],
            ['cause_index' => 9, 'ten' => 'Dùng ổn áp, ngắt thiết bị gây nhiễu', 'desc' => 'Lắp thêm ổn áp điện. Tắt bớt loa bluetooth, điện thoại đặt gần tivi để loại trừ sóng từ trường gây nhiễu rè âm thanh.', 'price' => null],
            ['cause_index' => 10, 'ten' => 'Reset Smart Hub và Cấu hình DNS', 'desc' => 'Tắt Router wifi 5-10 phút. Vào Cài đặt mạng -> Đổi DNS thủ công thành 8.8.8.8. Khôi phục lại Smart Hub để trị mã lỗi 0-1, 7-1, 012, 102, 118, 301.', 'price' => null],
            ['cause_index' => 11, 'ten' => 'Đổi ngôn ngữ bàn phím và định dạng phụ đề', 'desc' => 'Lỗi gõ số ra ký tự: Cài đặt bàn phím -> Chọn English (US International). Lỗi phụ đề: Đổi file sang .srt/.sub, trùng tên video và dùng phông Unicode.', 'price' => null],
            ['cause_index' => 11, 'ten' => 'Tắt chế độ cửa hàng (Quảng cáo)', 'desc' => 'Vào Hệ thống -> Nhập PIN 0000 -> Đổi từ chế độ Trưng bày (Cửa hàng) sang chế độ Tại nhà.', 'price' => null],
            ['cause_index' => 12, 'ten' => 'Thay pin, vệ sinh mắt hồng ngoại', 'desc' => 'Thay pin cho Remote hoặc kính 3D. Đồng bộ lại kính 3D với tivi bằng nút nguồn. Lau sạch mắt nhận tín hiệu trên tivi.', 'price' => null],
            ['cause_index' => 13, 'ten' => 'Hard Reset / Cập nhật Firmware qua USB', 'desc' => 'Khôi phục cài đặt gốc, xóa bớt ứng dụng gây xung đột, hoặc tải Firmware mới nhất vào USB để cập nhật lại hệ điều hành cho tivi.', 'price' => null],
        ];

        foreach ($resolutions as $res) {
            HuongXuLy::updateOrCreate(
                [
                    'nguyen_nhan_id' => $causeIds[$res['cause_index']],
                    'ten_huong_xu_ly' => $res['ten']
                ],
                [
                    'mo_ta_cong_viec' => $res['desc'],
                    'gia_tham_khao' => $res['price'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // 4. Mapping Symptoms to Causes (Inferred)
        $mapping = [
            1 => [1, 3, 9],
            2 => [2, 4, 5, 6],
            3 => [2, 4, 6],
            4 => [3, 13],
            5 => [4, 5, 6],
            6 => [4, 5, 6],
            7 => [4, 6],
            8 => [7, 9],
            9 => [8, 3],
            10 => [12, 3],
            11 => [11, 3],
            12 => [11, 13],
            13 => [1, 2, 3, 6],
            14 => [10],
        ];

        foreach ($mapping as $sIndex => $cIndexes) {
            $symptom = TrieuChung::find($symptomIds[$sIndex]);
            if ($symptom) {
                $attachIds = [];
                foreach ($cIndexes as $cIndex) {
                    $attachIds[] = $causeIds[$cIndex];
                }
                $symptom->nguyenNhans()->syncWithoutDetaching($attachIds);
            }
        }
    }
}

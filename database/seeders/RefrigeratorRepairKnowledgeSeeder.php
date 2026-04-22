<?php

namespace Database\Seeders;

use App\Models\HuongXuLy;
use App\Models\NguyenNhan;
use App\Models\TrieuChung;
use Illuminate\Database\Seeder;

class RefrigeratorRepairKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceId = 7; // Sửa Tủ Lạnh

        // 1. Trieu Chung (Symptoms)
        $symptoms = [
            1 => 'Tủ lạnh không lạnh, kém lạnh, thời gian làm lạnh kéo dài bất thường',
            2 => 'Tủ lạnh bám tuyết dày đặc dàn lạnh, bị đóng đá, không tự xả tuyết',
            3 => 'Block (máy nén) không chạy, kêu to, nóng bất thường hoặc đóng/ngắt liên tục',
            4 => 'Tủ lạnh mất nguồn, đèn không sáng, không có bất kỳ tín hiệu hoạt động nào',
            5 => 'Tủ lạnh phát ra tiếng ồn lớn, kêu to (tiếng gõ, tiếng è è, rung lắc mạnh)',
            6 => 'Tủ lạnh bị rò rỉ nước ra sàn, đọng sương (ra mồ hôi) ở vỏ hoặc mép cửa tủ',
            7 => 'Tủ lạnh bị rò rỉ điện, chạm vỏ kim loại gây giật',
            8 => 'Mã lỗi tủ lạnh LG Inverter hiển thị màn hình (Er FF, Er rF, Er IF, Er dH, Er CO, Er CF, Er HS, Er dS...)',
            9 => 'Mã lỗi tủ lạnh LG không màn hình báo bằng số lần nháy đèn (nháy 1-14 lần) hoặc tiếng bíp',
            10 => 'Mã lỗi tủ lạnh Samsung Inverter & Side by Side (22E, 24E, 84C, 39E, 88 88, E1, F0, F1, PC ER...)',
            11 => 'Mã lỗi tủ lạnh Samsung báo bằng số lần nháy đèn trên bo mạch (Nháy 2, 3, 6, 9, 10, 11, 14 lần)',
            12 => 'Mã lỗi tủ lạnh Panasonic nội địa Nhật (U04, U10, U11, H01 đến H64, H97, H98...)',
            13 => 'Mã lỗi tủ lạnh Toshiba (F1, F2, F4, F5, F9...)',
            14 => 'Mã lỗi tủ lạnh Hitachi (F0-01, F0-12, F0-21, F0-22...)',
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
            1 => 'Thiếu gas, rò rỉ gas do bục dàn, hở rắc-co, thủng ống đồng (Làm lạnh yếu, block chạy liên tục)',
            2 => 'Tắc ẩm, tắc bẩn hệ thống (Gây nghẹt cáp, sương bám ống, tắc phin lọc lạnh)',
            3 => 'Hỏng Block (máy nén) do kẹt cơ, đứt cuộn dây, quá tải hoặc hỏng tụ điện máy nén',
            4 => 'Hỏng Rơ le khởi động (Thermic) hoặc hỏng Thermostat (Rơ le nhiệt, đo sai trị số)',
            5 => 'Hỏng hệ thống xả đá (Hỏng Rơ le âm/dương, Timer, đứt điện trở xả đá)',
            6 => 'Hỏng Bo mạch điều khiển (Inverter/Công suất) do chập cháy, IC hỏng, lỗi EPROM',
            7 => 'Hỏng Quạt gió dàn lạnh/dàn nóng (Đứt dây, kẹt tuyết, cháy motor quạt)',
            8 => 'Hỏng Cảm biến nhiệt độ (Sensor) do đứt dây hoặc đo sai trị số điện trở',
            9 => 'Vỏ tủ không kín do ron (zoăng) cao su bị lão hóa, rách, bản lề xệ, cửa hở',
            10 => 'Tủ chứa quá nhiều thực phẩm che khuất gió, vị trí đặt tủ hẹp không tản được nhiệt',
            11 => 'Tắc nghẽn ống thoát nước xả đá, đĩa hứng nước thải bị nứt/lệch gây rò rỉ nước',
            12 => 'Lỗi LG (Er FF, rF, IF, CF, dS, HS): Hỏng quạt dàn lạnh/ngăn mát/lốc, cảm biến độ ẩm/cảm biến cửa',
            13 => 'Lỗi LG (Er dH, Er CO): Hỏng cảm biến xả đá, đứt dây giao tiếp giữa bo chính và bo hiển thị',
            14 => 'Lỗi LG (Đèn nháy/Bíp): 5 bíp (Cảm biến đá), 1 bíp dài (Lốc), 7 nháy (Quạt), 3 nháy (Phá băng), 6 nháy (Bo mạch)',
            15 => 'Lỗi Samsung (22E, 24E, 21E, 25E, 40E, E4): Quạt bị kẹt do mở cửa lâu, hệ thống rã đông bất thường',
            16 => 'Lỗi Samsung (84C, 83E, 85C, 86E, 88 88): Lốc quá dòng, quá áp, hụt áp, lỗi bo mạch công suất',
            17 => 'Lỗi Samsung (39E, 26E, 76C, E1, F1-01): Van nước hỏng, cảm biến khay đá/máy làm đá lỗi',
            18 => 'Lỗi Samsung (Giao tiếp/Nháy đèn): PC ER (Lỗi giao tiếp), Nháy 10 lần (Bo công suất), Nháy 14 lần (Giao tiếp)',
            19 => 'Lỗi Panasonic (U04, U10, U11, U20): Mở cửa quá nhiều, tắc nghẽn hệ thống lạnh, nhiệt độ buồng tăng cao',
            20 => 'Lỗi Panasonic (H01 đến H18, H21): Hỏng sò lạnh, hỏng mạch cảm biến nhiệt độ, motor máy làm đá kẹt',
            21 => 'Lỗi Panasonic (H22 đến H29, H30, H40, H64): Quạt buồng máy/bay hơi hỏng, khóa IPM, hỏng bo mạch trung tâm',
            22 => 'Lỗi Toshiba (F1, F2, F4, F5, F9): Hỏng cảm biến ngăn mát/ngăn đông, lỗi bộ xả đá, quạt gió, cảm biến môi trường',
            23 => 'Lỗi Hitachi (F0-01, F0-12, F0-21, F0-22): Hỏng cảm biến dàn, quạt ngăn đá không quay, bộ xả đá lỗi, board trung tâm hỏng',
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
            ['cause_index' => 1, 'ten' => 'Hàn kín chỗ rò rỉ và nạp lại gas tủ lạnh', 'desc' => 'Kiểm tra vết dầu loang hoặc dùng bọt xà phòng dò vết thủng dàn/ống đồng. Hàn kín, dùng máy hút chân không kỹ và bơm lại môi chất gas chuẩn.'],
            ['cause_index' => 2, 'ten' => 'Xử lý tắc ẩm, tắc bẩn hệ thống', 'desc' => 'Xả hết gas cũ, sấy khô hệ thống, thổi nito đẩy cặn bẩn, cắt hàn thay phin lọc sấy mới và tiến hành nạp lại gas từ đầu.'],
            ['cause_index' => 3, 'ten' => 'Thay tụ điện hoặc thay Block mới', 'desc' => 'Đo điện trở cuộn dây máy nén, nếu hỏng tụ thì thay tụ kích block. Nếu block kẹt cơ, chập cháy phải thợ chuyên nghiệp cắt thay block mới cùng công suất.'],
            ['cause_index' => 4, 'ten' => 'Thay Rơ le khởi động/Thermostat', 'desc' => 'Kiểm tra tiếp điểm rơ le nhiệt, nếu đứt/hỏng thì thay thế rơ le mới để block đóng ngắt đúng chu trình nhiệt độ.'],
            ['cause_index' => 5, 'ten' => 'Thay thế linh kiện xả đá (Rơ le, Timer)', 'desc' => 'Dùng đồng hồ VOM đo kiểm tra Timer (rơ le thời gian), rơ le âm (sò lạnh), rơ le dương (sò nóng), bóng sấy. Thay linh kiện hỏng để tủ phá được băng tuyết.'],
            ['cause_index' => 6, 'ten' => 'Sửa chữa hoặc thay Bo mạch điều khiển', 'desc' => 'Tháo bo mạch mang đi test. Kiểm tra đo đạc IC, EPROM, xử lý chống chập đoản mạch hoặc thay bo mạch chính hãng mới.'],
            ['cause_index' => 7, 'ten' => 'Rã đông tuyết kẹt hoặc thay quạt mới', 'desc' => 'Rút điện 3-4h để rã đông nếu quạt bị kẹt bởi đá. Nếu đo cuộn dây motor quạt bị cháy đứt thì tiến hành tháo vách thay motor quạt mới.'],
            ['cause_index' => 8, 'ten' => 'Thay cảm biến nhiệt độ (Sensor) mới', 'desc' => 'Dùng đồng hồ đo trị số điện trở (K) của sensor. Thay sensor chuẩn hãng (vd LG 2K/8K, Samsung 4K, Toshiba...) nếu cảm biến bị đứt hoặc sai số.'],
            ['cause_index' => 9, 'ten' => 'Thay ron cao su cửa hoặc nắn chỉnh bản lề', 'desc' => 'Tháo ron cao su cũ, ngâm ron mới vào nước ấm để làm mềm rồi ép chặt vào rãnh cửa tủ. Xiết lại ốc bản lề cho cân đối chống xệ cánh.'],
            ['cause_index' => 10, 'ten' => 'Sắp xếp lại thực phẩm, dời vị trí tủ', 'desc' => 'Bỏ bớt thực phẩm tránh che khuất cửa tản gió lạnh. Kê tủ cách tường và hai bên ít nhất 15-20cm để dàn nóng giải nhiệt tốt hơn.'],
            ['cause_index' => 11, 'ten' => 'Thông ống xả nước, chỉnh lại khay hứng', 'desc' => 'Dùng que dài thông tắc lỗ thoát nước rã đông. Đặt lại khay hứng nước dưới gầm/sau lưng tủ cho khớp vị trí, kiểm tra vết nứt vỡ máng.'],
            ['cause_index' => 12, 'ten' => 'Khắc phục quạt và cảm biến tủ LG (Er FF, dS)', 'desc' => 'Dựa trên mã lỗi màn hình LG: Đo kiểm tra và thay thế quạt (ngăn đá/ngăn mát/lốc) hoặc thay cảm biến độ ẩm/cửa hỏng.'],
            ['cause_index' => 13, 'ten' => 'Kiểm tra bo mạch, cảm biến xả đá tủ LG', 'desc' => 'Khắc phục lỗi Er dH bằng cách thay cảm biến xả đá. Lỗi Er CO cần kiểm tra, cắm lại dây truyền tín hiệu giữa bo mạch chính và bo hiển thị.'],
            ['cause_index' => 14, 'ten' => 'Khắc phục tủ LG qua nhịp nháy đèn/tiếng bíp', 'desc' => 'Khoanh vùng lỗi qua số lần nháy/bíp: 1 bíp thay lốc, 5 bíp thay sensor ngăn đá, 7 nháy thay quạt, 6 nháy/3 nháy sửa bo mạch phá băng.'],
            ['cause_index' => 15, 'ten' => 'Sửa lỗi quạt và xả đá tủ Samsung (22E, E4)', 'desc' => 'Rút điện mở cửa vài giờ để tan băng chống kẹt quạt (Lỗi 22E). Kiểm tra, thay thế rơ le, điện trở xả đá nếu hệ thống rã đông gặp sự cố (Lỗi E4).'],
            ['cause_index' => 16, 'ten' => 'Xử lý lỗi máy nén, nguồn tủ Samsung (84C, 88)', 'desc' => 'Gặp lỗi 84C tuyệt đối không cắm điện test nhiều lần tránh cháy bo mạch. Kiểm tra nguồn cấp, sửa IC công suất Inverter hoặc thay thế block máy nén mới.'],
            ['cause_index' => 17, 'ten' => 'Sửa hệ thống làm đá tự động Samsung (39E)', 'desc' => 'Thông tắc ống nạp nước đá. Thay van điện từ cấp nước hoặc thay thế cụm cảm biến/môtơ khay làm đá tự động.'],
            ['cause_index' => 18, 'ten' => 'Xử lý lỗi giao tiếp và bo tủ Samsung', 'desc' => 'Lỗi PC ER: Rút phích cắm, kết nối lại cáp nịt trên cửa. Nếu bo mạch nháy đèn 10 lần (Bo công suất) hoặc 14 lần (Giao tiếp), cần thợ chuyên nghiệp mang bo về sửa.'],
            ['cause_index' => 19, 'ten' => 'Xử lý báo động cửa và nhiệt độ Panasonic', 'desc' => 'Lỗi U10/U11: Hạn chế tần suất đóng mở, đóng chặt cửa tủ. Lỗi U04: Súc xả, xử lý điểm tắc nghẽn của hệ thống ống dẫn môi chất lạnh.'],
            ['cause_index' => 20, 'ten' => 'Thay cảm biến và cụm làm đá Panasonic (H01)', 'desc' => 'Đo and thay thế sò lạnh, cảm biến nhiệt buồng (H01-H18). Kiểm tra motor làm đá (Lỗi H21).'],
            ['cause_index' => 21, 'ten' => 'Sửa quạt và bo mạch Panasonic nội địa', 'desc' => 'Thay động cơ quạt buồng máy/bay hơi (H22-H29). Thợ chuyên nghiệp xử lý sửa mạch IPM hoặc hỏng bo điều khiển trung tâm (H40, H64).'],
            ['cause_index' => 22, 'ten' => 'Khắc phục bảng mã lỗi tủ Toshiba', 'desc' => 'Ngắt điện, xả tuyết thủ công. Đo kiểm tra bo mạch, thay thế cảm biến nhiệt độ, quạt gió hoặc cụm thiết bị xả đá tùy theo mã F1, F2, F4, F5, F9.'],
            ['cause_index' => 23, 'ten' => 'Khắc phục bảng mã lỗi tủ Hitachi', 'desc' => 'Thay cảm biến dàn (F0-01, F0-02), thay quạt ngăn đá bị kẹt/cháy (F0-12), đo sửa bộ xả đá hoặc sửa bo mạch trung tâm (F0-21, F0-22).'],
        ];

        foreach ($resolutions as $res) {
            HuongXuLy::updateOrCreate(
                [
                    'nguyen_nhan_id' => $causeIds[$res['cause_index']],
                    'ten_huong_xu_ly' => $res['ten']
                ],
                [
                    'mo_ta_cong_viec' => $res['desc'],
                    'gia_tham_khao' => null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // 4. Mapping Symptoms to Causes (Inferred)
        $mapping = [
            1 => [1, 2, 3, 7, 9, 10, 19],
            2 => [5, 7, 8],
            3 => [3, 4, 6, 16],
            4 => [6],
            5 => [3, 7],
            6 => [11, 9],
            7 => [3, 6],
            8 => [12, 13],
            9 => [14],
            10 => [15, 16, 17],
            11 => [18],
            12 => [19, 20, 21],
            13 => [22],
            14 => [23],
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

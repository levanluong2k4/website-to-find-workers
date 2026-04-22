<?php

namespace Database\Seeders;

use App\Models\DanhMucDichVu;
use App\Models\HuongXuLy;
use App\Models\NguyenNhan;
use App\Models\TrieuChung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CivilElectricalWaterKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceId = 13; // Điện nước dân dụng

        // 1. Trieu Chung (Symptoms)
        $symptoms = [
            1 => 'Aptomat (CB) nhảy liên tục, mất điện cục bộ hoặc toàn phần',
            2 => 'Ổ cắm, công tắc điện bị lỏng, nẹt lửa, chập cháy, không hoạt động',
            3 => 'Rò rỉ điện ra tường hoặc vỏ thiết bị kim loại (gây điện giật)',
            4 => 'Đèn điện nhấp nháy, mờ dần, chập chờn hoặc không sáng',
            5 => 'Chập điện (ngắn mạch), có mùi khét, dây điện bị nóng/chảy vỏ nhựa',
            6 => 'Thiết bị điện hư hỏng hàng loạt khi có sấm sét hoặc điện áp tăng đột ngột',
            7 => 'Rò rỉ nước âm tường, thấm dột tường/sàn/trần nhà',
            8 => 'Bồn cầu đặt không vững, kênh nhẹ, rò rỉ nước chân bồn và bốc mùi hôi',
            9 => 'Bồn cầu xả yếu, nước trôi chậm, phải xả nhiều lần mới trôi',
            10 => 'Két nước bồn cầu không vào nước, vào chậm, hoặc nước chảy tràn liên tục',
            11 => 'Sen tắm, vòi chậu lavabo chảy nước yếu, tắc nghẽn, hoặc rò rỉ nước',
            12 => 'Thiết bị vệ sinh cảm ứng (bệ tiểu) xả liên tục hoặc không nhận tín hiệu',
            13 => 'Bố trí nguồn điện, ổ cắm, đường ống thoát nước mất thẩm mỹ, sai cao độ',
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
            1 => 'Quá tải hệ thống điện: Cắm nhiều thiết bị, dây/CB không đủ tải công suất',
            2 => 'Đấu nối điện sai kỹ thuật: Xoắn dây không dùng đầu cos/wago, lỏng ốc vít',
            3 => 'Không sử dụng hộp đấu nối, ống luồn bảo vệ khiến dây ẩm ướt, chuột cắn',
            4 => 'Đấu nhầm dây pha (L) và trung tính (N) hoặc không thi công dây tiếp địa',
            5 => 'Linh kiện điện (Aptomat, công tắc, chấn lưu đèn) cũ, lão hóa, kém chất lượng',
            6 => 'Thiết bị điện bị lỗi, kẹt động cơ, hoặc mạch điện tử bên trong bị chập',
            7 => 'Không trang bị thiết bị bảo vảo quá áp, chống sét lan truyền tại tủ điện',
            8 => 'Lắp lệch tâm xả bồn cầu so với ống chờ âm sàn',
            9 => 'Sử dụng keo silicone không chuẩn hoặc trám bít kín chân bồn cầu sai kỹ thuật',
            10 => 'Lắp sai két nước, van xả bẩn, hoặc gioăng cao su bị chai cứng',
            11 => 'Lắp sai vị trí phao bồn cầu (đặt phao quá cao hoặc quá thấp)',
            12 => 'Thi công ống nước ẩu: Dán keo PVC không đều, hàn nhiệt PPR không đủ thời gian',
            13 => 'Không xả sạch cặn bẩn, mạt gạch trong đường ống trước khi lắp sen/vòi',
            14 => 'Hỏng roăng cao su hoặc mòn đĩa sứ chia nước trong lõi sen/vòi lavabo',
            15 => 'Thiết bị cảm ứng hết pin bị chảy axit, hoặc nhiễu do mắt thần lỗi',
            16 => 'Thiết kế hệ thống không đồng bộ, nhà thầu thiếu kinh nghiệm thi công',
            17 => 'Bỏ qua bước thử áp lực nước và kiểm tra cách điện trước khi hoàn thiện',
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
            ['nguyen_nhan_index' => 1, 'ten' => 'Phân bổ lại tải và thay Aptomat/Dây dẫn', 'desc' => 'Tính toán lại công suất. Kéo dây tiết diện lớn hơn (ví dụ 2.5mm2 cho thiết bị 3500W). Thay CB định mức cao hơn (20A-32A cho máy lạnh).'],
            ['nguyen_nhan_index' => 2, 'ten' => 'Xử lý lại mối nối đúng tiêu chuẩn', 'desc' => 'Cắt bỏ mối nối cũ. Dùng kìm bấm đầu cos, kẹp wago hoặc hàn thiếc. Dùng tua vít lực siết chặt ốc tại cầu dao, ổ cắm.'],
            ['nguyen_nhan_index' => 3, 'ten' => 'Lắp đặt hộp điện và luồn ống gen', 'desc' => 'Bọc lại mối nối bằng băng keo chịu nhiệt (3M, Nitto). Đặt mối nối vào hộp điện an toàn. Đi lại ống luồn dây điện đạt chuẩn TCVN 9208:2012.'],
            ['nguyen_nhan_index' => 4, 'ten' => 'Đảo lại dây pha, thi công tiếp địa', 'desc' => 'Dùng đồng hồ đo điện đấu đúng màu dây (Đỏ/Nâu: Pha, Xanh dương: Trung tính). Đóng cọc tiếp địa hệ thống đạt điện trở <= 4 ôm.'],
            ['nguyen_nhan_index' => 5, 'ten' => 'Thay thế Aptomat, công tắc, bóng đèn mới', 'desc' => 'Ngắt điện toàn bộ. Tháo bỏ Aptomat hoặc công tắc cũ lỏng tiếp điểm. Thay thế thiết bị mới chính hãng.'],
            ['nguyen_nhan_index' => 6, 'ten' => 'Rút phích cắm, sửa chữa thiết bị', 'desc' => 'Rút ngay thiết bị gây chập điện ra khỏi nguồn. Mang thiết bị đi bảo hành hoặc thay mới bộ phận động cơ bị kẹt.'],
            ['nguyen_nhan_index' => 7, 'ten' => 'Lắp thiết bị chống sét/Ổn áp', 'desc' => 'Trang bị thêm thiết bị bảo vệ chống quá áp, chống sét lan truyền hoặc ổn áp tại vị trí tủ điện chính.'],
            ['nguyen_nhan_index' => 8, 'ten' => 'Sử dụng bích nối lệch tâm', 'desc' => 'Tháo bồn cầu. Dùng phụ kiện bích nối lệch tâm (co lệch) để bù khoảng cách lệch giữa tâm xả và ống chờ. Trám kín xi măng và lắp lại bồn cầu.'],
            ['nguyen_nhan_index' => 9, 'ten' => 'Cạo keo cũ, bắn lại Silicone trung tính', 'desc' => 'Cạo sạch lớp keo cũ nấm mốc. Bơm lại Silicone trung tính chống mốc quanh chân bồn, lưu ý chừa khe 3-5mm để thoát hơi ẩm, không bít kín hoàn toàn.'],
            ['nguyen_nhan_index' => 10, 'ten' => 'Thay gioăng, chỉnh xích, vệ sinh van', 'desc' => 'Khóa nước. Chỉnh lại độ trễ dây xích van xả. Vệ sinh cặn bẩn trong bộ xả. Nếu gioăng cao su lớn tiếp giáp két nước bị chai cứng thì phải thay mới.'],
            ['nguyen_nhan_index' => 11, 'ten' => 'Căn chỉnh lại cao độ phao nước', 'desc' => 'Mở két nước, dùng vít chỉnh hạ thấp hoặc nâng cao thanh trượt phao (hoặc uốn nhẹ thanh kim loại). Đảm bảo phao không cọ vào thành két, mức nước thấp hơn ống tràn 2-3cm.'],
            ['nguyen_nhan_index' => 12, 'ten' => 'Dò rò rỉ, cắt nối và hàn lại ống', 'desc' => 'Xác định điểm rò rỉ âm tường. Cắt bỏ đoạn ống lỗi. Bôi đều keo PVC hoặc dùng máy hàn nhiệt PPR đủ thời gian quy định để nối ống mới. Quấn băng tan kín ren.'],
            ['nguyen_nhan_index' => 13, 'ten' => 'Tháo rửa lưới lọc, xả cặn đường ống', 'desc' => 'Tháo bát sen tắm hoặc đầu vòi lavabo. Lấy màng lưới lọc rác ra xịt rửa sạch mạt cát, cặn bẩn. Xả nước một lúc cho trôi hết cặn trong ống rồi lắp lại.'],
            ['nguyen_nhan_index' => 14, 'ten' => 'Thay roăng cao su hoặc bộ óc vòi (lõi sứ)', 'desc' => 'Tháo tay gạt vòi nước. Kiểm tra và thay thế roăng cao su bị rách hoặc thay mới toàn bộ củ vòi (bộ óc chia nước bằng sứ) nếu bị mòn, sứt mẻ.'],
            ['nguyen_nhan_index' => 15, 'ten' => 'Thay pin, vệ sinh mắt cảm ứng', 'desc' => 'Lau sạch mắt thần cảm ứng. Tháo hộp pin kiểm tra xem có bị chảy axit không, tiến hành thay pin mới hoặc liên hệ hãng bảo hành mạch điện tử.'],
            ['nguyen_nhan_index' => 16, 'ten' => 'Combine bản vẽ, dời vị trí thiết bị', 'desc' => 'Nhà thầu cần đối chiếu (combine) hệ thống M&E với kiến trúc. Dời vị trí hộp điện, điều chỉnh lại cao độ ống thoát nước máy giặt/bồn cầu cho thẩm mỹ.'],
            ['nguyen_nhan_index' => 17, 'ten' => 'Test nghiệm thu áp lực và điện trở', 'desc' => 'Trước khi trát tường/lát gạch, bắt buộc phải dùng máy thử áp lực đường ống nước ngầm và dùng đồng hồ đo điện trở cách điện của toàn bộ dây điện.'],
        ];

        foreach ($resolutions as $res) {
            HuongXuLy::updateOrCreate(
                [
                    'nguyen_nhan_id' => $causeIds[$res['nguyen_nhan_index']],
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
            1 => [1, 2, 5, 6],
            2 => [2, 5],
            3 => [3, 4, 6],
            4 => [2, 5, 6],
            5 => [1, 2, 3, 6],
            6 => [7],
            7 => [12, 17],
            8 => [8, 9],
            9 => [10, 11, 13],
            10 => [10, 11],
            11 => [13, 14],
            12 => [15],
            13 => [16],
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

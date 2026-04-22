<?php

namespace Database\Seeders;

use App\Models\HuongXuLy;
use App\Models\NguyenNhan;
use App\Models\TrieuChung;
use Illuminate\Database\Seeder;

class ElectricFanRepairDetailedKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceId = 9; // Sửa Quạt Điện (Correct ID from database)

        // 1. Trieu Chung (Symptoms)
        $symptoms = [
            1 => 'Quạt không hoạt động, không quay, mất nguồn điện hoàn toàn',
            2 => 'Quạt quay chậm, lờ đờ, quay yếu hoặc phải dùng tay mồi mới quay',
            3 => 'Quạt phát ra tiếng kêu to, kêu rè rè, lạch cạch, tiếng rít hoặc tiếng ù',
            4 => 'Quạt bị rung lắc mạnh, đung đưa khi chạy',
            5 => 'Động cơ (motor) quạt nóng ran bất thường, có mùi khét',
            6 => 'Quạt chỉ quay một hướng, không đảo gió được, hỏng tuốc năng',
            7 => 'Dây điện, dây nguồn bị đứt, cháy xém',
            8 => 'Cánh quạt bị tuột, rơi văng ra khỏi trục khi đang hoạt động',
            9 => 'Quạt chạy một lúc rồi ngắt đột ngột, chập cháy nổ',
            10 => 'Quạt điều khiển từ xa cắm điện bật không lên, không nhận tín hiệu remote',
            11 => 'Nút bấm cơ trên quạt bị kẹt cứng, không ăn, hoặc nhảy số lộn xộn',
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
            1 => 'Nguồn điện không ổn định, đứt dây do chuột cắn, kẹp vào chân bàn ghế hoặc ổ cắm lỏng',
            2 => 'Hỏng tụ điện (Tụ khởi động): Tụ bị già, phồng, giảm dung lượng (microfarad) hoặc đứt ngầm',
            3 => 'Kẹt trục, bó bạc do khô dầu mỡ, bụi bẩn tích tụ lâu ngày hoặc bị tóc quấn vào trục quay',
            4 => 'Hỏng Stator: Cuộn dây đồng bị đứt ngầm, chập cuộn chạy/cuộn đề, từ trường không đối xứng',
            5 => 'Đứt cầu chì nhiệt bảo vệ động cơ do nhiệt độ motor vượt quá mức cho phép (bị kẹt cơ lâu ngày)',
            6 => 'Lỗi cơ khí lỏng lẻo: Cánh quạt lắp lệch, cong vênh, lỏng ốc lồng, lỏng chân đế',
            7 => 'Hỏng bộ túp năng (tuốc năng): Bánh răng nhựa bị mài mòn, gãy chốt hoặc hỏng motor đảo gió',
            8 => 'Lỗi cụm lắp ráp cánh: Núm siết, long đen, chốt hãm cánh quạt bị lỏng, mòn hoặc gãy',
            9 => 'Lỗi bo mạch điện tử (Quạt điều khiển): Điện áp tăng đột biến làm nổ cầu chì, chập diode, hỏng thạch anh hoặc IC vi xử lý',
            10 => 'Lỗi điều khiển cơ: Nút bấm rỉ sét, bụi bẩn, nhựa lão hóa, gãy lò xo tiếp điểm',
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
            ['cause_index' => 1, 'ten' => 'Thay mới và đấu nối lại dây nguồn', 'desc' => 'Kiểm tra và thay đoạn dây bị hỏng bằng dây điện mới có cùng tiết diện. Đấu nối đúng kỹ thuật, bọc băng keo cách điện hoặc ống co nhiệt cẩn thận. Tuyệt đối không nối tạm bằng xoắn tay rồi để hở.'],
            ['cause_index' => 2, 'ten' => 'Thay tụ điện khởi động mới', 'desc' => 'Cắt 2 dây của tụ cũ, thay tụ điện mới có chính xác chỉ số điện dung (uF) tương đương. Vì tụ quạt không phân cực nên có thể đấu nối hai dây tùy ý, sau đó quấn băng keo cách điện kín.'],
            ['cause_index' => 3, 'ten' => 'Vệ sinh và tra dầu bôi trơn trục bạc', 'desc' => 'Tháo lồng và cánh quạt, dùng cọ vệ sinh sạch bụi và gỡ tóc rối. Nhỏ vài giọt dầu máy chuyên dụng vào trục quay và ổ bạc (bạc thau/bạc đạn), xoay tay để dầu thấm đều.'],
            ['cause_index' => 3, 'ten' => 'Thay thế cụm bạc đạn/bạc thau mới', 'desc' => 'Nếu trục quay đã bị xước rãnh, bạc bị mòn quá nặng dẫn đến sát cốt (cọ xát rotor vào stator), tiến hành đóng bạc đạn mới vào ốp motor và thay trục mới.'],
            ['cause_index' => 4, 'ten' => 'Quấn lại hoặc thay cụm Stator mới', 'desc' => 'Dùng đồng hồ VOM đo điện trở. Nếu cuộn dây bị chập hoặc đứt ngầm (điện trở bằng 0 hoặc vô cùng), mang ra thợ điện cơ quấn lại dây đồng hoặc thay nguyên cụm Stator mới.'],
            ['cause_index' => 5, 'ten' => 'Thay thế cầu chì nhiệt mới', 'desc' => 'Xác định hộp chứa cầu chì nhiệt (thường bọc trong ống gen sát bó dây đồng). Đo thông mạch xác nhận đứt, hàn thay thế bằng cầu chì mới có cùng chỉ số dòng điện và nhiệt độ.'],
            ['cause_index' => 5, 'ten' => 'Luồn dây đấu tắt qua cầu chì (Chữa cháy)', 'desc' => 'Dùng 1 sợi dây luồn từ động cơ xuống bộ chuyển số, nối vào đầu nguồn không qua công tắc. Phía trên nối với 1 trong 2 dây của tụ (dùng đồng hồ đo, bên dây tụ nào cho điện trở lớn hơn thì nối vào).'],
            ['cause_index' => 6, 'ten' => 'Siết chặt ốc vít, cân bằng lại cánh quạt', 'desc' => 'Kiểm tra và dùng tua vít siết chặt toàn bộ ốc lồng, ốc gầm. Nếu cánh quạt bị nứt, vênh hoặc sứt mẻ phải mua cánh mới cùng kích cỡ để đảm bảo cân bằng động khi quay.'],
            ['cause_index' => 7, 'ten' => 'Tra mỡ bò hoặc thay tuốc năng mới', 'desc' => 'Mở nắp túp năng phía sau, lau sạch mỡ bẩn và tra mỡ bò chịu nhiệt mới. Nếu nhông bánh răng nhựa đã bị mòn trượt hoặc motor đảo gió bị cháy, tiến hành thay nguyên cụm tuốc năng.'],
            ['cause_index' => 8, 'ten' => 'Lắp ráp đúng thứ tự và thay chốt hãm', 'desc' => 'Kiểm tra và gắn lại chính xác theo thứ tự: Long đen -> Chốt hãm -> Cánh quạt -> Núm siết. Nếu chốt nhựa/núm siết bị mòn gãy, thay phụ kiện chính hãng để tránh cánh văng ra gây nguy hiểm.'],
            ['cause_index' => 9, 'ten' => 'Sửa chữa linh kiện bo mạch điện tử', 'desc' => 'Đo nguội tụ hóa cấp nguồn (thường 470uF/1000uF). Sửa chữa thay thế cầu chì, diode ổn áp bị chập do điện lưới không ổn định, hoặc thay thế IC vi xử lý bị cháy.'],
            ['cause_index' => 10, 'ten' => 'Vệ sinh công tắc, thay lò xo tiếp điểm', 'desc' => 'Tháo bảng điều khiển, xịt dung dịch vệ sinh tiếp điểm (như RP7) để làm sạch rỉ sét ở nút bấm. Nắn lại hoặc thay thế lò xo bên trong nếu bị gãy khiến nút bị kẹt.'],
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
            1 => [1, 4, 5, 9, 10],
            2 => [2, 3],
            3 => [3, 6],
            4 => [6],
            5 => [3, 4],
            6 => [7],
            7 => [1],
            8 => [6, 8],
            9 => [5, 9],
            10 => [9],
            11 => [10],
        ];

        foreach ($mapping as $sIndex => $cIndexes) {
            $symptom = TrieuChung::find($symptomIds[$sIndex]);
            if ($symptom) {
                $attachIds = [];
                foreach ($cIndexes as $cIndex) {
                    if (isset($causeIds[$cIndex])) {
                        $attachIds[] = $causeIds[$cIndex];
                    }
                }
                $symptom->nguyenNhans()->syncWithoutDetaching($attachIds);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\HuongXuLy;
use App\Models\NguyenNhan;
use App\Models\TrieuChung;
use Illuminate\Database\Seeder;

class ElectricFanRepairKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceId = 9; // Sửa Quạt Điện

        // 1. Trieu Chung (Symptoms)
        $symptoms = [
            1 => 'Quạt không hoạt động, không quay, mất nguồn điện hoàn toàn',
            2 => 'Quạt quay chậm, lờ đờ, quay yếu hoặc phải dùng tay mồi mới quay',
            3 => 'Quạt phát ra tiếng kêu to, kêu rè rè, lạch cạch, tiếng rít hoặc tiếng ù',
            4 => 'Quạt bị rung lắc mạnh, đung đưa khi chạy',
            5 => 'Động cơ (motor) quạt nóng ran bất thường, có mùi khét',
            6 => 'Quạt chỉ quay một hướng, không đảo gió được, hỏng tuốc năng',
            7 => 'Nút bấm, công tắc bị kẹt, không ăn, hoặc mất kiểm soát tốc độ',
            8 => 'Cánh quạt bị tuột, rơi khỏi trục khi đang hoạt động',
            9 => 'Quạt chạy một lúc rồi ngắt đột ngột, cháy cầu chì',
            10 => 'Dây điện, dây nguồn bị đứt, cháy xém',
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
            1 => 'Nguồn điện không ổn định, đứt dây nguồn, phích cắm lỏng do chuột cắn hoặc chập cháy',
            2 => 'Hỏng tụ điện: Tụ khởi động bị già, phồng, giảm dung lượng hoặc đứt ngầm',
            3 => 'Kẹt trục, bó bạc do khô dầu mỡ, bụi bẩn tích tụ lâu ngày hoặc bị tóc quấn vào trục',
            4 => 'Hỏng Stator: Cuộn dây đồng bị đứt ngầm, chập cuộn, từ trường không đối xứng do quấn sai',
            5 => 'Đứt cầu chì nhiệt bảo vệ động cơ (nhiệt độ motor vượt quá mức cho phép 115 - 130 độ C)',
            6 => 'Lỗi cơ khí lỏng lẻo: Cánh quạt lắp lệch, cong vênh, lỏng ốc lồng, lỏng chân đế',
            7 => 'Hỏng bộ túp năng (tuốc năng): Bánh răng nhựa bị mài mòn, gãy chốt hoặc hỏng motor đảo gió',
            8 => 'Lỗi điều khiển: Nút bấm rỉ sét, bụi bẩn, nhựa lão hóa, gãy lò xo hoặc hỏng bộ hẹn giờ',
            9 => 'Lắp ráp sai thứ tự phụ kiện: Núm siết, long đen, chốt hãm cánh quạt bị lỏng hoặc gãy',
            10 => 'Hoạt động quá tải: Quạt chạy liên tục nhiều giờ, điện áp tăng đột ngột làm đoản mạch',
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
            ['cause_index' => 1, 'ten' => 'Kiểm tra, đấu nối hoặc thay dây nguồn mới', 'desc' => 'Ngắt điện, kiểm tra phích cắm và dọc dây nguồn. Thay thế bằng dây điện mới có cùng thông số, đấu nối đúng kỹ thuật, bọc băng keo cách điện hoặc ống co nhiệt cẩn thận.'],
            ['cause_index' => 2, 'ten' => 'Thay tụ điện khởi động mới', 'desc' => 'Cắt 2 dây của tụ cũ, thay tụ mới có CHÍNH XÁC chỉ số microfarad (uF) tương đương (vd: 1.5uF, 2uF). Tụ quạt là tụ không phân cực nên đấu nối hai dây tùy ý. Quấn băng keo cách điện.', 'price' => 20000.00],
            ['cause_index' => 3, 'ten' => 'Vệ sinh, tra dầu bôi trơn trục bạc', 'desc' => 'Tháo lồng và cánh quạt, vệ sinh sạch bụi và gỡ tóc rối. Nhỏ vài giọt dầu máy (dầu bôi trơn chuyên dụng) vào trục quay và ổ bạc (bạc đạn). Xoay tay để dầu thấm đều.'],
            ['cause_index' => 3, 'ten' => 'Thay thế cụm bạc đạn (ổ trục) mới', 'desc' => 'Nếu bạc đạn đã bị xước, mòn quá nặng dẫn đến sát cốt, cần tiến hành đóng bạc đạn mới vào ốp motor.'],
            ['cause_index' => 4, 'ten' => 'Sửa chữa hoặc thay nguyên cụm Stator', 'desc' => 'Dùng đồng hồ VOM đo điện trở cuộn chạy và cuộn đề. Nếu cuộn dây bị chập hoặc đứt ngầm (điện trở bằng 0 hoặc vô cùng), cần mang ra thợ quấn lại dây đồng hoặc thay cụm Stator mới.'],
            ['cause_index' => 5, 'ten' => 'Thay thế cầu chì nhiệt mới', 'desc' => 'Tháo nắp bầu động cơ, tìm cầu chì nhiệt bọc trong ống gen sát bó dây đồng. Đo thông mạch để xác định đứt, hàn thay thế cầu chì mới đúng trị số (khoảng 115 - 130 độ C).'],
            ['cause_index' => 5, 'ten' => 'Đấu tắt bỏ qua cầu chì nhiệt (Chữa cháy)', 'desc' => 'Trong trường hợp khẩn cấp, dùng 1 sợi dây điện nối từ đầu nguồn vào trực tiếp đầu ra cầu chì của tụ điện (phân biệt bằng cách đo điện trở, bên nào trị số lớn thì nối vào). Lưu ý: Không khuyến khích dùng lâu dài.'],
            ['cause_index' => 6, 'ten' => 'Siết chặt ốc vít, nắn chỉnh hoặc thay cánh quạt', 'desc' => 'Kiểm tra và dùng tua vít siết chặt toàn bộ ốc trên thân, đế, và lồng quạt. Tháo cánh quạt lắp lại cho đúng khớp. Nếu cánh bị nứt gãy, cong vênh phải mua cánh mới cùng model thay vào để cân bằng động.'],
            ['cause_index' => 7, 'ten' => 'Vệ sinh, tra mỡ bò hoặc thay tuốc năng', 'desc' => 'Mở cụm túp năng phía sau quạt, lau sạch mỡ cũ và tra mỡ bò chịu nhiệt mới. Nếu bánh răng nhựa bị mòn trượt hoặc motor đảo gió bị cháy thì thay thế nguyên bộ tuốc năng mới.'],
            ['cause_index' => 8, 'ten' => 'Vệ sinh công tắc, thay tiếp điểm/lò xo', 'desc' => 'Mở bảng điều khiển, dùng dung dịch vệ sinh tiếp điểm (MC-Kenic/RP7) làm sạch bụi bẩn, rỉ sét ở nút bấm. Kéo dãn hoặc thay lò xo bên trong nếu bị gãy.'],
            ['cause_index' => 9, 'ten' => 'Lắp ráp lại đúng thứ tự chi tiết hãm', 'desc' => 'Kiểm tra và gắn lại theo đúng thứ tự: Long đen -> Chốt -> Cánh quạt -> Núm siết. Nếu chốt nhựa hãm bị mòn, thay thế phụ kiện chính hãng để cánh không văng ra khi chạy.'],
            ['cause_index' => 10, 'ten' => 'Làm mát động cơ, thay cầu chì nguồn', 'desc' => 'Tắt quạt để động cơ nghỉ ngơi và tản nhiệt, không bật quạt số lớn liên tục nhiều giờ. Đảm bảo đặt quạt ở nơi thoáng khí. Kiểm tra hộp cầu chì nguồn và thay thế nếu bị cháy.'],
        ];

        foreach ($resolutions as $res) {
            HuongXuLy::updateOrCreate(
                [
                    'nguyen_nhan_id' => $causeIds[$res['cause_index']],
                    'ten_huong_xu_ly' => $res['ten']
                ],
                [
                    'mo_ta_cong_viec' => $res['desc'],
                    'gia_tham_khao' => $res['price'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // 4. Mapping Symptoms to Causes (Inferred)
        $mapping = [
            1 => [1, 4, 5, 10],
            2 => [2, 3],
            3 => [3, 6],
            4 => [6],
            5 => [3, 4, 10],
            6 => [7],
            7 => [8],
            8 => [6, 9],
            9 => [5, 10],
            10 => [1],
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

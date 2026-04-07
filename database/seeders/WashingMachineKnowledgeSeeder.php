<?php

namespace Database\Seeders;

use App\Models\DanhMucDichVu;
use App\Models\HuongXuLy;
use App\Models\NguyenNhan;
use App\Models\TrieuChung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WashingMachineKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceId = $this->resolveWashingMachineServiceId();

        $symptomIds = [];
        foreach ($this->symptoms() as $index => $name) {
            $symptomIds[$index] = $this->upsertSymptom($serviceId, $name)->id;
        }

        $causeIds = [];
        foreach ($this->causes() as $index => $name) {
            $causeIds[$index] = $this->upsertCause($name)->id;
        }

        foreach ($this->symptomCauseMap() as $symptomIndex => $causeIndexes) {
            $symptomId = $symptomIds[$symptomIndex] ?? null;
            if (!$symptomId) {
                continue;
            }

            $attachIds = array_values(array_filter(array_map(
                fn (int $causeIndex): ?int => $causeIds[$causeIndex] ?? null,
                $causeIndexes
            )));

            if ($attachIds === []) {
                continue;
            }

            TrieuChung::query()->find($symptomId)?->nguyenNhans()->syncWithoutDetaching($attachIds);
        }

        foreach ($this->resolutions() as $row) {
            $causeId = $causeIds[$row['cause_index']] ?? null;
            if (!$causeId) {
                continue;
            }

            $this->upsertResolution($causeId, $row);
        }
    }

    private function resolveWashingMachineServiceId(): int
    {
        $services = DanhMucDichVu::query()->get(['id', 'ten_dich_vu']);

        $service = $services->first(function (DanhMucDichVu $item): bool {
            $name = Str::lower(Str::ascii((string) $item->ten_dich_vu));
            return Str::contains($name, 'may giat');
        });

        if (!$service) {
            $service = $services->first(function (DanhMucDichVu $item): bool {
                $name = Str::lower(Str::ascii((string) $item->ten_dich_vu));
                return Str::contains($name, 'giat');
            });
        }

        if ($service) {
            return (int) $service->id;
        }

        return (int) DanhMucDichVu::query()->create([
            'ten_dich_vu' => 'Sửa máy giặt',
            'mo_ta' => 'Sửa máy giặt lồng ngang, lồng đứng, không vắt, lỗi bo mạch',
            'hinh_anh' => 'local_laundry_service',
            'trang_thai' => true,
        ])->id;
    }

    private function upsertSymptom(int $serviceId, string $name): TrieuChung
    {
        $lookup = $this->normalizeLookup($name);
        $model = TrieuChung::query()
            ->where('dich_vu_id', $serviceId)
            ->get()
            ->first(fn (TrieuChung $item): bool => $this->normalizeLookup($item->ten_trieu_chung) === $lookup);

        if ($model) {
            if ($model->ten_trieu_chung !== $name) {
                $model->forceFill(['ten_trieu_chung' => $name])->save();
            }

            return $model;
        }

        return TrieuChung::query()->create([
            'dich_vu_id' => $serviceId,
            'ten_trieu_chung' => $name,
        ]);
    }

    private function upsertCause(string $name): NguyenNhan
    {
        $lookup = $this->normalizeLookup($name);
        $model = NguyenNhan::query()
            ->get()
            ->first(fn (NguyenNhan $item): bool => $this->normalizeLookup($item->ten_nguyen_nhan) === $lookup);

        if ($model) {
            if ($model->ten_nguyen_nhan !== $name) {
                $model->forceFill(['ten_nguyen_nhan' => $name])->save();
            }

            return $model;
        }

        return NguyenNhan::query()->create([
            'ten_nguyen_nhan' => $name,
        ]);
    }

    /**
     * @param array{name:string, description:string, price_text:string, cause_index:int} $row
     */
    private function upsertResolution(int $causeId, array $row): void
    {
        $lookup = $this->normalizeLookup($row['name']);
        $model = HuongXuLy::query()
            ->where('nguyen_nhan_id', $causeId)
            ->get()
            ->first(fn (HuongXuLy $item): bool => $this->normalizeLookup($item->ten_huong_xu_ly) === $lookup);

        $payload = [
            'nguyen_nhan_id' => $causeId,
            'ten_huong_xu_ly' => $row['name'],
            'mo_ta_cong_viec' => $row['description'],
            'gia_tham_khao' => $this->normalizePrice($row['price_text']),
        ];

        if ($model) {
            $model->forceFill($payload)->save();
            return;
        }

        HuongXuLy::query()->create($payload);
    }

    private function normalizeLookup(string $value): string
    {
        return Str::lower(Str::squish(Str::ascii($value)));
    }

    /**
     * @return array<int, string>
     */
    private function symptoms(): array
    {
        return [
            1 => 'Mất nguồn / Đèn không sáng / Bật không lên (Kể cả cắm điện)',
            2 => 'Liệt phím / Bảng điều khiển chập chờn / Loạn chương trình',
            3 => 'Rung lắc dữ dội / Kêu lạch cạch / Kêu gầm rú khi vắt',
            4 => 'Rò rỉ nước / Chảy nước ra sàn / Đọng nước dưới gầm',
            5 => 'Rò rỉ điện / Chạm mát vỏ máy / Bị giật điện',
            6 => 'Máy xả nước liên tục / Tắt máy nước vẫn tự cấp vào lồng',
            7 => 'Có mùi khét / Mùi hôi nấm mốc bên trong lồng giặt',
            8 => 'Quần áo giặt không sạch / Dính cặn bột giặt / Bị rách',
            9 => 'Máy bị trào bọt ra ngoài cửa/khay xà phòng',
            10 => 'LỖI CẤP NƯỚC (Nước vào yếu/Không cấp): IE, 4C, 4E, E10, E11, E5, U14, C51, E1',
            11 => 'LỖI XẢ NƯỚC (Không xả/Nghẹt ống): OE, 5C, 5E, E20, E21, E1, U11, C1, F02, E2',
            12 => 'LỖI MẤT CÂN BẰNG (Không vắt): UE, UB, E4, E3, U13, F, EF5, C4',
            13 => 'LỖI CỬA (Không đóng/kẹt/hỏng khóa): dE, dC, E40-E45, E2, U12, U4, H27, E1',
            14 => 'LỖI PHAO/MỰC NƯỚC/TRÀN NƯỚC: PE, 1C, E31-E35, H01, H21, E4, E8, F1, C51',
            15 => 'LỖI ĐỘNG CƠ/TRUYỀN ĐỘNG/INVERTER: LE, CE, 3E, E50-E5F, E7-4, H51, H55, EA',
            16 => 'LỖI CẢM BIẾN NHIỆT/GIA NHIỆT/SẤY: dHE, tE, E61-E74, H11, H17, nhóm EH/EJ/EU/EF',
            17 => 'LỖI BO MẠCH/GIAO TIẾP/IC: E90-E9F, H04, H05, H09, AE, 9C, E7-1, F01',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function causes(): array
    {
        return [
            1 => 'Nguồn điện chập chờn, phích cắm lỏng, điện áp quá cao/quá thấp',
            2 => 'Đứt/Hở dây điện (Do chuột cắn, oxy hóa, gập gãy)',
            3 => 'Hỏng bo mạch: Chết IC nguồn (LNK304), chập Triac, nổ tụ, lỗi chip EEPROM',
            4 => 'Bo mạch bị nhiễm ẩm, dính nước, côn trùng (gián/thằn lằn) làm tổ gây chập',
            5 => 'Phím bấm bị oxy hóa, rỉ sét do môi trường ẩm ướt',
            6 => 'Chưa tháo ốc định vị lồng giặt (Lỗi thường gặp khi mới mua máy)',
            7 => 'Máy đặt không cân bằng, mặt sàn gồ ghề, chân đế hỏng/lỏng',
            8 => 'Lỗi cơ khí: Vỡ bạc đạn (ổ bi), gãy chạc ba, mòn trục cốt',
            9 => 'Lỗi cơ khí: Hỏng lò xo giảm xóc, ty phuộc nhún bị xì dầu/cong',
            10 => 'Mất cân bằng tải: Quần áo xoắn cục, quá tải khối lượng, giặt đồ cồng kềnh',
            11 => 'Tắc/Hỏng hệ thống cấp: Khóa vòi nước, tắc lưới lọc, hỏng van điện từ cấp nước',
            12 => 'Tắc/Hỏng hệ thống xả: Tắc ống xả do xơ vải/dị vật, đặt ống sai vị trí, hỏng bơm xả/mô tơ kéo xả',
            13 => 'Dị vật (đồng xu, kẹp tóc, chìa khóa) rớt vào lồng giặt hoặc kẹt ở bơm xả',
            14 => 'Hỏng hệ thống truyền động: Đứt/chùng dây curoa, hỏng tụ đề, hỏng chổi than',
            15 => 'Hỏng động cơ chính, lỗi cảm biến tốc độ (đếm từ/Tacho), lỗi Inverter công suất',
            16 => 'Hỏng công tắc cửa an toàn, kẹt chốt cơ học, chưa đóng kín cửa',
            17 => 'Hỏng cảm biến mực nước (phao áp lực), thủng ống hơi áp suất, nghẹt ống hơi',
            18 => 'Hỏng thanh điện trở đun nước (chạm mass), hỏng cảm biến nhiệt NTC, hỏng quạt sấy',
            19 => 'Rách gioăng cao su cửa, thủng lồng giặt, nứt ống dẫn nước bên trong',
            20 => 'Sử dụng sai loại bột giặt (nhiều bọt), cho quá liều lượng xà phòng',
            21 => 'Không vệ sinh lồng giặt định kỳ gây tích tụ vi khuẩn, nấm mốc, cặn bẩn',
            22 => 'Chưa nối dây tiếp đất (Gây rò điện chạm vỏ)',
        ];
    }

    /**
     * @return array<int, array<int>>
     */
    private function symptomCauseMap(): array
    {
        return [
            1 => [1, 2, 3],
            2 => [3, 4, 5],
            3 => [6, 7, 8, 9, 10, 13],
            4 => [19, 12],
            5 => [22, 2, 18],
            6 => [12, 3],
            7 => [21, 3, 15],
            8 => [21, 20, 13],
            9 => [20],
            10 => [11, 3, 17],
            11 => [12, 13, 3],
            12 => [10, 7, 9, 14],
            13 => [16, 3],
            14 => [17, 3],
            15 => [14, 15, 3, 10],
            16 => [18, 3],
            17 => [19, 3, 4],
        ];
    }

    /**
     * @return array<int, array{name:string, description:string, price_text:string, cause_index:int}>
     */
    private function resolutions(): array
    {
        return [
            ['cause_index' => 1, 'name' => 'Khảo sát và ổn định nguồn điện', 'description' => 'Dùng đồng hồ đo áp, tư vấn lắp thêm ổn áp nếu điện quá yếu/cao.', 'price_text' => '100.000 - 200.000'],
            ['cause_index' => 2, 'name' => 'Nối và bọc lại dây điện chống chuột', 'description' => 'Dò tìm điểm đứt do chuột cắn, hàn nối dây và bọc gen co nhiệt an toàn.', 'price_text' => '150.000 - 250.000'],
            ['cause_index' => 22, 'name' => 'Thi công dây tiếp đất an toàn', 'description' => 'Đóng cọc tiếp địa, chạy dây tiếp đất từ vỏ máy giặt xuống đất chống rò điện.', 'price_text' => '150.000 - 250.000'],
            ['cause_index' => 7, 'name' => 'Cân chỉnh chân đế, di dời vị trí', 'description' => 'Dùng thước thủy cân bằng lồng máy, kê lại chân mút cao su chống rung.', 'price_text' => '150.000 - 250.000'],
            ['cause_index' => 6, 'name' => 'Tháo ốc định vị lồng giặt', 'description' => 'Tháo 4 ốc định vị phía sau lưng máy giặt cửa ngang.', 'price_text' => '100.000 - 150.000'],
            ['cause_index' => 13, 'name' => 'Gắp dị vật kẹt trong lồng / bơm xả', 'description' => 'Tháo rốn xả hoặc mâm giặt để gắp chìa khóa, kẹp tóc, đồng xu, đồ lót kẹt.', 'price_text' => '200.000 - 300.000'],
            ['cause_index' => 21, 'name' => 'Vệ sinh bảo dưỡng máy giặt toàn diện', 'description' => 'Tháo rời lồng giặt, dùng máy xịt áp lực cao tẩy sạch cặn bẩn, nấm mốc, bùn đất.', 'price_text' => '300.000 - 550.000'],
            ['cause_index' => 19, 'name' => 'Thay gioăng cửa cao su', 'description' => 'Tháo mặt trước máy cửa ngang, thay gioăng cao su do rách hoặc ố mốc nặng.', 'price_text' => '300.000 - 500.000 (Chưa linh kiện)'],
            ['cause_index' => 8, 'name' => 'Phục hồi, thay bạc đạn / chạc ba', 'description' => 'Rã toàn bộ lồng giặt, tháo bi cũ rỉ sét, thay bi mới chính hãng, hàn chạc ba thủng.', 'price_text' => '500.000 - 900.000 (Chưa linh kiện)'],
            ['cause_index' => 9, 'name' => 'Thay phuộc nhún / lò xo treo', 'description' => 'Tháo gầm máy thay cặp phuộc giảm xóc bị xì dầu, hoặc thay 4 ty treo lồng đứng.', 'price_text' => '300.000 - 500.000 (Chưa linh kiện)'],
            ['cause_index' => 11, 'name' => 'Vệ sinh lưới lọc van cấp', 'description' => 'Tháo vòi nước, dùng bàn chải vệ sinh sạch cặn rỉ sét tại màng lọc van cấp.', 'price_text' => '150.000 - 200.000'],
            ['cause_index' => 11, 'name' => 'Thay van cấp nước điện từ', 'description' => 'Tháo nắp trên, thay van cấp nước đơn/đôi/ba do cuộn từ bị đứt hoặc kẹt màng cao su.', 'price_text' => '200.000 - 350.000 (Chưa linh kiện)'],
            ['cause_index' => 12, 'name' => 'Thông tắc đường xả / thay bơm xả', 'description' => 'Thông ống thoát bị gập/tắc nghẽn. Thay mô tơ kéo xả hoặc bơm ly tâm xả.', 'price_text' => '200.000 - 350.000 (Chưa linh kiện)'],
            ['cause_index' => 17, 'name' => 'Xử lý ống hơi / thay phao áp lực', 'description' => 'Vệ sinh cặn xà phòng kẹt ống hơi. Thay cảm biến mực nước do sai tần số dao động.', 'price_text' => '200.000 - 300.000 (Chưa linh kiện)'],
            ['cause_index' => 10, 'name' => 'Dàn đồ, hướng dẫn sử dụng', 'description' => 'Hướng dẫn khách hàng phân loại đồ, không giặt quá tải hoặc giặt 1 đồ cồng kềnh.', 'price_text' => '100.000 - 150.000'],
            ['cause_index' => 14, 'name' => 'Thay dây curoa truyền động', 'description' => 'Kiểm tra độ chùng, thay đai curoa mới và cân chỉnh độ căng motor.', 'price_text' => '200.000 - 300.000 (Chưa linh kiện)'],
            ['cause_index' => 15, 'name' => 'Sửa động cơ / thay đếm từ Tacho', 'description' => 'Phục hồi chổi than mòn, thay IC đếm từ động cơ Direct Drive Inverter.', 'price_text' => '350.000 - 600.000 (Chưa linh kiện)'],
            ['cause_index' => 16, 'name' => 'Thay công tắc khóa cửa', 'description' => 'Mở mặt nạ, tháo và thay thế cụm khóa cửa an toàn chập cháy/gãy lẫy.', 'price_text' => '200.000 - 350.000 (Chưa linh kiện)'],
            ['cause_index' => 18, 'name' => 'Thay thanh điện trở gia nhiệt / quạt sấy', 'description' => 'Đo trở kháng NTC, thay sợi đốt đun nước hoặc phục hồi quạt sấy máy giặt sấy.', 'price_text' => '300.000 - 500.000 (Chưa linh kiện)'],
            ['cause_index' => 5, 'name' => 'Vệ sinh mạch, phủ keo chống oxy hóa', 'description' => 'Sấy khô mạch, đánh sạch rỉ sét phím bấm, xịt CR7 và phủ keo chống ẩm mạch dán.', 'price_text' => '250.000 - 400.000'],
            ['cause_index' => 4, 'name' => 'Sửa mạch dính ẩm, diệt tổ côn trùng', 'description' => 'Làm sạch xác gián/thạch sùng, xử lý đường mạch đồng bị đứt do acid côn trùng.', 'price_text' => '350.000 - 600.000'],
            ['cause_index' => 3, 'name' => 'Sửa bo mạch / thay IC nguồn', 'description' => 'Đo đạc, hàn thay IC LNK304, thay Triac van xả, phục hồi EEPROM.', 'price_text' => '500.000 - 1.200.000'],
            ['cause_index' => 3, 'name' => 'Thay nguyên cụm bo mạch mới', 'description' => 'Tháo bo mạch hỏng nặng/cháy vi xử lý, đặt hàng thay mâm bo mạch chính hãng mới.', 'price_text' => '200.000 - 400.000 (Tiền công, chưa bo mạch)'],
            ['cause_index' => 19, 'name' => 'Xử lý giắc cắm, lỗi giao tiếp tín hiệu', 'description' => 'Đấu nối lại cáp dữ liệu bị oxy hóa giữa bo hiển thị và bo công suất Inverter.', 'price_text' => '300.000 - 500.000'],
        ];
    }

    private function normalizePrice(string $priceText): ?float
    {
        preg_match_all('/\d[\d\.]*/', $priceText, $matches);

        $numbers = array_values(array_filter(array_map(function (string $raw): ?int {
            $normalized = preg_replace('/[^\d]/', '', $raw);
            if ($normalized === null || $normalized === '') {
                return null;
            }

            return (int) $normalized;
        }, $matches[0] ?? [])));

        if ($numbers === []) {
            return null;
        }

        if (count($numbers) === 1) {
            return (float) $numbers[0];
        }

        return (float) round(array_sum($numbers) / count($numbers), 2);
    }
}

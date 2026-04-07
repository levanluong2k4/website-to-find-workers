<?php

namespace Database\Seeders;

use App\Models\DanhMucDichVu;
use App\Models\HuongXuLy;
use App\Models\NguyenNhan;
use App\Models\TrieuChung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MicrowaveKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceId = $this->resolveMicrowaveServiceId();

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

    private function resolveMicrowaveServiceId(): int
    {
        $services = DanhMucDichVu::query()->get(['id', 'ten_dich_vu']);

        $service = $services->first(function (DanhMucDichVu $item): bool {
            $name = Str::lower(Str::ascii((string) $item->ten_dich_vu));
            return Str::contains($name, 'lo vi song');
        });

        if (!$service) {
            $service = $services->first(function (DanhMucDichVu $item): bool {
                $name = Str::lower(Str::ascii((string) $item->ten_dich_vu));
                return Str::contains($name, 'microwave');
            });
        }

        if ($service) {
            return (int) $service->id;
        }

        return (int) DanhMucDichVu::query()->create([
            'ten_dich_vu' => 'Sửa lò vi sóng',
            'mo_ta' => 'Sửa lò vi sóng không nóng, đĩa không quay, hỏng phím bấm,...',
            'hinh_anh' => 'microwave',
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

        if (!$model) {
            $model = TrieuChung::query()
                ->get()
                ->first(fn (TrieuChung $item): bool => $this->normalizeLookup($item->ten_trieu_chung) === $lookup);
        }

        if ($model) {
            if ((int) $model->dich_vu_id !== $serviceId || $model->ten_trieu_chung !== $name) {
                $model->forceFill([
                    'dich_vu_id' => $serviceId,
                    'ten_trieu_chung' => $name,
                ])->save();
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
     * @param array{cause_index:int,name:string,description:string,price:int|float|string|null} $row
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
            'gia_tham_khao' => $this->normalizePrice($row['price']),
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

    private function normalizePrice(int|float|string|null $price): ?float
    {
        if ($price === null || $price === '') {
            return null;
        }

        if (is_int($price) || is_float($price) || is_numeric($price)) {
            return round((float) $price, 2);
        }

        preg_match_all('/\d[\d\.]*/', (string) $price, $matches);
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

    /**
     * @return array<int, string>
     */
    private function symptoms(): array
    {
        return [
            1 => 'Lò vi sóng không nóng (Mã lỗi F16, F19 LG; H97, H98 Panasonic; H64)',
            2 => 'Lò vi sóng mất nguồn, tắt giữa chừng (Mã lỗi F8, PF, F3)',
            3 => 'Lò vi sóng không quay đĩa (Mã lỗi H10 Toshiba, H74 Panasonic)',
            4 => 'Lò vi sóng đánh tia lửa điện, nẹt lửa trong khoang lò',
            5 => 'Lò vi sóng kêu to, ồn ào bất thường (Mã lỗi H78, H79 Panasonic)',
            6 => 'Liệt phím, bảng điều khiển đơ (Mã lỗi SE Samsung; Err7 Whirlpool; F5 Bosch)',
            7 => 'Đèn bên trong lò vi sóng không sáng',
            8 => 'Cửa lò không đóng/mở được (Mã lỗi F1, F2 Bosch/Sharp; DOOR Whirlpool; LOCK LG)',
            9 => 'Lò vi sóng bốc khói hoặc có mùi khét',
            10 => 'Lò vi sóng bị rò rỉ điện, hở điện ra vỏ',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function causes(): array
    {
        return [
            1 => 'Hỏng cục phát sóng (Magnetron)',
            2 => 'Hỏng biến áp cao áp',
            3 => 'Hỏng tụ điện cao áp hoặc Diode',
            4 => 'Đứt cầu chì (nguồn/cao áp)',
            5 => 'Hỏng board mạch điều khiển (Bo vi xử lý/Inverter)',
            6 => 'Hỏng motor đĩa xoay hoặc kẹt trục quay',
            7 => 'Cháy, thủng lá chắn sóng (Mica)',
            8 => 'Hỏng quạt tản nhiệt',
            9 => 'Hỏng công tắc cửa hoặc chốt lò xo cửa',
            10 => 'Bóng đèn bị cháy/hỏng',
            11 => 'Sử dụng vật đựng bằng kim loại/giấy bạc gây phản xạ sóng',
            12 => 'Rỉ sét, hở lớp cách điện',
            13 => 'Hỏng Timer (Bộ định thời cơ học)',
        ];
    }

    /**
     * @return array<int, array<int>>
     */
    private function symptomCauseMap(): array
    {
        return [
            1 => [1, 2, 3, 4, 5, 13],
            2 => [4, 5, 8],
            3 => [6, 5],
            4 => [7, 1, 11, 12],
            5 => [8, 6, 1, 2],
            6 => [5],
            7 => [10, 9, 5],
            8 => [9, 5],
            9 => [7, 1, 2, 5],
            10 => [12, 2],
        ];
    }

    /**
     * @return array<int, array{cause_index:int,name:string,description:string,price:int|float|string|null}>
     */
    private function resolutions(): array
    {
        return [
            ['cause_index' => 1, 'name' => 'Thay cục sóng lò vi sóng cơ', 'price' => 450000, 'description' => 'Tháo vỏ, xả tụ, kiểm tra và thay thế cục phát sóng (Magnetron) lò cơ.'],
            ['cause_index' => 1, 'name' => 'Thay cục sóng lò vi sóng phím điện tử', 'price' => 550000, 'description' => 'Thay thế cục phát sóng cho lò phím điện tử chính hãng.'],
            ['cause_index' => 1, 'name' => 'Thay cục sóng lò vi sóng Inverter', 'price' => 850000, 'description' => 'Thay thế Magnetron chuyên dụng cho lò Inverter, đồng bộ hóa mạch biến tần.'],
            ['cause_index' => 2, 'name' => 'Thay biến áp cao áp', 'price' => 500000, 'description' => 'Xử lý đầu cosse, thay thế cụm biến áp cao áp mới, kiểm tra rò điện.'],
            ['cause_index' => 3, 'name' => 'Thay tụ điện lò vi sóng', 'price' => 300000, 'description' => 'Xả điện an toàn, đo dung lượng và thay tụ điện cao thế mới.'],
            ['cause_index' => 4, 'name' => 'Thay cầu chì lò vi sóng', 'price' => 150000, 'description' => 'Xác định nguyên nhân gây quá tải, thay cầu chì nguồn hoặc cầu chì cao áp.'],
            ['cause_index' => 5, 'name' => 'Sửa board mạch điều khiển cơ bản', 'price' => 550000, 'description' => 'Kiểm tra, hàn lại IC, thay rơ-le trung gian hoặc xử lý liệt phím màng than.'],
            ['cause_index' => 5, 'name' => 'Sửa board mạch Inverter', 'price' => 800000, 'description' => 'Thay thế linh kiện công suất (IGBT), sửa mạch driver Inverter.'],
            ['cause_index' => 6, 'name' => 'Thay motor đĩa thủy tinh', 'price' => 250000, 'description' => 'Tháo mặt đáy lò, thay thế động cơ xoay đồng bộ, vệ sinh bánh xe lăn.'],
            ['cause_index' => 7, 'name' => 'Thay tấm lá chắn sóng Mica', 'price' => 250000, 'description' => 'Vệ sinh sạch sẽ hốc dẫn sóng (loại bỏ mỡ khét), cắt và lắp tấm mica chắn sóng mới.'],
            ['cause_index' => 8, 'name' => 'Thay quạt tản nhiệt', 'price' => 400000, 'description' => 'Tháo cụm quạt, vệ sinh khe gió, thay động cơ quạt làm mát mới.'],
            ['cause_index' => 9, 'name' => 'Sửa/Thay công tắc cửa', 'price' => 250000, 'description' => 'Kiểm tra chốt cửa, lò xo, thay thế cụm công tắc hành trình cửa lò.'],
            ['cause_index' => 10, 'name' => 'Thay bóng đèn bên trong lò', 'price' => 150000, 'description' => 'Tháo ốp bảo vệ, thay bóng đèn chiếu sáng chịu nhiệt chuyên dụng.'],
            ['cause_index' => 12, 'name' => 'Sơn, xử lý rỉ sét khoang lò', 'price' => 450000, 'description' => 'Tẩy rỉ sét, sơn lại bằng sơn tĩnh điện/sơn chịu nhiệt chuyên dụng.'],
            ['cause_index' => 12, 'name' => 'Thay vỏ lò vi sóng', 'price' => 500000, 'description' => 'Tháo toàn bộ linh kiện nội thất để chuyển sang khung vỏ mới không bị thủng/rỉ.'],
            ['cause_index' => 13, 'name' => 'Thay bộ Timer (Lò cơ)', 'price' => 450000, 'description' => 'Tháo lắp bộ định thời bánh răng, đấu nối lại cơ chế vặn giờ.'],
        ];
    }
}

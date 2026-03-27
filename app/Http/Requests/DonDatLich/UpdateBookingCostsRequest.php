<?php

namespace App\Http\Requests\DonDatLich;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingCostsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $laborItems = $this->normalizeLaborItems($this->input('chi_tiet_tien_cong'));
        if ($laborItems === [] && $this->normalizeAmount($this->input('tien_cong')) > 0) {
            $laborItems = [[
                'noi_dung' => 'Tien cong sua chua',
                'so_tien' => $this->normalizeAmount($this->input('tien_cong')),
            ]];
        }

        $partsItems = $this->normalizePartItems($this->input('chi_tiet_linh_kien'));
        if (
            $partsItems === []
            && (
                $this->normalizeAmount($this->input('phi_linh_kien')) > 0
                || trim((string) $this->input('ghi_chu_linh_kien', '')) !== ''
            )
        ) {
            $partsItems = [[
                'noi_dung' => $this->extractLegacyPartDescription((string) $this->input('ghi_chu_linh_kien', '')),
                'so_tien' => $this->normalizeAmount($this->input('phi_linh_kien')),
                'bao_hanh_thang' => null,
            ]];
        }

        $this->merge([
            'tien_cong' => $this->normalizeAmount($this->input('tien_cong')),
            'phi_linh_kien' => $this->normalizeAmount($this->input('phi_linh_kien')),
            'tien_thue_xe' => $this->normalizeAmount($this->input('tien_thue_xe')),
            'chi_tiet_tien_cong' => $laborItems,
            'chi_tiet_linh_kien' => $partsItems,
            'ghi_chu_linh_kien' => $this->normalizeNullableString($this->input('ghi_chu_linh_kien')),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tien_cong' => 'nullable|numeric|min:0',
            'tien_thue_xe' => 'nullable|numeric|min:0',
            'phi_linh_kien' => 'nullable|numeric|min:0',
            'ghi_chu_linh_kien' => 'nullable|string',
            'chi_tiet_tien_cong' => 'required|array|min:1',
            'chi_tiet_tien_cong.*.noi_dung' => 'required|string|max:255',
            'chi_tiet_tien_cong.*.so_tien' => 'required|numeric|min:0',
            'chi_tiet_linh_kien' => 'nullable|array',
            'chi_tiet_linh_kien.*.linh_kien_id' => 'nullable|integer|exists:linh_kien,id',
            'chi_tiet_linh_kien.*.noi_dung' => 'required|string|max:255',
            'chi_tiet_linh_kien.*.so_tien' => 'required|numeric|min:0',
            'chi_tiet_linh_kien.*.bao_hanh_thang' => 'nullable|integer|min:0|max:60',
        ];
    }

    public function messages(): array
    {
        return [
            'chi_tiet_tien_cong.required' => 'Vui long nhap it nhat 1 dong tien cong.',
            'chi_tiet_tien_cong.min' => 'Vui long nhap it nhat 1 dong tien cong.',
            'chi_tiet_tien_cong.*.noi_dung.required' => 'Moi dong tien cong can co noi dung.',
            'chi_tiet_tien_cong.*.so_tien.required' => 'Moi dong tien cong can co so tien.',
            'chi_tiet_linh_kien.*.noi_dung.required' => 'Moi linh kien can co ten hoac noi dung thay the.',
            'chi_tiet_linh_kien.*.so_tien.required' => 'Moi linh kien can co gia tien.',
            'chi_tiet_linh_kien.*.bao_hanh_thang.integer' => 'Bao hanh linh kien phai la so thang hop le.',
        ];
    }

    private function normalizeLaborItems(mixed $items): array
    {
        return array_values(array_filter(array_map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $description = trim((string) ($item['noi_dung'] ?? ''));
            $amount = $this->normalizeAmount($item['so_tien'] ?? 0);

            if ($description === '' && $amount <= 0) {
                return null;
            }

            return [
                'noi_dung' => $description,
                'so_tien' => $amount,
            ];
        }, $this->normalizeItemPayload($items))));
    }

    private function normalizePartItems(mixed $items): array
    {
        return array_values(array_filter(array_map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $description = trim((string) ($item['noi_dung'] ?? ''));
            $amount = $this->normalizeAmount($item['so_tien'] ?? 0);
            $warrantyMonths = $item['bao_hanh_thang'] ?? null;
            $warrantyMonths = $warrantyMonths === '' || $warrantyMonths === null
                ? null
                : (int) $warrantyMonths;

            if ($description === '' && $amount <= 0 && $warrantyMonths === null) {
                return null;
            }

            return [
                'linh_kien_id' => isset($item['linh_kien_id']) && $item['linh_kien_id'] !== ''
                    ? (int) $item['linh_kien_id']
                    : null,
                'noi_dung' => $description,
                'so_tien' => $amount,
                'bao_hanh_thang' => $warrantyMonths,
            ];
        }, $this->normalizeItemPayload($items))));
    }

    private function normalizeItemPayload(mixed $items): array
    {
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : [];
        }

        return is_array($items) ? $items : [];
    }

    private function normalizeAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return is_numeric($value) ? (float) $value : 0;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private function extractLegacyPartDescription(string $note): string
    {
        $note = trim($note);
        if ($note === '') {
            return 'Linh kien thay the';
        }

        $firstLine = preg_split('/\r\n|\r|\n/', $note)[0] ?? $note;
        $firstLine = trim($firstLine);

        return $firstLine !== '' ? $firstLine : 'Linh kien thay the';
    }
}

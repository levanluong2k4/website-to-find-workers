<?php

namespace App\Http\Requests\DanhGia;

use Illuminate\Foundation\Http\FormRequest;

class StoreDanhGiaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'video_duration' => $this->normalizeVideoDuration($this->input('video_duration')),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['customer', 'admin'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'don_dat_lich_id' => 'required|exists:don_dat_lich,id',
            'so_sao' => 'required|integer|min:1|max:5',
            'nhan_xet' => 'nullable|string|max:1000',
            'hinh_anh_danh_gia' => 'nullable|array|max:5',
            'hinh_anh_danh_gia.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'video_danh_gia' => 'nullable|file|mimes:mp4,mov,avi,wmv,webm|max:20480',
            'video_duration' => 'nullable|numeric|max:20',
        ];
    }

    private function normalizeVideoDuration(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}

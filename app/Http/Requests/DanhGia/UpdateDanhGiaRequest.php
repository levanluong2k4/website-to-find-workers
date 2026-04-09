<?php

namespace App\Http\Requests\DanhGia;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDanhGiaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $existingImages = $this->input('existing_hinh_anh_danh_gia', []);

        if (!is_array($existingImages)) {
            $existingImages = [$existingImages];
        }

        $this->merge([
            'existing_hinh_anh_danh_gia' => collect($existingImages)
                ->filter(static fn ($value) => is_string($value) && trim($value) !== '')
                ->values()
                ->all(),
            'keep_existing_video' => filter_var($this->input('keep_existing_video', false), FILTER_VALIDATE_BOOLEAN),
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
            'so_sao' => 'required|integer|min:1|max:5',
            'nhan_xet' => 'nullable|string|max:1000',
            'existing_hinh_anh_danh_gia' => 'nullable|array|max:5',
            'existing_hinh_anh_danh_gia.*' => 'url|max:2048',
            'hinh_anh_danh_gia' => 'nullable|array|max:5',
            'hinh_anh_danh_gia.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'video_danh_gia' => 'nullable|file|mimes:mp4,mov,avi,wmv,webm|max:20480',
            'keep_existing_video' => 'nullable|boolean',
            'video_duration' => 'nullable|numeric|max:20',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $keptImages = count($this->input('existing_hinh_anh_danh_gia', []));
            $newImages = count($this->file('hinh_anh_danh_gia', []));

            if (($keptImages + $newImages) > 5) {
                $validator->errors()->add('hinh_anh_danh_gia', 'Toi da 5 anh cho moi danh gia.');
            }

            if ($this->boolean('keep_existing_video') && $this->hasFile('video_danh_gia')) {
                $validator->errors()->add('video_danh_gia', 'Chi duoc giu video cu hoac tai video moi.');
            }
        });
    }

    private function normalizeVideoDuration(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}

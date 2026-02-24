<?php

namespace App\Http\Requests\HoSoTho;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHoSoThoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Require the user to be a worker to update their own profile
        return $this->user() && $this->user()->role === 'worker';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cccd' => 'nullable|string|max:20',
            'kinh_nghiem' => 'nullable|string|max:1000',
            'chung_chi' => 'nullable|string|max:1000',
            'bang_gia_tham_khao' => 'nullable|string',
            'vi_do' => 'nullable|numeric|between:-90,90',
            'kinh_do' => 'nullable|numeric|between:-180,180',
            'ban_kinh_phuc_vu' => 'nullable|numeric|min:0',
            'dang_hoat_dong' => 'nullable|boolean',
            'dich_vu_ids' => 'nullable|array',
            'dich_vu_ids.*' => 'exists:danh_muc_dich_vu,id'
        ];
    }
}

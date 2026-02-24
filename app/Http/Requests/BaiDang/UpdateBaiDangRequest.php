<?php

namespace App\Http\Requests\BaiDang;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBaiDangRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'customer';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dich_vu_id' => 'nullable|exists:danh_muc_dich_vu,id',
            'tieu_de' => 'nullable|string|max:255',
            'mo_ta_chi_tiet' => 'nullable|string|max:2000',
            'muc_gia_du_kien' => 'nullable|numeric|min:0',
            'dia_chi' => 'nullable|string|max:500',
            'vi_do' => 'nullable|numeric|between:-90,90',
            'kinh_do' => 'nullable|numeric|between:-180,180',
            'hinh_anhs' => 'nullable|array|max:5',
            'hinh_anhs.*' => 'string'
        ];
    }
}

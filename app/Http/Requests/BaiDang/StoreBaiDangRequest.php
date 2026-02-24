<?php

namespace App\Http\Requests\BaiDang;

use Illuminate\Foundation\Http\FormRequest;

class StoreBaiDangRequest extends FormRequest
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
            'dich_vu_id' => 'required|exists:danh_muc_dich_vu,id',
            'tieu_de' => 'required|string|max:255',
            'mo_ta_chi_tiet' => 'required|string|max:2000',
            'muc_gia_du_kien' => 'required|numeric|min:0',
            'dia_chi' => 'required|string|max:500',
            'vi_do' => 'required|numeric|between:-90,90',
            'kinh_do' => 'required|numeric|between:-180,180',
            'hinh_anhs' => 'nullable|array|max:5', // Tối đa 5 hình
            'hinh_anhs.*' => 'string' // Base64 hoặc URL
        ];
    }
}

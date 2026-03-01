<?php

namespace App\Http\Requests\DanhGia;

use Illuminate\Foundation\Http\FormRequest;

class StoreDanhGiaRequest extends FormRequest
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
            'don_dat_lich_id' => 'required|exists:don_dat_lich,id',
            'so_sao' => 'required|integer|min:1|max:5',
            'nhan_xet' => 'nullable|string|max:1000'
        ];
    }
}

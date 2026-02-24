<?php

namespace App\Http\Requests\DonDatLich;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrangThaiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We handles authorization in Controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trang_thai' => 'required|string|in:da_xac_nhan,dang_lam,cho_hoan_thanh,da_xong,da_huy',
            'ly_do_huy' => 'required_if:trang_thai,da_huy|string|max:1000'
        ];
    }
}

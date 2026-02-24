<?php

namespace App\Http\Requests\DanhMucDichVu;

use Illuminate\Foundation\Http\FormRequest;

class StoreDanhMucDichVuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Only admin can create service categories
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
            'ten_dich_vu' => 'required|string|max:255|unique:danh_muc_dich_vu',
            'mo_ta' => 'nullable|string',
            'hinh_anh' => 'nullable|string',
        ];
    }
}

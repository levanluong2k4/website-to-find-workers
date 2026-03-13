<?php

namespace App\Http\Requests\DanhMucDichVu;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDanhMucDichVuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'ten_dich_vu' => 'string|max:255|unique:danh_muc_dich_vu,ten_dich_vu,' . $id,
            'mo_ta' => 'nullable|string',
            'hinh_anh' => 'nullable|string',
            'trang_thai' => 'boolean'
        ];
    }
}

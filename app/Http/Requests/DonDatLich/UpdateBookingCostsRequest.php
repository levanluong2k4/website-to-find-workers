<?php

namespace App\Http\Requests\DonDatLich;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingCostsRequest extends FormRequest
{
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
            'tien_cong' => 'required|numeric|min:0',
            'tien_thue_xe' => 'nullable|numeric|min:0',
            'phi_linh_kien' => 'required|numeric|min:0',
            'ghi_chu_linh_kien' => 'nullable|string',
        ];
    }
}

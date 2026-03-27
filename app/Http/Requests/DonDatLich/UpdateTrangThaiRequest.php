<?php

namespace App\Http\Requests\DonDatLich;

use App\Models\DonDatLich;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'ma_ly_do_huy' => [
                Rule::requiredIf(fn () => $this->input('trang_thai') === 'da_huy'),
                'nullable',
                'string',
                Rule::in(DonDatLich::cancelReasonCodes()),
            ],
            'ly_do_huy' => 'nullable|string|max:1000',
        ];
    }
}

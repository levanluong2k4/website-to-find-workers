<?php

namespace App\Http\Requests\BaoGia;

use Illuminate\Foundation\Http\FormRequest;

class StoreBaoGiaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'bai_dang_id' => 'required|exists:bai_dang,id',
            'muc_gia' => 'required|numeric|min:0',
            'ghi_chu' => 'nullable|string|max:2000'
        ];
    }
}

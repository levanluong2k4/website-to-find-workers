<?php

namespace App\Http\Requests\DonDatLich;

use Illuminate\Foundation\Http\FormRequest;

class StoreDonDatLichRequest extends FormRequest
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
            'loai_dat_lich' => 'required|in:at_home,at_store',
            'dich_vu_id' => 'required|exists:danh_muc_dich_vu,id',
            'tho_id' => 'nullable|exists:users,id',
            'ngay_hen' => 'required|date|after_or_equal:today',
            'khung_gio_hen' => 'required|in:08:00-10:00,10:00-12:00,12:00-14:00,14:00-17:00,08:00 - 10:00,10:00 - 12:00,12:00 - 14:00,14:00 - 17:00',
            'dia_chi' => 'required_if:loai_dat_lich,at_home|nullable|string',
            'vi_do' => 'required_if:loai_dat_lich,at_home|nullable|numeric',
            'kinh_do' => 'required_if:loai_dat_lich,at_home|nullable|numeric',
            'mo_ta_van_de' => 'nullable|string',
            'thue_xe_cho' => 'nullable|boolean',
            'hinh_anh_mo_ta.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // max 5MB per image
            'video_mo_ta' => 'nullable|file|mimes:mp4,mov,avi,wmv|max:20480', // max 20MB
        ];
    }
}

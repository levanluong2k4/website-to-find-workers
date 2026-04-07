<?php

namespace App\Http\Requests\DonDatLich;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $latestBookingDate = now()->addDays(6)->toDateString();

        return [
            'ngay_hen' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', "before_or_equal:{$latestBookingDate}"],
            'khung_gio_hen' => ['required', 'in:08:00-10:00,10:00-12:00,12:00-14:00,14:00-17:00'],
        ];
    }

    public function messages(): array
    {
        return [
            'ngay_hen.required' => 'Vui long chon ngay hen moi.',
            'ngay_hen.date_format' => 'Ngay hen khong dung dinh dang.',
            'ngay_hen.after_or_equal' => 'Ngay hen phai tu hom nay tro di.',
            'ngay_hen.before_or_equal' => 'Ngay hen chi duoc doi trong 7 ngay toi.',
            'khung_gio_hen.required' => 'Vui long chon khung gio moi.',
            'khung_gio_hen.in' => 'Khung gio hen khong hop le.',
        ];
    }
}

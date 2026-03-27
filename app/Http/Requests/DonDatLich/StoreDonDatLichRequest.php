<?php

namespace App\Http\Requests\DonDatLich;

use App\Models\HoSoTho;
use Illuminate\Foundation\Http\FormRequest;

class StoreDonDatLichRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $serviceIds = $this->input('dich_vu_ids', []);

        if ((is_array($serviceIds) && empty($serviceIds)) || $serviceIds === null || $serviceIds === '') {
            $serviceIds = $this->input('dich_vu_id', []);
        }

        if (!is_array($serviceIds)) {
            $serviceIds = [$serviceIds];
        }

        $serviceIds = collect($serviceIds)
            ->filter(static fn ($value) => $value !== null && $value !== '')
            ->map(static fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'dich_vu_ids' => $serviceIds,
            'dich_vu_id' => $serviceIds[0] ?? null,
        ]);
    }

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
        $latestBookingDate = now()->addDays(6)->toDateString();

        return [
            'loai_dat_lich' => 'required|in:at_home,at_store',
            'dich_vu_ids' => 'required|array|min:1',
            'dich_vu_ids.*' => 'required|exists:danh_muc_dich_vu,id',
            'tho_id' => 'nullable|exists:users,id',
            'ngay_hen' => "required|date|after_or_equal:today|before_or_equal:{$latestBookingDate}",
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

    public function messages(): array
    {
        return [
            'ngay_hen.after_or_equal' => 'Ngay hen phai tu hom nay tro di.',
            'ngay_hen.before_or_equal' => 'Ngay hen chi duoc dat trong 7 ngay toi.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $workerId = $this->input('tho_id');
            $serviceIds = collect($this->input('dich_vu_ids', []))
                ->map(static fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if (!$workerId || $serviceIds->isEmpty()) {
                return;
            }

            $hoSoTho = HoSoTho::with('user.dichVus:id')
                ->where('user_id', $workerId)
                ->first();

            if (
                !$hoSoTho ||
                !$hoSoTho->user ||
                !$hoSoTho->dang_hoat_dong ||
                $hoSoTho->trang_thai_duyet !== 'da_duyet' ||
                !$hoSoTho->user->is_active
            ) {
                $validator->errors()->add('tho_id', 'Tho duoc chon hien khong kha dung de nhan don.');
                return;
            }

            $workerServiceIds = $hoSoTho->user->dichVus
                ->pluck('id')
                ->map(static fn ($id) => (int) $id);

            $missingServiceIds = $serviceIds->diff($workerServiceIds);

            if ($missingServiceIds->isNotEmpty()) {
                $validator->errors()->add('dich_vu_ids', 'Danh sach dich vu da chon co muc thợ nay khong the sua.');
            }
        });
    }
}

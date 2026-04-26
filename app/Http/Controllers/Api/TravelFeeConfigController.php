<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TravelFeeConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TravelFeeConfigController extends Controller
{
    public function public(TravelFeeConfigService $travelFeeConfigService)
    {
        return response()->json([
            'status' => 'success',
            'data' => $travelFeeConfigService->getPublicState(),
        ]);
    }

    public function show(TravelFeeConfigService $travelFeeConfigService)
    {
        return response()->json([
            'status' => 'success',
            'data' => $travelFeeConfigService->getEditorState(),
        ]);
    }

    public function update(Request $request, TravelFeeConfigService $travelFeeConfigService)
    {
        $validator = Validator::make($request->all(), [
            'free_distance_km' => 'nullable|numeric|min:0|max:1000',
            'max_service_distance_km' => 'nullable|numeric|min:0|max:1000',
            'default_per_km' => 'nullable|numeric|min:0|max:1000000',
            'store_address' => 'required|string|max:500',
            'store_latitude' => 'nullable|numeric|between:-90,90',
            'store_longitude' => 'nullable|numeric|between:-180,180',
            'store_transport_fee' => 'nullable|numeric|min:0|max:100000000',
            'store_hotline' => 'nullable|string|max:50',
            'store_opening_hours' => 'nullable|string|max:100',
            'booking_time_slots' => 'required|array|min:1|max:12',
            'booking_time_slots.*' => 'required|string|max:20',
            'complaint_window_days' => 'nullable|integer|min:1|max:30',
            'tiers' => 'required|array|min:1|max:20',
            'tiers.*.from_km' => 'required|numeric|min:0|max:1000',
            'tiers.*.to_km' => 'required|numeric|min:0|max:1000',
            'tiers.*.transport_fee' => 'required|numeric|min:0|max:100000000',
            'tiers.*.travel_fee' => 'required|numeric|min:0|max:100000000',
        ]);

        $validator->after(function ($validator) use ($request) {
            $tiers = collect($request->input('tiers', []))
                ->filter(static fn ($tier) => is_array($tier))
                ->map(function (array $tier, int $index): array {
                    return [
                        'index' => $index,
                        'from_km' => (float) ($tier['from_km'] ?? 0),
                        'to_km' => (float) ($tier['to_km'] ?? 0),
                    ];
                })
                ->sort(function (array $left, array $right): int {
                    if ($left['from_km'] === $right['from_km']) {
                        return $left['to_km'] <=> $right['to_km'];
                    }

                    return $left['from_km'] <=> $right['from_km'];
                })
                ->values();

            $previousToKm = null;

            foreach ($tiers as $tier) {
                if ($tier['to_km'] <= $tier['from_km']) {
                    $validator->errors()->add(
                        'tiers.' . $tier['index'] . '.to_km',
                        'Moc den km phai lon hon moc tu km.'
                    );
                }

                if ($previousToKm !== null && $tier['from_km'] < $previousToKm) {
                    $validator->errors()->add(
                        'tiers.' . $tier['index'] . '.from_km',
                        'Khoang cach dang bi chong len voi dong truoc.'
                    );
                }

                $previousToKm = $tier['to_km'];
            }

            $hasStoreLatitude = trim((string) $request->input('store_latitude', '')) !== '';
            $hasStoreLongitude = trim((string) $request->input('store_longitude', '')) !== '';

            if ($hasStoreLatitude xor $hasStoreLongitude) {
                $validator->errors()->add(
                    $hasStoreLatitude ? 'store_longitude' : 'store_latitude',
                    'Vui long nhap day du ca vi do va kinh do cua cua hang.'
                );
            }

            $slots = collect($request->input('booking_time_slots', []))
                ->map(fn ($slot, $index) => [
                    'index' => $index,
                    'value' => preg_replace('/\s+/', '', (string) $slot) ?: '',
                ])
                ->filter(fn (array $slot) => $slot['value'] !== '')
                ->map(function (array $slot): array {
                    preg_match('/^(?<start>\d{2}:\d{2})-(?<end>\d{2}:\d{2})$/', $slot['value'], $matches);

                    return [
                        ...$slot,
                        'start' => $matches['start'] ?? null,
                        'end' => $matches['end'] ?? null,
                    ];
                })
                ->values();

            if ($slots->isEmpty()) {
                $validator->errors()->add('booking_time_slots', 'Vui lòng cấu hình ít nhất 1 khung giờ đặt đơn.');
            }

            $seenValues = [];
            $sortedSlots = collect();

            foreach ($slots as $slot) {
                if ($slot['start'] === null || $slot['end'] === null) {
                    $validator->errors()->add(
                        'booking_time_slots.' . $slot['index'],
                        'Khung giờ phải có định dạng HH:MM-HH:MM.'
                    );
                    continue;
                }

                $startMinutes = $this->slotTimeToMinutes($slot['start']);
                $endMinutes = $this->slotTimeToMinutes($slot['end']);

                if ($startMinutes === null || $endMinutes === null || $endMinutes <= $startMinutes) {
                    $validator->errors()->add(
                        'booking_time_slots.' . $slot['index'],
                        'Giờ kết thúc phải lớn hơn giờ bắt đầu.'
                    );
                    continue;
                }

                if (in_array($slot['value'], $seenValues, true)) {
                    $validator->errors()->add(
                        'booking_time_slots.' . $slot['index'],
                        'Khung giờ này đang bị trùng.'
                    );
                    continue;
                }

                $seenValues[] = $slot['value'];
                $sortedSlots->push([
                    ...$slot,
                    'start_minutes' => $startMinutes,
                    'end_minutes' => $endMinutes,
                ]);
            }

            $sortedSlots = $sortedSlots->sortBy('start_minutes')->values();

            for ($index = 1; $index < $sortedSlots->count(); $index += 1) {
                $previous = $sortedSlots[$index - 1];
                $current = $sortedSlots[$index];

                if ($current['start_minutes'] < $previous['end_minutes']) {
                    $validator->errors()->add(
                        'booking_time_slots.' . $current['index'],
                        'Khung giờ này đang chồng lấn với khung trước.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = [
            'store_address' => $request->input('store_address'),
            'tiers' => $request->input('tiers', []),
        ];

        if ($request->exists('free_distance_km')) {
            $payload['free_distance_km'] = (float) $request->input('free_distance_km');
        }

        if ($request->exists('max_service_distance_km')) {
            $payload['max_service_distance_km'] = (float) $request->input('max_service_distance_km');
        }

        if ($request->exists('default_per_km')) {
            $payload['default_per_km'] = (float) $request->input('default_per_km');
        }

        if ($request->exists('store_latitude')) {
            $payload['store_latitude'] = $request->filled('store_latitude')
                ? (float) $request->input('store_latitude')
                : null;
        }

        if ($request->exists('store_longitude')) {
            $payload['store_longitude'] = $request->filled('store_longitude')
                ? (float) $request->input('store_longitude')
                : null;
        }

        if ($request->exists('store_transport_fee')) {
            $payload['store_transport_fee'] = (float) $request->input('store_transport_fee');
        }

        if ($request->exists('store_hotline')) {
            $payload['store_hotline'] = (string) $request->input('store_hotline');
        }

        if ($request->exists('store_opening_hours')) {
            $payload['store_opening_hours'] = (string) $request->input('store_opening_hours');
        }

        if ($request->exists('booking_time_slots')) {
            $payload['booking_time_slots'] = collect($request->input('booking_time_slots', []))
                ->map(fn ($slot) => preg_replace('/\s+/', '', (string) $slot) ?: '')
                ->filter()
                ->values()
                ->all();
        }

        if ($request->exists('complaint_window_days')) {
            $payload['complaint_window_days'] = (int) $request->input('complaint_window_days');
        }

        $state = $travelFeeConfigService->updateConfig($payload, $request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat bang phi van chuyen theo khoang cach',
            'data' => $state,
        ]);
    }

    private function slotTimeToMinutes(string $value): ?int
    {
        if (!preg_match('/^(?<hour>\d{2}):(?<minute>\d{2})$/', trim($value), $matches)) {
            return null;
        }

        $hour = (int) $matches['hour'];
        $minute = (int) $matches['minute'];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return ($hour * 60) + $minute;
    }
}

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
            'default_per_km' => 'nullable|numeric|min:0|max:1000000',
            'store_address' => 'required|string|max:500',
            'store_transport_fee' => 'nullable|numeric|min:0|max:100000000',
            'store_hotline' => 'nullable|string|max:50',
            'store_opening_hours' => 'nullable|string|max:100',
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

                if ($previousToKm !== null && $tier['from_km'] <= $previousToKm) {
                    $validator->errors()->add(
                        'tiers.' . $tier['index'] . '.from_km',
                        'Khoang cach dang bi chong len hoac cham moc ket thuc cua dong truoc.'
                    );
                }

                $previousToKm = $tier['to_km'];
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

        if ($request->exists('default_per_km')) {
            $payload['default_per_km'] = (float) $request->input('default_per_km');
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
}

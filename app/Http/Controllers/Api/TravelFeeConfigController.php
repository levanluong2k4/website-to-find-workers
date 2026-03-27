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
            'default_per_km' => 'required|numeric|min:0|max:1000000',
            'tiers' => 'nullable|array|max:20',
            'tiers.*.from_km' => 'required|numeric|min:0|max:1000',
            'tiers.*.to_km' => 'required|numeric|min:0|max:1000',
            'tiers.*.fee' => 'required|numeric|min:0|max:100000000',
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
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $state = $travelFeeConfigService->updateConfig([
            'default_per_km' => (float) $request->input('default_per_km', 5000),
            'tiers' => $request->input('tiers', []),
        ], $request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat bang phi di lai',
            'data' => $state,
        ]);
    }
}

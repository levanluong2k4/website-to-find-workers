<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HuongXuLy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HuongXuLyController extends Controller
{
    public function index(Request $request)
    {
        $serviceIds = $this->normalizeServiceIds(
            $request->input('dich_vu_ids', $request->input('dich_vu_id'))
        );
        $keyword = trim((string) $request->input('keyword', ''));
        $groupBySymptom = $request->boolean('group_by_symptom');

        $query = HuongXuLy::query()
            ->with([
                'nguyenNhan:id,ten_nguyen_nhan',
                'nguyenNhan.trieuChungs' => function ($builder) use ($serviceIds) {
                    $builder->select('trieu_chung.id', 'trieu_chung.dich_vu_id', 'trieu_chung.ten_trieu_chung');

                    if ($serviceIds !== []) {
                        $builder->whereIn('trieu_chung.dich_vu_id', $serviceIds);
                    }
                },
                'nguyenNhan.trieuChungs.dichVu:id,ten_dich_vu',
            ])
            ->when($serviceIds !== [], function ($builder) use ($serviceIds) {
                $builder->whereHas('nguyenNhan.trieuChungs', function ($nested) use ($serviceIds) {
                    $nested->whereIn('trieu_chung.dich_vu_id', $serviceIds);
                });
            })
            ->when($keyword !== '', function ($builder) use ($keyword) {
                $builder->where(function ($nested) use ($keyword) {
                    $nested->where('ten_huong_xu_ly', 'like', '%' . $keyword . '%')
                        ->orWhere('mo_ta_cong_viec', 'like', '%' . $keyword . '%')
                        ->orWhereHas('nguyenNhan', function ($causeQuery) use ($keyword) {
                            $causeQuery->where('ten_nguyen_nhan', 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('nguyenNhan.trieuChungs', function ($symptomQuery) use ($keyword) {
                            $symptomQuery->where('ten_trieu_chung', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->orderBy('ten_huong_xu_ly');

        $resolutions = $query->get();

        if ($groupBySymptom) {
            return response()->json($this->buildGroupedSymptoms($resolutions));
        }

        return response()->json(
            $resolutions->map(function (HuongXuLy $resolution): array {
                $symptoms = $resolution->nguyenNhan?->trieuChungs ?? collect();
                $services = $symptoms
                    ->map(static fn ($symptom) => $symptom->dichVu)
                    ->filter()
                    ->unique('id')
                    ->values();

                return [
                    'id' => $resolution->id,
                    'nguyen_nhan_id' => $resolution->nguyen_nhan_id,
                    'ten_huong_xu_ly' => $resolution->ten_huong_xu_ly,
                    'gia_tham_khao' => $resolution->gia_tham_khao === null
                        ? null
                        : (float) $resolution->gia_tham_khao,
                    'mo_ta_cong_viec' => $resolution->mo_ta_cong_viec,
                    'nguyen_nhan' => $resolution->nguyenNhan === null
                        ? null
                        : [
                            'id' => $resolution->nguyenNhan->id,
                            'ten_nguyen_nhan' => $resolution->nguyenNhan->ten_nguyen_nhan,
                        ],
                    'dich_vus' => $services->map(static function ($service): array {
                        return [
                            'id' => $service->id,
                            'ten_dich_vu' => $service->ten_dich_vu,
                        ];
                    })->all(),
                    'trieu_chungs' => $symptoms
                        ->unique('id')
                        ->values()
                        ->take(3)
                        ->map(static function ($symptom): array {
                            return [
                                'id' => $symptom->id,
                                'ten_trieu_chung' => $symptom->ten_trieu_chung,
                                'dich_vu_id' => $symptom->dich_vu_id,
                            ];
                        })->all(),
                ];
            })->values()
        );
    }

    private function buildGroupedSymptoms(Collection $resolutions): array
    {
        return $resolutions
            ->flatMap(function (HuongXuLy $resolution) {
                $cause = $resolution->nguyenNhan;
                $symptoms = $cause?->trieuChungs ?? collect();

                return $symptoms->map(static function ($symptom) use ($resolution, $cause): array {
                    return [
                        'symptom' => $symptom,
                        'resolution' => $resolution,
                        'cause' => $cause,
                    ];
                });
            })
            ->groupBy(static fn (array $item) => (int) ($item['symptom']->id ?? 0))
            ->map(function (Collection $items): array {
                $first = $items->first();
                $symptom = $first['symptom'];
                $prices = $items
                    ->map(static fn (array $item) => $item['resolution']->gia_tham_khao)
                    ->filter(static fn ($price) => $price !== null && (float) $price > 0)
                    ->map(static fn ($price) => (float) $price)
                    ->values();
                $causeNames = $items
                    ->map(static fn (array $item) => trim((string) ($item['cause']?->ten_nguyen_nhan ?? '')))
                    ->filter()
                    ->unique()
                    ->values();
                $resolutionNames = $items
                    ->map(static fn (array $item) => trim((string) ($item['resolution']->ten_huong_xu_ly ?? '')))
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'id' => (int) $symptom->id,
                    'ten_trieu_chung' => $symptom->ten_trieu_chung,
                    'dich_vu_id' => (int) ($symptom->dich_vu_id ?? 0),
                    'dich_vu_name' => $symptom->dichVu?->ten_dich_vu,
                    'gia_tham_khao_tu' => $prices->isEmpty() ? null : (float) $prices->min(),
                    'gia_tham_khao_den' => $prices->isEmpty() ? null : (float) $prices->max(),
                    'gia_tham_khao_count' => $prices->count(),
                    'nguyen_nhan_names' => $causeNames->all(),
                    'huong_xu_ly_count' => $resolutionNames->count(),
                    'huong_xu_ly_names' => $resolutionNames->all(),
                ];
            })
            ->sortBy([
                ['dich_vu_name', 'asc'],
                ['ten_trieu_chung', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function normalizeServiceIds(mixed $value): array
    {
        if (is_string($value)) {
            $value = str_contains($value, ',')
                ? explode(',', $value)
                : [$value];
        }

        if (!is_array($value)) {
            $value = $value === null ? [] : [$value];
        }

        return collect($value)
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}

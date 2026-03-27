<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LinhKien;
use Illuminate\Http\Request;

class LinhKienController extends Controller
{
    public function index(Request $request)
    {
        $serviceIds = $this->normalizeServiceIds(
            $request->input('dich_vu_ids', $request->input('dich_vu_id'))
        );
        $keyword = trim((string) $request->input('keyword', ''));

        $query = LinhKien::query()
            ->with('dichVu:id,ten_dich_vu')
            ->when($serviceIds !== [], function ($builder) use ($serviceIds) {
                $builder->whereIn('dich_vu_id', $serviceIds);
            })
            ->when($keyword !== '', function ($builder) use ($keyword) {
                $builder->where('ten_linh_kien', 'like', '%' . $keyword . '%');
            })
            ->orderBy('ten_linh_kien');

        return response()->json($query->get());
    }

    public function show(string $id)
    {
        $part = LinhKien::query()
            ->with('dichVu:id,ten_dich_vu,mo_ta,hinh_anh')
            ->find($id);

        if ($part === null) {
            return response()->json(['message' => 'Khong tim thay linh kien'], 404);
        }

        $related = LinhKien::query()
            ->with('dichVu:id,ten_dich_vu')
            ->where('dich_vu_id', $part->dich_vu_id)
            ->whereKeyNot($part->id)
            ->orderByRaw('gia IS NULL')
            ->orderBy('gia')
            ->orderBy('ten_linh_kien')
            ->limit(6)
            ->get();

        return response()->json([
            'data' => $part,
            'related' => $related,
            'service_part_count' => LinhKien::query()
                ->where('dich_vu_id', $part->dich_vu_id)
                ->count(),
        ]);
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

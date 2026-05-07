<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAddressController extends Controller
{
    /** GET /user/addresses */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('la_mac_dinh')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $addresses]);
    }

    /** POST /user/addresses */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'  => 'nullable|string|max:50',
            'tinh'   => 'required|string|max:100',
            'xa'     => 'required|string|max:100',
            'so_nha' => 'required|string|max:255',
        ]);

        $user = $request->user();

        $data['dia_chi_day_du'] = implode(', ', array_filter([
            $data['so_nha'], $data['xa'], $data['tinh'],
        ]));
        $data['user_id'] = $user->id;

        // First address becomes default automatically
        $isFirst = !$user->addresses()->exists();
        $data['la_mac_dinh'] = $isFirst;

        $address = UserAddress::create($data);

        return response()->json([
            'message' => 'Đã thêm địa chỉ mới.',
            'data'    => $address,
        ], 201);
    }

    /** PUT /user/addresses/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $address = $this->findOwned($request, $id);

        $data = $request->validate([
            'label'  => 'nullable|string|max:50',
            'tinh'   => 'required|string|max:100',
            'xa'     => 'required|string|max:100',
            'so_nha' => 'required|string|max:255',
        ]);

        $data['dia_chi_day_du'] = implode(', ', array_filter([
            $data['so_nha'], $data['xa'], $data['tinh'],
        ]));

        $address->update($data);

        return response()->json(['message' => 'Đã cập nhật địa chỉ.', 'data' => $address]);
    }

    /** POST /user/addresses/{id}/set-default */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $address = $this->findOwned($request, $id);

        DB::transaction(function () use ($user, $address) {
            $user->addresses()->update(['la_mac_dinh' => false]);
            $address->update(['la_mac_dinh' => true]);
        });

        return response()->json(['message' => 'Đã đặt địa chỉ mặc định.', 'data' => $address->fresh()]);
    }

    /** DELETE /user/addresses/{id} */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user    = $request->user();
        $address = $this->findOwned($request, $id);
        $wasDefault = $address->la_mac_dinh;

        $address->delete();

        // Promote next address to default if deleted was default
        if ($wasDefault) {
            $next = $user->addresses()->orderByDesc('created_at')->first();
            $next?->update(['la_mac_dinh' => true]);
        }

        return response()->json(['message' => 'Đã xóa địa chỉ.']);
    }

    private function findOwned(Request $request, int $id): UserAddress
    {
        return $request->user()->addresses()->findOrFail($id);
    }
}

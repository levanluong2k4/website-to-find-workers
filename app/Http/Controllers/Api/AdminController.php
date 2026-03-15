<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DanhMucDichVu;
use App\Models\DonDatLich;
use App\Models\HoSoTho;
use App\Models\User;
use App\Services\Chat\AssistantSoulConfigService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function getDashboardStats()
    {
        $totalCustomers = User::where('role', 'customer')->count();
        $totalWorkers = User::where('role', 'worker')->count();
        $pendingWorkerProfiles = HoSoTho::where('trang_thai_duyet', 'cho_duyet')->count();

        $totalBookings = DonDatLich::count();
        $completedBookings = DonDatLich::whereIn('trang_thai', ['hoan_thanh', 'da_xong'])->count();
        $canceledBookings = DonDatLich::where('trang_thai', 'da_huy')->count();

        $totalRevenue = DonDatLich::whereIn('trang_thai', ['hoan_thanh', 'da_xong'])
            ->sum('tong_tien');

        $systemCommission = $totalRevenue * 0.10;

        return response()->json([
            'status' => 'success',
            'data' => [
                'users' => [
                    'customers' => $totalCustomers,
                    'workers' => $totalWorkers,
                    'pending_worker_profiles' => $pendingWorkerProfiles,
                ],
                'bookings' => [
                    'total' => $totalBookings,
                    'completed' => $completedBookings,
                    'canceled' => $canceledBookings,
                ],
                'revenue' => [
                    'total_revenue' => $totalRevenue,
                    'system_commission' => $systemCommission,
                ],
            ],
        ]);
    }

    public function getUsers(Request $request)
    {
        $role = $request->query('role');

        $query = User::query()
            ->with([
                'hoSoTho',
                'dichVus:id,ten_dich_vu',
            ])
            ->where('role', '!=', 'admin');

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->orderByDesc('created_at')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    public function toggleUserStatus(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong the thay doi trang thai cua admin.',
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => $user->is_active ? 'Da mo khoa tai khoan' : 'Da khoa tai khoan',
            'data' => $user,
        ]);
    }

    public function getWorkerProfiles(Request $request)
    {
        $approvalStatus = $request->query('approval_status');

        $query = HoSoTho::query()
            ->with([
                'user:id,name,email,phone,avatar,is_active,created_at',
                'user.dichVus:id,ten_dich_vu',
            ])
            ->latest('updated_at');

        if ($approvalStatus) {
            $query->where('trang_thai_duyet', $approvalStatus);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    public function updateWorkerApproval(Request $request, string $userId)
    {
        $validator = Validator::make($request->all(), [
            'trang_thai_duyet' => 'required|in:cho_duyet,da_duyet,tu_choi',
            'ghi_chu_admin' => 'nullable|string|max:2000',
            'dang_hoat_dong' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $workerProfile = HoSoTho::query()
            ->where('user_id', $userId)
            ->first();

        if (!$workerProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay ho so tho',
            ], 404);
        }

        $approvalStatus = (string) $request->input('trang_thai_duyet');
        $isApproved = $approvalStatus === 'da_duyet';

        $workerProfile->update([
            'trang_thai_duyet' => $approvalStatus,
            'ghi_chu_admin' => trim((string) $request->input('ghi_chu_admin', '')) ?: null,
            'dang_hoat_dong' => $request->has('dang_hoat_dong')
                ? (bool) $request->boolean('dang_hoat_dong')
                : $isApproved,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $isApproved ? 'Da duyet ho so tho' : ($approvalStatus === 'tu_choi' ? 'Da tu choi ho so tho' : 'Da chuyen ho so ve cho duyet'),
            'data' => $workerProfile->fresh([
                'user:id,name,email,phone,avatar,is_active,created_at',
                'user.dichVus:id,ten_dich_vu',
            ]),
        ]);
    }

    public function getAllBookings(Request $request)
    {
        $status = $request->query('status');

        $query = DonDatLich::with([
            'khachHang:id,name,phone',
            'tho:id,name,phone',
            'dichVu:id,ten_dich_vu',
        ]);

        if ($status) {
            $query->where('trang_thai', $status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderByDesc('created_at')->get(),
        ]);
    }

    public function getServices(Request $request)
    {
        $status = $request->query('status');

        $query = DanhMucDichVu::query()->orderByDesc('id');

        if ($status !== null && $status !== '') {
            $query->where('trang_thai', (int) $status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    public function storeService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ten_dich_vu' => 'required|string|max:255|unique:danh_muc_dich_vu,ten_dich_vu',
            'mo_ta' => 'nullable|string',
            'hinh_anh' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'trang_thai' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $service = DanhMucDichVu::query()->create([
            'ten_dich_vu' => trim((string) $request->input('ten_dich_vu')),
            'mo_ta' => trim((string) $request->input('mo_ta', '')) ?: null,
            'hinh_anh' => $request->hasFile('hinh_anh')
                ? $this->storeServiceImage($request->file('hinh_anh'))
                : null,
            'trang_thai' => $request->has('trang_thai') ? (int) $request->boolean('trang_thai') : 1,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Da them dich vu',
            'data' => $service,
        ], 201);
    }

    public function updateService(Request $request, string $id)
    {
        $service = DanhMucDichVu::query()->find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay dich vu',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'ten_dich_vu' => 'required|string|max:255|unique:danh_muc_dich_vu,ten_dich_vu,' . $service->id,
            'mo_ta' => 'nullable|string',
            'hinh_anh' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'remove_image' => 'nullable|boolean',
            'trang_thai' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $imagePath = $service->getRawOriginal('hinh_anh');

        if ($request->boolean('remove_image')) {
            $this->deleteStoredServiceImage($imagePath);
            $imagePath = null;
        }

        if ($request->hasFile('hinh_anh')) {
            $this->deleteStoredServiceImage($imagePath);
            $imagePath = $this->storeServiceImage($request->file('hinh_anh'));
        }

        $service->update([
            'ten_dich_vu' => trim((string) $request->input('ten_dich_vu')),
            'mo_ta' => trim((string) $request->input('mo_ta', '')) ?: null,
            'hinh_anh' => $imagePath,
            'trang_thai' => $request->has('trang_thai') ? (int) $request->boolean('trang_thai') : (int) $service->trang_thai,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat dich vu',
            'data' => $service->fresh(),
        ]);
    }

    public function destroyService(string $id)
    {
        $service = DanhMucDichVu::query()->find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay dich vu',
            ], 404);
        }

        $service->update([
            'trang_thai' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Da xoa dich vu',
            'data' => $service->fresh(),
        ]);
    }

    public function getAssistantSoulConfig(AssistantSoulConfigService $assistantSoulConfigService)
    {
        return response()->json([
            'status' => 'success',
            'data' => $assistantSoulConfigService->getEditorState(),
        ]);
    }

    public function updateAssistantSoulConfig(Request $request, AssistantSoulConfigService $assistantSoulConfigService)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:5000',
            'identity_rules' => 'required|array|min:1',
            'identity_rules.*' => 'required|string|max:1000',
            'required_rules' => 'required|array|min:1',
            'required_rules.*' => 'required|string|max:2000',
            'response_goals' => 'required|array|min:1',
            'response_goals.*' => 'required|string|max:2000',
            'assistant_text_order' => 'required|array|min:1',
            'assistant_text_order.*' => 'required|string|max:1000',
            'json_keys' => 'required|array|min:1',
            'json_keys.*' => 'required|string|max:255',
            'output_style' => 'required|string|max:2000',
            'service_process' => 'required|array|min:1',
            'service_process.*' => 'required|string|max:1000',
            'emergency_keywords' => 'required|array|min:1',
            'emergency_keywords.*' => 'required|string|max:255',
            'emergency_response' => 'required|array',
            'emergency_response.fallback_price_line' => 'required|string|max:1000',
            'emergency_response.price_line_template' => 'required|string|max:1000',
            'emergency_response.lines' => 'required|array|min:1',
            'emergency_response.lines.*' => 'required|string|max:1500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $config = $this->normalizeAssistantSoulPayload($validator->validated());
        $assistantSoulConfigService->updateConfig($config, $request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Da cap nhat ASSISTANT SOUL',
            'data' => $assistantSoulConfigService->getEditorState(),
        ]);
    }

    public function resetAssistantSoulConfig(AssistantSoulConfigService $assistantSoulConfigService)
    {
        $assistantSoulConfigService->resetConfig();

        return response()->json([
            'status' => 'success',
            'message' => 'Da khoi phuc cau hinh mac dinh',
            'data' => $assistantSoulConfigService->getEditorState(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeAssistantSoulPayload(array $payload): array
    {
        return [
            'name' => trim((string) $payload['name']),
            'role' => trim((string) $payload['role']),
            'identity_rules' => $this->normalizeStringList($payload['identity_rules'] ?? []),
            'required_rules' => $this->normalizeStringList($payload['required_rules'] ?? []),
            'response_goals' => $this->normalizeStringList($payload['response_goals'] ?? []),
            'assistant_text_order' => $this->normalizeStringList($payload['assistant_text_order'] ?? []),
            'json_keys' => $this->normalizeStringList($payload['json_keys'] ?? []),
            'output_style' => trim((string) $payload['output_style']),
            'service_process' => $this->normalizeStringList($payload['service_process'] ?? []),
            'emergency_keywords' => $this->normalizeStringList($payload['emergency_keywords'] ?? []),
            'emergency_response' => [
                'fallback_price_line' => trim((string) data_get($payload, 'emergency_response.fallback_price_line', '')),
                'price_line_template' => trim((string) data_get($payload, 'emergency_response.price_line_template', '')),
                'lines' => $this->normalizeStringList(data_get($payload, 'emergency_response.lines', [])),
            ],
        ];
    }

    /**
     * @param  mixed  $items
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item): string {
            return trim((string) $item);
        }, $items), static fn (string $item): bool => $item !== ''));
    }

    private function storeServiceImage(UploadedFile $file): string
    {
        return $file->store('services', 'public');
    }

    private function deleteStoredServiceImage(?string $imagePath): void
    {
        $imagePath = trim((string) $imagePath);

        if ($imagePath === '') {
            return;
        }

        if (Str::startsWith($imagePath, ['http://', 'https://', 'data:'])) {
            $storagePrefix = rtrim(asset('storage'), '/');

            if (!Str::startsWith($imagePath, $storagePrefix . '/')) {
                return;
            }

            $imagePath = Str::after($imagePath, $storagePrefix . '/');
        }

        if (Str::startsWith($imagePath, '/storage/')) {
            $imagePath = Str::after($imagePath, '/storage/');
        } elseif (Str::startsWith($imagePath, 'storage/')) {
            $imagePath = Str::after($imagePath, 'storage/');
        }

        if ($imagePath !== '' && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }
}

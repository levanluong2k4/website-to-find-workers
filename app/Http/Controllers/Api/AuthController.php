<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Mail\OtpMail;
use App\Models\HoSoTho;
use App\Models\OtpCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $request->validated();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
        ]);

        if ($user->role === 'worker') {
            HoSoTho::create([
                'user_id' => $user->id,
                'cccd' => 'WAITING_UPDATE_' . $user->id,
            ]);
        }

        $otp = sprintf('%06d', mt_rand(1, 999999));

        OtpCode::updateOrCreate(
            ['email' => $request->email],
            ['code' => $otp, 'expires_at' => Carbon::now()->addMinutes(10)]
        );

        Mail::to($request->email)->send(new OtpMail($otp));

        $responseData = [
            'message' => 'Đăng ký thành công, vui lòng kiểm tra email để nhận mã OTP',
            'email' => $user->email,
        ];

        if (config('app.env') === 'local') {
            $responseData['debug_otp'] = $otp;
        }

        return response()->json($responseData, 201);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Email hoặc mật khẩu không đúng'], 401);
        }

        if ($roleMismatch = $this->ensureRequestedRoleMatchesUser($user, $validated['role'])) {
            return $roleMismatch;
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Tài khoản đã bị khóa'], 403);
        }

        $otp = sprintf('%06d', mt_rand(1, 999999));

        OtpCode::updateOrCreate(
            ['email' => $validated['email']],
            ['code' => $otp, 'expires_at' => Carbon::now()->addMinutes(10)]
        );

        Mail::to($validated['email'])->send(new OtpMail($otp));

        $responseData = ['message' => 'Mã OTP đã được gửi vào email của bạn'];

        if (config('app.env') === 'local') {
            $responseData['debug_otp'] = $otp;
        }

        return response()->json($responseData);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $validated = $request->validated();

        $otpRecord = OtpCode::where('email', $validated['email'])
            ->where('code', $validated['code'])
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'Mã OTP không hợp lệ hoặc đã hết hạn'], 400);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại'], 404);
        }

        if ($roleMismatch = $this->ensureRequestedRoleMatchesUser($user, $validated['role'])) {
            return $roleMismatch;
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $otpRecord->delete();
        $requiresPhoneVerification = (bool) config('phone_verification.required', false) && !$user->phone_verified_at;

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'user' => $user,
            'access_token' => $token,
            'requires_phone_verification' => $requiresPhoneVerification,
            'phone_verification_url' => $requiresPhoneVerification ? url('/verify-phone') : null,
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|string|in:customer,worker,admin',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại'], 404);
        }

        if ($roleMismatch = $this->ensureRequestedRoleMatchesUser($user, $validated['role'])) {
            return $roleMismatch;
        }

        $otp = sprintf('%06d', mt_rand(1, 999999));

        OtpCode::updateOrCreate(
            ['email' => $validated['email']],
            ['code' => $otp, 'expires_at' => Carbon::now()->addMinutes(10)]
        );

        Mail::to($validated['email'])->send(new OtpMail($otp));

        $responseData = ['message' => 'Mã OTP mới đã được gửi vào email của bạn'];

        if (config('app.env') === 'local') {
            $responseData['debug_otp'] = $otp;
        }

        return response()->json($responseData);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Đã đăng xuất thành công',
        ]);
    }

    private function ensureRequestedRoleMatchesUser(User $user, string $requestedRole): ?JsonResponse
    {
        if ($user->role === 'admin' || $user->role === $requestedRole) {
            return null;
        }

        return response()->json([
            'message' => $this->buildRoleMismatchMessage($requestedRole),
            'actual_role' => $user->role,
            'selected_role' => $requestedRole,
        ], 403);
    }

    private function buildRoleMismatchMessage(string $requestedRole): string
    {
        return match ($requestedRole) {
            'worker' => 'Tài khoản này không phải tài khoản thợ.',
            'customer' => 'Tài khoản này không phải tài khoản khách hàng.',
            default => 'Tài khoản này không thuộc vai trò đã chọn.',
        };
    }
}

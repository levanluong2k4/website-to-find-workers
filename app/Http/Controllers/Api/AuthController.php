<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Models\HoSoTho;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Nếu là thợ, tự động tạo hồ sơ thợ trống
        if ($user->role === 'worker') {
            HoSoTho::create([
                'user_id' => $user->id,
                'cccd' => 'WAITING_UPDATE_' . $user->id, // Tạm thời
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng ký thành công',
            'user' => $user,
            'access_token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại'], 404);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Tài khoản đã bị khóa'], 403);
        }

        // Tạo OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        
        OtpCode::updateOrCreate(
            ['email' => $request->email],
            ['code' => $otp, 'expires_at' => Carbon::now()->addMinutes(10)]
        );

        // Send Email
        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json(['message' => 'Mã OTP đã được gửi vào email của bạn']);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $validated = $request->validated();

        $otpRecord = OtpCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'Mã OTP không hợp lệ hoặc đã hết hạn'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Xóa OTP sau khi dùng
        $otpRecord->delete();

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'user' => $user,
            'access_token' => $token
        ]);
    }
}

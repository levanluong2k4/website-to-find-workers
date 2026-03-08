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
            'phone' => $request->phone,
            'role' => $request->role,
        ]);

        // Nếu là thợ, tự động tạo hồ sơ thợ trống
        if ($user->role === 'worker') {
            HoSoTho::create([
                'user_id' => $user->id,
                'cccd' => 'WAITING_UPDATE_' . $user->id, // Tạm thời
            ]);
        }

        // Generate OTP and Send Mail
        $otp = sprintf("%06d", mt_rand(1, 999999));

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

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email hoặc mật khẩu không đúng'], 401);
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

        $responseData = ['message' => 'Mã OTP đã được gửi vào email của bạn'];

        if (config('app.env') === 'local') {
            $responseData['debug_otp'] = $otp;
        }

        return response()->json($responseData);
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

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại'], 404);
        }

        $otp = sprintf("%06d", mt_rand(1, 999999));

        OtpCode::updateOrCreate(
            ['email' => $request->email],
            ['code' => $otp, 'expires_at' => Carbon::now()->addMinutes(10)]
        );

        Mail::to($request->email)->send(new OtpMail($otp));

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
            'message' => 'Đã đăng xuất thành công'
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
{
    public function requestCode(Request $request, PhoneVerificationService $phoneVerificationService)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'mode' => 'required|string|in:demo,real',
        ]);

        try {
            $payload = $phoneVerificationService->requestCode(
                $request->user(),
                $validated['phone'],
                $validated['mode']
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return response()->json(array_filter([
            'message' => $payload['message'],
            'phone' => $payload['phone'],
            'mode' => $payload['mode'],
            'debug_otp' => $payload['debug_otp'],
        ], static fn ($value): bool => $value !== null));
    }

    public function verifyCode(Request $request, PhoneVerificationService $phoneVerificationService)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'mode' => 'required|string|in:demo,real',
            'code' => 'required|string|size:6',
        ]);

        $user = $phoneVerificationService->verifyCode(
            $request->user(),
            $validated['phone'],
            $validated['mode'],
            $validated['code']
        );

        return response()->json([
            'message' => 'Xac minh so dien thoai thanh cong.',
            'user' => $user,
            'redirect_to' => $this->resolvePostVerificationPath($user->role),
        ]);
    }

    private function resolvePostVerificationPath(string $role): string
    {
        return match ($role) {
            'admin' => url('/admin/dashboard'),
            'worker' => url('/worker/dashboard'),
            default => url('/customer/home'),
        };
    }
}

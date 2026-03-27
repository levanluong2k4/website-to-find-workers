<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            !(bool) config('phone_verification.required', false)
            || !$user
            || $user->role === 'admin'
            || $user->phone_verified_at
        ) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Ban can xac minh so dien thoai truoc khi tiep tuc.',
            'requires_phone_verification' => true,
            'phone_verification_url' => url('/verify-phone'),
        ], 403);
    }
}

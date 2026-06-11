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

        if ($user && $user->role === 'worker') {
            $user->loadMissing('hoSoTho');
            $approvalStatus = (string) ($user->hoSoTho?->trang_thai_duyet ?? 'cho_duyet');

            if ($approvalStatus !== 'da_duyet') {
                return response()->json([
                    'message' => $approvalStatus === 'tu_choi'
                        ? 'Tai khoan tho cua ban da bi tu choi. Vui long lien he admin de duoc ho tro.'
                        : 'Tai khoan tho cua ban dang cho admin duyet. Ban chi co the su dung he thong sau khi duoc duyet.',
                    'approval_status' => $approvalStatus,
                ], 403);
            }
        }

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

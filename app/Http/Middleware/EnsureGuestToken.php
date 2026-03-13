<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guestToken = (string) $request->headers->get('X-Guest-Token', '');
        if ($guestToken === '') {
            $guestToken = (string) $request->cookie('guest_token', '');
        }
        $needsCookie = $guestToken === '';

        if ($needsCookie) {
            $guestToken = (string) Str::uuid();
        }

        $request->attributes->set('chat_guest_token', $guestToken);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        if ($needsCookie) {
            $response->headers->setCookie(
                cookie(
                    name: 'guest_token',
                    value: $guestToken,
                    minutes: 60 * 24 * 365,
                    path: '/',
                    domain: null,
                    secure: $request->isSecure(),
                    httpOnly: true,
                    raw: false,
                    sameSite: 'Lax'
                )
            );
        }

        return $response;
    }
}

<?php

namespace App\Providers;

use App\Models\CustomerFeedbackCase;
use App\Models\DanhGia;
use App\Models\DanhMucDichVu;
use App\Models\DonDatLich;
use App\Models\HoSoTho;
use App\Models\HuongXuLy;
use App\Models\NguyenNhan;
use App\Models\TrieuChung;
use App\Models\User;
use App\Observers\CustomerFeedbackCaseObserver;
use App\Observers\DanhGiaObserver;
use App\Observers\DanhMucDichVuObserver;
use App\Observers\DonDatLichObserver;
use App\Observers\HoSoThoObserver;
use App\Observers\HuongXuLyObserver;
use App\Observers\NguyenNhanObserver;
use App\Observers\TrieuChungObserver;
use App\Observers\UserObserver;
use App\Support\CertificatePathResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DanhMucDichVu::observe(DanhMucDichVuObserver::class);
        DonDatLich::observe(DonDatLichObserver::class);
        HoSoTho::observe(HoSoThoObserver::class);
        CustomerFeedbackCase::observe(CustomerFeedbackCaseObserver::class);
        TrieuChung::observe(TrieuChungObserver::class);
        NguyenNhan::observe(NguyenNhanObserver::class);
        HuongXuLy::observe(HuongXuLyObserver::class);
        User::observe(UserObserver::class);
        DanhGia::observe(DanhGiaObserver::class);

        $this->configureChatRateLimiters();
        $this->configureCaBundleFallback();
    }

    private function configureChatRateLimiters(): void
    {
        RateLimiter::for('chat-history', function (Request $request): Limit {
            return Limit::perMinute(max(1, (int) config('services.chat.history_rate_limit', 60)))
                ->by('chat-history:' . $this->chatThrottleKey($request));
        });

        RateLimiter::for('chat-send', function (Request $request): Limit {
            return Limit::perMinute(max(1, (int) config('services.chat.send_rate_limit', 18)))
                ->by('chat-send:' . $this->chatThrottleKey($request));
        });

        RateLimiter::for('chat-sync', function (Request $request): Limit {
            return Limit::perMinute(max(1, (int) config('services.chat.sync_rate_limit', 8)))
                ->by('chat-sync:' . $this->chatThrottleKey($request));
        });

        RateLimiter::for('chat-admin-preview', function (Request $request): Limit {
            return Limit::perMinute(max(1, (int) config('services.chat.admin_preview_rate_limit', 20)))
                ->by('chat-admin-preview:' . $this->chatThrottleKey($request));
        });
    }

    private function chatThrottleKey(Request $request): string
    {
        $userId = $request->user()?->id;
        if ($userId !== null) {
            return 'user:' . $userId;
        }

        $guestToken = trim((string) $request->attributes->get('chat_guest_token', $request->header('X-Guest-Token', $request->cookie('guest_token', ''))));
        if ($guestToken !== '') {
            return 'guest:' . $guestToken;
        }

        return 'ip:' . (string) $request->ip();
    }

    private function configureCaBundleFallback(): void
    {
        $configuredCurlCa = (string) ini_get('curl.cainfo');
        $configuredOpenSslCa = (string) ini_get('openssl.cafile');

        if (CertificatePathResolver::isReadableCertificateFile($configuredCurlCa) || CertificatePathResolver::isReadableCertificateFile($configuredOpenSslCa)) {
            $resolvedPath = CertificatePathResolver::isReadableCertificateFile($configuredCurlCa) ? $configuredCurlCa : $configuredOpenSslCa;
            $this->applyCaBundlePath($resolvedPath);
            return;
        }

        $fallbackPath = CertificatePathResolver::resolveFromCandidates([
            env('CURL_CA_BUNDLE'),
            env('SSL_CERT_FILE'),
            base_path('cacert.pem'),
            base_path('certs/cacert.pem'),
            base_path('storage/certs/cacert.pem'),
            CertificatePathResolver::resolveLaragonCaBundlePath(),
            'D:\\laragon\\etc\\ssl\\cacert.pem',
            'C:\\laragon\\etc\\ssl\\cacert.pem',
        ]);

        if ($fallbackPath) {
            $this->applyCaBundlePath($fallbackPath);
        }
    }

    private function applyCaBundlePath(string $path): void
    {
        putenv("CURL_CA_BUNDLE={$path}");
        putenv("SSL_CERT_FILE={$path}");

        $_ENV['CURL_CA_BUNDLE'] = $path;
        $_ENV['SSL_CERT_FILE'] = $path;
        $_SERVER['CURL_CA_BUNDLE'] = $path;
        $_SERVER['SSL_CERT_FILE'] = $path;

        @ini_set('curl.cainfo', $path);
        @ini_set('openssl.cafile', $path);
    }
}

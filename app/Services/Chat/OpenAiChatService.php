<?php

namespace App\Services\Chat;

use App\Support\HttpClientTlsConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiChatService
{
    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function generateResponse(array $messages): array
    {
        $apiKey = (string) config('services.gemini.api_key', '');
        $primaryModel = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/models'), '/');
        $timeout = (int) config('services.gemini.timeout', 25);
        $forceJsonResponse = (bool) config('services.gemini.force_json_response', true);

        if ($apiKey === '') {
            return $this->fallbackPayload(
                'He thong AI chua duoc cau hinh GEMINI_API_KEY.',
                $primaryModel,
                'system_fallback_not_configured'
            );
        }

        $models = $this->candidateModels($primaryModel);
        $lastFailure = null;

        foreach ($models as $index => $model) {
            $attempt = $this->requestModel($messages, $apiKey, $model, $baseUrl, $timeout, $forceJsonResponse);

            if (($attempt['response'] ?? null) instanceof Response && $attempt['response']->successful()) {
                return $this->successPayload(
                    $attempt['response']->json(),
                    $model,
                    $primaryModel,
                    $index > 0
                );
            }

            $lastFailure = $attempt;

            if ($index < count($models) - 1 && $this->shouldTryFallbackModel($attempt)) {
                Log::warning('Gemini primary model unavailable, trying fallback model.', [
                    'failed_model' => $model,
                    'next_model' => $models[$index + 1],
                    'status' => $attempt['status_code'] ?? null,
                    'exception' => $attempt['exception_message'] ?? null,
                ]);
                continue;
            }

            break;
        }

        return $this->fallbackFromFailure($lastFailure, $primaryModel);
    }

    private function resolveEndpoint(string $baseUrl, string $model): string
    {
        return sprintf('%s/%s:generateContent', $baseUrl, $model);
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    private function buildGeminiPayload(array $messages, bool $withJsonResponseFormat): array
    {
        $systemMessages = [];
        $contents = [];

        foreach ($messages as $message) {
            $role = (string) ($message['role'] ?? 'user');
            $content = trim((string) ($message['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            if ($role === 'system') {
                $systemMessages[] = $content;
                continue;
            }

            $contents[] = [
                'role' => $role === 'assistant' ? 'model' : 'user',
                'parts' => [
                    ['text' => $content],
                ],
            ];
        }

        if ($contents === []) {
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Xin chao'],
                ],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.4,
            ],
        ];

        if ($systemMessages !== []) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => implode("\n\n", $systemMessages)],
                ],
            ];
        }

        if ($withJsonResponseFormat) {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractTextFromResponse(array $response): string
    {
        /** @var Collection<int, array<string, mixed>> $parts */
        $parts = collect((array) data_get($response, 'candidates.0.content.parts', []));

        return (string) $parts
            ->pluck('text')
            ->filter()
            ->implode("\n");
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    private function requestModel(
        array $messages,
        string $apiKey,
        string $model,
        string $baseUrl,
        int $timeout,
        bool $forceJsonResponse
    ): array {
        $attempts = max(1, (int) config('services.gemini.retry_attempts', 3));
        $baseSleepMs = max(0, (int) config('services.gemini.retry_base_sleep_ms', 350));
        $maxSleepMs = max($baseSleepMs, (int) config('services.gemini.retry_max_sleep_ms', 2200));

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::withOptions(HttpClientTlsConfig::options())
                    ->connectTimeout(min($timeout, 10))
                    ->timeout($timeout)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->acceptJson()
                    ->post($this->resolveEndpoint($baseUrl, $model), $this->buildGeminiPayload($messages, $forceJsonResponse));

                if ($response->successful()) {
                    return [
                        'response' => $response,
                        'model' => $model,
                        'attempt' => $attempt,
                        'status_code' => $response->status(),
                    ];
                }

                Log::warning('Gemini API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $model,
                    'attempt' => $attempt,
                    'base_url' => $baseUrl,
                ]);

                if (!$this->isRetryableStatus($response->status()) || $attempt === $attempts) {
                    return [
                        'response' => $response,
                        'model' => $model,
                        'attempt' => $attempt,
                        'status_code' => $response->status(),
                    ];
                }
            } catch (Throwable $exception) {
                Log::warning('Gemini request exception', [
                    'message' => $exception->getMessage(),
                    'model' => $model,
                    'attempt' => $attempt,
                ]);

                if ($attempt === $attempts) {
                    return [
                        'response' => null,
                        'model' => $model,
                        'attempt' => $attempt,
                        'status_code' => null,
                        'exception_message' => $exception->getMessage(),
                    ];
                }
            }

            usleep($this->backoffDelayMs($attempt, $baseSleepMs, $maxSleepMs) * 1000);
        }

        return [
            'response' => null,
            'model' => $model,
            'attempt' => $attempts,
            'status_code' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $responseJson
     * @return array<string, mixed>
     */
    private function successPayload(array $responseJson, string $usedModel, string $primaryModel, bool $usedFallbackModel): array
    {
        $content = $this->extractTextFromResponse($responseJson);
        $decoded = json_decode($content, true);
        $isJsonPayload = is_array($decoded);
        $assistantText = $isJsonPayload
            ? (string) ($decoded['assistant_text'] ?? 'Minh da phan tich du lieu va san sang ho tro ban.')
            : (trim($content) !== '' ? trim($content) : 'AI tra ve du lieu khong hop le, minh se tra loi theo du lieu he thong.');

        return [
            'assistant_text' => $assistantText,
            'cases' => $isJsonPayload && is_array($decoded['cases'] ?? null) ? $decoded['cases'] : [],
            'technicians' => $isJsonPayload && is_array($decoded['technicians'] ?? null) ? $decoded['technicians'] : [],
            'youtube_links' => $isJsonPayload && is_array($decoded['youtube_links'] ?? null) ? $decoded['youtube_links'] : [],
            'model' => $usedModel,
            'ai' => [
                'status' => $usedFallbackModel ? 'fallback_model' : 'ok',
                'degraded' => $usedFallbackModel,
                'used_system_data' => false,
                'primary_model' => $primaryModel,
                'model' => $usedModel,
                'notice' => $usedFallbackModel
                    ? 'Model chinh tam thoi qua tai, he thong da tu dong chuyen sang model du phong.'
                    : null,
                'badge' => null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $failure
     * @return array<string, mixed>
     */
    private function fallbackFromFailure(?array $failure, string $primaryModel): array
    {
        $status = $failure['status_code'] ?? null;

        if ($status === 429) {
            return $this->fallbackPayload(
                'Tai khoan Gemini dang het quota. Vui long kiem tra usage hoac billing trong Google AI Studio.',
                $primaryModel,
                'system_fallback_quota'
            );
        }

        if (in_array($status, [401, 403], true)) {
            return $this->fallbackPayload(
                'GEMINI_API_KEY khong hop le hoac khong co quyen truy cap model.',
                $primaryModel,
                'system_fallback_auth'
            );
        }

        if (in_array($status, [500, 502, 503, 504], true) || ($failure['exception_message'] ?? null) !== null) {
            return $this->fallbackPayload(
                'Gemini dang qua tai tam thoi, minh van se dua tren du lieu he thong de ho tro ban. Ban thu gui lai sau it phut nua.',
                $primaryModel,
                'system_fallback_overloaded'
            );
        }

        return $this->fallbackPayload(
            'AI tam thoi ban, minh se dua tren du lieu he thong de ho tro ban.',
            $primaryModel,
            'system_fallback_generic'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackPayload(string $assistantText, string $primaryModel, string $status): array
    {
        $isOverloaded = $status === 'system_fallback_overloaded';

        return [
            'assistant_text' => $assistantText,
            'cases' => [],
            'technicians' => [],
            'youtube_links' => [],
            'model' => null,
            'ai' => [
                'status' => $status,
                'degraded' => true,
                'used_system_data' => true,
                'primary_model' => $primaryModel,
                'model' => null,
                'notice' => $assistantText,
                'badge' => $isOverloaded ? [
                    'label' => 'AI qua tai',
                    'message' => 'AI qua tai, dang dung du lieu he thong',
                    'tone' => 'warning',
                ] : null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attempt
     */
    private function shouldTryFallbackModel(array $attempt): bool
    {
        $status = $attempt['status_code'] ?? null;

        return in_array($status, [500, 502, 503, 504, 408], true)
            || (($attempt['exception_message'] ?? null) !== null);
    }

    /**
     * @return array<int, string>
     */
    private function candidateModels(string $primaryModel): array
    {
        $fallbackModels = config('services.gemini.fallback_models', []);
        if (!is_array($fallbackModels)) {
            $fallbackModels = [];
        }

        return array_values(array_unique(array_filter(array_merge([$primaryModel], $fallbackModels), static function ($model): bool {
            return trim((string) $model) !== '';
        })));
    }

    private function isRetryableStatus(int $statusCode): bool
    {
        return in_array($statusCode, [408, 429, 500, 502, 503, 504], true);
    }

    private function backoffDelayMs(int $attempt, int $baseSleepMs, int $maxSleepMs): int
    {
        $delay = $baseSleepMs * (2 ** max(0, $attempt - 1));
        $jitter = random_int(0, max(50, (int) floor($baseSleepMs / 2)));

        return min($delay + $jitter, $maxSleepMs);
    }
}

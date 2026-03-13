<?php

namespace App\Services\Chat;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiChatService
{
    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function generateResponse(array $messages): array
    {
        $apiKey = (string) config('services.gemini.api_key', '');
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/models'), '/');
        $timeout = (int) config('services.gemini.timeout', 25);
        $forceJsonResponse = (bool) config('services.gemini.force_json_response', true);

        if ($apiKey === '') {
            return $this->fallbackPayload('He thong AI chua duoc cau hinh GEMINI_API_KEY.');
        }

        try {
            $response = Http::timeout($timeout)
                ->retry(2, 250, null, false)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->acceptJson()
                ->post($this->resolveEndpoint($baseUrl, $model), $this->buildGeminiPayload($messages, $forceJsonResponse));

            if (!$response->successful()) {
                Log::warning('Gemini API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $model,
                    'base_url' => $baseUrl,
                ]);

                if ($response->status() === 429) {
                    return $this->fallbackPayload('Tai khoan Gemini dang het quota. Vui long kiem tra usage hoac billing trong Google AI Studio.');
                }

                if ($response->status() === 401 || $response->status() === 403) {
                    return $this->fallbackPayload('GEMINI_API_KEY khong hop le hoac khong co quyen truy cap model.');
                }

                return $this->fallbackPayload('AI tam thoi ban, minh se dua tren du lieu he thong de ho tro ban.');
            }

            $content = $this->extractTextFromResponse($response->json());
            $decoded = json_decode($content, true);

            if (!is_array($decoded)) {
                $assistantText = trim($content) !== ''
                    ? trim($content)
                    : 'AI tra ve du lieu khong hop le, minh se tra loi theo du lieu he thong.';

                return [
                    'assistant_text' => $assistantText,
                    'cases' => [],
                    'technicians' => [],
                    'youtube_links' => [],
                    'model' => $model,
                ];
            }

            return [
                'assistant_text' => (string) ($decoded['assistant_text'] ?? 'Minh da phan tich du lieu va san sang ho tro ban.'),
                'cases' => is_array($decoded['cases'] ?? null) ? $decoded['cases'] : [],
                'technicians' => is_array($decoded['technicians'] ?? null) ? $decoded['technicians'] : [],
                'youtube_links' => is_array($decoded['youtube_links'] ?? null) ? $decoded['youtube_links'] : [],
                'model' => $model,
            ];
        } catch (\Throwable $exception) {
            Log::error('Gemini request exception', [
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackPayload('Khong ket noi duoc AI, minh se phan tich dua tren du lieu san co.');
        }
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
     * @return array<string, mixed>
     */
    private function fallbackPayload(string $assistantText): array
    {
        return [
            'assistant_text' => $assistantText,
            'cases' => [],
            'technicians' => [],
            'youtube_links' => [],
            'model' => null,
        ];
    }
}


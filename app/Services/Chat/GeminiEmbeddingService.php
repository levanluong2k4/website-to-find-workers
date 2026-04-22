<?php

namespace App\Services\Chat;

use App\Support\HttpClientTlsConfig;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiEmbeddingService
{
    /**
     * @return array<int, float>
     */
    public function embedDocument(string $text): array
    {
        return $this->embed($text, 'RETRIEVAL_DOCUMENT');
    }

    /**
     * @return array<int, float>
     */
    public function embedQuery(string $text): array
    {
        return $this->embed($text, 'RETRIEVAL_QUERY');
    }

    /**
     * @return array<int, float>
     */
    private function embed(string $text, string $taskType): array
    {
        $apiKey = (string) config('services.gemini.api_key', '');
        $model = (string) config('services.gemini.embedding_model', 'gemini-embedding-001');
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/models'), '/');
        $timeout = (int) config('services.gemini.timeout', 25);
        $dimension = (int) config('services.gemini.embedding_output_dimensionality', 768);
        $normalize = (bool) config('services.gemini.embedding_normalize_vectors', true);

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $payload = [
            'model' => 'models/' . $model,
            'content' => [
                'parts' => [
                    ['text' => trim($text)],
                ],
            ],
            'taskType' => $taskType,
        ];

        if ($dimension > 0) {
            $payload['outputDimensionality'] = $dimension;
        }

        $response = Http::withOptions(HttpClientTlsConfig::options())
            ->timeout($timeout)
            ->retry(2, 250, null, false)
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->post(sprintf('%s/%s:embedContent', $baseUrl, $model), $payload);

        if (!$response->successful()) {
            throw new RuntimeException(sprintf(
                'Gemini embedding request failed with status %s: %s',
                $response->status(),
                $response->body()
            ));
        }

        $vector = data_get($response->json(), 'embedding.values', []);
        if (!is_array($vector) || $vector === []) {
            throw new RuntimeException('Gemini embedding response did not contain embedding.values.');
        }

        $vector = array_map(static fn ($value): float => (float) $value, $vector);

        return $normalize ? $this->normalize($vector) : $vector;
    }

    /**
     * @param  array<int, float>  $vector
     * @return array<int, float>
     */
    private function normalize(array $vector): array
    {
        $sumSquares = 0.0;

        foreach ($vector as $value) {
            $sumSquares += $value * $value;
        }

        if ($sumSquares <= 0.0) {
            return $vector;
        }

        $norm = sqrt($sumSquares);

        return array_map(static fn ($value): float => $value / $norm, $vector);
    }
}

<?php

namespace App\Services\Chat;

use App\Support\HttpClientTlsConfig;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class QdrantClientService
{
    public function ensureCollection(string $collection, int $vectorSize, string $distance = 'Cosine'): void
    {
        $response = $this->request()->get($this->url('/collections/' . $collection));

        if ($response->status() === 404) {
            $createResponse = $this->request()->put($this->url('/collections/' . $collection), [
                'vectors' => [
                    'size' => $vectorSize,
                    'distance' => $distance,
                ],
            ]);

            if (!$createResponse->successful()) {
                throw new RuntimeException(sprintf(
                    'Qdrant collection creation failed with status %s: %s',
                    $createResponse->status(),
                    $createResponse->body()
                ));
            }

            return;
        }

        if (!$response->successful()) {
            throw new RuntimeException(sprintf(
                'Qdrant collection lookup failed with status %s: %s',
                $response->status(),
                $response->body()
            ));
        }
    }

    public function ensurePayloadIndex(string $collection, string $fieldName, string $fieldSchema): void
    {
        $response = $this->request()->put($this->url('/collections/' . $collection . '/index'), [
            'field_name' => $fieldName,
            'field_schema' => $fieldSchema,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException(sprintf(
                'Qdrant payload index creation failed for %s with status %s: %s',
                $fieldName,
                $response->status(),
                $response->body()
            ));
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $points
     */
    public function upsertPoints(string $collection, array $points): void
    {
        if ($points === []) {
            return;
        }

        $response = $this->request()->put($this->url('/collections/' . $collection . '/points'), [
            'points' => $points,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException(sprintf(
                'Qdrant upsert failed with status %s: %s',
                $response->status(),
                $response->body()
            ));
        }
    }

    /**
     * @param  array<int, int|string>  $pointIds
     */
    public function deletePoints(string $collection, array $pointIds): void
    {
        $pointIds = array_values(array_filter($pointIds, static fn ($pointId): bool => $pointId !== null && $pointId !== ''));
        if ($pointIds === []) {
            return;
        }

        $response = $this->request()->post($this->url('/collections/' . $collection . '/points/delete'), [
            'points' => $pointIds,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException(sprintf(
                'Qdrant delete failed with status %s: %s',
                $response->status(),
                $response->body()
            ));
        }
    }

    /**
     * @param  array<int, float>  $vector
     * @param  array<string, mixed>|null  $filter
     * @return array<int, array<string, mixed>>
     */
    public function search(string $collection, array $vector, ?array $filter = null, int $limit = 5): array
    {
        $payload = [
            'vector' => $vector,
            'limit' => $limit,
            'with_payload' => true,
        ];

        if ($filter !== null) {
            $payload['filter'] = $filter;
        }

        $response = $this->request()->post($this->url('/collections/' . $collection . '/points/search'), $payload);

        if (!$response->successful()) {
            throw new RuntimeException(sprintf(
                'Qdrant search failed with status %s: %s',
                $response->status(),
                $response->body()
            ));
        }

        $results = data_get($response->json(), 'result', []);

        return is_array($results) ? $results : [];
    }

    private function request()
    {
        $url = (string) config('services.qdrant.url', '');
        $apiKey = (string) config('services.qdrant.api_key', '');
        $timeout = (int) config('services.qdrant.timeout', 10);

        if ($url === '') {
            throw new RuntimeException('QDRANT_URL is not configured.');
        }

        $request = Http::withOptions(HttpClientTlsConfig::options())
            ->timeout($timeout)
            ->retry(2, 250, null, false)
            ->acceptJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
            ]);

        if ($apiKey !== '') {
            $request = $request->withHeaders([
                'api-key' => $apiKey,
            ]);
        }

        return $request;
    }

    private function url(string $path): string
    {
        $baseUrl = rtrim((string) config('services.qdrant.url', ''), '/');

        return $baseUrl . $path;
    }
}

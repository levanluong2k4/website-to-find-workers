<?php

namespace App\Services\Chat;

class YoutubeSuggestionService
{
    /**
     * @param  array<int, array<string, mixed>>  $cases
     * @return array<int, array<string, string>>
     */
    public function suggest(string $message, array $cases, int $limit = 3): array
    {
        $queries = [];
        $base = trim($message);

        if ($base !== '') {
            $queries[] = $base . ' huong dan sua chua';
            $queries[] = $base . ' cach kiem tra loi';
        }

        foreach ($cases as $case) {
            $serviceType = trim((string) ($case['service_type'] ?? ''));
            if ($serviceType !== '') {
                $queries[] = $serviceType . ' huong dan sua chua tai nha';
            }
        }

        $result = [];
        $seen = [];
        foreach ($queries as $query) {
            $normalized = TextNormalizer::normalize($query);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $result[] = [
                'title' => 'Video YouTube: ' . $query,
                'url' => 'https://www.youtube.com/results?search_query=' . rawurlencode($query),
            ];

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }
}


<?php

namespace App\Services\Chat;

use App\Models\DanhMucDichVu;

class ServiceSearchIntentService
{
    /**
     * @return array{
     *     is_service_search: bool,
     *     service_id: int|null,
     *     service_name: string|null,
     *     is_unsupported_service_search: bool,
     *     requested_service_name: string|null
     * }
     */
    public function detect(string $message): array
    {
        $normalizedMessage = TextNormalizer::normalize($message);
        if ($normalizedMessage === '') {
            return $this->emptyResult();
        }

        $requestedServiceName = $this->extractRequestedServiceName($message);
        $messageTokens = TextNormalizer::tokens($message);
        $bestMatch = null;
        $bestScore = 0.0;

        $services = DanhMucDichVu::query()
            ->select('id', 'ten_dich_vu')
            ->get();

        foreach ($services as $service) {
            $serviceName = trim((string) $service->ten_dich_vu);
            $serviceTokens = TextNormalizer::tokens($serviceName);
            if ($serviceName === '' || $serviceTokens === []) {
                continue;
            }

            $normalizedService = TextNormalizer::normalize($serviceName);
            $phraseScore = str_contains($normalizedMessage, $normalizedService) ? 1.0 : 0.0;
            $tokenScore = $this->serviceTokenScore($messageTokens, $serviceTokens);
            $score = max($phraseScore, $tokenScore);

            if ($score <= $bestScore) {
                continue;
            }

            $bestScore = $score;
            $bestMatch = [
                'service_id' => (int) $service->id,
                'service_name' => $serviceName,
                'service_tokens' => $serviceTokens,
            ];
        }

        if ($bestMatch === null || $bestScore < 0.6) {
            if ($this->hasServiceRequestIntent($normalizedMessage) && $requestedServiceName !== null) {
                return [
                    'is_service_search' => false,
                    'service_id' => null,
                    'service_name' => null,
                    'is_unsupported_service_search' => true,
                    'requested_service_name' => $requestedServiceName,
                ];
            }

            return $this->emptyResult();
        }

        if (!$this->hasServiceSearchIntent($normalizedMessage) && !$this->isCompactServiceRequest($normalizedMessage, $bestMatch['service_name'])) {
            return $this->emptyResult();
        }

        return [
            'is_service_search' => true,
            'service_id' => $bestMatch['service_id'],
            'service_name' => $bestMatch['service_name'],
            'is_unsupported_service_search' => false,
            'requested_service_name' => $requestedServiceName,
        ];
    }

    /**
     * @param  array<int, string>  $messageTokens
     * @param  array<int, string>  $serviceTokens
     */
    private function serviceTokenScore(array $messageTokens, array $serviceTokens): float
    {
        if ($messageTokens === [] || $serviceTokens === []) {
            return 0.0;
        }

        $messageMap = array_fill_keys($messageTokens, true);
        $hits = 0;

        foreach ($serviceTokens as $token) {
            if (isset($messageMap[$token])) {
                $hits++;
            }
        }

        return $hits / count($serviceTokens);
    }

    private function hasServiceSearchIntent(string $normalizedMessage): bool
    {
        $markers = [
            'tim tho',
            'can tho',
            'goi tho',
            'dat lich',
            'dat tho',
            'thue tho',
            'tho sua',
            'tho nao sua',
            'tim nguoi sua',
            'can nguoi sua',
        ];

        foreach ($markers as $marker) {
            if (str_contains($normalizedMessage, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function hasServiceRequestIntent(string $normalizedMessage): bool
    {
        return $this->hasServiceSearchIntent($normalizedMessage)
            || preg_match('/\b(sua|ve sinh|lap dat)\b/u', $normalizedMessage) === 1;
    }

    private function isCompactServiceRequest(string $normalizedMessage, string $serviceName): bool
    {
        $normalizedService = TextNormalizer::normalize($serviceName);

        return $normalizedService !== ''
            && (
                $normalizedMessage === $normalizedService
                || $normalizedMessage === 'sua ' . $normalizedService
                || $normalizedMessage === 've sinh ' . $normalizedService
                || $normalizedMessage === 'lap dat ' . $normalizedService
            );
    }

    /**
     * @return array{
     *     is_service_search: bool,
     *     service_id: int|null,
     *     service_name: string|null,
     *     is_unsupported_service_search: bool,
     *     requested_service_name: string|null
     * }
     */
    private function emptyResult(): array
    {
        return [
            'is_service_search' => false,
            'service_id' => null,
            'service_name' => null,
            'is_unsupported_service_search' => false,
            'requested_service_name' => null,
        ];
    }

    private function extractRequestedServiceName(string $message): ?string
    {
        $patterns = [
            '/\b(?:thợ nào sửa|tim tho sua|tìm thợ sửa|can tho sua|cần thợ sửa|goi tho sua|gọi thợ sửa|thue tho sua|thuê thợ sửa|tim nguoi sua|tìm người sửa|can nguoi sua|cần người sửa)\s+(.+)$/iu',
            '/\b(?:sua|sửa|ve sinh|vệ sinh|lap dat|lắp đặt)\s+(.+)$/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches) !== 1) {
                continue;
            }

            $candidate = trim((string) ($matches[1] ?? ''));
            $candidate = preg_replace('/[?.!,;:]+$/u', '', $candidate) ?? $candidate;
            $candidate = trim($candidate);

            if ($candidate === '') {
                continue;
            }

            $tokens = preg_split('/\s+/u', TextNormalizer::normalize($candidate)) ?: [];
            if (count($tokens) > 5) {
                return null;
            }

            return $candidate;
        }

        return null;
    }
}

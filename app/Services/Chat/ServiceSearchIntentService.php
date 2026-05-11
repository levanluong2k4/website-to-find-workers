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

        $requestedServiceName = $this->extractRequestedServiceName($normalizedMessage);
        $serviceMatch = $this->findBestServiceMatch($normalizedMessage, TextNormalizer::tokens($message));
        $bestMatch = $serviceMatch['best_match'];
        $bestScore = $serviceMatch['best_score'];

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

        if (
            !$this->hasServiceSearchIntent($normalizedMessage)
            && !$this->isCompactServiceRequest($normalizedMessage, $bestMatch['service_name'])
        ) {
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
     * @return array{
     *     is_unsupported_service_issue: bool,
     *     requested_service_name: string|null
     * }
     */
    public function detectUnsupportedIssueService(string $message): array
    {
        $normalizedMessage = TextNormalizer::normalize($message);
        if ($normalizedMessage === '' || !$this->hasIssueIntent($normalizedMessage)) {
            return $this->emptyUnsupportedIssueResult();
        }

        $requestedServiceName = $this->extractIssueRequestedServiceName($normalizedMessage);
        if ($requestedServiceName === null) {
            return $this->emptyUnsupportedIssueResult();
        }

        $serviceMatch = $this->findBestServiceMatch($requestedServiceName, TextNormalizer::tokens($requestedServiceName));
        if (($serviceMatch['best_match'] ?? null) !== null && ($serviceMatch['best_score'] ?? 0.0) >= 0.6) {
            return $this->emptyUnsupportedIssueResult();
        }

        $unsupportedServiceName = $this->matchUnsupportedIssueAlias($requestedServiceName);
        if ($unsupportedServiceName === null) {
            return $this->emptyUnsupportedIssueResult();
        }

        return [
            'is_unsupported_service_issue' => true,
            'requested_service_name' => $unsupportedServiceName,
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

    private function hasIssueIntent(string $normalizedMessage): bool
    {
        $markers = [
            'khong',
            'bi',
            'loi',
            'hong',
            'hu',
            'mat',
            'liet',
            'keu',
            'nong',
            'ro',
            'chay',
            'nguyen nhan',
        ];

        foreach ($markers as $marker) {
            if (str_contains($normalizedMessage, $marker)) {
                return true;
            }
        }

        return false;
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

    /**
     * @return array{
     *     is_unsupported_service_issue: bool,
     *     requested_service_name: string|null
     * }
     */
    private function emptyUnsupportedIssueResult(): array
    {
        return [
            'is_unsupported_service_issue' => false,
            'requested_service_name' => null,
        ];
    }

    private function extractRequestedServiceName(string $normalizedMessage): ?string
    {
        $patterns = [
            '/\b(?:tho nao sua|tim tho sua|can tho sua|goi tho sua|thue tho sua|tim nguoi sua|can nguoi sua)\s+(.+)$/u',
            '/\b(?:sua|ve sinh|lap dat)\s+(.+)$/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalizedMessage, $matches) !== 1) {
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

            return implode(' ', $tokens);
        }

        return null;
    }

    /**
     * @param  array<int, string>  $messageTokens
     * @return array{best_match: array<string, mixed>|null, best_score: float}
     */
    private function findBestServiceMatch(string $normalizedMessage, array $messageTokens): array
    {
        $bestMatch = null;
        $bestScore = 0.0;

        foreach (DanhMucDichVu::query()->select('id', 'ten_dich_vu')->get() as $service) {
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

        return [
            'best_match' => $bestMatch,
            'best_score' => $bestScore,
        ];
    }

    private function extractIssueRequestedServiceName(string $normalizedMessage): ?string
    {
        $normalizedMessage = preg_replace('/^(tai sao|vi sao|cho hoi|hoi)\s+/u', '', $normalizedMessage) ?? $normalizedMessage;

        if (
            preg_match(
                '/^(.+?)\s+(khong|bi|loi|hong|hu|mat|liet|keu|nong|ro|chay|nguyen nhan)\b/u',
                $normalizedMessage,
                $matches
            ) !== 1
        ) {
            return null;
        }

        $candidate = trim((string) ($matches[1] ?? ''));
        $candidate = preg_replace('/\b(cua toi|nha toi|cua em|nha em|cua minh|nha minh|o nha|tai nha)\b.*$/u', '', $candidate) ?? $candidate;
        $candidate = preg_replace('/^(cai|chiec|con)\s+/u', '', $candidate) ?? $candidate;
        $candidate = trim($candidate);

        if ($candidate === '') {
            return null;
        }

        $tokens = preg_split('/\s+/u', $candidate) ?: [];
        $tokens = array_values(array_filter($tokens, static function (string $token): bool {
            return !in_array($token, ['cai', 'chiec', 'con', 'cua', 'toi', 'em', 'minh', 'nha', 'o', 'tai'], true);
        }));

        if ($tokens === [] || count($tokens) > 4) {
            return null;
        }

        return implode(' ', $tokens);
    }

    private function matchUnsupportedIssueAlias(string $normalizedCandidate): ?string
    {
        $aliases = [
            'dien thoai' => 'dien thoai',
            'iphone' => 'dien thoai',
            'ipad' => 'may tinh bang',
            'tablet' => 'may tinh bang',
            'laptop' => 'laptop',
            'macbook' => 'laptop',
            'may tinh' => 'may tinh',
            'computer' => 'may tinh',
            'pc' => 'may tinh',
            'camera' => 'camera',
            'may anh' => 'may anh',
            'may in' => 'may in',
            'tai nghe' => 'tai nghe',
        ];

        foreach ($aliases as $alias => $displayName) {
            if ($normalizedCandidate === $alias || str_contains($normalizedCandidate, $alias)) {
                return $displayName;
            }
        }

        return null;
    }
}

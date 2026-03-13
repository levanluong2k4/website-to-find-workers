<?php

namespace App\Services\Chat;

class TextNormalizer
{
    /**
     * Normalize text for robust Vietnamese keyword matching.
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = self::stripAccents($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    /**
     * @return array<int, string>
     */
    public static function tokens(string $text): array
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return [];
        }

        $stopWords = [
            'bi', 'bị', 'khong', 'không', 'toi', 'tôi', 'nha', 'nhà', 'can', 'cần', 'sua', 'sửa', 'cho', 'giup', 'giúp',
            'may', 'máy', 'tu', 'tủ', 'de', 'để', 'va', 'và', 'la', 'là', 'co', 'có', 'khach', 'khách',
        ];
        $stopMap = array_fill_keys($stopWords, true);

        $parts = preg_split('/\s+/u', $normalized) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            if (mb_strlen($part, 'UTF-8') < 2) {
                continue;
            }
            if (isset($stopMap[$part])) {
                continue;
            }
            $tokens[] = $part;
        }

        return array_values(array_unique($tokens));
    }

    public static function overlapScore(array $sourceTokens, array $targetTokens): float
    {
        if ($sourceTokens === [] || $targetTokens === []) {
            return 0.0;
        }

        $targetMap = array_fill_keys($targetTokens, true);
        $hits = 0;
        foreach ($sourceTokens as $token) {
            if (isset($targetMap[$token])) {
                $hits++;
            }
        }

        return min(1.0, $hits / max(count($sourceTokens), 1));
    }

    private static function stripAccents(string $text): string
    {
        $map = [
            'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
            'đ' => 'd',
        ];

        return strtr($text, $map);
    }
}


<?php

namespace App\Services\Chat;

class ChatContextBuilderService
{
    public function __construct(
        private readonly AssistantSoulConfigService $assistantSoulConfigService
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $historyMessages
     * @param  array<int, array<string, mixed>>  $cases
     * @param  array<int, array<string, mixed>>  $technicians
     * @param  array<int, array<string, mixed>>  $youtubeLinks
     * @return array{messages: array<int, array<string, string>>, knowledge: array<string, mixed>}
     */
    public function build(
        array $historyMessages,
        array $cases,
        array $technicians,
        array $youtubeLinks
    ): array {
        $assistantConfig = $this->assistantSoulConfigService->getConfig();

        $knowledge = [
            'cases' => array_slice($cases, 0, 3),
            'technicians' => array_slice($technicians, 0, 3),
            'youtube_links' => array_slice($youtubeLinks, 0, 3),
            'service_process' => $this->arrayConfig($assistantConfig, 'service_process'),
            'emergency_keywords' => $this->arrayConfig($assistantConfig, 'emergency_keywords'),
            'reference_prices' => $this->extractReferencePrices($technicians),
        ];

        $messages = [
            [
                'role' => 'system',
                'content' => $this->buildSystemPrompt($assistantConfig),
            ],
            [
                'role' => 'system',
                'content' => 'Du lieu thuc te tu database: ' . json_encode($knowledge, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
        ];

        foreach (array_slice($historyMessages, -10) as $historyMessage) {
            $sender = (string) ($historyMessage['sender'] ?? 'user');
            $messages[] = [
                'role' => $sender === 'assistant' ? 'assistant' : 'user',
                'content' => (string) ($historyMessage['text'] ?? ''),
            ];
        }

        return [
            'messages' => $messages,
            'knowledge' => $knowledge,
        ];
    }

    /**
     * @param  array<string, mixed>  $assistantConfig
     */
    private function buildSystemPrompt(array $assistantConfig): string
    {
        $sections = [];

        $name = trim((string) ($assistantConfig['name'] ?? 'ASSISTANT SOUL'));
        if ($name !== '') {
            $sections[] = $name;
        }

        $role = trim((string) ($assistantConfig['role'] ?? ''));
        if ($role !== '') {
            $sections[] = $role;
        }

        $identityRules = $this->arrayConfig($assistantConfig, 'identity_rules');
        if ($identityRules !== []) {
            $sections[] = "Vai tro va cach xung ho:\n" . $this->formatBulletList($identityRules);
        }

        $requiredRules = $this->arrayConfig($assistantConfig, 'required_rules');
        if ($requiredRules !== []) {
            $sections[] = "Quy tac bat buoc:\n" . $this->formatBulletList($requiredRules);
        }

        $responseGoals = $this->arrayConfig($assistantConfig, 'response_goals');
        if ($responseGoals !== []) {
            $sections[] = "Muc tieu tra loi:\n" . $this->formatBulletList($responseGoals);
        }

        $assistantTextOrder = $this->arrayConfig($assistantConfig, 'assistant_text_order');
        if ($assistantTextOrder !== []) {
            $sections[] = "Noi dung assistant_text uu tien theo thu tu:\n" . $this->formatNumberedList($assistantTextOrder);
        }

        $jsonKeys = $this->arrayConfig($assistantConfig, 'json_keys');
        if ($jsonKeys !== []) {
            $sections[] = "Bat buoc tra ve JSON object voi cac key:\n" . $this->formatBulletList(array_map(
                static fn (string $key): string => $key . ': ' . ($key === 'assistant_text' ? 'string' : 'array'),
                $jsonKeys
            ));
        }

        $outputStyle = trim((string) ($assistantConfig['output_style'] ?? ''));
        if ($outputStyle !== '') {
            $sections[] = $outputStyle;
        }

        return implode("\n\n", array_filter($sections));
    }

    /**
     * @param  array<int, array<string, mixed>>  $technicians
     * @return array<int, string>
     */
    private function extractReferencePrices(array $technicians): array
    {
        $prices = [];

        foreach ($technicians as $technician) {
            $price = trim((string) ($technician['reference_price'] ?? ''));
            if ($price === '' || in_array($price, $prices, true)) {
                continue;
            }

            $prices[] = $price;
        }

        return $prices;
    }

    /**
     * @param  array<string, mixed>  $assistantConfig
     * @return array<int, string>
     */
    private function arrayConfig(array $assistantConfig, string $key): array
    {
        $value = $assistantConfig[$key] ?? [];

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item): string {
            return trim((string) $item);
        }, $value), static fn (string $item): bool => $item !== ''));
    }

    /**
     * @param  array<int, string>  $items
     */
    private function formatBulletList(array $items): string
    {
        return implode("\n", array_map(static fn (string $item): string => '- ' . $item, $items));
    }

    /**
     * @param  array<int, string>  $items
     */
    private function formatNumberedList(array $items): string
    {
        $lines = [];

        foreach (array_values($items) as $index => $item) {
            $lines[] = ($index + 1) . '. ' . $item;
        }

        return implode("\n", $lines);
    }
}

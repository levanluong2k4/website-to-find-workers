<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class TravelFeeConfigService
{
    private const SETTING_KEY = 'travel_fee_config';

    private const DEFAULT_PER_KM = 5000;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $resolvedConfig = null;

    private bool $settingLoaded = false;

    private ?AppSetting $setting = null;

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        if ($this->resolvedConfig !== null) {
            return $this->resolvedConfig;
        }

        $value = $this->getSetting()?->value;
        $this->resolvedConfig = $this->normalizeConfig(is_array($value) ? $value : []);

        return $this->resolvedConfig;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditorState(): array
    {
        $setting = $this->getSetting();
        $config = $this->getConfig();

        return [
            'config' => $config,
            'has_override' => $setting !== null,
            'updated_at' => optional($setting?->updated_at)->toISOString(),
            'updated_by' => $setting?->updater?->name,
            'preview' => $this->buildPreview($config),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPublicState(): array
    {
        $config = $this->getConfig();

        return [
            'config' => $config,
            'mode' => count($config['tiers']) > 0 ? 'tiered' : 'per_km',
            'preview' => $this->buildPreview($config),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function updateConfig(array $config, ?User $user = null): array
    {
        $normalized = $this->normalizeConfig($config);

        if ($this->settingsTableExists()) {
            AppSetting::query()->updateOrCreate(
                ['key' => self::SETTING_KEY],
                [
                    'value' => $normalized,
                    'updated_by' => $user?->id,
                ]
            );
        }

        $this->settingLoaded = false;
        $this->setting = null;
        $this->resolvedConfig = null;

        return $this->getEditorState();
    }

    public function resolveFee(float $distanceKm): float
    {
        $distance = round(max(0, $distanceKm), 2);
        $config = $this->getConfig();

        foreach ($config['tiers'] as $tier) {
            if ($this->matchesTier($distance, $tier)) {
                return (float) $tier['fee'];
            }
        }

        return round($distance * (float) $config['default_per_km']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveTier(float $distanceKm): ?array
    {
        $distance = round(max(0, $distanceKm), 2);

        foreach ($this->getConfig()['tiers'] as $tier) {
            if ($this->matchesTier($distance, $tier)) {
                return $tier;
            }
        }

        return null;
    }

    private function getSetting(): ?AppSetting
    {
        if ($this->settingLoaded) {
            return $this->setting;
        }

        if (!$this->settingsTableExists()) {
            $this->settingLoaded = true;
            $this->setting = null;

            return null;
        }

        $this->setting = AppSetting::query()
            ->with('updater:id,name')
            ->where('key', self::SETTING_KEY)
            ->first();
        $this->settingLoaded = true;

        return $this->setting;
    }

    private function settingsTableExists(): bool
    {
        try {
            return Schema::hasTable('app_settings');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeConfig(array $config): array
    {
        $defaultPerKm = round(max(0, (float) ($config['default_per_km'] ?? self::DEFAULT_PER_KM)));
        $tiers = collect($config['tiers'] ?? [])
            ->filter(static fn ($tier) => is_array($tier))
            ->map(function (array $tier): array {
                return [
                    'from_km' => round(max(0, (float) ($tier['from_km'] ?? 0)), 2),
                    'to_km' => round(max(0, (float) ($tier['to_km'] ?? 0)), 2),
                    'fee' => round(max(0, (float) ($tier['fee'] ?? 0))),
                ];
            })
            ->filter(static fn (array $tier) => $tier['to_km'] > $tier['from_km'])
            ->sort(function (array $left, array $right): int {
                if ($left['from_km'] === $right['from_km']) {
                    return $left['to_km'] <=> $right['to_km'];
                }

                return $left['from_km'] <=> $right['from_km'];
            })
            ->values()
            ->all();

        return [
            'default_per_km' => $defaultPerKm,
            'tiers' => $tiers,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function buildPreview(array $config): array
    {
        $samples = [1, 3, 5, 8];

        return [
            'tiers' => array_map(function (array $tier): array {
                return [
                    'from_km' => $tier['from_km'],
                    'to_km' => $tier['to_km'],
                    'fee' => $tier['fee'],
                    'label' => $this->formatTierLabel($tier),
                ];
            }, $config['tiers']),
            'samples' => array_map(function (float $distanceKm): array {
                return [
                    'distance_km' => $distanceKm,
                    'fee' => $this->resolveFee($distanceKm),
                ];
            }, $samples),
        ];
    }

    /**
     * @param  array<string, mixed>  $tier
     */
    private function matchesTier(float $distanceKm, array $tier): bool
    {
        return $distanceKm >= (float) $tier['from_km']
            && $distanceKm <= (float) $tier['to_km'];
    }

    /**
     * @param  array<string, mixed>  $tier
     */
    private function formatTierLabel(array $tier): string
    {
        return rtrim(rtrim(number_format((float) $tier['from_km'], 2, '.', ''), '0'), '.')
            . ' - '
            . rtrim(rtrim(number_format((float) $tier['to_km'], 2, '.', ''), '0'), '.')
            . ' km';
    }
}

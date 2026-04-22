<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;

class TravelFeeConfigService
{
    private const SETTING_KEY = 'travel_fee_config';

    private const DEFAULT_PER_KM = 5000;

    private const DEFAULT_FREE_DISTANCE_KM = 1;

    private const DEFAULT_STORE_ADDRESS = '2 Đường Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa';

    private const DEFAULT_STORE_TRANSPORT_FEE = 0;

    private const DEFAULT_STORE_HOTLINE = '0905 123 456';

    private const DEFAULT_STORE_OPENING_HOURS = 'Thứ 2 - CN: 07:00 - 20:00';

    private const DEFAULT_COMPLAINT_WINDOW_DAYS = 3;

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
        $currentConfig = $this->getConfig();
        $normalized = $this->normalizeConfig([
            'free_distance_km' => array_key_exists('free_distance_km', $config)
                ? $config['free_distance_km']
                : ($currentConfig['free_distance_km'] ?? self::DEFAULT_FREE_DISTANCE_KM),
            'default_per_km' => array_key_exists('default_per_km', $config)
                ? $config['default_per_km']
                : ($currentConfig['default_per_km'] ?? self::DEFAULT_PER_KM),
            'store_address' => array_key_exists('store_address', $config)
                ? $config['store_address']
                : ($currentConfig['store_address'] ?? self::DEFAULT_STORE_ADDRESS),
            'store_transport_fee' => array_key_exists('store_transport_fee', $config)
                ? $config['store_transport_fee']
                : ($currentConfig['store_transport_fee'] ?? self::DEFAULT_STORE_TRANSPORT_FEE),
            'store_hotline' => array_key_exists('store_hotline', $config)
                ? $config['store_hotline']
                : ($currentConfig['store_hotline'] ?? self::DEFAULT_STORE_HOTLINE),
            'store_opening_hours' => array_key_exists('store_opening_hours', $config)
                ? $config['store_opening_hours']
                : ($currentConfig['store_opening_hours'] ?? self::DEFAULT_STORE_OPENING_HOURS),
            'complaint_window_days' => array_key_exists('complaint_window_days', $config)
                ? $config['complaint_window_days']
                : ($currentConfig['complaint_window_days'] ?? self::DEFAULT_COMPLAINT_WINDOW_DAYS),
            'tiers' => array_key_exists('tiers', $config)
                ? $config['tiers']
                : ($currentConfig['tiers'] ?? []),
        ]);

        if (AppSetting::tableExists()) {
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
        $tier = $this->resolveTier($distance);

        if ($tier !== null) {
            return $this->resolveTierTravelFee($tier);
        }

        if ($distance < (float) ($config['free_distance_km'] ?? self::DEFAULT_FREE_DISTANCE_KM)) {
            return 0;
        }

        return round($distance * (float) $config['default_per_km']);
    }

    public function resolveFreeDistanceKm(): float
    {
        return (float) ($this->getConfig()['free_distance_km'] ?? self::DEFAULT_FREE_DISTANCE_KM);
    }

    public function resolveStoreAddress(): string
    {
        return (string) ($this->getConfig()['store_address'] ?? self::DEFAULT_STORE_ADDRESS);
    }

    public function resolveStoreTransportFee(): float
    {
        $config = $this->getConfig();
        $configuredFee = round(max(0, (float) ($config['store_transport_fee'] ?? self::DEFAULT_STORE_TRANSPORT_FEE)));

        if ($configuredFee > 0) {
            return $configuredFee;
        }

        return $this->deriveStoreTransportFee($config['tiers'] ?? []) ?? self::DEFAULT_STORE_TRANSPORT_FEE;
    }

    public function resolveStoreHotline(): string
    {
        return (string) ($this->getConfig()['store_hotline'] ?? self::DEFAULT_STORE_HOTLINE);
    }

    public function resolveStoreOpeningHours(): string
    {
        return (string) ($this->getConfig()['store_opening_hours'] ?? self::DEFAULT_STORE_OPENING_HOURS);
    }

    public function resolveStoreMapUrl(): string
    {
        $address = trim($this->resolveStoreAddress());

        if ($address === '') {
            return '';
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address);
    }

    public function resolveComplaintWindowDays(): int
    {
        return (int) ($this->getConfig()['complaint_window_days'] ?? self::DEFAULT_COMPLAINT_WINDOW_DAYS);
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

        if (!AppSetting::tableExists()) {
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

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeConfig(array $config): array
    {
        $defaultPerKm = round(max(0, (float) ($config['default_per_km'] ?? self::DEFAULT_PER_KM)));
        $storeAddress = trim((string) ($config['store_address'] ?? self::DEFAULT_STORE_ADDRESS));
        $rawStoreTransportFee = round(max(0, (float) ($config['store_transport_fee'] ?? self::DEFAULT_STORE_TRANSPORT_FEE)));
        $storeHotline = trim((string) ($config['store_hotline'] ?? self::DEFAULT_STORE_HOTLINE));
        $storeOpeningHours = trim((string) ($config['store_opening_hours'] ?? self::DEFAULT_STORE_OPENING_HOURS));
        $tiers = collect($config['tiers'] ?? [])
            ->filter(static fn ($tier) => is_array($tier))
            ->map(function (array $tier) use ($rawStoreTransportFee): array {
                $travelFee = round(max(0, (float) ($tier['travel_fee'] ?? $tier['fee'] ?? 0)));
                $transportFee = round(max(0, (float) ($tier['transport_fee'] ?? $rawStoreTransportFee)));

                return [
                    'from_km' => round(max(0, (float) ($tier['from_km'] ?? 0)), 2),
                    'to_km' => round(max(0, (float) ($tier['to_km'] ?? 0)), 2),
                    'transport_fee' => $transportFee,
                    'travel_fee' => $travelFee,
                    'fee' => $travelFee,
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
        $freeDistanceKm = round(max(0, (float) (
            $config['free_distance_km']
            ?? $this->deriveFreeDistanceKm($tiers)
            ?? self::DEFAULT_FREE_DISTANCE_KM
        )), 2);
        $storeTransportFee = round(max(0, (float) (
            $config['store_transport_fee']
            ?? $this->deriveStoreTransportFee($tiers)
            ?? self::DEFAULT_STORE_TRANSPORT_FEE
        )));
        $complaintWindowDays = (int) ($config['complaint_window_days'] ?? self::DEFAULT_COMPLAINT_WINDOW_DAYS);
        $complaintWindowDays = max(1, min($complaintWindowDays, 30));

        return [
            'free_distance_km' => $freeDistanceKm,
            'default_per_km' => $defaultPerKm,
            'store_address' => $storeAddress !== '' ? $storeAddress : self::DEFAULT_STORE_ADDRESS,
            'store_transport_fee' => $storeTransportFee,
            'store_hotline' => $storeHotline !== '' ? $storeHotline : self::DEFAULT_STORE_HOTLINE,
            'store_opening_hours' => $storeOpeningHours !== '' ? $storeOpeningHours : self::DEFAULT_STORE_OPENING_HOURS,
            'complaint_window_days' => $complaintWindowDays,
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
            'store' => [
                'address' => $config['store_address'],
                'transport_fee' => $config['store_transport_fee'],
                'hotline' => $config['store_hotline'],
                'opening_hours' => $config['store_opening_hours'],
                'map_url' => $this->resolveStoreMapUrl(),
            ],
            'free_distance_km' => $config['free_distance_km'],
            'default_per_km' => $config['default_per_km'],
            'complaint_window_days' => $config['complaint_window_days'],
            'tiers' => array_map(function (array $tier): array {
                $travelFee = $this->resolveTierTravelFee($tier);

                return [
                    'from_km' => $tier['from_km'],
                    'to_km' => $tier['to_km'],
                    'transport_fee' => $this->resolveTierTransportFee($tier),
                    'travel_fee' => $travelFee,
                    'fee' => $travelFee,
                    'label' => $this->formatTierLabel($tier),
                ];
            }, $config['tiers']),
            'samples' => array_map(function (float $distanceKm): array {
                $tier = $this->resolveTier($distanceKm);
                $travelFee = $this->resolveFee($distanceKm);

                return [
                    'distance_km' => $distanceKm,
                    'transport_fee' => $tier ? $this->resolveTierTransportFee($tier) : 0,
                    'travel_fee' => $travelFee,
                    'fee' => $travelFee,
                    'label' => $tier ? $this->formatTierLabel($tier) : null,
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

    /**
     * @param  array<string, mixed>  $tier
     */
    private function resolveTierTravelFee(array $tier): float
    {
        return round(max(0, (float) ($tier['travel_fee'] ?? $tier['fee'] ?? 0)));
    }

    /**
     * @param  array<string, mixed>  $tier
     */
    private function resolveTierTransportFee(array $tier): float
    {
        return round(max(0, (float) ($tier['transport_fee'] ?? 0)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $tiers
     */
    private function deriveFreeDistanceKm(array $tiers): ?float
    {
        foreach ($tiers as $tier) {
            if ((float) ($tier['from_km'] ?? 0) !== 0.0) {
                continue;
            }

            if ($this->resolveTierTravelFee($tier) === 0.0) {
                return round(max(0, (float) ($tier['to_km'] ?? 0)), 2);
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tiers
     */
    private function deriveStoreTransportFee(array $tiers): ?float
    {
        foreach ($tiers as $tier) {
            $transportFee = $this->resolveTierTransportFee($tier);

            if ($transportFee > 0) {
                return $transportFee;
            }
        }

        return null;
    }
}

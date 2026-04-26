<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\DonDatLich;
use App\Models\User;

class TravelFeeConfigService
{
    private const SETTING_KEY = 'travel_fee_config';

    private const DEFAULT_PER_KM = 5000;

    private const DEFAULT_FREE_DISTANCE_KM = 1;

    private const DEFAULT_MAX_SERVICE_DISTANCE_KM = 8;

    private const DEFAULT_STORE_ADDRESS = '2 Đường Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa';

    private const DEFAULT_STORE_LATITUDE = 12.2618;

    private const DEFAULT_STORE_LONGITUDE = 109.1995;

    private const DEFAULT_STORE_TRANSPORT_FEE = 0;

    private const DEFAULT_STORE_HOTLINE = '0905 123 456';

    private const DEFAULT_STORE_OPENING_HOURS = 'Thứ 2 - CN: 07:00 - 20:00';

    private const DEFAULT_BOOKING_TIME_SLOTS = [
        '08:00-10:00',
        '10:00-12:00',
        '12:00-14:00',
        '14:00-17:00',
    ];

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
            'max_service_distance_km' => array_key_exists('max_service_distance_km', $config)
                ? $config['max_service_distance_km']
                : ($currentConfig['max_service_distance_km'] ?? self::DEFAULT_MAX_SERVICE_DISTANCE_KM),
            'default_per_km' => array_key_exists('default_per_km', $config)
                ? $config['default_per_km']
                : ($currentConfig['default_per_km'] ?? self::DEFAULT_PER_KM),
            'store_address' => array_key_exists('store_address', $config)
                ? $config['store_address']
                : ($currentConfig['store_address'] ?? self::DEFAULT_STORE_ADDRESS),
            'store_latitude' => array_key_exists('store_latitude', $config)
                ? $config['store_latitude']
                : ($currentConfig['store_latitude'] ?? self::DEFAULT_STORE_LATITUDE),
            'store_longitude' => array_key_exists('store_longitude', $config)
                ? $config['store_longitude']
                : ($currentConfig['store_longitude'] ?? self::DEFAULT_STORE_LONGITUDE),
            'store_transport_fee' => array_key_exists('store_transport_fee', $config)
                ? $config['store_transport_fee']
                : ($currentConfig['store_transport_fee'] ?? self::DEFAULT_STORE_TRANSPORT_FEE),
            'store_hotline' => array_key_exists('store_hotline', $config)
                ? $config['store_hotline']
                : ($currentConfig['store_hotline'] ?? self::DEFAULT_STORE_HOTLINE),
            'store_opening_hours' => array_key_exists('store_opening_hours', $config)
                ? $config['store_opening_hours']
                : ($currentConfig['store_opening_hours'] ?? self::DEFAULT_STORE_OPENING_HOURS),
            'booking_time_slots' => array_key_exists('booking_time_slots', $config)
                ? $config['booking_time_slots']
                : ($currentConfig['booking_time_slots'] ?? self::DEFAULT_BOOKING_TIME_SLOTS),
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

    public function resolveStoreLatitude(): float
    {
        return (float) ($this->getConfig()['store_latitude'] ?? self::DEFAULT_STORE_LATITUDE);
    }

    public function resolveStoreLongitude(): float
    {
        return (float) ($this->getConfig()['store_longitude'] ?? self::DEFAULT_STORE_LONGITUDE);
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function resolveStoreCoordinates(): array
    {
        return [
            'lat' => $this->resolveStoreLatitude(),
            'lng' => $this->resolveStoreLongitude(),
        ];
    }

    public function resolveMaxServiceDistanceKm(): float
    {
        return (float) ($this->getConfig()['max_service_distance_km'] ?? self::DEFAULT_MAX_SERVICE_DISTANCE_KM);
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

    /**
     * @return array<int, string>
     */
    public function resolveBookingTimeSlots(): array
    {
        $slots = $this->getConfig()['booking_time_slots'] ?? self::DEFAULT_BOOKING_TIME_SLOTS;

        return is_array($slots) && $slots !== []
            ? $slots
            : self::DEFAULT_BOOKING_TIME_SLOTS;
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
        $tiers = array_values($this->getConfig()['tiers'] ?? []);
        $lastTierIndex = count($tiers) - 1;

        foreach ($tiers as $index => $tier) {
            if ($this->matchesTier($distance, $tier, $index === $lastTierIndex)) {
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
        $maxServiceDistanceKm = round(max(0, (float) ($config['max_service_distance_km'] ?? self::DEFAULT_MAX_SERVICE_DISTANCE_KM)), 2);
        $storeAddress = trim((string) ($config['store_address'] ?? self::DEFAULT_STORE_ADDRESS));
        $storeLatitude = $this->normalizeCoordinate(
            $config['store_latitude'] ?? self::DEFAULT_STORE_LATITUDE,
            -90,
            90,
            self::DEFAULT_STORE_LATITUDE
        );
        $storeLongitude = $this->normalizeCoordinate(
            $config['store_longitude'] ?? self::DEFAULT_STORE_LONGITUDE,
            -180,
            180,
            self::DEFAULT_STORE_LONGITUDE
        );
        $rawStoreTransportFee = round(max(0, (float) ($config['store_transport_fee'] ?? self::DEFAULT_STORE_TRANSPORT_FEE)));
        $storeHotline = trim((string) ($config['store_hotline'] ?? self::DEFAULT_STORE_HOTLINE));
        $storeOpeningHours = trim((string) ($config['store_opening_hours'] ?? self::DEFAULT_STORE_OPENING_HOURS));
        $bookingTimeSlots = $this->normalizeBookingTimeSlots($config['booking_time_slots'] ?? self::DEFAULT_BOOKING_TIME_SLOTS);
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
            'max_service_distance_km' => $maxServiceDistanceKm,
            'default_per_km' => $defaultPerKm,
            'store_address' => $storeAddress !== '' ? $storeAddress : self::DEFAULT_STORE_ADDRESS,
            'store_latitude' => $storeLatitude,
            'store_longitude' => $storeLongitude,
            'store_transport_fee' => $storeTransportFee,
            'store_hotline' => $storeHotline !== '' ? $storeHotline : self::DEFAULT_STORE_HOTLINE,
            'store_opening_hours' => $storeOpeningHours !== '' ? $storeOpeningHours : self::DEFAULT_STORE_OPENING_HOURS,
            'booking_time_slots' => $bookingTimeSlots,
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
                'latitude' => $config['store_latitude'],
                'longitude' => $config['store_longitude'],
                'transport_fee' => $config['store_transport_fee'],
                'hotline' => $config['store_hotline'],
                'opening_hours' => $config['store_opening_hours'],
                'booking_time_slots' => array_map(function (string $slot): array {
                    return [
                        'value' => $slot,
                        'label' => str_replace('-', ' - ', $slot),
                    ];
                }, $config['booking_time_slots']),
                'map_url' => $this->resolveStoreMapUrl(),
            ],
            'free_distance_km' => $config['free_distance_km'],
            'max_service_distance_km' => $config['max_service_distance_km'],
            'default_per_km' => $config['default_per_km'],
            'complaint_window_days' => $config['complaint_window_days'],
            'booking_time_slots' => array_map(function (string $slot): array {
                return [
                    'value' => $slot,
                    'label' => str_replace('-', ' - ', $slot),
                ];
            }, $config['booking_time_slots']),
            'tiers' => array_map(function (array $tier, int $index) use ($config): array {
                $travelFee = $this->resolveTierTravelFee($tier);
                $isLastTier = $index === (count($config['tiers']) - 1);

                return [
                    'from_km' => $tier['from_km'],
                    'to_km' => $tier['to_km'],
                    'transport_fee' => $this->resolveTierTransportFee($tier),
                    'travel_fee' => $travelFee,
                    'fee' => $travelFee,
                    'label' => $this->formatTierLabel($tier, $isLastTier),
                ];
            }, $config['tiers'], array_keys($config['tiers'])),
            'samples' => array_map(function (float $distanceKm): array {
                $tier = $this->resolveTier($distanceKm);
                $travelFee = $this->resolveFee($distanceKm);
                $tierIndex = $tier === null
                    ? null
                    : collect($this->getConfig()['tiers'])->search(static fn (array $configuredTier): bool => $configuredTier === $tier);
                $isLastTier = $tierIndex !== false && $tierIndex === (count($this->getConfig()['tiers']) - 1);

                return [
                    'distance_km' => $distanceKm,
                    'transport_fee' => $tier ? $this->resolveTierTransportFee($tier) : 0,
                    'travel_fee' => $travelFee,
                    'fee' => $travelFee,
                    'label' => $tier ? $this->formatTierLabel($tier, $isLastTier) : null,
                ];
            }, $samples),
        ];
    }

    /**
     * @param  array<string, mixed>  $tier
     */
    private function matchesTier(float $distanceKm, array $tier, bool $isLastTier = false): bool
    {
        return $distanceKm >= (float) $tier['from_km']
            && (
                $distanceKm < (float) $tier['to_km']
                || ($isLastTier && $distanceKm <= (float) $tier['to_km'])
            );
    }

    /**
     * @param  array<string, mixed>  $tier
     */
    private function formatTierLabel(array $tier, bool $isLastTier = false): string
    {
        return $this->formatDistanceValue((float) $tier['from_km'])
            . ' - '
            . $this->formatDistanceValue($this->resolveTierDisplayUpperBound($tier, $isLastTier))
            . ' km';
    }

    /**
     * @param  array<string, mixed>  $tier
     */
    private function resolveTierDisplayUpperBound(array $tier, bool $isLastTier = false): float
    {
        $fromKm = round(max(0, (float) ($tier['from_km'] ?? 0)), 2);
        $toKm = round(max(0, (float) ($tier['to_km'] ?? 0)), 2);

        if ($isLastTier) {
            return $toKm;
        }

        return round(max($fromKm, $toKm - 0.01), 2);
    }

    private function formatDistanceValue(float $distanceKm): string
    {
        return rtrim(rtrim(number_format($distanceKm, 2, '.', ''), '0'), '.');
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

    /**
     * @param  mixed  $value
     */
    private function normalizeCoordinate(mixed $value, float $min, float $max, float $default): float
    {
        $numeric = is_numeric($value) ? (float) $value : $default;
        $numeric = min($max, max($min, $numeric));

        return round($numeric, 6);
    }

    /**
     * @param  mixed  $slots
     * @return array<int, string>
     */
    private function normalizeBookingTimeSlots(mixed $slots): array
    {
        $normalizedSlots = collect(is_array($slots) ? $slots : [])
            ->map(fn ($slot) => DonDatLich::normalizeTimeSlot((string) $slot))
            ->filter(fn (string $slot) => $slot !== '' && $this->isValidBookingTimeSlot($slot))
            ->unique()
            ->sortBy(fn (string $slot) => $this->timeToMinutes(explode('-', $slot, 2)[0] ?? '00:00'))
            ->values()
            ->all();

        return $normalizedSlots !== [] ? $normalizedSlots : self::DEFAULT_BOOKING_TIME_SLOTS;
    }

    private function isValidBookingTimeSlot(string $slot): bool
    {
        if (!preg_match('/^(?<start>\d{2}:\d{2})-(?<end>\d{2}:\d{2})$/', $slot, $matches)) {
            return false;
        }

        $startMinutes = $this->timeToMinutes($matches['start']);
        $endMinutes = $this->timeToMinutes($matches['end']);

        return $startMinutes !== null && $endMinutes !== null && $endMinutes > $startMinutes;
    }

    private function timeToMinutes(?string $value): ?int
    {
        $value = trim((string) $value);
        if (!preg_match('/^(?<hour>\d{2}):(?<minute>\d{2})$/', $value, $matches)) {
            return null;
        }

        $hour = (int) $matches['hour'];
        $minute = (int) $matches['minute'];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return ($hour * 60) + $minute;
    }
}

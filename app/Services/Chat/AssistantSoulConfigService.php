<?php

namespace App\Services\Chat;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AssistantSoulConfigService
{
    private const SETTING_KEY = 'assistant_soul_override';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $resolvedConfig = null;

    private bool $overrideSettingLoaded = false;

    private ?AppSetting $overrideSetting = null;

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        if ($this->resolvedConfig !== null) {
            return $this->resolvedConfig;
        }

        $default = $this->getDefaultConfig();
        $override = $this->getOverrideValue();

        $this->resolvedConfig = $this->mergeConfig($default, $override);

        return $this->resolvedConfig;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultConfig(): array
    {
        $config = config('assistant_soul', []);

        return is_array($config) ? $config : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditorState(): array
    {
        $override = $this->getOverrideSetting();

        return [
            'config' => $this->getConfig(),
            'has_override' => $override !== null,
            'updated_at' => optional($override?->updated_at)->toISOString(),
            'updated_by' => $override?->updater?->name,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function updateConfig(array $config, ?User $user = null): void
    {
        if (!$this->settingsTableExists()) {
            return;
        }

        AppSetting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            [
                'value' => $config,
                'updated_by' => $user?->id,
            ]
        );

        $this->overrideSettingLoaded = false;
        $this->overrideSetting = null;
        $this->resolvedConfig = null;
    }

    public function resetConfig(): void
    {
        if (!$this->settingsTableExists()) {
            return;
        }

        AppSetting::query()
            ->where('key', self::SETTING_KEY)
            ->delete();

        $this->overrideSettingLoaded = false;
        $this->overrideSetting = null;
        $this->resolvedConfig = null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getOverrideValue(): array
    {
        $setting = $this->getOverrideSetting();

        return is_array($setting?->value) ? $setting->value : [];
    }

    private function getOverrideSetting(): ?AppSetting
    {
        if ($this->overrideSettingLoaded) {
            return $this->overrideSetting;
        }

        if (!$this->settingsTableExists()) {
            return null;
        }

        $this->overrideSetting = AppSetting::query()
            ->with('updater:id,name')
            ->where('key', self::SETTING_KEY)
            ->first();
        $this->overrideSettingLoaded = true;

        return $this->overrideSetting;
    }

    private function settingsTableExists(): bool
    {
        static $exists;

        if ($exists !== null) {
            return $exists;
        }

        try {
            $exists = Schema::hasTable('app_settings');
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }

    /**
     * @param  array<string, mixed>  $default
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function mergeConfig(array $default, array $override): array
    {
        $merged = $default;

        foreach ($override as $key => $value) {
            if (is_array($value) && isset($default[$key]) && is_array($default[$key]) && $this->isAssoc($value) && $this->isAssoc($default[$key])) {
                $merged[$key] = $this->mergeConfig($default[$key], $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * @param  array<mixed>  $value
     */
    private function isAssoc(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }
}

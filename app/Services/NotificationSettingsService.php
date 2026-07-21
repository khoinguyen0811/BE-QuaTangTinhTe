<?php

namespace App\Services;

use App\Models\ProjectSetting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class NotificationSettingsService
{
    public const SETTING_KEY = 'notification_settings';

    private const ENCRYPTED_PREFIX = 'encrypted:';

    private const SECRET_PATHS = [
        'zalo_oa.secret_key',
        'zalo_oa.access_token',
        'zalo_oa.refresh_token',
        'zalo_personal.bot_token',
        'smtp.password',
    ];

    public function defaults(): array
    {
        return [
            'zalo_oa' => [
                'enabled' => false,
                'app_id' => '',
                'secret_key' => '',
                'access_token' => '',
                'refresh_token' => '',
                'template_id' => '',
                'template_data' => '{"order_code":"{{order_number}}","customer_name":"{{customer_name}}","status":"{{status}}","amount":"{{grand_total}}"}',
            ],
            'zalo_personal' => [
                'enabled' => false,
                'bot_token' => '',
                'chat_id' => '',
            ],
            'smtp' => [
                'enabled' => false,
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => '',
                'password' => '',
                'from_email' => '',
                'from_name' => (string) config('app.name', 'Cửa hàng'),
                'owner_email' => '',
            ],
            'dashboard' => [
                'enabled' => true,
                'play_sound' => true,
                'auto_refresh' => true,
            ],
        ];
    }

    public function exists(): bool
    {
        return ProjectSetting::query()
            ->where('setting_key', self::SETTING_KEY)
            ->exists();
    }

    /**
     * Return merged settings. Secrets are decrypted only for server-side callers.
     */
    public function get(bool $withSecrets = true): array
    {
        $stored = $this->raw();
        $settings = $this->merge($stored);

        foreach (self::SECRET_PATHS as $path) {
            data_set(
                $settings,
                $path,
                $withSecrets ? $this->decryptSecret((string) data_get($settings, $path, '')) : ''
            );
        }

        return $settings;
    }

    /**
     * Data safe to render in HTML. Secret values never leave the server.
     */
    public function forForm(): array
    {
        return $this->get(false);
    }

    public function configuredSecrets(): array
    {
        $stored = $this->raw();
        $configured = [];

        foreach (self::SECRET_PATHS as $path) {
            data_set($configured, $path, $this->hasSecretValue(data_get($stored, $path)));
        }

        return $configured;
    }

    /**
     * Merge submitted settings, preserving blank secrets and encrypting every secret at rest.
     */
    public function save(array $settings): array
    {
        $existing = $this->raw();
        $merged = array_replace_recursive($this->defaults(), $existing, $settings);

        foreach (self::SECRET_PATHS as $path) {
            $submitted = data_get($settings, $path);
            $existingValue = data_get($existing, $path);

            if (is_string($submitted) && trim($submitted) !== '') {
                data_set($merged, $path, $this->encryptSecret(trim($submitted)));

                continue;
            }

            if ($this->hasSecretValue($existingValue)) {
                // Also migrates legacy plaintext credentials the next time settings are saved.
                data_set($merged, $path, $this->encryptSecret($this->decryptSecret((string) $existingValue)));
            } else {
                data_set($merged, $path, '');
            }
        }

        $merged['smtp']['port'] = (int) ($merged['smtp']['port'] ?? 587);

        foreach (['zalo_oa', 'zalo_personal', 'smtp', 'dashboard'] as $section) {
            $merged[$section]['enabled'] = (bool) ($merged[$section]['enabled'] ?? false);
        }

        $merged['dashboard']['play_sound'] = (bool) ($merged['dashboard']['play_sound'] ?? false);
        $merged['dashboard']['auto_refresh'] = (bool) ($merged['dashboard']['auto_refresh'] ?? false);

        ProjectSetting::query()->updateOrCreate(
            ['setting_key' => self::SETTING_KEY],
            ['setting_value' => $merged, 'updated_at' => now()]
        );

        return $this->get();
    }

    /**
     * Merge unsaved form values with stored credentials for connection-test endpoints.
     */
    public function mergeForTest(array $partial): array
    {
        $settings = array_replace_recursive($this->get(), $partial);

        foreach (self::SECRET_PATHS as $path) {
            $submitted = data_get($partial, $path);
            if (! is_string($submitted) || trim($submitted) === '') {
                data_set($settings, $path, data_get($this->get(), $path, ''));
            }
        }

        return $settings;
    }

    public function hasConfiguredSecret(string $path): bool
    {
        return $this->hasSecretValue(data_get($this->raw(), $path));
    }

    /**
     * Register a dedicated runtime mailer and return its name.
     */
    public function configureSmtp(array $smtp): string
    {
        $encryption = strtolower((string) ($smtp['encryption'] ?? 'tls'));
        $scheme = $encryption === 'ssl' ? 'smtps' : 'smtp';
        $password = (string) ($smtp['password'] ?? '');
        if (str_contains(strtolower((string) ($smtp['host'] ?? '')), 'gmail.com')) {
            $password = preg_replace('/\s+/', '', $password) ?? $password;
        }

        config([
            'mail.mailers.admin_smtp' => [
                'transport' => 'smtp',
                'scheme' => $scheme,
                'host' => (string) ($smtp['host'] ?? ''),
                'port' => (int) ($smtp['port'] ?? 587),
                'username' => (string) ($smtp['username'] ?? ''),
                'password' => $password,
                'timeout' => 15,
                'auto_tls' => $encryption !== 'none',
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
            ],
            'mail.from.address' => (string) ($smtp['from_email'] ?? ''),
            'mail.from.name' => (string) ($smtp['from_name'] ?? config('app.name', 'Cửa hàng')),
        ]);

        // A long-running PHP worker may already have resolved this mailer using old values.
        app('mail.manager')->purge('admin_smtp');

        return 'admin_smtp';
    }

    private function raw(): array
    {
        $record = ProjectSetting::query()
            ->where('setting_key', self::SETTING_KEY)
            ->first();
        $value = $record?->setting_value;

        return is_array($value) ? $value : [];
    }

    private function merge(array $stored): array
    {
        return array_replace_recursive($this->defaults(), $stored);
    }

    private function encryptSecret(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return self::ENCRYPTED_PREFIX.Crypt::encryptString($value);
    }

    private function decryptSecret(string $value): string
    {
        if (! str_starts_with($value, self::ENCRYPTED_PREFIX)) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen(self::ENCRYPTED_PREFIX)));
        } catch (Throwable) {
            return '';
        }
    }

    private function hasSecretValue(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
}

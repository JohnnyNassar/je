<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureMailFromSettings();

        $prefix = trim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');

        if ($prefix === '') {
            return;
        }

        $prefix = '/' . $prefix;

        Livewire::setUpdateRoute(function ($handle) use ($prefix) {
            return Route::post($prefix . '/livewire/update', $handle);
        });

        Livewire::setScriptRoute(function ($handle) use ($prefix) {
            return Route::get($prefix . '/livewire/livewire.js', $handle);
        });
    }

    /**
     * Apply the admin-configured SMTP settings (Notifications → Email) over
     * Laravel's mail config at runtime, so the shop's email "just works" once
     * the owner pastes their relay credentials — no .env editing required.
     */
    private function configureMailFromSettings(): void
    {
        try {
            if (! filter_var(\App\Models\Setting::get('mail_enabled'), FILTER_VALIDATE_BOOLEAN)) {
                return;
            }

            $encryption = \App\Models\Setting::get('mail_encryption') ?: 'tls';

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => \App\Models\Setting::get('mail_host'),
                'mail.mailers.smtp.port' => (int) (\App\Models\Setting::get('mail_port') ?: 587),
                'mail.mailers.smtp.username' => \App\Models\Setting::get('mail_username'),
                'mail.mailers.smtp.password' => \App\Models\Setting::get('mail_password'),
                'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
            ]);

            if ($from = \App\Models\Setting::get('mail_from_address')) {
                config(['mail.from.address' => $from]);
            }
            if ($fromName = \App\Models\Setting::get('mail_from_name')) {
                config(['mail.from.name' => $fromName]);
            }
        } catch (\Throwable $e) {
            // Settings table not ready (e.g. before migrations) — fall back to .env mail config.
        }
    }
}

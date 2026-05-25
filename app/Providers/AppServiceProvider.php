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
        $this->registerActivityAuthLog();

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

    /**
     * Record sign-in events to the activity log: admin/staff on the web guard
     * (login/logout/failed) and customers on the storefront guard (login/logout).
     * Customer failed logins are intentionally not logged — a public storefront
     * attracts bot credential-stuffing that would flood the log.
     */
    private function registerActivityAuthLog(): void
    {
        $write = function (string $event, string $description, $user = null, array $properties = [], string $logName = 'auth'): void {
            try {
                \App\Models\ActivityLog::create([
                    'log_name' => $logName,
                    'event' => $event,
                    'description' => $description,
                    'causer_type' => $user?->getMorphClass(),
                    'causer_id' => $user?->getKey(),
                    'properties' => $properties ?: null,
                ]);
            } catch (\Throwable $e) {
                // Never block authentication on a logging failure.
            }
        };

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($e) use ($write) {
            if (($e->guard ?? null) === 'web') {
                $write('login', 'Logged in', $e->user);
            } elseif (($e->guard ?? null) === 'customer') {
                $write('login', 'Customer logged in', $e->user, [], 'customer-auth');
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, function ($e) use ($write) {
            if (($e->guard ?? null) === 'web') {
                $write('logout', 'Logged out', $e->user);
            } elseif (($e->guard ?? null) === 'customer') {
                $write('logout', 'Customer logged out', $e->user, [], 'customer-auth');
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class, function ($e) use ($write) {
            if (($e->guard ?? null) === 'web') {
                $write('failed_login', 'Failed login', null, ['email' => $e->credentials['email'] ?? null]);
            }
        });
    }
}

<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->colors([
                // Brand teal (matches the storefront brand palette / logo).
                'primary' => Color::hex('#287d88'),
            ])
            // Use the full screen width instead of Filament's default ~1280px cap.
            ->maxContentWidth(MaxWidth::Full)
            // In-app ("Dashboard") notification bell in the topbar.
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => '<script src="' . asset('js/media-picker.js') . '?v=' . filemtime(public_path('js/media-picker.js')) . '"></script>'
            )
            // Density pass — proportionally shrinks the whole back-office. Tweak the
            // root font-size knob in public/css/admin-density.css to adjust.
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="stylesheet" href="' . asset('css/admin-density.css') . '?v=' . filemtime(public_path('css/admin-density.css')) . '">'
            )
            // "Main" badge on the first product image so the cover is obvious.
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="stylesheet" href="' . asset('css/product-images.css') . '?v=' . filemtime(public_path('css/product-images.css')) . '">'
            )
            // Mirrored top horizontal scrollbar for wide list tables.
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_END,
                fn (): string => '<script src="' . asset('js/admin-table-scroll.js') . '?v=' . filemtime(public_path('js/admin-table-scroll.js')) . '"></script>'
            )
            // Live "Main" tag that follows the first product image as you reorder.
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_END,
                fn (): string => '<script src="' . asset('js/product-images-main.js') . '?v=' . filemtime(public_path('js/product-images-main.js')) . '"></script>'
            );
    }
}

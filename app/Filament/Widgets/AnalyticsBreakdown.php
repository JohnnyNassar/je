<?php

namespace App\Filament\Widgets;

use App\Services\GoogleAnalytics;
use Filament\Widgets\Widget;

/**
 * Where the traffic went and where it came from — the three lists that answer
 * "is the bazaar page pulling anyone in, and from where".
 */
class AnalyticsBreakdown extends Widget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.analytics-breakdown';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() && app(GoogleAnalytics::class)->isConfigured();
    }

    public function getViewData(): array
    {
        $ga = app(GoogleAnalytics::class);

        return [
            'pages' => $ga->topPages(28, 8),
            'sources' => $ga->topSources(28, 6),
            'countries' => $ga->topCountries(28, 6),
            'propertyUrl' => 'https://analytics.google.com/analytics/web/#/p' . $ga->propertyId() . '/reports/intelligenthome',
        ];
    }
}

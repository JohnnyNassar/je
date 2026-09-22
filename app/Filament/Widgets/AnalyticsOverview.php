<?php

namespace App\Filament\Widgets;

use App\Services\GoogleAnalytics;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Visitors, sessions and page views from GA4, next to the order stats rather
 * than in a separate console. Numbers are up to 15 minutes stale by design.
 */
class AnalyticsOverview extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() && app(GoogleAnalytics::class)->isConfigured();
    }

    protected function getStats(): array
    {
        $ga = app(GoogleAnalytics::class);
        $totals = $ga->totals(28);

        if ($totals === null) {
            return [
                Stat::make('Website traffic', 'Unavailable')
                    ->description('Could not reach Google Analytics — check the log')
                    ->color('gray'),
            ];
        }

        $daily = $ga->dailyUsers(28) ?? [];
        $chart = array_values($daily);

        return [
            $this->stat('Visitors', $totals['active_users'], $totals['previous']['active_users'], $chart),
            $this->stat('Sessions', $totals['sessions'], $totals['previous']['sessions']),
            $this->stat('Page views', $totals['page_views'], $totals['previous']['page_views']),
        ];
    }

    private function stat(string $label, int $now, int $before, array $chart = []): Stat
    {
        $stat = Stat::make($label, number_format($now))
            ->description($this->trend($now, $before))
            ->descriptionIcon($now >= $before ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($now >= $before ? 'success' : 'danger');

        // A flat line from a single data point looks like a bug, not a trend.
        if (count($chart) > 1) {
            $stat->chart($chart);
        }

        return $stat;
    }

    private function trend(int $now, int $before): string
    {
        if ($before === 0) {
            return 'Last 28 days';
        }

        $change = round((($now - $before) / $before) * 100);

        return sprintf('%s%d%% vs previous 28 days', $change >= 0 ? '+' : '', $change);
    }
}

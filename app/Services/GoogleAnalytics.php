<?php

namespace App\Services;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Reads the GA4 property through the Data API.
 *
 * Every call is cached, because the admin dashboard would otherwise hit Google
 * on each page load and burn the property's daily token quota. The numbers are
 * hours old by nature — nobody needs yesterday's traffic to the second.
 *
 * Failures never reach the page: a missing key file, a revoked grant or a
 * Google outage returns null and the widget says so, rather than 500-ing the
 * whole admin over a reporting panel.
 */
class GoogleAnalytics
{
    private const CACHE_MINUTES = 15;

    public function isConfigured(): bool
    {
        return $this->propertyId() !== ''
            && $this->credentialsPath() !== ''
            && is_readable($this->credentialsPath());
    }

    public function propertyId(): string
    {
        return trim((string) config('services.google_analytics.property_id'));
    }

    public function credentialsPath(): string
    {
        return trim((string) config('services.google_analytics.credentials'));
    }

    /**
     * Headline totals for a window, plus the window immediately before it so
     * the widget can show a trend instead of a bare number.
     */
    public function totals(int $days = 28): ?array
    {
        return $this->remember("totals:{$days}", function () use ($days) {
            $rows = $this->report(
                metrics: ['activeUsers', 'sessions', 'screenPageViews'],
                ranges: [
                    ['start_date' => $days . 'daysAgo', 'end_date' => 'today'],
                    ['start_date' => ($days * 2) . 'daysAgo', 'end_date' => ($days + 1) . 'daysAgo'],
                ],
            );

            $current = $rows[0] ?? [0, 0, 0];
            $previous = $rows[1] ?? [0, 0, 0];

            return [
                'active_users' => (int) $current[0],
                'sessions' => (int) $current[1],
                'page_views' => (int) $current[2],
                'previous' => [
                    'active_users' => (int) $previous[0],
                    'sessions' => (int) $previous[1],
                    'page_views' => (int) $previous[2],
                ],
            ];
        });
    }

    /**
     * Active users per day, oldest first, for the sparkline.
     *
     * @return array<string, int>|null date (Y-m-d) => users
     */
    public function dailyUsers(int $days = 28): ?array
    {
        return $this->remember("daily:{$days}", function () use ($days) {
            $rows = $this->report(
                metrics: ['activeUsers'],
                ranges: [['start_date' => $days . 'daysAgo', 'end_date' => 'today']],
                dimensions: ['date'],
                orderByDimension: 'date',
            );

            $out = [];
            foreach ($rows as $row) {
                // GA hands dates back as 20260922.
                $raw = (string) ($row['_dimensions'][0] ?? '');
                if (strlen($raw) === 8) {
                    $key = substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2);
                    $out[$key] = (int) $row[0];
                }
            }

            return $out;
        });
    }

    public function topPages(int $days = 28, int $limit = 8): ?array
    {
        return $this->breakdown("pages:{$days}:{$limit}", 'pagePath', 'screenPageViews', $days, $limit);
    }

    public function topSources(int $days = 28, int $limit = 6): ?array
    {
        return $this->breakdown("sources:{$days}:{$limit}", 'sessionSource', 'sessions', $days, $limit);
    }

    public function topCountries(int $days = 28, int $limit = 6): ?array
    {
        return $this->breakdown("countries:{$days}:{$limit}", 'country', 'activeUsers', $days, $limit);
    }

    /** Drop the cached reports, so the next dashboard load refetches. */
    public function forget(int $days = 28): void
    {
        $keys = [
            "totals:{$days}",
            "daily:{$days}",
            "pages:{$days}:8",
            "sources:{$days}:6",
            "countries:{$days}:6",
        ];

        foreach ($keys as $key) {
            Cache::forget($this->cachePrefix() . ':' . $key);
        }
    }

    /**
     * @return array<int, array{label: string, value: int}>|null
     */
    private function breakdown(string $cacheKey, string $dimension, string $metric, int $days, int $limit): ?array
    {
        return $this->remember($cacheKey, function () use ($dimension, $metric, $days, $limit) {
            $rows = $this->report(
                metrics: [$metric],
                ranges: [['start_date' => $days . 'daysAgo', 'end_date' => 'today']],
                dimensions: [$dimension],
                orderByMetric: $metric,
                limit: $limit,
            );

            return array_map(fn (array $row) => [
                'label' => (string) ($row['_dimensions'][0] ?? '—'),
                'value' => (int) $row[0],
            ], $rows);
        });
    }

    /**
     * One runReport call, flattened to plain arrays so nothing downstream has
     * to know about protobuf objects.
     */
    private function report(
        array $metrics,
        array $ranges,
        array $dimensions = [],
        ?string $orderByMetric = null,
        ?string $orderByDimension = null,
        ?int $limit = null,
    ): array {
        $request = (new RunReportRequest())
            ->setProperty('properties/' . $this->propertyId())
            ->setDateRanges(array_map(fn (array $range) => new DateRange($range), $ranges))
            ->setMetrics(array_map(fn (string $name) => new Metric(['name' => $name]), $metrics));

        if ($dimensions !== []) {
            $request->setDimensions(array_map(fn (string $name) => new Dimension(['name' => $name]), $dimensions));
        }

        if ($orderByMetric !== null) {
            $request->setOrderBys([new OrderBy([
                'metric' => new MetricOrderBy(['metric_name' => $orderByMetric]),
                'desc' => true,
            ])]);
        }

        if ($orderByDimension !== null) {
            $request->setOrderBys([new OrderBy([
                'dimension' => new DimensionOrderBy(['dimension_name' => $orderByDimension]),
                'desc' => false,
            ])]);
        }

        if ($limit !== null) {
            $request->setLimit($limit);
        }

        $response = $this->client()->runReport($request);

        $out = [];
        foreach ($response->getRows() as $row) {
            $flat = [];
            foreach ($row->getMetricValues() as $index => $value) {
                $flat[$index] = $value->getValue();
            }

            $dimensionValues = [];
            foreach ($row->getDimensionValues() as $value) {
                $dimensionValues[] = $value->getValue();
            }
            $flat['_dimensions'] = $dimensionValues;

            $out[] = $flat;
        }

        return $out;
    }

    private function client(): BetaAnalyticsDataClient
    {
        return new BetaAnalyticsDataClient(['credentials' => $this->credentialsPath()]);
    }

    /**
     * Cache the callback, and turn any failure into null so a reporting panel
     * can never take the admin down with it.
     */
    private function remember(string $key, callable $callback): mixed
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return Cache::remember(
            $this->cachePrefix() . ':' . $key,
            now()->addMinutes(self::CACHE_MINUTES),
            function () use ($callback) {
                try {
                    return $callback();
                } catch (\Throwable $e) {
                    Log::warning('Google Analytics report failed: ' . $e->getMessage());

                    return null;
                }
            }
        );
    }

    private function cachePrefix(): string
    {
        return 'ga:' . $this->propertyId();
    }
}

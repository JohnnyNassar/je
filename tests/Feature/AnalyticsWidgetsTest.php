<?php

namespace Tests\Feature;

use App\Filament\Widgets\AnalyticsBreakdown;
use App\Filament\Widgets\AnalyticsOverview;
use App\Models\User;
use App\Services\GoogleAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The analytics widgets talk to Google, so what matters here is that they stay
 * out of the way when they can't: no credentials, a bad path, or a non-admin
 * looking. A reporting panel must never be able to break the dashboard.
 */
class AnalyticsWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private function configure(?string $property, ?string $credentials): void
    {
        config([
            'services.google_analytics.property_id' => $property,
            'services.google_analytics.credentials' => $credentials,
        ]);
    }

    public function test_it_is_not_configured_without_a_property_id(): void
    {
        $this->configure(null, __FILE__);

        $this->assertFalse(app(GoogleAnalytics::class)->isConfigured());
    }

    public function test_it_is_not_configured_when_the_key_file_is_missing(): void
    {
        // The likeliest production failure: the path is set but the file was
        // never copied across, or a deploy landed on a fresh server.
        $this->configure('538833054', '/etc/joreption/does-not-exist.json');

        $this->assertFalse(app(GoogleAnalytics::class)->isConfigured());
    }

    public function test_it_is_configured_when_both_are_present(): void
    {
        $this->configure('538833054', __FILE__);

        $this->assertTrue(app(GoogleAnalytics::class)->isConfigured());
    }

    public function test_reports_return_null_rather_than_calling_google_when_unconfigured(): void
    {
        $this->configure(null, null);

        $ga = app(GoogleAnalytics::class);

        $this->assertNull($ga->totals());
        $this->assertNull($ga->dailyUsers());
        $this->assertNull($ga->topPages());
        $this->assertNull($ga->topSources());
        $this->assertNull($ga->topCountries());
    }

    public function test_the_widgets_hide_themselves_when_analytics_is_not_set_up(): void
    {
        $this->configure(null, null);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));

        $this->assertFalse(AnalyticsOverview::canView());
        $this->assertFalse(AnalyticsBreakdown::canView());
    }

    public function test_the_widgets_stay_hidden_from_non_admin_staff(): void
    {
        $this->configure('538833054', __FILE__);
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $this->assertFalse(AnalyticsOverview::canView());
        $this->assertFalse(AnalyticsBreakdown::canView());
    }

    public function test_the_dashboard_still_renders_with_analytics_switched_off(): void
    {
        $this->configure(null, null);

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get('/admin')
            ->assertOk();
    }
}

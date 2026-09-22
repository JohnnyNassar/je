<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Filament\Pages\Settings as SettingsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Each part of the hero banner has its own switch in /admin/settings, so the
 * owner can strip the banner back without clearing the words they may want
 * again later.
 */
class HeroBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('coming_soon_enabled', 'false');

        Product::create([
            'name_en' => 'A product', 'name_ar' => 'منتج',
            'price' => 10, 'stock' => 1, 'is_active' => true,
        ]);
    }

    private function shop(): string
    {
        return $this->get('/')->assertOk()->getContent();
    }

    private function hero(): string
    {
        preg_match('#<section class="relative overflow-hidden rounded-2xl.*?</section>#s', $this->shop(), $m);

        return $m[0] ?? '';
    }

    public function test_every_part_is_on_by_default(): void
    {
        $hero = $this->hero();

        $this->assertNotSame('', $hero, 'the banner renders');
        $this->assertStringContainsString('JorEption', $hero);
        $this->assertStringContainsString('Quality finds at garage-sale prices.', $hero);
        $this->assertStringContainsString('Browse Catalog', $hero);
        $this->assertStringContainsString('Deals', $hero);
        $this->assertStringContainsString('Cash on Delivery', $hero);
    }

    public function test_turning_the_banner_off_removes_the_whole_section(): void
    {
        Setting::set('hero_enabled', 'false');

        $this->assertSame('', $this->hero(), 'no banner markup at all');

        // The rest of the page must still be there.
        $this->assertStringContainsString('A product', $this->shop());
    }

    public function test_each_part_can_be_switched_off_on_its_own(): void
    {
        $cases = [
            'hero_headline_enabled' => 'JorEption',
            'hero_tagline_enabled' => 'Quality finds at garage-sale prices.',
            'hero_cta_enabled' => 'Browse Catalog',
            'hero_pill_deals_enabled' => 'Deals',
            'hero_pill_cod_enabled' => 'Cash on Delivery',
        ];

        foreach ($cases as $key => $text) {
            Setting::set($key, 'false');
            $this->assertStringNotContainsString($text, $this->hero(), "{$key} should hide “{$text}”");

            // Back on, and the banner is whole again — the words were never lost.
            Setting::set($key, 'true');
            $this->assertStringContainsString($text, $this->hero(), "{$key} should restore “{$text}”");
        }
    }

    public function test_the_pill_row_disappears_when_both_pills_are_off(): void
    {
        Setting::set('hero_pill_deals_enabled', 'false');
        Setting::set('hero_pill_cod_enabled', 'false');

        // No empty flex row left behind above the headline.
        $this->assertStringNotContainsString('flex items-center gap-2 mb-5', $this->hero());
    }

    public function test_switching_a_part_off_does_not_erase_its_wording(): void
    {
        Setting::set('hero_tagline_en', 'Everything must go.');
        Setting::set('hero_tagline_enabled', 'false');

        $this->assertStringNotContainsString('Everything must go.', $this->hero());
        $this->assertSame('Everything must go.', Setting::get('hero_tagline_en'));

        Setting::set('hero_tagline_enabled', 'true');
        $this->assertStringContainsString('Everything must go.', $this->hero());
    }

    public function test_a_switched_on_part_with_no_text_still_stays_hidden(): void
    {
        Setting::set('hero_tagline_enabled', 'true');
        Setting::set('hero_tagline_en', '');

        $this->assertStringNotContainsString('Quality finds at garage-sale prices.', $this->hero());
    }

    public function test_the_settings_page_shows_a_switch_for_each_part(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->get('/admin/settings')
            ->assertOk()
            ->assertSee('Show the hero banner')
            ->assertSee('Show the headline')
            ->assertSee('Show the tagline')
            ->assertSee('Show the button')
            ->assertSee('Deals” pill', false)
            ->assertSee('Cash on Delivery” pill', false);
    }

    public function test_saving_the_settings_page_persists_the_switches(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        // Save turns the form's booleans into the strings the settings table
        // stores, which is where an off switch could quietly come back on.
        // Drive the real page rather than trusting Setting::set().
        Livewire::actingAs($admin)
            ->test(SettingsPage::class)
            ->fillForm([
                'hero_tagline_enabled' => false,
                'hero_pill_deals_enabled' => false,
                'hero_cta_label_en' => 'Shop the sale',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse(Setting::enabled('hero_tagline_enabled'));
        $this->assertFalse(Setting::enabled('hero_pill_deals_enabled'));
        $this->assertTrue(Setting::enabled('hero_headline_enabled'), 'untouched switches stay on');
        $this->assertSame('Shop the sale', Setting::get('hero_cta_label_en'));

        $hero = $this->hero();
        $this->assertStringNotContainsString('Quality finds at garage-sale prices.', $hero);
        $this->assertStringContainsString('Shop the sale', $hero);
    }
}

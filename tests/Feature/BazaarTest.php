<?php

namespace Tests\Feature;

use App\Models\BazaarBooking;
use App\Models\BazaarBookingDocument;
use App\Models\BazaarPeriod;
use App\Models\BazaarTable;
use App\Models\BazaarVendorCategory;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\BazaarSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BazaarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The season layout is fixed data the whole feature is built on, so
        // every test starts from the real seeded floor plan.
        $this->seed(BazaarSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function period(int $skip = 0): BazaarPeriod
    {
        return BazaarPeriod::orderBy('starts_on')->skip($skip)->first();
    }

    private function table(): BazaarTable
    {
        return BazaarTable::bookable()->orderBy('number')->first();
    }

    private function plainCategory(): BazaarVendorCategory
    {
        return BazaarVendorCategory::where('requires_health_certificate', false)->first();
    }

    private function foodCategory(): BazaarVendorCategory
    {
        return BazaarVendorCategory::where('requires_health_certificate', true)->first();
    }

    // ------------------------------------------------------------- seeding

    public function test_the_season_is_seeded_as_weekends(): void
    {
        $this->assertSame(15, BazaarPeriod::count(), '15 bookable weekends');
        $this->assertSame(30, \App\Models\BazaarNight::count(), '30 trading nights');
        $this->assertSame(30, \App\Models\BazaarNight::whereNotNull('bazaar_period_id')->count(),
            'every night belongs to a weekend');
        $this->assertSame(100, BazaarTable::count());
        $this->assertSame(88, BazaarTable::bookable()->count());
    }

    public function test_each_weekend_is_a_thursday_and_the_friday_after_it(): void
    {
        foreach (BazaarPeriod::with('nights')->get() as $period) {
            $this->assertTrue($period->starts_on->isThursday(), 'weekend starts on a Thursday');
            $this->assertTrue($period->ends_on->isFriday(), 'weekend ends on a Friday');
            $this->assertSame(1, (int) $period->starts_on->diffInDays($period->ends_on),
                'the Friday is the day after the Thursday');
            $this->assertCount(2, $period->nights, 'a weekend holds exactly two nights');
        }
    }

    /** Contract Art. 21: both nights open at 18:00 and run to midnight. */
    public function test_both_nights_open_at_six_pm(): void
    {
        foreach (\App\Models\BazaarNight::all() as $night) {
            $this->assertSame('18:00', $night->starts_at->format('H:i'),
                "{$night->event_date->toDateString()} should open at 18:00");
            $this->assertSame('00:00', $night->ends_at->format('H:i'));
            $this->assertTrue($night->ends_at->isAfter($night->starts_at));
        }
    }

    public function test_section_counts_match_the_architects_legend(): void
    {
        $bySection = BazaarTable::selectRaw('section, count(*) c')->groupBy('section')
            ->pluck('c', 'section')->all();

        $this->assertSame(
            ['A' => 14, 'B' => 12, 'C' => 56, 'D' => 6, 'RESTAURANT' => 12],
            array_map('intval', array_intersect_key($bySection, array_flip(['A', 'B', 'C', 'D', 'RESTAURANT'])))
        );
    }

    public function test_vendor_categories_are_seeded_with_health_flags(): void
    {
        $this->assertGreaterThan(0, BazaarVendorCategory::count());
        $this->assertTrue(BazaarVendorCategory::where('slug', 'food')->value('requires_health_certificate'));
        $this->assertFalse((bool) BazaarVendorCategory::where('slug', 'clothing')->value('requires_health_certificate'));
    }

    // ------------------------------------------------------- booking rules

    public function test_a_table_cannot_be_double_booked_in_the_same_weekend(): void
    {
        $this->makeBooking($this->period(), $this->table());

        $this->expectException(QueryException::class);
        $this->makeBooking($this->period(), $this->table());
    }

    public function test_the_same_table_can_be_booked_in_a_different_weekend(): void
    {
        $this->makeBooking($this->period(0), $this->table());
        $second = $this->makeBooking($this->period(1), $this->table());

        $this->assertNotNull($second->id);
    }

    public function test_cancelling_releases_the_table_for_rebooking(): void
    {
        $first = $this->makeBooking($this->period(), $this->table());
        $first->update(['status' => BazaarBooking::STATUS_CANCELLED]);

        $this->assertNull($first->fresh()->active_slot);
        $this->assertNotNull($this->makeBooking($this->period(), $this->table())->id);
        $this->assertDatabaseHas('bazaar_bookings', [
            'id' => $first->id,
            'status' => BazaarBooking::STATUS_CANCELLED,
        ]);
    }

    public function test_availability_is_counted_per_weekend(): void
    {
        $period = $this->period();
        $bookable = BazaarTable::bookable()->count();

        $this->assertSame($bookable, $period->availableCount());

        $booking = $this->makeBooking($period, $this->table());
        $this->assertSame($bookable - 1, $period->fresh()->availableCount());

        $booking->update(['status' => BazaarBooking::STATUS_CANCELLED]);
        $this->assertSame($bookable, $period->fresh()->availableCount());
    }

    public function test_vendor_phone_numbers_normalise_to_a_whatsapp_link(): void
    {
        foreach ([
            '0790000000' => 'https://wa.me/962790000000',
            '+962 79 000 0000' => 'https://wa.me/962790000000',
            '00962790000000' => 'https://wa.me/962790000000',
            '790000000' => 'https://wa.me/962790000000',
        ] as $input => $expected) {
            $this->assertSame($expected, (new BazaarBooking(['vendor_phone' => $input]))->whatsapp_url, "input: {$input}");
        }

        $this->assertNull((new BazaarBooking(['vendor_phone' => '']))->whatsapp_url);
    }

    // ------------------------------------------------------------- pricing

    public function test_one_fee_covers_the_whole_weekend_plus_a_deposit(): void
    {
        $response = $this->bookAs($this->plainCategory());
        $response->assertRedirect();

        $booking = BazaarBooking::latest('id')->first();

        // 30 JOD buys both nights together -- not 30 per night.
        $this->assertEquals(30, $booking->price);
        $this->assertEquals(10, $booking->deposit);
        $this->assertEquals(40, $booking->total_due);
        $this->assertCount(2, $booking->period->nights, 'and it covers two nights');
    }

    public function test_the_deposit_comes_from_settings_not_the_request(): void
    {
        Setting::set('bazaar_deposit', '15');

        $this->bookAs($this->plainCategory(), ['deposit' => 999]);

        $this->assertEquals(15, BazaarBooking::latest('id')->first()->deposit);
    }

    public function test_the_price_comes_from_the_table_not_the_request(): void
    {
        $this->bookAs($this->plainCategory(), ['price' => 1]);

        $this->assertEquals($this->table()->price, BazaarBooking::latest('id')->first()->price);
    }

    // ------------------------------------------------ certificates & rules

    public function test_a_food_vendor_cannot_book_without_a_health_certificate(): void
    {
        $this->bookAs($this->foodCategory())
            ->assertSessionHasErrors('health_certificate');

        $this->assertSame(0, BazaarBooking::count());
    }

    public function test_a_food_vendor_can_book_when_the_certificate_is_attached(): void
    {
        Storage::fake('local');

        $this->bookAs($this->foodCategory(), [
            'health_certificate' => UploadedFile::fake()->create('health.pdf', 200, 'application/pdf'),
        ])->assertRedirect();

        $booking = BazaarBooking::latest('id')->first();
        $document = $booking->documentOf(BazaarBookingDocument::KIND_HEALTH);

        $this->assertNotNull($document);
        $this->assertFalse($booking->isMissingHealthCertificate());
        Storage::disk('local')->assertExists($document->path);

        // Certificates must never land anywhere the web server can serve.
        $this->assertStringStartsWith(BazaarBookingDocument::DIRECTORY, $document->path);
        $this->assertStringNotContainsString('public', $document->path);
    }

    public function test_a_non_food_vendor_may_attach_a_work_licence(): void
    {
        Storage::fake('local');

        $this->bookAs($this->plainCategory(), [
            'work_certificate' => UploadedFile::fake()->image('licence.jpg'),
        ])->assertRedirect();

        $booking = BazaarBooking::latest('id')->first();
        $this->assertNotNull($booking->documentOf(BazaarBookingDocument::KIND_WORK));
        $this->assertFalse($booking->isMissingHealthCertificate(), 'no health cert needed for this category');
    }

    public function test_executable_uploads_are_rejected(): void
    {
        Storage::fake('local');

        $this->bookAs($this->plainCategory(), [
            'work_certificate' => UploadedFile::fake()->create('payload.php', 10, 'application/x-httpd-php'),
        ])->assertSessionHasErrors('work_certificate');

        $this->assertSame(0, BazaarBooking::count());
    }

    public function test_a_booking_requires_a_category(): void
    {
        $this->post('/bazar/book', [
            'bazaar_period_id' => $this->period()->id,
            'bazaar_table_id' => $this->table()->id,
            'vendor_name' => 'No Category',
            'vendor_phone' => '0791234567',
        ])->assertSessionHasErrors('bazaar_vendor_category_id');
    }

    public function test_certificates_are_only_downloadable_by_staff(): void
    {
        Storage::fake('local');

        $this->bookAs($this->foodCategory(), [
            'health_certificate' => UploadedFile::fake()->create('health.pdf', 100, 'application/pdf'),
        ]);

        $document = BazaarBookingDocument::latest('id')->first();

        // A guest -- even the vendor who uploaded it -- gets nothing.
        $this->get(route('admin.bazaar.document', $document))->assertRedirect();

        $this->actingAs($this->admin())
            ->get(route('admin.bazaar.document', $document))
            ->assertOk()
            ->assertDownload('health.pdf');
    }

    public function test_deleting_a_booking_removes_its_uploaded_files(): void
    {
        Storage::fake('local');

        $this->bookAs($this->foodCategory(), [
            'health_certificate' => UploadedFile::fake()->create('health.pdf', 100, 'application/pdf'),
        ]);

        $booking = BazaarBooking::latest('id')->first();
        $path = $booking->documentOf(BazaarBookingDocument::KIND_HEALTH)->path;
        Storage::disk('local')->assertExists($path);

        $booking->documents->each->delete();
        Storage::disk('local')->assertMissing($path);
    }

    // ---------------------------------------------------------- public web

    public function test_the_public_page_renders_weekends(): void
    {
        $response = $this->get('/bazar')->assertOk();

        $response->assertSee('Book your table');
        $response->assertSee('Bazaar floor plan');
        $response->assertSee('All bazaar weekends');
        $response->assertSee('What do you sell?');
        // The old per-night wording must be gone.
        $response->assertDontSee('Per table, per night');
    }

    public function test_the_page_shows_the_fee_and_the_deposit(): void
    {
        $response = $this->get('/bazar')->assertOk();

        $response->assertSee('Refundable deposit');
        $response->assertSee('Due on the night');
    }

    public function test_the_bazaar_page_is_reachable_while_coming_soon_is_on(): void
    {
        Setting::set('coming_soon_enabled', 'true');

        $this->get('/bazar')->assertOk()->assertSee('Book your table');
        $this->get('/')->assertOk()->assertDontSee('Book your table');

        Setting::set('coming_soon_enabled', 'false');
    }

    public function test_the_bazaar_page_renders_in_arabic(): void
    {
        $response = $this->get('/bazar?lang=ar')->assertOk();

        $response->assertSee('dir="rtl"', false);
        $response->assertSee('احجز طاولتك', false);
        $response->assertSee('نهاية الأسبوع', false);
        $response->assertDontSee('Book your table');
        $response->assertDontSee('What do you sell?');
    }

    public function test_the_bazaar_spelling_redirects(): void
    {
        $this->get('/bazaar')->assertRedirect('/bazar');
    }

    public function test_booking_a_taken_table_is_rejected(): void
    {
        $this->makeBooking($this->period(), $this->table());

        $this->bookAs($this->plainCategory())->assertSessionHasErrors('bazaar_table_id');

        $this->assertSame(1, BazaarBooking::where('bazaar_table_id', $this->table()->id)->count());
    }

    public function test_restaurant_units_cannot_be_booked(): void
    {
        $restaurant = BazaarTable::where('section', BazaarTable::SECTION_RESTAURANT)->first();

        $this->bookAs($this->plainCategory(), ['bazaar_table_id' => $restaurant->id])
            ->assertSessionHasErrors('bazaar_table_id');

        $this->assertSame(0, BazaarBooking::count());
    }

    public function test_a_closed_weekend_is_rejected(): void
    {
        $period = $this->period();
        $period->update(['is_active' => false]);

        $this->bookAs($this->plainCategory())->assertSessionHasErrors('bazaar_period_id');
    }

    public function test_booking_requires_a_name_and_a_usable_phone(): void
    {
        $this->bookAs($this->plainCategory(), ['vendor_name' => '', 'vendor_phone' => '123'])
            ->assertSessionHasErrors(['vendor_name', 'vendor_phone']);
    }

    public function test_a_confirmation_page_is_private_to_the_person_who_booked(): void
    {
        $this->bookAs($this->plainCategory());
        $booking = BazaarBooking::latest('id')->first();

        $this->get(route('bazaar.confirmation', $booking))->assertOk()->assertSee('Table requested');

        $this->flushSession();
        $this->get(route('bazaar.confirmation', $booking))->assertNotFound();

        $this->actingAs($this->admin())
            ->get(route('bazaar.confirmation', $booking))
            ->assertOk();
    }

    // --------------------------------------------------------------- admin

    /** @dataProvider adminPages */
    public function test_admin_pages_render(string $path): void
    {
        $this->actingAs($this->admin())->get($path)->assertOk();
    }

    public static function adminPages(): array
    {
        return [
            'bookings' => ['/admin/bazaar-bookings'],
            'bookings create' => ['/admin/bazaar-bookings/create'],
            'weekends' => ['/admin/bazaar-periods'],
            'weekends create' => ['/admin/bazaar-periods/create'],
            'nights' => ['/admin/bazaar-nights'],
            'categories' => ['/admin/bazaar-vendor-categories'],
            'categories create' => ['/admin/bazaar-vendor-categories/create'],
            'tables' => ['/admin/bazaar-tables'],
        ];
    }

    public function test_admin_booking_edit_page_renders(): void
    {
        $booking = $this->makeBooking($this->period(), $this->table());

        $this->actingAs($this->admin())
            ->get("/admin/bazaar-bookings/{$booking->id}/edit")
            ->assertOk();
    }

    public function test_the_bazaar_admin_is_closed_to_guests(): void
    {
        $this->get('/admin/bazaar-bookings')->assertRedirect();
        $this->get('/admin/bazaar-periods')->assertRedirect();
    }

    // ------------------------------------------------------------- helpers

    private function makeBooking(BazaarPeriod $period, BazaarTable $table, array $overrides = []): BazaarBooking
    {
        return BazaarBooking::create(array_merge([
            'bazaar_period_id' => $period->id,
            'bazaar_table_id' => $table->id,
            'bazaar_vendor_category_id' => $this->plainCategory()->id,
            'vendor_name' => 'Test Vendor',
            'vendor_phone' => '0790000000',
            'price' => $table->price,
            'deposit' => 10,
        ], $overrides));
    }

    /** POSTs the public booking form with sensible defaults. */
    private function bookAs(BazaarVendorCategory $category, array $overrides = [])
    {
        return $this->post('/bazar/book', array_merge([
            'bazaar_period_id' => $this->period()->id,
            'bazaar_table_id' => $this->table()->id,
            'bazaar_vendor_category_id' => $category->id,
            'vendor_name' => 'Rana',
            'vendor_phone' => '0791234567',
            'vendor_business' => 'Rana Vintage',
            'goods_description' => 'Clothes and bags',
        ], $overrides));
    }
}

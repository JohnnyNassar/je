<?php

namespace Tests\Feature;

use App\Models\BazaarBooking;
use App\Models\BazaarNight;
use App\Models\BazaarTable;
use App\Models\User;
use Database\Seeders\BazaarSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_the_season_is_seeded_as_expected(): void
    {
        $this->assertSame(30, BazaarNight::count(), '30 trading nights');
        $this->assertSame(100, BazaarTable::count(), '100 tables on the plan');
        $this->assertSame(88, BazaarTable::bookable()->count(), '88 bookable (restaurants excluded)');

        // Section totals must match the architect's legend.
        $bySection = BazaarTable::selectRaw('section, count(*) c')
            ->groupBy('section')
            ->pluck('c', 'section')
            ->all();

        $this->assertSame(
            ['A' => 14, 'B' => 12, 'C' => 56, 'D' => 6, 'RESTAURANT' => 12],
            array_map('intval', array_intersect_key($bySection, array_flip(['A', 'B', 'C', 'D', 'RESTAURANT'])))
        );
    }

    public function test_every_night_is_a_thursday_or_friday(): void
    {
        foreach (BazaarNight::all() as $night) {
            $this->assertTrue(
                $night->event_date->isThursday() || $night->event_date->isFriday(),
                "{$night->event_date->toDateString()} is not a Thursday or Friday"
            );
        }
    }

    public function test_a_table_cannot_be_double_booked_on_the_same_night(): void
    {
        $night = BazaarNight::orderBy('event_date')->first();
        $table = BazaarTable::bookable()->orderBy('number')->first();

        $first = $this->makeBooking($night, $table);
        $this->assertSame(BazaarBooking::STATUS_PENDING, $first->status);

        $this->expectException(QueryException::class);
        $this->makeBooking($night, $table);
    }

    public function test_cancelling_releases_the_table_for_rebooking(): void
    {
        $night = BazaarNight::orderBy('event_date')->first();
        $table = BazaarTable::bookable()->orderBy('number')->first();

        $first = $this->makeBooking($night, $table);
        $first->update(['status' => BazaarBooking::STATUS_CANCELLED]);

        $this->assertNull($first->fresh()->active_slot, 'cancelling frees the slot');

        $second = $this->makeBooking($night, $table);
        $this->assertNotNull($second->id, 'the table can be booked again');

        // The cancelled booking is kept for history rather than deleted.
        $this->assertDatabaseHas('bazaar_bookings', [
            'id' => $first->id,
            'status' => BazaarBooking::STATUS_CANCELLED,
        ]);
    }

    public function test_the_same_table_can_be_booked_on_different_nights(): void
    {
        $nights = BazaarNight::orderBy('event_date')->take(2)->get();
        $table = BazaarTable::bookable()->orderBy('number')->first();

        $this->makeBooking($nights[0], $table);
        $second = $this->makeBooking($nights[1], $table);

        $this->assertNotNull($second->id);
    }

    public function test_availability_reflects_active_bookings_only(): void
    {
        $night = BazaarNight::orderBy('event_date')->first();
        $table = BazaarTable::bookable()->orderBy('number')->first();
        $bookable = BazaarTable::bookable()->count();

        $this->assertSame($bookable, $night->availableCount());

        $booking = $this->makeBooking($night, $table);
        $this->assertSame($bookable - 1, $night->fresh()->availableCount());

        $booking->update(['status' => BazaarBooking::STATUS_CANCELLED]);
        $this->assertSame($bookable, $night->fresh()->availableCount());
    }

    public function test_vendor_phone_numbers_normalise_to_a_whatsapp_link(): void
    {
        $cases = [
            '0790000000' => 'https://wa.me/962790000000',
            '+962 79 000 0000' => 'https://wa.me/962790000000',
            '00962790000000' => 'https://wa.me/962790000000',
            '790000000' => 'https://wa.me/962790000000',
        ];

        foreach ($cases as $input => $expected) {
            $booking = new BazaarBooking(['vendor_phone' => $input]);
            $this->assertSame($expected, $booking->whatsapp_url, "input: {$input}");
        }

        $this->assertNull((new BazaarBooking(['vendor_phone' => '']))->whatsapp_url);
    }

    /** @dataProvider adminPages */
    public function test_admin_pages_render(string $path): void
    {
        $this->actingAs($this->admin())
            ->get($path)
            ->assertOk();
    }

    public static function adminPages(): array
    {
        return [
            'bookings list' => ['/admin/bazaar-bookings'],
            'bookings create' => ['/admin/bazaar-bookings/create'],
            'nights list' => ['/admin/bazaar-nights'],
            'nights create' => ['/admin/bazaar-nights/create'],
            'tables list' => ['/admin/bazaar-tables'],
            'tables create' => ['/admin/bazaar-tables/create'],
        ];
    }

    public function test_admin_booking_edit_page_renders(): void
    {
        $night = BazaarNight::orderBy('event_date')->first();
        $table = BazaarTable::bookable()->orderBy('number')->first();
        $booking = $this->makeBooking($night, $table);

        $this->actingAs($this->admin())
            ->get("/admin/bazaar-bookings/{$booking->id}/edit")
            ->assertOk();
    }

    public function test_the_bazaar_admin_is_closed_to_guests(): void
    {
        $this->get('/admin/bazaar-bookings')->assertRedirect();
    }

    // ---------------------------------------------------------------- public

    public function test_the_public_bazaar_page_renders_with_the_floor_plan(): void
    {
        $response = $this->get('/bazar')->assertOk();

        $response->assertSee('JorEption Bazar');
        $response->assertSee('Book your table');
        // Every table on the plan is drawn, restaurants included.
        $response->assertSee('Bazaar floor plan');
    }

    public function test_the_bazaar_page_is_reachable_while_coming_soon_is_on(): void
    {
        \App\Models\Setting::set('coming_soon_enabled', 'true');

        $this->get('/bazar')->assertOk()->assertSee('Book your table');
        // ...while the shop itself is still behind the splash.
        $this->get('/')->assertOk()->assertDontSee('Book your table');

        \App\Models\Setting::set('coming_soon_enabled', 'false');
    }

    public function test_the_bazaar_page_renders_in_arabic(): void
    {
        $response = $this->get('/bazar?lang=ar')->assertOk();

        $response->assertSee('dir="rtl"', false);
        $response->assertSee('احجز طاولتك', false);
        $response->assertSee('عمّان — الدوار الخامس', false);

        // The English source strings must not leak through untranslated.
        $response->assertDontSee('Book your table');
        $response->assertDontSee('Request this table');
    }

    public function test_the_bazaar_spelling_redirects(): void
    {
        $this->get('/bazaar')->assertRedirect('/bazar');
    }

    public function test_a_vendor_can_book_a_table(): void
    {
        $night = BazaarNight::orderBy('event_date')->first();
        $table = BazaarTable::bookable()->orderBy('number')->first();

        $response = $this->post('/bazar/book', [
            'bazaar_night_id' => $night->id,
            'bazaar_table_id' => $table->id,
            'vendor_name' => 'Rana',
            'vendor_phone' => '0791234567',
            'vendor_business' => 'Rana Vintage',
            'goods_description' => 'Clothes and bags',
        ]);

        $booking = BazaarBooking::latest('id')->first();

        $response->assertRedirect(route('bazaar.confirmation', $booking));

        $this->assertDatabaseHas('bazaar_bookings', [
            'bazaar_night_id' => $night->id,
            'bazaar_table_id' => $table->id,
            'vendor_name' => 'Rana',
            'status' => BazaarBooking::STATUS_PENDING,
        ]);

        // Vendors are linked to a customer record by phone.
        $this->assertDatabaseHas('customers', ['phone' => '0791234567']);

        // ...and the booking is priced from the table, not the request.
        $this->assertEquals($table->price, $booking->price);
    }

    public function test_booking_an_already_taken_table_is_rejected(): void
    {
        $night = BazaarNight::orderBy('event_date')->first();
        $table = BazaarTable::bookable()->orderBy('number')->first();

        $this->makeBooking($night, $table);

        $this->post('/bazar/book', [
            'bazaar_night_id' => $night->id,
            'bazaar_table_id' => $table->id,
            'vendor_name' => 'Second Vendor',
            'vendor_phone' => '0799999999',
        ])->assertSessionHasErrors('bazaar_table_id');

        $this->assertSame(1, BazaarBooking::where('bazaar_table_id', $table->id)->count());
    }

    public function test_restaurant_units_cannot_be_booked_by_vendors(): void
    {
        $night = BazaarNight::orderBy('event_date')->first();
        $restaurant = BazaarTable::where('section', BazaarTable::SECTION_RESTAURANT)->first();

        $this->post('/bazar/book', [
            'bazaar_night_id' => $night->id,
            'bazaar_table_id' => $restaurant->id,
            'vendor_name' => 'Chancer',
            'vendor_phone' => '0790000000',
        ])->assertSessionHasErrors('bazaar_table_id');

        $this->assertSame(0, BazaarBooking::count());
    }

    public function test_a_booking_is_rejected_for_a_night_that_has_finished(): void
    {
        $night = BazaarNight::orderBy('event_date')->first();
        $night->update(['is_active' => false]);
        $table = BazaarTable::bookable()->orderBy('number')->first();

        $this->post('/bazar/book', [
            'bazaar_night_id' => $night->id,
            'bazaar_table_id' => $table->id,
            'vendor_name' => 'Too Late',
            'vendor_phone' => '0790000000',
        ])->assertSessionHasErrors('bazaar_night_id');
    }

    public function test_booking_requires_a_name_and_a_usable_phone(): void
    {
        $night = BazaarNight::orderBy('event_date')->first();
        $table = BazaarTable::bookable()->orderBy('number')->first();

        $this->post('/bazar/book', [
            'bazaar_night_id' => $night->id,
            'bazaar_table_id' => $table->id,
            'vendor_name' => '',
            'vendor_phone' => '123',
        ])->assertSessionHasErrors(['vendor_name', 'vendor_phone']);
    }

    public function test_a_confirmation_page_is_private_to_the_person_who_booked(): void
    {
        $night = BazaarNight::orderBy('event_date')->first();
        $table = BazaarTable::bookable()->orderBy('number')->first();

        // Booking through the form puts it in this session -> visible.
        $this->post('/bazar/book', [
            'bazaar_night_id' => $night->id,
            'bazaar_table_id' => $table->id,
            'vendor_name' => 'Rana',
            'vendor_phone' => '0791234567',
        ]);

        $booking = BazaarBooking::latest('id')->first();
        $this->get(route('bazaar.confirmation', $booking))->assertOk()->assertSee('Table requested');

        // A different visitor cannot read someone else's details.
        $this->flushSession();
        $this->get(route('bazaar.confirmation', $booking))->assertNotFound();

        // Staff can.
        $this->actingAs($this->admin())
            ->get(route('bazaar.confirmation', $booking))
            ->assertOk();
    }

    private function makeBooking(BazaarNight $night, BazaarTable $table, array $overrides = []): BazaarBooking
    {
        return BazaarBooking::create(array_merge([
            'bazaar_night_id' => $night->id,
            'bazaar_table_id' => $table->id,
            'vendor_name' => 'Test Vendor',
            'vendor_phone' => '0790000000',
            'price' => $table->price,
        ], $overrides));
    }
}

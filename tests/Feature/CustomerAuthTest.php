<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Storefront accounts. These replace the Laravel Breeze scaffolding tests that
 * shipped with the skeleton: those asserted the default `web` guard and routes
 * (/profile, /verify-email, /confirm-password, a `dashboard`) this project
 * never built, so they had failed since the day customer auth was written.
 * Shoppers authenticate on the `customer` guard; staff go through Filament.
 */
class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Settings default to coming-soon ON, which serves the splash instead
        // of any storefront page.
        Setting::set('coming_soon_enabled', 'false');
    }

    private function customer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Sami',
            'email' => 'sami@example.com',
            'phone' => '0790000000',
            'password' => 'password',
        ], $attributes));
    }

    public function test_the_login_screen_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_a_customer_can_log_in(): void
    {
        $customer = $this->customer();

        $response = $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($customer, 'customer');
        $response->assertRedirect(route('my-orders.index'));
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $customer = $this->customer();

        $this->post('/login', [
            'email' => $customer->email,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('customer');
    }

    public function test_a_customer_can_log_out(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')->post('/logout');

        $this->assertGuest('customer');
        $response->assertRedirect(route('catalog.index'));
    }

    public function test_a_new_customer_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Rana',
            'phone' => '0791111111',
            'email' => 'rana@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $this->assertAuthenticated('customer');
        $response->assertRedirect(route('my-orders.index'));
        $this->assertDatabaseHas('customers', ['email' => 'rana@example.com']);
    }

    public function test_registering_adopts_the_guest_row_with_the_same_phone(): void
    {
        // Guests who order without an account get a passwordless customer row.
        // Registering later must claim that row, not create a second one, or
        // the shopper loses the order history and the points on it.
        $guest = Customer::create([
            'name' => 'Guest order',
            'phone' => '079-222 2222',
            'points_balance' => 40,
        ]);

        $this->post('/register', [
            'name' => 'Rana',
            'phone' => '0792222222',
            'email' => 'rana2@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $this->assertSame(1, Customer::count());
        $this->assertAuthenticatedAs($guest->fresh(), 'customer');
        $this->assertSame(40, $guest->fresh()->points_balance, 'the points earned as a guest survive');
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        $this->customer(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => 'Someone else',
            'phone' => '0793333333',
            'email' => 'taken@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_a_password_reset_link_can_be_requested(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_my_orders_needs_a_logged_in_customer(): void
    {
        $this->get('/my-orders')->assertRedirect(route('customer.login'));

        $this->actingAs($this->customer(), 'customer')->get('/my-orders')->assertOk();
    }
}

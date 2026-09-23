<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\StatsOverview;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * can_view_orders: an administrator who keeps the whole back office but is not
 * shown what the business takes.
 *
 * Hiding /admin/orders is the easy half. Order data also reaches the dashboard
 * as revenue, and the customers screen as an order count, a total spent, a
 * "has orders" filter and a full order history tab — each of those is a way to
 * read the takings without ever opening the Orders page.
 */
class OrderVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function restrictedAdmin(): User
    {
        return User::factory()->create(['role' => 'admin', 'can_view_orders' => false, 'can_view_cost' => false]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'can_view_orders' => true, 'can_view_cost' => true]);
    }

    private function customerWithOrder(): Customer
    {
        $customer = Customer::create(['name' => 'Sami', 'phone' => '0790000000']);

        Order::create([
            'customer_id' => $customer->id,
            'phone' => '0790000000',
            'city' => 'Amman',
            'address' => '5th Circle',
            'status' => 'delivered',
            'payment_method' => 'cod',
            'total' => 75,
        ]);

        return $customer;
    }

    public function test_the_orders_page_is_refused(): void
    {
        $response = $this->actingAs($this->restrictedAdmin())->get('/admin/orders');

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    public function test_an_unrestricted_admin_still_reaches_it(): void
    {
        $this->actingAs($this->admin())->get('/admin/orders')->assertOk();
    }

    public function test_the_rest_of_the_back_office_is_untouched(): void
    {
        // The whole point: this is not a demotion to Staff.
        $user = $this->restrictedAdmin();

        $this->actingAs($user)->get('/admin/products')->assertOk();
        $this->actingAs($user)->get('/admin/customers')->assertOk();
        $this->actingAs($user)->get('/admin/coupons')->assertOk();
        $this->actingAs($user)->get('/admin/categories')->assertOk();
    }

    public function test_the_dashboard_drops_the_money_but_keeps_the_rest(): void
    {
        $this->customerWithOrder();
        $this->actingAs($this->restrictedAdmin());

        $this->assertFalse(OrdersChart::canView());
        $this->assertFalse(LatestOrders::canView());
        $this->assertTrue(StatsOverview::canView(), 'the stats row itself stays');

        $widget = new StatsOverview();
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);
        $labels = array_map(fn ($stat) => $stat->getLabel(), $method->invoke($widget));

        $this->assertNotContains('Orders', $labels);
        $this->assertNotContains('Revenue (delivered)', $labels);
        $this->assertContains('Customers', $labels);
        $this->assertContains('Low stock', $labels);
    }

    public function test_the_dashboard_keeps_the_money_for_an_unrestricted_admin(): void
    {
        $this->customerWithOrder();
        $this->actingAs($this->admin());

        $widget = new StatsOverview();
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);
        $labels = array_map(fn ($stat) => $stat->getLabel(), $method->invoke($widget));

        $this->assertContains('Orders', $labels);
        $this->assertContains('Revenue (delivered)', $labels);
    }

    public function test_the_customer_list_hides_order_count_and_spend(): void
    {
        $this->customerWithOrder();

        Livewire::actingAs($this->restrictedAdmin())
            ->test(ListCustomers::class)
            ->assertTableColumnHidden('orders_count')
            ->assertTableColumnHidden('orders_sum_total');
    }

    public function test_the_customer_list_shows_them_to_an_unrestricted_admin(): void
    {
        $this->customerWithOrder();

        Livewire::actingAs($this->admin())
            ->test(ListCustomers::class)
            ->assertTableColumnVisible('orders_count')
            ->assertTableColumnVisible('orders_sum_total');
    }

    public function test_the_order_history_tab_disappears_from_a_customer(): void
    {
        $this->actingAs($this->restrictedAdmin());
        $this->assertSame([], CustomerResource::getRelations(), 'no orders relation manager');

        $this->actingAs($this->admin());
        $this->assertNotSame([], CustomerResource::getRelations());
    }

    public function test_the_total_spent_never_reaches_the_page_html(): void
    {
        $this->customerWithOrder();

        $html = $this->actingAs($this->restrictedAdmin())
            ->get('/admin/customers')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Total spent', $html);
        $this->assertStringNotContainsString('75.00', $html);
    }

    public function test_the_owner_always_sees_orders(): void
    {
        // Even with the flag off, so nobody can lock the owner out of takings.
        $owner = User::factory()->create(['role' => 'super_admin', 'can_view_orders' => false]);

        $this->assertTrue($owner->canViewOrders());
        $this->actingAs($owner)->get('/admin/orders')->assertOk();
    }

    public function test_cost_can_now_be_refused_to_an_administrator(): void
    {
        // Previously isAdmin() short-circuited the flag, so the only way to
        // hide cost from an admin was to demote them.
        $this->assertFalse($this->restrictedAdmin()->canViewCost());
        $this->assertTrue($this->admin()->canViewCost());

        $owner = User::factory()->create(['role' => 'super_admin', 'can_view_cost' => false]);
        $this->assertTrue($owner->canViewCost(), 'the owner always sees cost');
    }

    public function test_the_staff_list_shows_each_capability_at_a_glance(): void
    {
        $owner = User::factory()->create(['role' => 'super_admin', 'can_view_orders' => false, 'can_view_cost' => false]);
        $restricted = $this->restrictedAdmin();
        $full = $this->admin();

        $table = Livewire::actingAs($owner)->test(ListUsers::class);

        // The owner is exempt from both flags, so the column has to report what
        // is true rather than what the column stores — otherwise the one row
        // guaranteed to see everything would read as seeing nothing.
        $table->assertTableColumnStateSet('can_view_orders', true, $owner);
        $table->assertTableColumnStateSet('can_view_cost', true, $owner);

        $table->assertTableColumnStateSet('can_view_orders', false, $restricted);
        $table->assertTableColumnStateSet('can_view_cost', false, $restricted);

        $table->assertTableColumnStateSet('can_view_orders', true, $full);
        $table->assertTableColumnStateSet('can_view_cost', true, $full);
    }
}

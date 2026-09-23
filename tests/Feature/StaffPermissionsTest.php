<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\StatsOverview;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Staff tier: a catalogue person who can add and edit products but must
 * not see what the business earns or what it paid.
 *
 * This is a permission boundary, so it is asserted from the outside — real
 * HTTP requests to the URLs, not just the helper methods that back them. A
 * hidden navigation item is not access control; the URL has to refuse too.
 */
class StaffPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function staff(bool $canViewCost = false): User
    {
        return User::factory()->create(['role' => 'staff', 'can_view_cost' => $canViewCost]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function product(): Product
    {
        return Product::create([
            'name_en' => 'Kettle', 'name_ar' => 'غلاية',
            'price' => 30, 'cost_price' => 12, 'stock' => 4, 'is_active' => true,
        ]);
    }

    /** @return array<int, string> */
    public static function forbiddenUrls(): array
    {
        return [
            'orders' => ['/admin/orders'],
            'customers' => ['/admin/customers'],
            'coupons' => ['/admin/coupons'],
            'loyalty transactions' => ['/admin/loyalty-transactions'],
            'settings' => ['/admin/settings'],
            'staff' => ['/admin/users'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('forbiddenUrls')]
    public function test_staff_are_refused_at_the_url_not_just_the_menu(string $url): void
    {
        $response = $this->actingAs($this->staff())->get($url);

        $this->assertTrue(
            in_array($response->status(), [403, 404], true),
            "{$url} returned {$response->status()} for a staff user; expected 403/404",
        );
    }

    public function test_an_admin_can_reach_the_orders_page(): void
    {
        $this->actingAs($this->admin())->get('/admin/orders')->assertOk();
    }

    public function test_staff_can_still_do_their_job(): void
    {
        $this->actingAs($this->staff())->get('/admin/products')->assertOk();
        $this->actingAs($this->staff())->get('/admin/categories')->assertOk();
    }

    public function test_staff_do_not_see_cost_or_profit_in_the_products_list(): void
    {
        $this->product();

        Livewire::actingAs($this->staff())
            ->test(ListProducts::class)
            ->assertTableColumnHidden('cost_price')
            ->assertTableColumnHidden('profit');
    }

    public function test_staff_granted_cost_access_do_see_them(): void
    {
        $this->product();

        // The escape hatch: one catalogue person trusted with margins, without
        // handing them the whole back office.
        Livewire::actingAs($this->staff(canViewCost: true))
            ->test(ListProducts::class)
            ->assertTableColumnVisible('cost_price')
            ->assertTableColumnVisible('profit');
    }

    public function test_the_cost_price_never_reaches_a_staff_members_edit_page(): void
    {
        $product = $this->product();

        $html = $this->actingAs($this->staff())
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->getContent();

        // Not merely hidden by CSS — the number must not be in the payload.
        $this->assertStringNotContainsString('Cost price', $html);
        $this->assertStringNotContainsString('"cost_price":"12.00"', $html);
    }

    public function test_the_dashboard_shows_staff_no_order_figures(): void
    {
        $this->actingAs($this->staff());

        $this->assertFalse(StatsOverview::canView(), 'revenue and order counts');
        $this->assertFalse(OrdersChart::canView());
        $this->assertFalse(LatestOrders::canView());
    }

    public function test_the_dashboard_still_loads_for_staff(): void
    {
        // With every widget hidden the page must render empty, not error.
        $this->actingAs($this->staff())->get('/admin')->assertOk();
    }

    public function test_an_admin_sees_cost_without_the_flag(): void
    {
        $this->assertTrue($this->admin()->canViewCost());
        $this->assertFalse($this->staff()->canViewCost());
        $this->assertTrue($this->staff(canViewCost: true)->canViewCost());
    }
}

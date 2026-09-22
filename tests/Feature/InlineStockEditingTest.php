<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Stock is editable straight from the products list.
 *
 * The catch is that a product with variations does not own its own total —
 * ProductVariant's saved event sums the variants into products.stock — so
 * typing into that cell would be undone the moment any variant was touched.
 * The cell is therefore disabled for those, and the row action edits the
 * variants instead.
 *
 * These call `updateTableColumnState`, which is the method the column's own
 * JavaScript calls on blur, so they exercise the same path a typed value does.
 */
class InlineStockEditingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function product(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name_en' => 'Kettle',
            'name_ar' => 'غلاية',
            'price' => 10,
            'stock' => 5,
            'is_active' => true,
        ], $attributes));
    }

    private function type(Product $product, mixed $value): void
    {
        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->call('updateTableColumnState', 'stock', (string) $product->getKey(), $value);
    }

    public function test_stock_can_be_typed_into_the_list_page(): void
    {
        $product = $this->product(['stock' => 5]);

        $this->type($product, 42);

        $this->assertSame(42, $product->fresh()->stock);
    }

    public function test_the_cell_refuses_a_negative_number(): void
    {
        $product = $this->product(['stock' => 5]);

        $this->type($product, -3);

        $this->assertSame(5, $product->fresh()->stock, 'stock must not go negative');
    }

    public function test_the_cell_refuses_something_that_is_not_a_number(): void
    {
        $product = $this->product(['stock' => 5]);

        $this->type($product, 'lots');

        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_zero_is_allowed_because_selling_out_is_a_real_answer(): void
    {
        $product = $this->product(['stock' => 5]);

        $this->type($product, 0);

        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_a_variant_products_cell_cannot_be_typed_into(): void
    {
        $product = $this->product(['stock' => 0]);
        ProductVariant::create(['product_id' => $product->id, 'name' => 'red', 'stock' => 4]);
        ProductVariant::create(['product_id' => $product->id, 'name' => 'blue', 'stock' => 6]);

        $this->assertSame(10, $product->fresh()->stock, 'the total is the sum of its variants');

        $this->type($product, 999);

        $this->assertSame(10, $product->fresh()->stock, 'a disabled cell must not write');
    }

    public function test_the_variant_action_edits_each_variation_and_rolls_up_the_total(): void
    {
        $product = $this->product(['stock' => 0]);
        $red = ProductVariant::create(['product_id' => $product->id, 'name' => 'red', 'stock' => 4]);
        $blue = ProductVariant::create(['product_id' => $product->id, 'name' => 'blue', 'stock' => 6]);

        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->callTableAction('variantStock', $product, [
                'variants' => [
                    ['id' => $red->id, 'name' => 'red', 'stock' => 1],
                    ['id' => $blue->id, 'name' => 'blue', 'stock' => 2],
                ],
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(1, $red->fresh()->stock);
        $this->assertSame(2, $blue->fresh()->stock);
        $this->assertSame(3, $product->fresh()->stock, 'the product total follows its variants');
    }

    public function test_the_variant_action_cannot_write_to_another_products_variant(): void
    {
        $mine = $this->product(['name_en' => 'Mine']);
        $mineVariant = ProductVariant::create(['product_id' => $mine->id, 'name' => 'red', 'stock' => 4]);

        $theirs = $this->product(['name_en' => 'Theirs']);
        $theirVariant = ProductVariant::create(['product_id' => $theirs->id, 'name' => 'green', 'stock' => 9]);

        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->callTableAction('variantStock', $mine, [
                'variants' => [
                    ['id' => $mineVariant->id, 'name' => 'red', 'stock' => 1],
                    ['id' => $theirVariant->id, 'name' => 'green', 'stock' => 999],
                ],
            ]);

        $this->assertSame(1, $mineVariant->fresh()->stock);
        $this->assertSame(9, $theirVariant->fresh()->stock, 'a posted id from another product is ignored');
    }

    public function test_the_action_is_hidden_for_a_product_without_variations(): void
    {
        $plain = $this->product();

        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->assertTableActionHidden('variantStock', $plain);
    }

    public function test_the_action_is_visible_for_a_product_with_variations(): void
    {
        $product = $this->product();
        ProductVariant::create(['product_id' => $product->id, 'name' => 'red', 'stock' => 4]);

        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->assertTableActionVisible('variantStock', $product);
    }

    public function test_variant_count_is_right_with_or_without_the_eager_count(): void
    {
        // The list page eager-loads withCount('variants'); anything else that
        // reads a Product does not, and must still get the right answer.
        $product = $this->product();
        ProductVariant::create(['product_id' => $product->id, 'name' => 'red', 'stock' => 4]);

        $this->assertSame(1, Product::find($product->id)->variantCount(), 'without the eager count');
        $this->assertSame(1, Product::withCount('variants')->find($product->id)->variantCount(), 'with it');
        $this->assertSame(0, Product::find($this->product()->id)->variantCount());
    }

    public function test_the_list_page_renders_with_both_kinds_of_product(): void
    {
        $plain = $this->product(['name_en' => 'Plain kettle']);
        $withVariants = $this->product(['name_en' => 'Colourful kettle']);
        ProductVariant::create(['product_id' => $withVariants->id, 'name' => 'red', 'stock' => 4]);

        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->assertCanSeeTableRecords([$plain, $withVariants])
            ->assertSuccessful();
    }
}

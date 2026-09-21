<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    public function test_adding_color_creates_variants_for_each_active_size(): void
    {
        $this->actingAsRole();
        Size::query()->insert([
            ['name' => 'S', 'sort' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'M', 'sort' => 2, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'XL', 'sort' => 3, 'status' => 'inactive', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $product = $this->product();
        $color = Color::query()->create(['name_uz' => 'Qora', 'name_en' => 'Black', 'hex' => '#000000']);

        $response = $this->postJson("/api/v1/admin/products/{$product->id}/colors", ['color_id' => $color->id]);

        $response->assertCreated()->assertJsonCount(2, 'data.variants');
        $this->assertDatabaseCount('product_variants', 2);
        $this->assertStringContainsString('BLAC', ProductVariant::query()->first()->sku);
    }

    public function test_receiving_batch_increments_stock_once(): void
    {
        $this->actingAsRole();
        $variant = $this->variant();

        $batch = $this->postJson('/api/v1/admin/inventory-batches', [
            'supplier_country' => 'CN',
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 40, 'unit_cost' => 30000]],
        ])->assertCreated();

        $id = $batch->json('data.id');
        $this->assertStringStartsWith('BATCH-'.now()->year, $batch->json('data.number'));

        $this->postJson("/api/v1/admin/inventory-batches/{$id}/receive")->assertOk()->assertJsonPath('data.status', 'received');
        $this->assertSame(40, $variant->fresh()->quantity);

        // Ikkinchi marta qabul qilib bo'lmaydi.
        $this->postJson("/api/v1/admin/inventory-batches/{$id}/receive")->assertUnprocessable();
        $this->assertSame(40, $variant->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $variant->id, 'type' => 'in', 'quantity' => 40]);
    }

    public function test_manual_adjust_cannot_go_below_reserved(): void
    {
        $this->actingAsRole();
        $variant = $this->variant(['quantity' => 10, 'reserved' => 8]);

        $this->postJson("/api/v1/admin/variants/{$variant->id}/adjust", ['quantity' => -5])->assertUnprocessable();
        $this->postJson("/api/v1/admin/variants/{$variant->id}/adjust", ['quantity' => -2])->assertOk()->assertJsonPath('data.available', 0);
    }

    private function product(): Product
    {
        $category = Category::query()->create(['name_uz' => 'Futbolka', 'slug' => 'tshirt']);

        return Product::query()->create([
            'category_id' => $category->id, 'name_uz' => 'Klassik', 'name_en' => 'Classic', 'slug' => 'classic',
            'base_price' => 89000, 'print_price' => 25000,
        ]);
    }

    private function variant(array $attrs = []): ProductVariant
    {
        $product = $this->product();
        $color = Color::query()->create(['name_uz' => 'Oq', 'name_en' => 'White', 'hex' => '#FFFFFF']);
        $size = Size::query()->create(['name' => 'M']);
        $pc = $product->colors()->create(['color_id' => $color->id]);

        return ProductVariant::query()->create([
            'product_id' => $product->id, 'product_color_id' => $pc->id, 'size_id' => $size->id, 'sku' => 'TEST-M',
        ] + $attrs);
    }
}

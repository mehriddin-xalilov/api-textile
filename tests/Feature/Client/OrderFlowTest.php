<?php

namespace Tests\Feature\Client;

use App\Models\Category;
use App\Models\Color;
use App\Models\Design;
use App\Models\OrderItem;
use App\Models\PrintArea;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    private ProductVariant $variant;

    private User $customer;

    private PrintArea $area;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::query()->create(['name_uz' => 'Futbolka', 'slug' => 'tshirt']);
        $product = Product::query()->create(['category_id' => $category->id, 'name_uz' => 'Klassik', 'slug' => 'classic', 'base_price' => 100000, 'print_price' => 20000]);
        $this->area = $product->printAreas()->create(['side' => 'front', 'name' => 'Old', 'x' => 30, 'y' => 25, 'width' => 40, 'height' => 40, 'max_width_cm' => 28, 'max_height_cm' => 35]);
        $color = Color::query()->create(['name_uz' => 'Oq', 'hex' => '#FFFFFF']);
        $size = Size::query()->create(['name' => 'M']);
        $pc = $product->colors()->create(['color_id' => $color->id]);
        $this->variant = ProductVariant::query()->create(['product_id' => $product->id, 'product_color_id' => $pc->id, 'size_id' => $size->id, 'sku' => 'CLS-WHT-M', 'quantity' => 10]);

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
        Passport::actingAs($this->customer, ['*'], 'api');
    }

    public function test_order_with_design_reserves_stock_and_snapshots_prices(): void
    {
        $design = $this->postJson('/api/v1/designs', [
            'product_id' => $this->variant->product_id, 'product_color_id' => $this->variant->product_color_id,
            'canvas' => ['version' => 1, 'sides' => ['front' => ['layers' => [
                ['id' => 'l1', 'type' => 'text', 'print_area_id' => $this->area->id, 'x' => 10, 'y' => 10, 'width' => 80, 'height' => 20, 'text' => 'SALOM', 'font_family' => 'Oswald', 'font_style' => 'italic'],
            ]]]],
        ])->assertCreated()->json('data.id');

        $order = $this->postJson('/api/v1/orders', [
            'items' => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 2, 'design_id' => $design],
                ['product_variant_id' => $this->variant->id, 'quantity' => 1],
            ],
            'recipient_name' => 'Ali', 'recipient_phone' => '+998 90 000 00 00', 'delivery_address' => 'Toshkent', 'payment_method' => 'payme',
        ]);

        // (100000 + 20000) * 2 + 100000 * 1
        $order->assertCreated()->assertJsonPath('data.total', '340000.00')->assertJsonPath('data.status', 'new');
        $this->assertSame(3, $this->variant->fresh()->reserved);
        $this->assertSame(7, $this->variant->fresh()->available);
        $this->assertDatabaseHas('designs', ['id' => $design, 'status' => 'ready']);
    }

    public function test_order_exceeding_available_stock_is_rejected(): void
    {
        $this->postJson('/api/v1/orders', [
            'items' => [['product_variant_id' => $this->variant->id, 'quantity' => 11]],
            'recipient_name' => 'Ali', 'recipient_phone' => '+998900000000', 'delivery_address' => 'Toshkent', 'payment_method' => 'payme',
        ])->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');

        $this->assertSame(0, $this->variant->fresh()->reserved);
    }

    public function test_cancel_releases_reservation_and_ship_consumes_stock(): void
    {
        $orderId = $this->createOrder(4);

        $this->postJson("/api/v1/orders/{$orderId}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame(0, $this->variant->fresh()->reserved);
        $this->assertSame(10, $this->variant->fresh()->quantity);

        $orderId = $this->createOrder(4);
        $this->actingAsRole('operator');

        foreach (['confirmed', 'printing', 'sewing', 'ready', 'shipped'] as $status) {
            $this->postJson("/api/v1/admin/orders/{$orderId}/status", ['status' => $status])->assertOk()->assertJsonPath('data.status', $status);
        }

        $this->assertSame(6, $this->variant->fresh()->quantity);
        $this->assertSame(0, $this->variant->fresh()->reserved);
        $this->assertDatabaseCount('order_status_histories', 6 + 2);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $orderId = $this->createOrder(1);
        $this->actingAsRole('operator');

        $this->postJson("/api/v1/admin/orders/{$orderId}/status", ['status' => 'shipped'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_customer_cannot_see_others_orders(): void
    {
        $orderId = $this->createOrder(1);

        $other = User::factory()->create();
        $other->assignRole('customer');
        Passport::actingAs($other, ['*'], 'api');

        $this->getJson("/api/v1/orders/{$orderId}")->assertNotFound();
    }

    public function test_buying_a_template_clones_the_design_and_keeps_the_template(): void
    {
        $template = Design::query()->create([
            'user_id' => $this->customer->id,
            'product_id' => $this->variant->product_id,
            'product_color_id' => $this->variant->product_color_id,
            'name' => 'HAYOT',
            'canvas' => ['version' => 2, 'engine' => 'shirt-designer-3d', 'colors' => ['body' => '#111111'], 'layers' => []],
            'is_template' => true,
            'template_title' => 'HAYOT',
        ]);

        $orderId = $this->postJson('/api/v1/orders', [
            'items' => [['product_variant_id' => $this->variant->id, 'quantity' => 1, 'design_id' => $template->id]],
            'recipient_name' => 'Ali', 'recipient_phone' => '+998900000000', 'delivery_address' => 'Toshkent', 'payment_method' => 'payme',
        ])->assertCreated()->json('data.id');

        $itemDesignId = OrderItem::query()->where('order_id', $orderId)->value('design_id');

        $this->assertNotSame($template->id, $itemDesignId, 'buyurtma shablonning nusxasiga bog\'lanishi kerak');
        $this->assertDatabaseHas('designs', ['id' => $itemDesignId, 'is_template' => false, 'user_id' => $this->customer->id]);
        $this->assertDatabaseHas('designs', ['id' => $template->id, 'is_template' => true]);
    }

    private function createOrder(int $qty): int
    {
        Passport::actingAs($this->customer, ['*'], 'api');

        return $this->postJson('/api/v1/orders', [
            'items' => [['product_variant_id' => $this->variant->id, 'quantity' => $qty]],
            'recipient_name' => 'Ali', 'recipient_phone' => '+998900000000', 'delivery_address' => 'Toshkent', 'payment_method' => 'payme',
        ])->assertCreated()->json('data.id');
    }
}

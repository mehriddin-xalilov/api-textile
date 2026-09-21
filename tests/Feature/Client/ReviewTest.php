<?php

namespace Tests\Feature\Client;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::query()->create(['name_uz' => 'Futbolka', 'slug' => 'tshirt']);
        $this->product = Product::query()->create(['category_id' => $category->id, 'name_uz' => 'Klassik', 'slug' => 'classic', 'base_price' => 100000, 'print_price' => 20000]);
        $color = Color::query()->create(['name_uz' => 'Oq', 'hex' => '#FFFFFF']);
        $pc = $this->product->colors()->create(['color_id' => $color->id]);
        $size = Size::query()->create(['name' => 'M']);
        $this->variant = ProductVariant::query()->create([
            'product_id' => $this->product->id, 'product_color_id' => $pc->id, 'size_id' => $size->id,
            'sku' => 'CLS-WHT-M', 'quantity' => 10,
        ]);
    }

    private function buyer(Product $product): User
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'number' => 'T-'.uniqid(),
            'status' => OrderStatus::Delivered,
            'subtotal' => '0',
            'total' => '0',
            'recipient_name' => 'Test',
            'recipient_phone' => '+998901111111',
            'delivery_address' => 'Tashkent',
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant->id,
            'product_name' => $product->name_uz,
            'color_name' => 'Oq',
            'color_hex' => '#FFFFFF',
            'size_name' => 'M',
            'quantity' => 1,
            'unit_price' => '0',
            'total_price' => '0',
        ]);

        return $user;
    }

    public function test_sotib_olgan_mijoz_sharh_qoldiradi(): void
    {
        $product = $this->product;
        $user = $this->buyer($product);

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/reviews', ['product_id' => $product->id, 'rating' => 5, 'comment' => 'Zo\'r'])
            ->assertCreated();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => ReviewStatus::Pending->value,
        ]);
    }

    public function test_sotib_olmagan_mijoz_sharh_qoldirolmaydi(): void
    {
        $product = $this->product;

        $this->actingAs(User::factory()->create(), 'api')
            ->postJson('/api/v1/reviews', ['product_id' => $product->id, 'rating' => 5])
            ->assertStatus(422);
    }

    public function test_ikkinchi_marta_sharh_qoldirib_bolmaydi(): void
    {
        $product = $this->product;
        $user = $this->buyer($product);

        $this->actingAs($user, 'api')->postJson('/api/v1/reviews', ['product_id' => $product->id, 'rating' => 4])->assertCreated();
        $this->actingAs($user, 'api')->postJson('/api/v1/reviews', ['product_id' => $product->id, 'rating' => 3])->assertStatus(422);
    }

    public function test_saytda_faqat_tasdiqlangan_sharhlar_korinadi(): void
    {
        $product = $this->product;
        $user = $this->buyer($product);

        Review::query()->create(['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 5, 'comment' => 'ok', 'status' => ReviewStatus::Approved]);
        Review::query()->create(['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 1, 'comment' => 'spam', 'status' => ReviewStatus::Pending]);

        $res = $this->getJson("/api/v1/reviews?filter[product_id]={$product->id}")->assertOk();
        $this->assertCount(1, $res->json('data'));

        $this->getJson("/api/v1/reviews/summary?product_id={$product->id}")
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.average', 5);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.payme.test_key' => 'testkey', 'payments.click.secret_key' => 'clicksecret', 'payments.click.service_id' => '11']);
        $category = Category::query()->create(['name_uz' => 'F', 'slug' => 'f']);
        $product = Product::query()->create(['category_id' => $category->id, 'name_uz' => 'K', 'slug' => 'k', 'base_price' => 100000]);
        $pc = $product->colors()->create(['color_id' => Color::query()->create(['name_uz' => 'Oq', 'hex' => '#FFFFFF'])->id]);
        $variant = ProductVariant::query()->create(['product_id' => $product->id, 'product_color_id' => $pc->id, 'size_id' => Size::query()->create(['name' => 'M'])->id, 'sku' => 'K-M', 'quantity' => 5]);
        $user = User::factory()->create();
        $user->assignRole('customer');
        Passport::actingAs($user, ['*'], 'api');
        $id = $this->postJson('/api/v1/orders', ['items' => [['product_variant_id' => $variant->id, 'quantity' => 1]], 'recipient_name' => 'A', 'recipient_phone' => '+998900000000', 'delivery_address' => 'T', 'payment_method' => 'payme'])->json('data.id');
        $this->order = Order::query()->find($id);
    }

    public function test_pay_url_for_each_provider(): void
    {
        foreach (['payme', 'click', 'uzum'] as $p) {
            $this->getJson("/api/v1/orders/{$this->order->id}/pay-url?provider={$p}")->assertOk()->assertJsonPath('data.provider', $p);
        }
        $this->assertStringContainsString('checkout.paycom.uz', $this->getJson("/api/v1/orders/{$this->order->id}/pay-url?provider=payme")->json('data.url'));
    }

    public function test_payme_full_flow_marks_order_paid(): void
    {
        $auth = ['Authorization' => 'Basic '.base64_encode('Paycom:testkey')];
        $amount = 100000 * 100;
        $rpc = fn (string $method, array $params) => $this->postJson('/api/v1/payments/payme', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], $auth);

        $rpc('CheckPerformTransaction', ['amount' => $amount, 'account' => ['order_id' => $this->order->id]])->assertJsonPath('result.allow', true);
        $rpc('CheckPerformTransaction', ['amount' => 5, 'account' => ['order_id' => $this->order->id]])->assertJsonPath('error.code', -31001);

        $rpc('CreateTransaction', ['id' => 'tx1', 'time' => now()->getPreciseTimestamp(3), 'amount' => $amount, 'account' => ['order_id' => $this->order->id]])->assertJsonPath('result.state', 1);
        $rpc('PerformTransaction', ['id' => 'tx1'])->assertJsonPath('result.state', 2);
        $this->assertSame('paid', $this->order->fresh()->payment_status->value);

        $rpc('CheckTransaction', ['id' => 'tx1'])->assertJsonPath('result.state', 2);
        $rpc('CancelTransaction', ['id' => 'tx1', 'reason' => 5])->assertJsonPath('result.state', -2);
        $this->assertSame('refunded', $this->order->fresh()->payment_status->value);

        $this->postJson('/api/v1/payments/payme', ['method' => 'CheckTransaction', 'params' => ['id' => 'tx1']], ['Authorization' => 'Basic '.base64_encode('Paycom:wrong')])->assertJsonPath('error.code', -32504);
    }

    public function test_click_prepare_and_complete(): void
    {
        $sign = fn (array $p, $prepare = '') => md5($p['click_trans_id'].$p['service_id'].'clicksecret'.$p['merchant_trans_id'].$prepare.$p['amount'].$p['action'].$p['sign_time']);
        $base = ['click_trans_id' => '555', 'service_id' => '11', 'merchant_trans_id' => (string) $this->order->id, 'amount' => '100000.00', 'sign_time' => '2026-09-21 10:00:00', 'error' => 0];

        $prepare = $base + ['action' => 0];
        $res = $this->postJson('/api/v1/payments/click/prepare', $prepare + ['sign_string' => $sign($prepare)])->assertJsonPath('error', 0);
        $prepareId = $res->json('merchant_prepare_id');

        $complete = $base + ['action' => 1, 'merchant_prepare_id' => $prepareId];
        $this->postJson('/api/v1/payments/click/complete', $complete + ['sign_string' => $sign($complete, $prepareId)])->assertJsonPath('error', 0);
        $this->assertSame('paid', $this->order->fresh()->payment_status->value);

        $this->postJson('/api/v1/payments/click/prepare', $prepare + ['sign_string' => 'bad'])->assertJsonPath('error', -1);
    }
}

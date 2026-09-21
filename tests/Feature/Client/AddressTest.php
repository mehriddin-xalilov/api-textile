<?php

namespace Tests\Feature\Client;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge([
            'label' => 'Uy',
            'recipient_name' => 'Ali',
            'recipient_phone' => '+998 90 123 45 67',
            'region' => 'Toshkent',
            'city' => 'Chilonzor',
            'street' => 'Bunyodkor 12',
        ], $extra);
    }

    public function test_birinchi_manzil_avtomatik_asosiy_boladi(): void
    {
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->postJson('/api/v1/addresses', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.recipient_phone', '+998901234567');
    }

    public function test_yangi_asosiy_manzil_eskisini_almashtiradi(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['*'], 'api');

        $first = $this->postJson('/api/v1/addresses', $this->payload())->json('data.id');
        $this->postJson('/api/v1/addresses', $this->payload(['label' => 'Ish', 'is_default' => true]))->assertCreated();

        $this->assertFalse(UserAddress::query()->find($first)->is_default);
    }

    public function test_boshqaning_manzilini_ochirib_bolmaydi(): void
    {
        $other = UserAddress::query()->create($this->payload(['user_id' => User::factory()->create()->id]));
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->deleteJson("/api/v1/addresses/{$other->id}")->assertForbidden();
    }
}

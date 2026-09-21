<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_with_spaced_phone_returns_tokens_and_permissions(): void
    {
        $user = User::factory()->create(['phone_number' => '+998901112233', 'password' => 'secret1']);
        $user->assignRole('super-admin');

        $response = $this->postJson('/api/v1/admin/auth/login', ['login' => '+998 90 111 22 33', 'password' => 'secret1']);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['user' => ['id', 'permissions'], 'token', 'refresh_token', 'expires_in']])
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertContains('orders.update', $response->json('data.user.permissions'));
    }

    public function test_login_without_plus_sign_works(): void
    {
        User::factory()->create(['phone_number' => '+998901112233', 'password' => 'secret1'])->assignRole('manager');

        $this->postJson('/api/v1/admin/auth/login', ['login' => '998901112233', 'password' => 'secret1'])->assertOk();
    }

    public function test_wrong_password_is_422(): void
    {
        User::factory()->create(['phone_number' => '+998901112233', 'password' => 'secret1']);

        $this->postJson('/api/v1/admin/auth/login', ['login' => '+998901112233', 'password' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('login');
    }

    public function test_refresh_rotates_tokens(): void
    {
        User::factory()->create(['phone_number' => '+998901112233', 'password' => 'secret1']);
        $login = $this->postJson('/api/v1/admin/auth/login', ['login' => '+998901112233', 'password' => 'secret1']);

        $refresh = $this->postJson('/api/v1/admin/auth/refresh', ['refresh_token' => $login->json('data.refresh_token')]);

        $refresh->assertOk()->assertJsonStructure(['data' => ['token', 'refresh_token']]);
        $this->assertNotSame($login->json('data.token'), $refresh->json('data.token'));
    }

    public function test_logout_revokes_access_and_refresh_tokens(): void
    {
        User::factory()->create(['phone_number' => '+998901112233', 'password' => 'secret1'])->assignRole('manager');
        $login = $this->postJson('/api/v1/admin/auth/login', ['login' => '+998901112233', 'password' => 'secret1']);
        $headers = ['Authorization' => 'Bearer '.$login->json('data.token')];

        $this->postJson('/api/v1/admin/auth/logout', [], $headers)->assertOk();

        // Bitta test ichida guard foydalanuvchini keshlaydi — haqiqiy yangi so'rovni taqlid qilamiz.
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/admin/get-me', $headers)->assertUnauthorized();
        $this->postJson('/api/v1/admin/auth/refresh', ['refresh_token' => $login->json('data.refresh_token')])->assertUnprocessable();
    }

    public function test_customer_cannot_access_admin_resources(): void
    {
        $this->actingAsRole('customer');

        $this->getJson('/api/v1/admin/users')->assertForbidden();
    }
}

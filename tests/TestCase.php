<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\PassportClientSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PassportClientSeeder::class, PermissionSeeder::class, RoleSeeder::class]);
    }

    protected function actingAsRole(string $role = 'super-admin'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        Passport::actingAs($user, ['*'], 'api');

        return $user;
    }
}

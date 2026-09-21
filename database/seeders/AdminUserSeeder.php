<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['phone_number' => '+998901234567'],
            ['first_name' => 'Super', 'last_name' => 'Admin', 'email' => 'admin@textile.uz', 'password' => 'admin123'],
        );
        $admin->syncRoles(['super-admin']);
    }
}

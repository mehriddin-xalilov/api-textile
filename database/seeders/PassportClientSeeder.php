<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\Passport;

/**
 * Password grant client'ni .env dagi ID/secret bilan deterministik yaratadi.
 * Shunda `migrate:fresh` dan keyin ham .env ni o'zgartirish shart emas.
 */
class PassportClientSeeder extends Seeder
{
    public function run(): void
    {
        $id = config('passport.password_client_id');
        $secret = config('passport.password_client_secret');

        if (! $id || ! $secret) {
            $this->command?->warn('PASSPORT_PASSWORD_CLIENT_ID / SECRET .env da yo\'q — client yaratilmadi.');

            return;
        }

        $client = Passport::client();

        if ($client->newQuery()->whereKey($id)->exists()) {
            return;
        }

        $client->forceFill([
            'id' => $id,
            'name' => 'Textile Password Grant',
            'secret' => $secret,
            'provider' => 'users',
            'redirect_uris' => [],
            'grant_types' => ['password', 'refresh_token'],
            'revoked' => false,
        ])->save();
    }
}

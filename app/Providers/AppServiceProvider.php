<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Lazy-loading (N+1) ni ishlab chiqishda darhol ushlaymiz.
        Model::shouldBeStrict(! $this->app->isProduction());

        Passport::enablePasswordGrant();
        Passport::tokensExpireIn(now()->addHours((int) config('passport.token_ttl_hours', 12)));
        Passport::refreshTokensExpireIn(now()->addDays((int) config('passport.refresh_ttl_days', 30)));

        if ($this->app->isLocal()) {
            DB::listen(function ($query) {
                if ($query->time > 200) {
                    logger()->warning('Slow query', ['sql' => $query->sql, 'ms' => $query->time]);
                }
            });
        }
    }
}

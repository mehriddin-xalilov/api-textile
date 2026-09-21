<?php

namespace App\Models;

use App\Enums\Status;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected string $guard_name = 'api';

    protected $fillable = [
        'first_name', 'last_name', 'phone_number', 'email', 'password',
        'status', 'locale', 'avatar_id', 'last_login_at', 'phone_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => Status::class,
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** Passport password grant: login = telefon yoki email. */
    public function findForPassport(string $username): ?self
    {
        $username = Phone::normalize($username);

        return static::query()
            ->where('status', Status::Active)
            ->where(fn ($q) => $q->where('phone_number', $username)->orWhere('email', $username))
            ->first();
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** @return BelongsTo<File, $this> */
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(File::class, 'avatar_id');
    }

    /** @return HasMany<Design, $this> */
    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** B2B kampaniya lidi. Token xatdagi havolada `?c=` bilan yuboriladi. */
class B2bLead extends Model
{
    protected $table = 'b2b_leads';

    protected $fillable = [
        'token', 'company', 'email', 'phone', 'segment', 'campaign', 'sent_at', 'opened_at',
        'first_click_at', 'last_click_at', 'clicks', 'last_path', 'last_ip', 'user_id', 'note',
    ];

    protected $casts = [
        'sent_at' => 'datetime', 'opened_at' => 'datetime',
        'first_click_at' => 'datetime', 'last_click_at' => 'datetime',
        'clicks' => 'integer',
    ];

    /** Email'dan deterministik token: mailer skripti ham xuddi shu formulani ishlatadi. */
    public static function tokenFor(string $email): string
    {
        return substr(sha1('motex|'.strtolower(trim($email))), 0, 10);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

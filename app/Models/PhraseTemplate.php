<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;

/** Trend so'z/gap shabloni — konstruktorda tayyor uslubdagi matn qatlami. */
class PhraseTemplate extends Model
{
    protected $fillable = ['text', 'category', 'font_family', 'font_weight', 'font_style', 'fill', 'sort', 'status'];

    protected $casts = ['status' => Status::class];
}

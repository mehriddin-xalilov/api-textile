<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $fillable = ['name', 'sort', 'status'];

    protected $casts = ['status' => Status::class];
}

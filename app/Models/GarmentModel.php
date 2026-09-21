<?php

namespace App\Models;

use App\Enums\Status;
use App\Support\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 3D kiyim modeli (GLB) — konstruktor uchun. */
class GarmentModel extends Model
{
    use Translatable;

    protected $fillable = ['name_uz', 'name_ru', 'name_en', 'file_id', 'thumbnail_id', 'zones', 'author', 'sort', 'status'];

    protected $casts = ['status' => Status::class, 'zones' => 'array'];

    /** @return BelongsTo<File, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /** @return BelongsTo<File, $this> */
    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(File::class, 'thumbnail_id');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

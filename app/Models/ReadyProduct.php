<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Enums\Status;
use App\Support\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tayyor mahsulot: admin panelda rasm bilan kiritiladi, darhol sotiladi.
 * Konstruktor (Design/GarmentModel) bilan aloqasi yo'q.
 *
 * @property-read int|null $approved_reviews_count
 * @property-read float|null $approved_reviews_avg_rating
 */
class ReadyProduct extends Model
{
    use SoftDeletes, Translatable;

    protected $fillable = [
        'category_id', 'name_uz', 'name_ru', 'name_en', 'slug',
        'description_uz', 'description_ru', 'description_en',
        'price', 'old_price', 'color_name', 'color_hex', 'sizes', 'specs', 'quantity', 'sold_count', 'status', 'sort',
    ];

    protected $casts = [
        'status' => Status::class,
        'sizes' => 'array',
        'specs' => 'array',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
    ];

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ReadyProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ReadyProductImage::class)->orderBy('sort')->orderBy('id');
    }

    /** Tasdiqlangan sharhlar — reyting shundan. @return HasMany<Review, $this> */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', ReviewStatus::Approved);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }
}

<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\ReviewStatus;
use App\Enums\Status;
use App\Support\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int|null $available_stock  withAvailableStock() scope orqali
 * @property-read int|null $approved_reviews_count  withCount('approvedReviews') orqali
 * @property-read float|null $approved_reviews_avg_rating  withAvg(...) orqali
 */
class Product extends Model
{
    use SoftDeletes, Translatable;

    protected $fillable = [
        'category_id', 'name_uz', 'name_ru', 'name_en', 'slug',
        'description_uz', 'description_ru', 'description_en',
        'fabric', 'origin_country', 'gender', 'type', 'garment_model_id', 'base_price', 'print_price', 'sort', 'status',
    ];

    protected $casts = [
        'status' => Status::class,
        'gender' => Gender::class,
        'base_price' => 'decimal:2',
        'print_price' => 'decimal:2',
    ];

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<GarmentModel, $this> */
    public function garmentModel(): BelongsTo
    {
        return $this->belongsTo(GarmentModel::class);
    }

    /** @return HasMany<ProductColor, $this> */
    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** @return HasMany<PrintArea, $this> */
    public function printAreas(): HasMany
    {
        return $this->hasMany(PrintArea::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Saytda ko'rinadigan (tasdiqlangan) izohlar — reyting shundan hisoblanadi. @return HasMany<Review, $this> */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', ReviewStatus::Approved);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /** Ombordagi sotuvga tayyor (band qilinmagan) jami miqdor. */
    public function scopeWithAvailableStock(Builder $query): Builder
    {
        return $query->addSelect([
            'available_stock' => ProductVariant::query()
                ->selectRaw('coalesce(sum(quantity - reserved), 0)')
                ->whereColumn('product_id', 'products.id'),
        ]);
    }
}

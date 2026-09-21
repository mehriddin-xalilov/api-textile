<?php

namespace App\Models;

use App\Enums\DesignStatus;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int|null $approved_reviews_count  withCount('approvedReviews') orqali
 * @property-read float|null $approved_reviews_avg_rating  withAvg(...) orqali
 */
class Design extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'product_id', 'product_color_id', 'name', 'canvas', 'preview_file_id', 'photo_file_id', 'print_file_id', 'status', 'is_template', 'template_title', 'sort'];

    protected $casts = ['canvas' => 'array', 'status' => DesignStatus::class, 'is_template' => 'boolean'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductColor, $this> */
    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class);
    }

    /** @return BelongsTo<File, $this> */
    public function preview(): BelongsTo
    {
        return $this->belongsTo(File::class, 'preview_file_id');
    }

    /** @return BelongsTo<File, $this> */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(File::class, 'photo_file_id');
    }

    /** Tasdiqlangan izohlar (tayyor dizayn kartochkasidagi reyting). @return HasMany<Review, $this> */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', ReviewStatus::Approved);
    }

    /** @return BelongsTo<File, $this> */
    public function printFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'print_file_id');
    }
}

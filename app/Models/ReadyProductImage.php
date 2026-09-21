<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadyProductImage extends Model
{
    protected $fillable = ['ready_product_id', 'file_id', 'sort'];

    /** @return BelongsTo<File, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /** @return BelongsTo<ReadyProduct, $this> */
    public function readyProduct(): BelongsTo
    {
        return $this->belongsTo(ReadyProduct::class);
    }
}

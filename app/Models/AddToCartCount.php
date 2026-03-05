<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddToCartCount extends Model
{
    public const SCOPE_PRODUCT = 'product';

    public const SCOPE_VARIANT = 'variant';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'scope',
        'product_id',
        'variant_id',
        'product_slug',
        'count',
    ];

    /**
     * Get the site that owns the count.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}

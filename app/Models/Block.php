<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    use HasFactory;

    public const TYPE_SHOPIFY_ADD_TO_CART_COUNTER = 'shopify_add_to_cart_counter';

    public const TYPE_VIDEO_CALL_BUTTON = 'video_call_button';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'type',
        'name',
        'status',
        'settings',
        'display_rules',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'display_rules' => 'array',
        ];
    }

    /**
     * Get the site that owns the block.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the events for the block.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the add-to-cart counts for this block (Shopify Add To Cart Counter).
     */
    public function addToCartCounts(): HasMany
    {
        return $this->hasMany(AddToCartCount::class);
    }

    /**
     * Check if the block is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

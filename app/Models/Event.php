<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    public const EVENT_IMPRESSION = 'impression';

    public const EVENT_CLICK = 'click';

    public const EVENT_CUSTOM = 'custom';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'block_id',
        'event_name',
        'event_at',
        'page_url',
        'referrer',
        'user_agent_hash',
        'ip_hash',
        'payload',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /**
     * Get the site that owns the event.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the block associated with the event.
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }
}

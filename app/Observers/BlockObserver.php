<?php

namespace App\Observers;

use App\Models\Block;
use App\Services\EmbedConfigService;

class BlockObserver
{
    public function __construct(
        private readonly EmbedConfigService $embedConfigService
    ) {}

    /**
     * Invalidate embed config cache when a block is saved or deleted.
     */
    public function saved(Block $block): void
    {
        $this->embedConfigService->invalidateCacheForSite($block->site);
    }

    /**
     * Invalidate embed config cache when a block is deleted.
     */
    public function deleted(Block $block): void
    {
        $this->embedConfigService->invalidateCacheForSite($block->site);
    }
}

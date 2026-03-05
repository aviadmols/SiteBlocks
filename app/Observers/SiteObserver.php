<?php

namespace App\Observers;

use App\Models\Site;
use App\Services\EmbedConfigService;

class SiteObserver
{
    public function __construct(
        private readonly EmbedConfigService $embedConfigService
    ) {}

    /**
     * Invalidate embed config cache when a site is saved or deleted.
     */
    public function saved(Site $site): void
    {
        $this->embedConfigService->invalidateCacheForSite($site);
    }

    /**
     * Invalidate embed config cache when a site is deleted.
     */
    public function deleted(Site $site): void
    {
        $this->embedConfigService->invalidateCacheForSite($site);
    }
}

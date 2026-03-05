<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Cache;

class EmbedConfigService
{
    private const CACHE_KEY_PREFIX = 'embed_config:';

    private const CACHE_TTL_SECONDS = 300;

    /**
     * Build the public config array for a site (active blocks only, with id, type, settings, display_rules).
     * Used for the embed script to load and run blocks.
     *
     * @return array{blocks: list<array{id: int, type: string, settings: array, display_rules: array|null}>}
     */
    public function getConfigForSite(Site $site): array
    {
        $blocks = $site->blocks()
            ->where('status', 'active')
            ->get(['id', 'type', 'settings', 'display_rules'])
            ->map(fn ($block) => [
                'id' => $block->id,
                'type' => $block->type,
                'settings' => $block->settings ?? [],
                'display_rules' => $block->display_rules,
            ])
            ->values()
            ->all();

        return ['blocks' => $blocks];
    }

    /**
     * Get config for a site by site_key. Resolves site first; returns empty blocks if not found or inactive.
     *
     * @return array{blocks: list<array{id: int, type: string, settings: array, display_rules: array|null}>}|null
     */
    public function getConfigBySiteKey(string $siteKey): ?array
    {
        $site = Site::where('site_key', $siteKey)
            ->where('status', Site::STATUS_ACTIVE)
            ->first();

        if (! $site) {
            return null;
        }

        $cacheKey = self::CACHE_KEY_PREFIX.$siteKey;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => $this->getConfigForSite($site));
    }

    /**
     * Invalidate cached config for a site (call when site or its blocks are updated).
     */
    public function invalidateCacheForSite(Site $site): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX.$site->site_key);
    }
}

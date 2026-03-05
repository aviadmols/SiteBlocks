<?php

namespace Tests\Unit;

use App\Models\Block;
use App\Models\Site;
use App\Services\EmbedConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmbedConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_config_for_site_returns_active_blocks_only(): void
    {
        $site = Site::factory()->create();
        Block::factory()->create(['site_id' => $site->id, 'status' => Block::STATUS_ACTIVE]);
        Block::factory()->create(['site_id' => $site->id, 'status' => Block::STATUS_INACTIVE]);

        $service = app(EmbedConfigService::class);
        $config = $service->getConfigForSite($site);

        $this->assertArrayHasKey('blocks', $config);
        $this->assertCount(1, $config['blocks']);
        $this->assertSame('shopify_add_to_cart_counter', $config['blocks'][0]['type']);
    }

    public function test_get_config_by_site_key_returns_null_for_inactive_site(): void
    {
        $site = Site::factory()->create(['status' => Site::STATUS_INACTIVE]);
        $service = app(EmbedConfigService::class);

        $this->assertNull($service->getConfigBySiteKey($site->site_key));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_returns_200_for_active_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id, 'status' => Site::STATUS_ACTIVE]);
        Block::factory()->create(['site_id' => $site->id, 'status' => Block::STATUS_ACTIVE]);

        $response = $this->getJson('/api/public/sites/'.$site->site_key.'/config');

        $response->assertStatus(200);
        $response->assertJsonStructure(['blocks']);
        $this->assertCount(1, $response->json('blocks'));
        $this->assertSame('shopify_add_to_cart_counter', $response->json('blocks.0.type'));
    }

    public function test_config_returns_404_for_missing_site_key(): void
    {
        $response = $this->getJson('/api/public/sites/nonexistent-key/config');

        $response->assertStatus(404);
    }

    public function test_config_returns_404_for_inactive_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id, 'status' => Site::STATUS_INACTIVE]);

        $response = $this->getJson('/api/public/sites/'.$site->site_key.'/config');

        $response->assertStatus(404);
    }
}

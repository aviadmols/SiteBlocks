<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopifyAddToCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_count_returns_zero_when_no_data(): void
    {
        $site = Site::factory()->create();

        $response = $this->getJson('/api/public/shopify/count?site_key='.$site->site_key.'&variant_id=123');

        $response->assertStatus(200);
        $response->assertJson(['count' => 0]);
    }

    public function test_add_to_cart_increments_and_count_returns_one(): void
    {
        $site = Site::factory()->create();

        $post = $this->postJson('/api/public/shopify/add-to-cart', [
            'site_key' => $site->site_key,
            'variant_id' => '456',
            'page_url' => 'https://example.com/product',
        ]);
        $post->assertStatus(200);
        $post->assertJson(['count' => 1]);

        $get = $this->getJson('/api/public/shopify/count?site_key='.$site->site_key.'&variant_id=456');
        $get->assertStatus(200);
        $get->assertJson(['count' => 1]);
    }

    public function test_count_returns_422_without_product_or_variant(): void
    {
        $site = Site::factory()->create();

        $response = $this->getJson('/api/public/shopify/count?site_key='.$site->site_key);

        $response->assertStatus(422);
    }
}

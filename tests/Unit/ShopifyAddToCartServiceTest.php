<?php

namespace Tests\Unit;

use App\Models\Site;
use App\Services\ShopifyAddToCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopifyAddToCartServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_count_returns_zero_initially(): void
    {
        $site = Site::factory()->create();
        $service = app(ShopifyAddToCartService::class);

        $count = $service->getCount($site, 'variant', null, '123');
        $this->assertSame(0, $count);
    }

    public function test_increment_count_returns_new_count(): void
    {
        $site = Site::factory()->create();
        $service = app(ShopifyAddToCartService::class);

        $count = $service->incrementCount($site, null, 'variant', null, '456', 'https://example.com');
        $this->assertSame(1, $count);

        $count2 = $service->incrementCount($site, null, 'variant', null, '456', 'https://example.com');
        $this->assertSame(2, $count2);

        $getCount = $service->getCount($site, 'variant', null, '456');
        $this->assertSame(2, $getCount);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_event_returns_204_for_valid_payload(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/public/events', [
            'site_key' => $site->site_key,
            'event_name' => Event::EVENT_IMPRESSION,
            'page_url' => 'https://example.com/page',
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseHas('events', [
            'site_id' => $site->id,
            'event_name' => Event::EVENT_IMPRESSION,
            'page_url' => 'https://example.com/page',
        ]);
    }

    public function test_store_event_returns_422_for_invalid_event_name(): void
    {
        $site = Site::factory()->create();

        $response = $this->postJson('/api/public/events', [
            'site_key' => $site->site_key,
            'event_name' => 'invalid',
            'page_url' => 'https://example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_event_returns_404_for_inactive_site(): void
    {
        $site = Site::factory()->create(['status' => Site::STATUS_INACTIVE]);

        $response = $this->postJson('/api/public/events', [
            'site_key' => $site->site_key,
            'event_name' => Event::EVENT_CLICK,
            'page_url' => 'https://example.com',
        ]);

        $response->assertStatus(404);
    }
}

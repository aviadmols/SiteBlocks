<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Site;

class AnalyticsIngestService
{
    /**
     * Ingest a single analytics event. Hashes IP and User-Agent; does not store raw values.
     *
     * @param  array{site_id: int, block_id?: int|null, event_name: string, page_url: string, referrer?: string|null, user_agent_raw?: string|null, ip_raw?: string|null, payload?: array|null}  $validated
     */
    public function ingest(array $validated): Event
    {
        $ipHash = isset($validated['ip_raw']) && $validated['ip_raw'] !== ''
            ? hash('sha256', $validated['ip_raw'])
            : null;
        $uaHash = isset($validated['user_agent_raw']) && $validated['user_agent_raw'] !== ''
            ? hash('sha256', $validated['user_agent_raw'])
            : null;

        return Event::create([
            'site_id' => $validated['site_id'],
            'block_id' => $validated['block_id'] ?? null,
            'event_name' => $validated['event_name'],
            'event_at' => now(),
            'page_url' => $validated['page_url'] ?? null,
            'referrer' => $validated['referrer'] ?? null,
            'user_agent_hash' => $uaHash,
            'ip_hash' => $ipHash,
            'payload' => $validated['payload'] ?? null,
        ]);
    }
}

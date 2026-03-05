<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Site;
use App\Services\AnalyticsIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicEventsController extends Controller
{
    public function __construct(
        private readonly AnalyticsIngestService $analyticsIngest
    ) {}

    /**
     * Ingest a single analytics event. Validates site_key, hashes IP/UA, rate limited.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_key' => ['required', 'string', 'max:64'],
            'block_id' => ['nullable', 'integer', 'exists:blocks,id'],
            'event_name' => ['required', 'string', Rule::in([Event::EVENT_IMPRESSION, Event::EVENT_CLICK, Event::EVENT_CUSTOM])],
            'page_url' => ['required', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'payload' => ['nullable', 'array'],
        ]);

        $site = Site::where('site_key', $validated['site_key'])
            ->where('status', Site::STATUS_ACTIVE)
            ->first();

        if (! $site) {
            return response()->json(['error' => 'Site not found or inactive'], 404);
        }

        if (isset($validated['block_id'])) {
            $block = $site->blocks()->find($validated['block_id']);
            if (! $block) {
                return response()->json(['error' => 'Block not found for this site'], 422);
            }
        }

        $ingestPayload = [
            'site_id' => $site->id,
            'block_id' => $validated['block_id'] ?? null,
            'event_name' => $validated['event_name'],
            'page_url' => $validated['page_url'],
            'referrer' => $validated['referrer'] ?? null,
            'user_agent_raw' => $request->userAgent(),
            'ip_raw' => $request->ip() ?? '',
            'payload' => $validated['payload'] ?? null,
        ];

        $this->analyticsIngest->ingest($ingestPayload);

        return response()->json(null, 204);
    }
}

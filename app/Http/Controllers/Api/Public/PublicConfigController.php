<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\EmbedConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class PublicConfigController extends Controller
{
    public function __construct(
        private readonly EmbedConfigService $embedConfigService
    ) {}

    /**
     * Return public config for the given site_key (active blocks only). Supports ETag and short cache.
     */
    public function show(Request $request, string $siteKey): JsonResponse
    {
        $config = $this->embedConfigService->getConfigBySiteKey($siteKey);

        if ($config === null) {
            return response()->json(['error' => 'Site not found or inactive'], 404);
        }

        $etag = '"'.hash('sha256', json_encode($config)).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response()->json(null, 304);
        }

        return response()->json($config, 200, [
            'Cache-Control' => 'public, max-age=60',
            'ETag' => $etag,
        ]);
    }
}

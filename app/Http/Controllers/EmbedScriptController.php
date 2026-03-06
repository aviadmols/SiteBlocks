<?php

namespace App\Http\Controllers;

use App\Models\Block;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class EmbedScriptController
{
    /**
     * Allowed block types for /embed/blocks/{type}.js (whitelist).
     *
     * @var list<string>
     */
    private const ALLOWED_BLOCK_TYPES = [
        Block::TYPE_SHOPIFY_ADD_TO_CART_COUNTER,
        Block::TYPE_VIDEO_CALL_BUTTON,
    ];

    /**
     * Return the embed loader script (vanilla JS). Cacheable; no auth.
     */
    public function loader(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost() ?: config('app.url');

        $content = View::make('embed.script', [
            'embedBaseUrl' => rtrim($baseUrl, '/'),
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Return a single block script by type. 404 if type not whitelisted.
     */
    public function block(string $type): Response
    {
        if (! in_array($type, self::ALLOWED_BLOCK_TYPES, true)) {
            return response('', 404);
        }

        $content = View::make('embed.blocks.' . $type)->render();

        return response($content, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

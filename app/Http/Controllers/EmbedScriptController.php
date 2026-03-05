<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class EmbedScriptController
{
    /**
     * Return the embed loader script (vanilla JS). Cacheable; no auth.
     */
    public function __invoke(): Response
    {
        $content = View::make('embed.script', [
            'embedBaseUrl' => rtrim(config('app.url'), '/'),
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

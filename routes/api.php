<?php

use App\Http\Controllers\Api\Public\PublicConfigController;
use App\Http\Controllers\Api\Public\PublicEventsController;
use App\Http\Controllers\Api\Public\ShopifyAddToCartController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['ok' => true], 200));

Route::prefix('public')->middleware('throttle:120,1')->group(function (): void {
    Route::get('/sites/{site_key}/config', [PublicConfigController::class, 'show'])->name('public.config');
    Route::post('/events', [PublicEventsController::class, 'store'])->middleware('throttle:60,1')->name('public.events');
    Route::get('/shopify/count', [ShopifyAddToCartController::class, 'count'])->name('public.shopify.count');
    Route::post('/shopify/add-to-cart', [ShopifyAddToCartController::class, 'addToCart'])->middleware('throttle:60,1')->name('public.shopify.add-to-cart');
});

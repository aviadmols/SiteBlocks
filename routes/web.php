<?php

use App\Http\Controllers\EmbedScriptController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/embed.js', [EmbedScriptController::class, 'loader'])
    ->middleware('throttle:120,1')
    ->name('embed.script');

Route::get('/embed/blocks/{type}.js', [EmbedScriptController::class, 'block'])
    ->where('type', 'shopify_add_to_cart_counter|video_call_button')
    ->middleware('throttle:120,1')
    ->name('embed.block');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

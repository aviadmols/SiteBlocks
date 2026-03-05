<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Creates the events table for analytics (hashed IP/UA only).
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_name', 64);
            $table->timestamp('event_at');
            $table->string('page_url', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->index(['site_id', 'event_at']);
            $table->index(['site_id', 'event_name', 'event_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

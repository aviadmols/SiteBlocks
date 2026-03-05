<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Creates add_to_cart_counts for Shopify block aggregation.
     */
    public function up(): void
    {
        Schema::create('add_to_cart_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 32);
            $table->string('product_id', 64)->nullable();
            $table->string('variant_id', 64)->nullable();
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'scope', 'product_id', 'variant_id'], 'add_to_cart_counts_site_scope_ids_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('add_to_cart_counts');
    }
};

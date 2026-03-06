<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('add_to_cart_counts', function (Blueprint $table) {
            $table->foreignId('block_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
        });

        Schema::table('add_to_cart_counts', function (Blueprint $table) {
            $table->dropUnique('add_to_cart_counts_site_scope_ids_unique');
        });

        Schema::table('add_to_cart_counts', function (Blueprint $table) {
            $table->unique(['site_id', 'block_id', 'scope', 'product_id', 'variant_id'], 'add_to_cart_counts_site_block_scope_ids_unique');
        });
    }

    public function down(): void
    {
        Schema::table('add_to_cart_counts', function (Blueprint $table) {
            $table->dropUnique('add_to_cart_counts_site_block_scope_ids_unique');
        });

        Schema::table('add_to_cart_counts', function (Blueprint $table) {
            $table->dropForeign(['block_id']);
            $table->dropColumn('block_id');
        });

        Schema::table('add_to_cart_counts', function (Blueprint $table) {
            $table->unique(['site_id', 'scope', 'product_id', 'variant_id'], 'add_to_cart_counts_site_scope_ids_unique');
        });
    }
};

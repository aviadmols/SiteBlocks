<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('add_to_cart_counts', function (Blueprint $table) {
            $table->string('product_slug', 255)->nullable()->after('variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('add_to_cart_counts', function (Blueprint $table) {
            $table->dropColumn('product_slug');
        });
    }
};

<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database: one user, one site, one block.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $site = Site::create([
            'user_id' => $user->id,
            'name' => 'Demo Site',
            'primary_domain' => 'demo.example.com',
            'allowed_domains' => ['demo.example.com', 'localhost'],
            'site_key' => Str::random(32),
            'site_secret' => Str::random(64),
            'status' => Site::STATUS_ACTIVE,
        ]);

        Block::create([
            'site_id' => $site->id,
            'type' => Block::TYPE_SHOPIFY_ADD_TO_CART_COUNTER,
            'name' => 'Shopify Add To Cart Counter',
            'status' => Block::STATUS_ACTIVE,
            'settings' => [
                'target_selector' => '[data-product-form], form[action*="/cart/add"]',
                'insert_position' => 'after',
                'message_template' => 'This product was added to cart {{count}} times',
                'message_class' => 'embed-add-to-cart-count',
                'min_count_to_show' => 0,
                'count_scope' => 'variant',
                'debug' => false,
            ],
            'display_rules' => null,
        ]);
    }
}

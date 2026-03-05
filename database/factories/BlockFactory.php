<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Block>
 */
class BlockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'type' => Block::TYPE_SHOPIFY_ADD_TO_CART_COUNTER,
            'name' => 'Add to Cart Counter',
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
        ];
    }
}

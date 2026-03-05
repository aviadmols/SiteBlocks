<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company().' Site',
            'primary_domain' => fake()->domainName(),
            'allowed_domains' => [fake()->domainName()],
            'site_key' => Str::random(32),
            'site_secret' => Str::random(64),
            'status' => Site::STATUS_ACTIVE,
        ];
    }
}

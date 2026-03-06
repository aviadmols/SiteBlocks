<?php

namespace App\Services;

use App\Models\AddToCartCount;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

class ShopifyAddToCartService
{
    /**
     * Get the current add-to-cart count for the given site and scope (product or variant).
     * When $blockId is provided, returns count for that block only; otherwise legacy (block_id null).
     */
    public function getCount(Site $site, string $scope, ?string $productId, ?string $variantId, ?int $blockId = null): int
    {
        $query = AddToCartCount::where('site_id', $site->id)->where('scope', $scope);

        if ($blockId !== null) {
            $query->where('block_id', $blockId);
        } else {
            $query->whereNull('block_id');
        }

        if ($scope === AddToCartCount::SCOPE_PRODUCT) {
            $query->where('product_id', (string) $productId)->whereNull('variant_id');
        } else {
            $query->where('variant_id', (string) $variantId);
        }

        $record = $query->first();

        return $record ? (int) $record->count : 0;
    }

    /**
     * Increment add-to-cart count and return the new count. Uses upsert for concurrency.
     */
    public function incrementCount(
        Site $site,
        ?int $blockId,
        string $scope,
        ?string $productId,
        ?string $variantId,
        string $pageUrl,
        ?string $productSlug = null
    ): int {
        $productId = $productId !== null && $productId !== '' ? (string) $productId : null;
        $variantId = $variantId !== null && $variantId !== '' ? (string) $variantId : null;
        $productSlug = $productSlug !== null && $productSlug !== '' ? (string) $productSlug : null;

        return (int) DB::transaction(function () use ($site, $blockId, $scope, $productId, $variantId, $pageUrl, $productSlug) {
            $query = AddToCartCount::where('site_id', $site->id)
                ->where('scope', $scope);
            if ($blockId !== null) {
                $query->where('block_id', $blockId);
            } else {
                $query->whereNull('block_id');
            }
            $record = $query
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if ($record) {
                $record->increment('count');
                if ($productSlug !== null) {
                    $record->update(['product_slug' => $productSlug]);
                }

                return $record->fresh()->count;
            }

            $record = AddToCartCount::create([
                'site_id' => $site->id,
                'block_id' => $blockId,
                'scope' => $scope,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'product_slug' => $productSlug,
                'count' => 1,
            ]);

            return $record->count;
        });
    }
}

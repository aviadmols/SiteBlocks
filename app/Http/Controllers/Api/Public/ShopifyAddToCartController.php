<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\AddToCartCount;
use App\Models\Site;
use App\Services\ShopifyAddToCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyAddToCartController extends Controller
{
    public function __construct(
        private readonly ShopifyAddToCartService $shopifyAddToCart
    ) {}

    /**
     * GET count: returns current add-to-cart count for product_id or variant_id.
     */
    public function count(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_key' => ['required', 'string', 'max:64'],
            'block_id' => ['nullable', 'integer', 'exists:blocks,id'],
            'product_id' => ['nullable', 'string', 'max:64'],
            'variant_id' => ['nullable', 'string', 'max:64'],
        ]);

        $site = Site::where('site_key', $validated['site_key'])
            ->where('status', Site::STATUS_ACTIVE)
            ->first();

        if (! $site) {
            return response()->json(['error' => 'Site not found or inactive'], 404);
        }

        $blockId = isset($validated['block_id']) ? (int) $validated['block_id'] : null;
        if ($blockId !== null && ! $site->blocks()->where('id', $blockId)->exists()) {
            return response()->json(['error' => 'Block not found for this site'], 422);
        }

        $productId = $validated['product_id'] ?? null;
        $variantId = $validated['variant_id'] ?? null;

        $scope = $this->resolveScope($productId, $variantId);
        if ($scope === null) {
            return response()->json(['error' => 'Provide product_id or variant_id'], 422);
        }

        $count = $this->shopifyAddToCart->getCount($site, $scope, $productId, $variantId, $blockId);

        return response()->json(['count' => $count]);
    }

    /**
     * POST add-to-cart: increment count and return new count.
     */
    public function addToCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_key' => ['required', 'string', 'max:64'],
            'block_id' => ['nullable', 'integer', 'exists:blocks,id'],
            'product_id' => ['nullable', 'string', 'max:64'],
            'variant_id' => ['nullable', 'string', 'max:64'],
            'page_url' => ['required', 'string', 'max:2048'],
            'product_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $site = Site::where('site_key', $validated['site_key'])
            ->where('status', Site::STATUS_ACTIVE)
            ->first();

        if (! $site) {
            return response()->json(['error' => 'Site not found or inactive'], 404);
        }

        $blockId = $validated['block_id'] ?? null;
        if ($blockId !== null) {
            $block = $site->blocks()->find($blockId);
            if (! $block) {
                return response()->json(['error' => 'Block not found for this site'], 422);
            }
        }

        $productId = $validated['product_id'] ?? null;
        $variantId = $validated['variant_id'] ?? null;
        $scope = $this->resolveScope($productId, $variantId);
        if ($scope === null) {
            return response()->json(['error' => 'Provide product_id or variant_id'], 422);
        }

        $count = $this->shopifyAddToCart->incrementCount(
            $site,
            $blockId,
            $scope,
            $productId,
            $variantId,
            $validated['page_url'],
            $validated['product_slug'] ?? null
        );

        return response()->json(['count' => $count]);
    }

    private function resolveScope(?string $productId, ?string $variantId): ?string
    {
        if ($productId !== null && $productId !== '') {
            return AddToCartCount::SCOPE_PRODUCT;
        }
        if ($variantId !== null && $variantId !== '') {
            return AddToCartCount::SCOPE_VARIANT;
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WishlistController extends Controller
{
    /**
     * Get the user's wishlist items.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $wishlists = Wishlist::where('user_id', $user->id)
                ->with(['product' => function ($query) {
                    $query->select([
                        'id',
                        'name',
                        'slug',
                        'sku',
                        'description',
                        'base_price',
                        'status',
                        'available_stock',
                    ]);
                }, 'product.media'])
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($wishlist) {
                    return [
                        'id' => $wishlist->id,
                        'product_id' => $wishlist->product_id,
                        'product' => $wishlist->product ? [
                            'id' => $wishlist->product->id,
                            'name' => $wishlist->product->name,
                            'slug' => $wishlist->product->slug,
                            'sku' => $wishlist->product->sku,
                            'short_description' => $wishlist->product->description,
                            'price' => $wishlist->product->base_price,
                            'image' => $wishlist->product->media->first()?->getUrl('thumb'),
                            'in_stock' => $wishlist->product->available_stock > 0,
                            'status' => $wishlist->product->status,
                        ] : null,
                        'added_at' => $wishlist->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Wishlist retrieved successfully.',
                'data' => $wishlists,
                'count' => $wishlists->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching wishlist: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Add a product to the wishlist.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ], [
            'product_id.required' => 'Product ID is required.',
            'product_id.exists' => 'Product not found.',
        ]);

        try {
            $user = $request->user();
            $productId = $request->product_id;

            // Check if already in wishlist
            $existing = Wishlist::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product is already in your wishlist.',
                    'data' => [
                        'id' => $existing->id,
                        'product_id' => $productId,
                    ],
                    'already_exists' => true,
                ]);
            }

            $wishlist = Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist.',
                'data' => [
                    'id' => $wishlist->id,
                    'product_id' => $productId,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error adding to wishlist: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'product_id' => $request->product_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add to wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove a product from the wishlist.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $wishlist = Wishlist::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$wishlist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wishlist item not found.',
                ], 404);
            }

            $wishlist->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing from wishlist: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'wishlist_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove from wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Toggle a product in the wishlist.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        try {
            $user = $request->user();
            $productId = $request->product_id;

            $existing = Wishlist::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if ($existing) {
                $existing->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Product removed from wishlist.',
                    'action' => 'removed',
                    'is_wishlisted' => false,
                ]);
            }

            $wishlist = Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist.',
                'action' => 'added',
                'is_wishlisted' => true,
                'data' => [
                    'id' => $wishlist->id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling wishlist: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'product_id' => $request->product_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Sync wishlist from local storage (bulk add).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['integer'],
        ]);

        try {
            $user = $request->user();
            $productIds = $request->product_ids;

            // Get valid product IDs
            $validProductIds = Product::whereIn('id', $productIds)
                ->pluck('id')
                ->toArray();

            // Get existing wishlisted product IDs
            $existingProductIds = Wishlist::where('user_id', $user->id)
                ->whereIn('product_id', $validProductIds)
                ->pluck('product_id')
                ->toArray();

            // Filter out already wishlisted products
            $newProductIds = array_diff($validProductIds, $existingProductIds);

            // Bulk insert new items
            $inserts = [];
            $now = now();
            foreach ($newProductIds as $productId) {
                $inserts[] = [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($inserts)) {
                DB::table('wishlists')->insert($inserts);
            }

            // Get updated count
            $totalCount = Wishlist::where('user_id', $user->id)->count();

            return response()->json([
                'success' => true,
                'message' => 'Wishlist synced successfully.',
                'data' => [
                    'added_count' => count($newProductIds),
                    'total_count' => $totalCount,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing wishlist: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Check if products are in the wishlist.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['integer'],
        ]);

        try {
            $user = $request->user();

            $wishlistedProductIds = Wishlist::where('user_id', $user->id)
                ->whereIn('product_id', $request->product_ids)
                ->pluck('product_id')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $wishlistedProductIds,
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking wishlist: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Get wishlist count.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function count(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $count = Wishlist::where('user_id', $user->id)->count();

            return response()->json([
                'success' => true,
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting wishlist count: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get wishlist count.',
            ], 500);
        }
    }

    /**
     * Clear all items from wishlist.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            Wishlist::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Wishlist cleared successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing wishlist: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Move wishlist item to cart.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function moveToCart(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $wishlist = Wishlist::where('user_id', $user->id)
                ->where('id', $id)
                ->with('product')
                ->first();

            if (!$wishlist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wishlist item not found.',
                ], 404);
            }

            // Check if product is available
            if (!$wishlist->product || $wishlist->product->available_stock <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is out of stock.',
                ], 422);
            }

            // This would integrate with CartController
            // For now, return the product_id for frontend to handle
            return response()->json([
                'success' => true,
                'message' => 'Add product to cart.',
                'data' => [
                    'product_id' => $wishlist->product_id,
                    'product' => [
                        'id' => $wishlist->product->id,
                        'name' => $wishlist->product->name,
                        'price' => $wishlist->product->base_price,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error moving to cart: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'wishlist_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to move to cart. Please try again.',
            ], 500);
        }
    }
}

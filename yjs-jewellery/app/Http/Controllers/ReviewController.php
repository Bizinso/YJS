<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Get all reviews for a product (public endpoint).
     */
    public function productReviews(int $productId, Request $request): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $query = $product->approvedReviews()
            ->with(['user:id,name'])
            ->orderBy('created_at', 'desc');

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $reviews = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews->items(),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
                'summary' => [
                    'average_rating' => $product->average_rating,
                    'review_count' => $product->review_count,
                    'rating_distribution' => $product->rating_distribution,
                ],
            ],
        ]);
    }

    /**
     * Get current user's reviews.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Review::where('user_id', $user->id)
            ->with(['product:id,name,slug,main_image', 'order:id,order_number'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 10);
        $reviews = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Check if user can review a product.
     */
    public function canReview(int $productId): JsonResponse
    {
        $user = Auth::user();
        $product = Product::findOrFail($productId);

        // Check if user has purchased and received this product
        $eligibleOrder = Order::where('customer_id', $user->id)
            ->where('order_status', 'delivered')
            ->whereHas('items', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->first();

        if (!$eligibleOrder) {
            return response()->json([
                'success' => true,
                'can_review' => false,
                'reason' => 'You can only review products you have purchased and received.',
            ]);
        }

        // Check if user already reviewed this product for this order
        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('order_id', $eligibleOrder->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => true,
                'can_review' => false,
                'reason' => 'You have already reviewed this product for this order.',
                'existing_review_id' => $existingReview->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'can_review' => true,
            'order_id' => $eligibleOrder->id,
        ]);
    }

    /**
     * Store a new review.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        // Verify user owns this order and it's delivered
        $order = Order::where('id', $request->order_id)
            ->where('customer_id', $user->id)
            ->where('order_status', 'delivered')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order or order not yet delivered.',
            ], 422);
        }

        // Verify product is in this order
        $hasProduct = $order->items()->where('product_id', $request->product_id)->exists();

        if (!$hasProduct) {
            return response()->json([
                'success' => false,
                'message' => 'This product is not part of the specified order.',
            ], 422);
        }

        // Check for existing review
        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('order_id', $request->order_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product for this order.',
            ], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'order_id' => $request->order_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully. It will be visible after approval.',
            'data' => $review,
        ], 201);
    }

    /**
     * Update an existing review.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $review = Review::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        // Only allow updating pending or rejected reviews
        if ($review->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update an approved review. Please contact support.',
            ], 422);
        }

        $request->validate([
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update([
            'rating' => $request->input('rating', $review->rating),
            'comment' => $request->input('comment', $review->comment),
            'status' => 'pending', // Reset to pending for re-approval
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully. It will be visible after approval.',
            'data' => $review->fresh(),
        ]);
    }

    /**
     * Delete a review.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();

        $review = Review::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ]);
    }

    /**
     * Get products awaiting review (delivered but not reviewed).
     */
    public function pendingReviews(): JsonResponse
    {
        $user = Auth::user();

        // Get all delivered orders with their items
        $deliveredOrders = Order::where('customer_id', $user->id)
            ->where('order_status', 'delivered')
            ->with(['items.product:id,name,slug,main_image'])
            ->get();

        $pendingReviews = [];

        foreach ($deliveredOrders as $order) {
            foreach ($order->items as $item) {
                // Check if this product has already been reviewed for this order
                $reviewed = Review::where('user_id', $user->id)
                    ->where('product_id', $item->product_id)
                    ->where('order_id', $order->id)
                    ->exists();

                if (!$reviewed && $item->product) {
                    $pendingReviews[] = [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_date' => $order->created_at,
                        'product_id' => $item->product_id,
                        'product' => $item->product,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $pendingReviews,
            'count' => count($pendingReviews),
        ]);
    }

    /**
     * Get a single review by ID.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $review = Review::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['product:id,name,slug,main_image', 'order:id,order_number'])
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $review,
        ]);
    }
}

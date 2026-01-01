<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $userId = auth('customer')->id();

        $cartItems = Cart::with(['product.charges', 'product.taxCharges'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get();

        $cartSubtotal = $cartItems->sum('product_base_price');
        $carttotal = $cartItems->sum('cart_total');
       // $cartTotalDiscount = $cartItems->sum('item_total_discount');
        $totalChargesAll = $cartItems->sum('charges_total');
        $totalTaxesAll = $cartItems->sum('tax_total');

        return response()->json([
            'data' => $cartItems->values(),
            'cart_subtotal' => $cartSubtotal,
            'carttotal' => $carttotal,
            'total_charges' => $totalChargesAll,
            'total_taxes' => $totalTaxesAll,
        ]);
    }

    public function store(Request $request)
    {
       
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'action'     => 'nullable|in:add,buy_now',
            'cod'        => 'nullable|boolean'
        ]);

        $userId = auth('customer')->id();
        $product = Product::with(['charges', 'taxCharges'])->findOrFail($validated['product_id']);

        // STEP 1: Calculate Product Charges
        $totalCharges = $this->calculateProductCharges($product);

        // STEP 2: Taxes
        $totalTaxes = $product->taxCharges->sum('amount');

  
        
        $finalPricePerUnit = $product->base_price + $totalCharges + $totalTaxes;
        $cartItem = null;
        // STEP 4: Check if cart item already exists
        if($request->has('buynow')){
            
        }else{
            $cartItem = Cart::where('user_id', $userId)
                ->where('product_id', $validated['product_id'])
                ->first();
        }
       

        $product = Product::select('available_stock as remaining_stock')->find($validated['product_id']);

        if (!$product) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        // Stock check
        $requestedQty = $validated['quantity'];
        $currentQtyInCart = $cartItem ? $cartItem->quantity : 0;
        $totalRequestedQty = $currentQtyInCart + $requestedQty;

        if ($totalRequestedQty > $product->remaining_stock) {
            // If user already has the product in cart
            if ($cartItem) {
                return response()->json([
                    'error' => 'Only ' . $product->remaining_stock . ' left in stock! You already have this product in your cart.'
                ], 400);
            } else {
                return response()->json([
                    'error' => 'Only ' . $product->remaining_stock . ' left in stock!'
                ], 400);
            }
        }

        // Proceed if stock is available
        if ($cartItem != null) {
            $cartItem->quantity = $totalRequestedQty;
        } else {
            $cartItem = new Cart();
            $cartItem->user_id = $userId;
            $cartItem->product_id = $validated['product_id'];
            $cartItem->quantity = $requestedQty;
        }

        // Update price-related fields
       // $cartItem->applied_offers = json_encode($appliedOffers);
       // $cartItem->total_discount = $totalDiscount;
        $cartItem->product_base_price = $product->base_price;
        $cartItem->charges_total = $totalCharges;
        $cartItem->tax_total = $totalTaxes;
        $cartItem->final_price = $finalPricePerUnit;
        $cartItem->cart_total = $finalPricePerUnit * $cartItem->quantity;

        $cartItem->save();
       // $this->revalidateAppliedOffers($userId);
        return response()->json([
            'message' => 'Product added to cart successfully!',
            'cart' => $cartItem
        ]);

        

        // STEP 6: Return updated cart summary
        return response()->json([
            'status'          => true,
            'message'         => ($validated['action'] ?? null) === 'buy_now' ? 'Proceed to checkout' : 'Item added to cart',
            'data'            => $cartItem,
            'applied_offers'  => $appliedOffers,
            'total_discount'  => 0,
            'charges_total'   => $totalCharges,
            'tax_total'       => $totalTaxes,
            'final_price'     => $finalPricePerUnit,
            'cart_total'      => $cartItem->cart_total,
            'cart_grand_total'=> $this->calculateCartTotal($userId),
        ]);
    }

    private function calculateProductCharges($product)
    {
        $basePrice = (float) $product->base_price;
        $totalCharges = 0;

        foreach ($product->charges as $charge) {
            $chargeBase = $basePrice;

            // Metal Weight Cost
            if ($charge->primary_cost === 'Metal Weight Cost') {
                $metalBasePrice = DB::table('metal_types')
                    ->where('metal_name_id', $product->material_type_id)
                    ->where('purity_id', $product->purity_karat_id)
                    ->value('price_per_gram') ?? 0;

                $chargeBase = $metalBasePrice * (float) $product->metal_weight;
            }

            // Apply charge type
            if ($charge->type === "Percentage (%)") {
                $totalCharges += ($chargeBase * $charge->value) / 100;
            } else {
                $totalCharges += (float)$charge->value;
            }
        }

        return round($totalCharges, 2);
    }

    private function calculateCartTotal($userId)
    {
        return Cart::where('user_id', $userId)->sum('cart_total');
    }

    public function syncFromLocalStorage(Request $request)
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            $userId = auth('customer')->id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $synced = 0;
            $errors = [];

        foreach ($request->items as $item) {
            try {
                // Use existing store logic but with direct product_id and quantity
                $product = Product::with(['charges', 'taxCharges'])->findOrFail($item['product_id']);

                // Calculate charges
                $totalCharges = $this->calculateProductCharges($product);
                $totalTaxes = $product->taxCharges->sum('amount');

                // Apply offers
                // $offerData = OfferHelper::applyProductOffers($product, []);
                // $discountedPricePerUnit = $offerData['final_price'];
                // $totalDiscount = $offerData['total_discount'];
                // $appliedOffers = $offerData['applied_offers'];

                $finalPricePerUnit = $product->base_price + $totalCharges + $totalTaxes;

                // Check if item already exists
                $cartItem = Cart::where('user_id', $userId)
                    ->where('product_id', $item['product_id'])
                    ->first();

                // Stock check
                $productStock = Product::select('available_stock as remaining_stock')->find($item['product_id']);
                if (!$productStock) {
                    $errors[] = ['product_id' => $item['product_id'], 'error' => 'Product not found'];
                    continue;
                }

                $requestedQty = $item['quantity'];
                $currentQtyInCart = $cartItem ? $cartItem->quantity : 0;
                $totalRequestedQty = $currentQtyInCart + $requestedQty;

                if ($totalRequestedQty > $productStock->remaining_stock) {
                    // Adjust quantity to available stock
                    $requestedQty = max(0, $productStock->remaining_stock - $currentQtyInCart);
                    if ($requestedQty <= 0) {
                        $errors[] = ['product_id' => $item['product_id'], 'error' => 'Insufficient stock'];
                        continue;
                    }
                }

                // Update or create cart item
                if ($cartItem != null) {
                    $cartItem->quantity = $totalRequestedQty;
                } else {
                    $cartItem = new Cart();
                    $cartItem->user_id = $userId;
                    $cartItem->product_id = $item['product_id'];
                    $cartItem->quantity = $requestedQty;
                }

                // Update price fields
                // $cartItem->applied_offers = json_encode($appliedOffers);
                $cartItem->product_base_price = $product->base_price;
                $cartItem->total_discount = 0;
                $cartItem->charges_total = $totalCharges;
                $cartItem->tax_total = $totalTaxes;
                $cartItem->final_price = $finalPricePerUnit;
                $cartItem->cart_total = $finalPricePerUnit * $cartItem->quantity;
                $cartItem->save();

                $synced++;
            } catch (\Exception $e) {
                $errors[] = [
                    'product_id' => $item['product_id'] ?? null,
                    'error' => $e->getMessage()
                ];
            }
        }

            // Revalidate offers after sync
            if ($synced > 0) {
               // $this->revalidateAppliedOffers($userId);
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$synced} item(s) to cart",
                'synced' => $synced,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync cart: ' . $e->getMessage(),
                'synced' => 0,
                'errors' => [],
            ], 500);
        }
    }


    public function cartCount(Request $request)
    {
        $userId = auth('customer')->id();

        $cart = Cart::where('user_id', $userId)
        ->whereNull('deleted_at')
        ->count();

        return response()->json([
            'success' => true,
            'count' => $cart
        ]);
    }

}


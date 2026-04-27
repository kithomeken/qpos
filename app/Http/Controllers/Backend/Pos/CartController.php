<?php

namespace App\Http\Controllers\Backend\Pos;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\PaymentMethods;
use App\Models\PosCart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $cartItems = PosCart::where('user_id', auth()->id())
                ->with('product')
                ->latest('created_at')
                ->get()
                ->map(function ($item) {
                    // Access the product's computed attribute (getDiscountedPriceAttribute)
                    $discountedPrice = $item->product->discounted_price;

                    // Inject brand name directly into the item for easier React access
                    $item->brand = $item->product->brand->name;
                    $item->currency = currency()->symbol;

                    // Calculate row total
                    $item->row_total = round(($item->quantity * $discountedPrice), 2);

                    return $item;
                });

            $total = $cartItems->sum('row_total');

            $paymentMethods = PaymentMethods::selectRaw('name, code, surcharge_type, surcharge_value, primary_method as primary')
                ->where('active', true)
                ->orderBy('primary', 'desc')
                ->get();

            return response()->json([
                'carts'     => $cartItems,
                'total'     => round($total, 2),
                'wallets'   => $paymentMethods,
                'currency'  => currency()->symbol,
            ]);
        }

        // Do not clear cart. Let user do this manually
        // PosCart::where('user_id', auth()->id())->delete();

        return view('backend.cart.index');
    }

    public function getProducts(Request $request)
    {
        // Select product with brand names
        $products = Product::query()->active()->stocked()
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->select([
                'products.*',
                'brands.name as brand'
            ]);

        // Single search query to return the same data set
        $products->where(function ($query) use ($request) {
            $search = "%{$request->search}%";

            $query->where('products.name', 'ILIKE', $search)
                ->orWhere('products.sku', 'ILIKE', $search)
                ->orWhere('brands.name', 'ILIKE', $search)
                ->orWhere('products.sku', 'ILIKE', $search);
        });

        // Return a list of 5 products that match to reduce load time
        // and improve UI/UX
        $products = $products->latest()->paginate(5)->map(function ($product) {
            // Add default currency
            $product->currency = currency()->symbol;
            return $product;
        });

        if (request()->wantsJson()) {
            return ProductResource::collection($products);
        }
    }

    public function store(Request $request)
    {
        // Validate request input
        $request->validate([
            'id' => 'required|exists:products,id',
        ]);

        $product_id = $request->id;

        // Fetch the product
        $product = Product::find($product_id);

        // Check if the product is active and has sufficient stock
        if (!$product->status) {
            return response()->json(['message' => 'Product is not available'], 400);
        }

        if ($product->quantity <= 0) {
            return response()->json(['message' => 'Insufficient stock available'], 400);
        }

        // Fetch the cart item for the current user and product
        $cartItem = PosCart::where('user_id', auth()->id())->where('product_id', $product_id)->first();

        if ($cartItem) {
            // If the product is already in the cart, increment the quantity
            if ($cartItem->quantity < $product->quantity) {
                $cartItem->quantity += 1;
                $cartItem->save();
                return response()->json(['message' => 'Quantity updated', 'quantity' => $cartItem->quantity], 200);
            } else {
                return response()->json(['message' => 'Cannot add more, stock limit reached'], 400);
            }
        } else {
            // If not in the cart, create a new cart item
            $cart = new PosCart();
            $cart->user_id = auth()->id();
            $cart->product_id = $product_id;
            $cart->quantity = 1;
            $cart->save();
            return response()->json(['message' => 'Product added to cart', 'quantity' => 1], 201);
        }
    }

    public function increment(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:pos_carts,id'
        ]);

        $cart = PosCart::with('product')->findOrFail($request->id);
        if ($cart->product->quantity <= 0) {
            return response()->json(['message' => 'Insufficient stock available'], 400);
        }
        if ($cart->quantity == $cart->product->quantity) {
            return response()->json(['message' => 'Cannot add more, stock limit reached'], 400);
        }
        $cart->quantity = $cart->quantity + 1;
        $cart->save();
        return response()->json(['message' => 'Cart Updated successfully'], 200);
    }

    public function decrement(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:pos_carts,id'
        ]);
        $cart = PosCart::findOrFail($request->id);
        if ($cart->quantity <= 1) {
            return response()->json(['message' => 'Quantity cannot be less than 1.'], 400);
        }
        $cart->quantity = $cart->quantity - 1;
        $cart->save();
        return response()->json(['message' => 'Cart Updated successfully'], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:pos_carts,id'
        ]);

        $cart = PosCart::findOrFail($request->id);
        $cart->delete();

        return response()->json(['message' => 'Item successfully deleted'], 200);
    }
    
    public function empty()
    {
        $deletedCount = PosCart::where('user_id', auth()->id())->delete();

        if ($deletedCount > 0) {
            return response()->json(['message' => 'Cart successfully cleared'], 200);
        }

        return response()->json(['message' => 'Cart is already empty'], 204);
    }
}

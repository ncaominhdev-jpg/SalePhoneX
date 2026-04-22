<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Inventory;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Lấy danh sách giỏ hàng
    public function index()
    {
        return response()->json(Cart::all());
    }

    // Lấy danh sách giỏ hàng theo user_id
    public function getByUserId($userId)
    {
        $carts = Cart::where('user_id', $userId)->get();
        return response()->json($carts);
    }

    // Tạo mới giỏ hàng
   public function store(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|integer|exists:users,id',
        'product_variant_id' => 'required|integer|exists:product_variants,id',
        'quantity' => 'required|integer|min:1',
    ]);

    // Lấy tổng tồn kho của biến thể
    $stockQty = Inventory::where('product_variant_id', $validated['product_variant_id'])->sum('quantity');

    // Tìm xem user đã có sản phẩm này trong giỏ chưa
    $cart = Cart::where('user_id', $validated['user_id'])
        ->where('product_variant_id', $validated['product_variant_id'])
        ->first();

    if ($cart) {
        // Nếu đã có thì cộng dồn số lượng
        $newQty = $cart->quantity + $validated['quantity'];
        if ($newQty > $stockQty) {
            return response()->json(['message' => 'Số lượng vượt quá tồn kho'], 400);
        }
        $cart->update(['quantity' => $newQty]);
    } else {
        // Nếu chưa có thì tạo mới
        if ($validated['quantity'] > $stockQty) {
            return response()->json(['message' => 'Số lượng vượt quá tồn kho'], 400);
        }
        $cart = Cart::create($validated);
    }

    return response()->json($cart, 201);
}


    // Xem chi tiết giỏ hàng
    public function show($id)
    {
        $cart = Cart::find($id);
        if (!$cart) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($cart);
    }

    // Cập nhật giỏ hàng
    public function update(Request $request, $id)
    {
        $cart = Cart::find($id);
        if (!$cart) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'user_id' => 'sometimes|integer|exists:users,id',
            'product_variant_id' => 'sometimes|integer|exists:product_variants,id',
            'quantity' => 'sometimes|integer|min:1',
        ]);

        // Lấy tổng tồn kho của biến thể
        $productVariantId = $validated['product_variant_id'] ?? $cart->product_variant_id;
        $quantity = $validated['quantity'] ?? $cart->quantity;
        $stockQty = Inventory::where('product_variant_id', $productVariantId)->sum('quantity');

        if ($quantity > $stockQty) {
            return response()->json(['message' => 'Số lượng vượt quá tồn kho'], 400);
        }

        $cart->update($validated);
        return response()->json($cart);
    }

    // Xóa giỏ hàng
    public function destroy($id)
    {
        $cart = Cart::find($id);
        if (!$cart) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $cart->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

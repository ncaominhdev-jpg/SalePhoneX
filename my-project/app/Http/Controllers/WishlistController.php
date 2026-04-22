<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    // Lấy danh sách wishlist
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $wishlists = Wishlist::with('product')
            ->where('user_id', $request->user_id)
            ->get();

        // map thêm URL ảnh đầy đủ
        $wishlists->map(function ($item) {
            if ($item->product && $item->product->image) {
                $item->product->image_url = asset('storage/' . $item->product->image);
            } else {
                $item->product->image_url = asset('fallback.png'); // fallback nếu thiếu ảnh
            }
            return $item;
        });

        return response()->json($wishlists);
    }

    // Thêm sản phẩm vào wishlist
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $request->user_id,
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'message' => 'Đã thêm vào yêu thích',
            'data' => $wishlist->load('product')
        ]);
    }

    // Xóa khỏi wishlist
    public function destroy(Request $request, $productId)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        Wishlist::where('user_id', $request->user_id)
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['message' => 'Đã xóa khỏi yêu thích']);
    }
}

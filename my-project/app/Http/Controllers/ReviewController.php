<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\User;
use App\Models\Order;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;


class ReviewController extends Controller
{
    // Lấy danh sách review của sản phẩm
    public function index($productId)
    {
        $reviews = Review::with('user')
            ->where('product_id', $productId)
            ->latest()
            ->get();

        $average = Review::where('product_id', $productId)->avg('rating');

        return response()->json([
            'reviews' => $reviews,
            'average' => round($average, 1)
        ]);
    }

    // Gửi đánh giá
    // use Illuminate\Validation\Rule;

    public function store(Request $request, $productId)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        if (!Product::whereKey($productId)->exists()) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'rating'             => ['required', 'integer', 'min:1', 'max:5'],
            'comment'            => ['nullable', 'string', 'max:2000'],
            'order_id'           => ['required', 'integer', 'exists:orders,id'],
            'product_variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $productId),
            ],
        ]);

        $orderId   = (int) $validated['order_id'];
        $variantId = $validated['product_variant_id'] ?? null;

        // Chọn đúng quan hệ chi tiết đơn: orderDetails hoặc orderItems
        $relation = method_exists(Order::class, 'orderDetails') ? 'orderDetails' : 'orderItems';

        // Đơn thuộc user, đã giao & có chứa đúng sản phẩm/biến thể
        $ok = Order::query()
            ->whereKey($orderId)
            ->where('user_id', $user->id)
            ->where('status', 'delivered')
            ->whereHas($relation, function ($q) use ($productId, $variantId) {
                if ($variantId) {
                    $q->where('product_variant_id', $variantId);
                } else {
                    $q->whereHas('productVariant', fn($qq) => $qq->where('product_id', $productId));
                }
            })
            ->exists();

        if (!$ok) {
            return response()->json([
                'error' => 'Chỉ được đánh giá khi đơn hàng chứa (biến thể của) sản phẩm này đã được giao.'
            ], 403);
        }

        // 🚫 CHẶN BÌNH LUẬN TRÙNG (theo biến thể + theo đơn)
        $already = Review::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('order_id', $orderId)
            ->when(
                $variantId !== null,
                fn($q) => $q->where('product_variant_id', $variantId),
                fn($q) => $q->whereNull('product_variant_id')
            )
            ->exists();

        if ($already) {
            return response()->json([
                'error' => 'Bạn đã đánh giá biến thể này cho đơn này rồi.'
            ], 409);
        }

        try {
            $review = Review::create([
                'user_id'            => $user->id,
                'product_id'         => (int) $productId,
                'product_variant_id' => $variantId, // có thể null
                'order_id'           => $orderId,
                'rating'             => (int) $validated['rating'],
                'comment'            => $validated['comment'] ?? null,
            ]);
        } catch (QueryException $e) {
            // Vi phạm unique → trả 409 thay vì 500
            $isDup = ((int)($e->errorInfo[1] ?? 0) === 1062) || ($e->getCode() == '23000');
            if ($isDup) {
                return response()->json(['error' => 'Bạn đã đánh giá biến thể này cho đơn này rồi.'], 409);
            }
            if (app()->environment('local')) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            report($e);
            return response()->json(['error' => 'Server error'], 500);
        }

        return response()->json($review->load('user'), 201);
    }
}

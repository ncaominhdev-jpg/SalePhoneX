<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    // Lấy danh sách biến thể sản phẩm
    public function index(Request $request)
    {
        $variants = ProductVariant::query();

        if ($request->has('product_id')) {
            $variants->where('product_id', $request->product_id);
        }

        return response()->json($variants->get());
    }

    // Tạo mới biến thể sản phẩm
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'img' => 'nullable|string',
            'status' => 'nullable|integer',
        ]);

        $variant = ProductVariant::create($validated);

        return response()->json($variant, 201);
    }

    // Xem chi tiết biến thể sản phẩm
    public function show($id)
    {
        $variant = ProductVariant::find($id);
        if (!$variant) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($variant);
    }

    // Cập nhật biến thể sản phẩm
    public function update(Request $request, $id)
    {
        $variant = ProductVariant::find($id);
        if (!$variant) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'product_id' => 'sometimes|integer|exists:products,id',
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric',
            'discount' => 'nullable|numeric',
            'img' => 'nullable|string',
            'status' => 'nullable|integer',
        ]);

        $variant->update($validated);

        return response()->json($variant);
    }

    // Xóa biến thể sản phẩm
    public function destroy($id)
    {
        $variant = ProductVariant::find($id);
        if (!$variant) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $variant->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use Illuminate\Http\Request;

class OrderDetailController extends Controller
{
    // Lấy danh sách chi tiết đơn hàng
    public function index()
    {
        return response()->json(OrderDetail::all());
    }

    // Tạo mới chi tiết đơn hàng
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'product_variant_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $orderDetail = OrderDetail::create($validated);
        return response()->json($orderDetail, 201);
    }

    // Xem chi tiết chi tiết đơn hàng
    public function show($id)
    {
        $orderDetail = OrderDetail::find($id);
        if (!$orderDetail) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($orderDetail);
    }

    // Cập nhật chi tiết đơn hàng
    public function update(Request $request, $id)
    {
        $orderDetail = OrderDetail::find($id);
        if (!$orderDetail) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'order_id' => 'sometimes|integer|exists:orders,id',
            'product_variant_id' => 'sometimes|integer',
            'quantity' => 'sometimes|integer|min:1',
        ]);

        $orderDetail->update($validated);
        return response()->json($orderDetail);
    }

    // Xóa chi tiết đơn hàng
    public function destroy($id)
    {
        $orderDetail = OrderDetail::find($id);
        if (!$orderDetail) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $orderDetail->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

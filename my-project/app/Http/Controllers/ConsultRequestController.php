<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConsultRequest;

class ConsultRequestController extends Controller
{
    // Lấy tất cả consult requests kèm user & product variant
    public function index()
    {
        $requests = ConsultRequest::with(['user', 'productVariant'])->orderBy('created_at', 'desc')->get();
        return response()->json($requests);
    }

    // Lấy 1 consult request
    public function show($id)
    {
        $request = ConsultRequest::with(['user', 'productVariant'])->find($id);

        if (!$request) {
            return response()->json(['message' => 'Không tìm thấy'], 404);
        }

        return response()->json($request);
    }

    // Tạo mới consult request
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'company_name' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'product_variant_id' => 'required|integer|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string',
            'receive_promotions' => 'boolean',
            'status' => 'nullable|string|max:50',
        ]);

        $consult = ConsultRequest::create($validated);

        return response()->json($consult, 201);
    }

    // Cập nhật consult request
    public function update(Request $request, $id)
    {
        $consult = ConsultRequest::find($id);
        if (!$consult) {
            return response()->json(['message' => 'Không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'customer_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'product_variant_id' => 'sometimes|integer|exists:product_variants,id',
            'quantity' => 'sometimes|integer|min:1',
            'note' => 'nullable|string',
            'receive_promotions' => 'boolean',
            'status' => 'sometimes|string|max:50',
        ]);

        $consult->update($validated);

        return response()->json($consult);
    }

    // Xoá consult request
    public function destroy($id)
    {
        $consult = ConsultRequest::find($id);
        if (!$consult) {
            return response()->json(['message' => 'Không tìm thấy'], 404);
        }

        $consult->delete();
        return response()->json(['message' => 'Đã xoá thành công']);
    }
}

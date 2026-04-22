<?php

namespace App\Http\Controllers;

use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShippingAddressController extends Controller
{
    // Lấy danh sách địa chỉ của user hiện tại
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Token không hợp lệ.'], 401);
        }

        $addresses = ShippingAddress::where('user_id', $user->id)->get();

        return response()->json($addresses);
    }

    // Thêm địa chỉ mới
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Token không hợp lệ.'], 401);
        }

        $request->validate([
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'ward' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ]);

        if ($request->is_default) {
            // Reset is_default cho các địa chỉ khác
            ShippingAddress::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $address = ShippingAddress::create([
            'user_id' => $user->id,
            'recipient_name' => $request->recipient_name,
            'phone' => $request->phone,
            'address' => $request->address,
            'ward' => $request->ward,
            'city' => $request->city,
            'is_default' => $request->is_default ?? false,
        ]);

        return response()->json([
            'message' => 'Thêm địa chỉ thành công',
            'address' => $address,
        ], 201);
    }

    // Cập nhật địa chỉ
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Token không hợp lệ.'], 401);
        }

        $address = ShippingAddress::where('id', $id)->where('user_id', $user->id)->first();
        if (!$address) {
            return response()->json(['message' => 'Địa chỉ không tồn tại.'], 404);
        }

        $request->validate([
            'recipient_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string|max:500',
            'ward' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ]);

        if ($request->has('is_default') && $request->is_default) {
            // Reset is_default cho các địa chỉ khác
            ShippingAddress::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $address->update($request->only([
            'recipient_name',
            'phone',
            'address',
            'ward',
            'city',
            'is_default',
        ]));

        return response()->json([
            'message' => 'Cập nhật địa chỉ thành công',
            'address' => $address,
        ]);
    }

    // Xóa địa chỉ
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Token không hợp lệ.'], 401);
        }

        $address = ShippingAddress::where('id', $id)->where('user_id', $user->id)->first();
        if (!$address) {
            return response()->json(['message' => 'Địa chỉ không tồn tại.'], 404);
        }

        $address->delete();

        return response()->json([
            'message' => 'Xóa địa chỉ thành công',
        ]);
    }

    // Đặt địa chỉ mặc định
    public function setDefault(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Token không hợp lệ.'], 401);
        }

        $address = ShippingAddress::where('id', $id)->where('user_id', $user->id)->first();
        if (!$address) {
            return response()->json(['message' => 'Địa chỉ không tồn tại.'], 404);
        }

        // Reset is_default cho các địa chỉ khác
        ShippingAddress::where('user_id', $user->id)->update(['is_default' => false]);

        $address->is_default = true;
        $address->save();

        return response()->json([
            'message' => 'Đặt địa chỉ mặc định thành công',
            'address' => $address,
        ]);
    }
}

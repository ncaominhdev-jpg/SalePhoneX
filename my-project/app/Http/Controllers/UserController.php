<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Lấy danh sách users
    public function index()
    {
        return response()->json(User::all());
    }

    // Tạo mới user
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|integer',
            'role' => 'required|string|max:50',
            'branch_id' => 'nullable|integer',
            'password' => 'required|string|min:6',
        ]);

        // Hash password nếu cần bảo mật
        $validated['password'] = bcrypt($validated['password']);

        $user = User::create($validated);
        return response()->json($user, 201);
    }

    // Xem chi tiết user
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($user);
    }

    // Cập nhật user
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|integer',
            'role' => 'sometimes|string|max:50',
            'branch_id' => 'nullable|integer',
            'password' => 'nullable|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);
        return response()->json($user);
    }

    // Xóa user
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
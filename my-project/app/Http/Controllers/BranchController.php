<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    // Lấy danh sách chi nhánh
    public function index()
    {
        return response()->json(Branch::all());
    }

    // Tạo mới chi nhánh
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'status' => 'required|boolean',
            'type' => 'required|string|max:50',
        ]);

        $branch = Branch::create($validated);
        return response()->json($branch, 201);
    }

    // Xem chi tiết chi nhánh
    public function show($id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($branch);
    }

    // Cập nhật chi nhánh
    public function update(Request $request, $id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'address' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'status' => 'sometimes|boolean',
            'type' => 'sometimes|string|max:50',
        ]);

        $branch->update($validated);
        return response()->json($branch);
    }

    // Xóa chi nhánh
    public function destroy($id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $branch->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

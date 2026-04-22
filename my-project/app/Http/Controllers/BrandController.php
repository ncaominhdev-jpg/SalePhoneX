<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    // Lấy danh sách thương hiệu
    public function index()
    {
        $brands = Brand::all()->map(function ($brand) {
            if ($brand->image && !str_starts_with($brand->image, 'http')) {
                $brand->image = url('storage/' . $brand->image);
            }
            return $brand;
        });

        return response()->json($brands);
    }

    // Tạo mới thương hiệu
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|string|max:255',
            'status' => 'required|boolean',
        ]);

        $brand = Brand::create($validated);
        return response()->json($brand, 201);
    }

    // Xem chi tiết thương hiệu
    public function show($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($brand);
    }

    // Cập nhật thương hiệu
    public function update(Request $request, $id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'image' => 'nullable|string|max:255',
            'status' => 'sometimes|boolean',
        ]);

        $brand->update($validated);
        return response()->json($brand);
    }

    // Xóa thương hiệu
    public function destroy($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $brand->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // Lấy danh sách brand theo category_id
    public function getBrandsByCategory($category_id)
    {
        $brands = Brand::whereHas('categories', function ($query) use ($category_id) {
            $query->where('category_id', $category_id);
        })->get();

        return response()->json($brands);
    }
}

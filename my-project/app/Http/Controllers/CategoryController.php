<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Lấy danh sách category
    public function index()
    {
        $categories = Category::all();

        $prefix = '/storage/';

        $categories->transform(function ($category) use ($prefix) {
            if ($category->image) {
                $category->image = url($prefix . $category->image);
            }
            return $category;
        });

        return response()->json($categories);
    }

    // Lọc 5 category đầu tiên
    public function filterFiveCategories()
    {
        $categories = Category::orderBy('id', 'asc')->limit(5)->get();
        return response()->json($categories);
    }

    // Lấy ảnh logo của danh mục theo id
    public function getLogo($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(['image' => $category->image]);
    }

    // Tạo mới category
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|string',
            'status' => 'required|integer',
            'parent_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
        ]);

        $category = Category::create($validated);
        return response()->json($category, 201);
    }

    // Xem chi tiết category
    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($category);
    }

    // Cập nhật category
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'image' => 'nullable|string',
            'status' => 'sometimes|integer',
            'parent_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
        ]);

        $category->update($validated);
        return response()->json($category);
    }

    // Xóa category
    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
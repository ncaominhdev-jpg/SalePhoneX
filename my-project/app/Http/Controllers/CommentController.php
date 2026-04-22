<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Lấy danh sách comment theo product_id, phân cấp theo parent_id
    public function index(Request $request)
    {
        $productId = $request->query('product_id');
        if (!$productId) {
            return response()->json(['message' => 'Thiếu product_id'], 400);
        }

        $comments = Comment::where('product_id', $productId)
            ->whereNull('parent_id')
            ->with(['children', 'user'])
            ->get();

        return response()->json($comments);
    }

    // Thêm comment mới
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'user_id' => 'required|integer',
            'parent_id' => 'nullable|integer',
            'content' => 'required|string|max:1000',
            'status' => 'nullable|integer',
        ]);

        // Tạo mảng dữ liệu trước khi gọi create để tránh lỗi cảnh báo của IDE
        $data = [
            'product_id' => $request->product_id,
            'user_id' => $request->user_id,
            'parent_id' => $request->parent_id,
            'content' => $request->input('content'),
            'status' => $request->status ?? 1,
        ];
        $comment = Comment::create($data);

        return response()->json([
            'message' => 'Thêm bình luận thành công',
            'comment' => $comment,
        ], 201);
    }

    // Cập nhật comment
    public function update(Request $request, $id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json(['message' => 'Bình luận không tồn tại'], 404);
        }

        $request->validate([
            'content' => 'sometimes|required|string',
            'status' => 'sometimes|integer',
        ]);

        $comment->update($request->only(['content', 'status']));

        return response()->json([
            'message' => 'Cập nhật bình luận thành công',
            'comment' => $comment,
        ]);
    }

    // Xóa comment
    public function destroy($id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json(['message' => 'Bình luận không tồn tại'], 404);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Xóa bình luận thành công',
        ]);
    }
}

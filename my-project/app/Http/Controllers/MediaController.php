<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    // 📥 Upload hình ảnh
    public function upload(Request $request, $productId)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|max:2048',
        ]);

        $urls = [];

        foreach ($request->file('images') as $image) {
            $path = $image->store('medias', 'public');

            $media = Media::create([
                'product_id' => $productId,
                'url' => $path,
                'is_thumbnail' => false
            ]);

            $media->url = URL::to('/storage/' . $media->url);
            $urls[] = $media;
        }

        return response()->json($urls);
    }

    // 🖼️ Lấy danh sách hình ảnh theo product_id
    public function index($productId)
    {
        $media = Media::where('product_id', $productId)->get()->map(function ($item) {
            // cột url trong DB chứa "medias/xxx.jpg" hoặc "1753727...jpg"
            $item->url = asset('storage/' . ltrim($item->url, '/'));
            return $item;
        });

        return response()->json($media);
    }


    // ❌ Xóa hình ảnh
    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        if (Storage::disk('public')->exists($media->url)) {
            Storage::disk('public')->delete($media->url);
        }

        $media->delete();

        return response()->json(['message' => 'Đã xóa hình ảnh.']);
    }

    // ✅ Cập nhật ảnh thumbnail
    public function updateThumbnail($id)
    {
        $media = Media::findOrFail($id);

        DB::table('media')
            ->where('product_id', $media->product_id)
            ->update(['is_thumbnail' => false]);

        $media->is_thumbnail = true;
        $media->save();

        return response()->json(['message' => 'Cập nhật ảnh thumbnail thành công.']);
    }
}

<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class ProductObserver
{
    public function created(Product $product)
    {
        $this->handleMedia($product);
    }

    public function updated(Product $product)
    {
        $this->handleMedia($product);
    }

    public function deleted(Product $product)
    {
        // Xóa các file media khi xóa product
        $product->media()->each(function ($media) {
            Storage::disk('public')->delete($media->url);
            $media->delete();
        });
    }

    protected function handleMedia(Product $product)
    {
        // Xử lý thumbnail
     if (!empty($product->thumbnail)) {
    // Lấy path tương đối (bỏ 'storage/' nếu có)
    $thumbnailPath = str_replace('storage/', '', $product->thumbnail);
    
    // Xóa thumbnail cũ
    $product->media()->where('is_thumbnail', true)->get()->each(function ($media) {
        Storage::disk('public')->delete($media->url);
        $media->delete();
    });
    
    // Tạo bản ghi mới
    Media::updateOrCreate(
        [
            'product_id' => $product->id,
            'is_thumbnail' => true
        ],
        [
            'url' => $thumbnailPath
        ]
    );
}

        // Xử lý gallery
        if (!empty($product->gallery)) {
    // Chuẩn hóa paths (bỏ 'storage/' nếu có)
    $galleryPaths = array_map(function ($path) {
        return str_replace('storage/', '', $path);
    }, $product->gallery);
    
    // Lấy danh sách ảnh hiện tại
    $currentMedia = $product->media()->where('is_thumbnail', false)->get();
    
    // Xóa các ảnh không còn trong gallery
    $currentMedia->each(function ($media) use ($galleryPaths) {
        if (!in_array($media->url, $galleryPaths)) {
            Storage::disk('public')->delete($media->url);
            $media->delete();
        }
    });
    
    // Thêm ảnh mới
    $existingPaths = $currentMedia->pluck('url')->toArray();
    foreach (array_diff($galleryPaths, $existingPaths) as $newImage) {
        Media::create([
            'product_id' => $product->id,
            'url' => $newImage,
            'is_thumbnail' => false,
        ]);
    }
}
    }
}
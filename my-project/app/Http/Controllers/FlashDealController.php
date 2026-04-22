<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FlashDeal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FlashDealController extends Controller
{
    /** Múi giờ mặc định VN */
    private string $tz = 'Asia/Ho_Chi_Minh';

    public function index(Request $r)
    {
        $tz = $this->tz;

        $deals = FlashDeal::with([
                'items.product.category',
                'items.product.media',
                'items.variant', // Thêm quan hệ variant
            ])
            // ->active()  // bỏ lọc active nếu muốn load tất cả
            ->whereDate('deal_date', '>=', Carbon::now($tz)->toDateString())
            ->orderBy('deal_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($d) => Carbon::parse($d->deal_date, $tz)->toDateString())
            ->map(fn ($grp) => $grp->map(fn ($d) => $this->mapDealSafe($d)))
            ->toArray();

        return response()->json(['data' => $deals]);
    }

    public function active(Request $r)
    {
        $tz  = $this->tz;
        $now = Carbon::now($tz);

        $today = FlashDeal::with([
                'items.product.category',
                'items.product.media',
                'items.variant', // Thêm quan hệ variant
            ])
            // ->active() // bỏ lọc giờ, chỉ lọc ngày hôm nay
            ->whereDate('deal_date', $now->toDateString())
            ->orderBy('start_time')
            ->get();

        // Lấy deal đầu tiên trong ngày hôm nay, không cần check giờ
        $current = $today->first();

        // Upcoming: các deal từ ngày mai trở đi
        $upcoming = FlashDeal::with([
                'items.product.category',
                'items.product.media',
                'items.variant', // Thêm quan hệ variant
            ])
            ->whereDate('deal_date', '>', $now->toDateString())
            ->orderBy('deal_date')
            ->orderBy('start_time')
            ->get()
            ->values();

        return response()->json([
            'current'  => $current ? $this->mapDealSafe($current) : null,
            'upcoming' => $upcoming->map(fn ($d) => $this->mapDealSafe($d))->values(),
        ]);
    }

    private function mapDealSafe(FlashDeal $d): array
    {
        $tz = $this->tz;

        $dealDate = $d->deal_date instanceof Carbon
            ? $d->deal_date->setTimezone($tz)->toDateString()
            : Carbon::parse($d->deal_date, $tz)->toDateString();

        $start = Carbon::parse("$dealDate {$d->start_time}", $tz);
        $end   = Carbon::parse("$dealDate {$d->end_time}",   $tz);

        return [
            'id'           => (int) $d->id,
            'date'         => $dealDate,
            'label'        => $start->format('H\h') . ' - ' . $end->format('H\h d/m'),
            'start'        => $start->toIso8601String(),          // ISO kèm TZ VN
            'end'          => $end->toIso8601String(),
            'start_at_ms'  => $start->getTimestampMs(),           // epoch ms — FE dùng cái này
            'end_at_ms'    => $end->getTimestampMs(),
            'items'        => $d->items
                ->filter(fn ($it) => $it->product)
                ->map(function ($it) {
                    $p = $it->product;
                    $v = $it->variant; // biến thể sản phẩm

                    return [
                        'id'                  => (int) $it->id,
                        'name'                => (string) ($p->name ?? 'Sản phẩm'),
                        'image'               => $this->resolveProductImage($p),
                        'slug'                => $p->slug ?? null,
                        'category_slug'       => optional($p->category)->slug,
                        'product_slug'        => $p->slug ?? null,
                        'product_variant_id'  => $it->product_variant_id, // ID biến thể
                        'variant_name'        => $v->name ?? null, // Tên biến thể
                        'badges'              => is_string($it->badges) ? (json_decode($it->badges, true) ?: []) : ($it->badges ?? []),
                        'note'                => $it->note,
                        'price_sale'          => (float) $it->price_sale,
                        'price_list'          => (float) $it->price_list,
                        'sold'                => (int) $it->sold,
                        'quota'               => (int) $it->stock_quota,
                    ];
                })
                ->values(),
        ];
    }

    /** Ưu tiên thumbnail media -> media đầu -> cột ảnh của product, chuẩn hoá URL */
    private function resolveProductImage($product): string
    {
        $media = $product->relationLoaded('media') ? $product->media : collect();
        $thumb = $media->firstWhere('is_thumbnail', true)?->url;
        $firstMedia = $thumb ?: ($media->first()?->url ?? null);

        $raw = $firstMedia
            ?: ($product->image ?? null)
            ?: ($product->thumbnail ?? null)
            ?: ($product->image_url ?? null)
            ?: ($product->thumbnail_url ?? null);

        if (!$raw) {
            return asset('images/placeholder.webp');
        }

        if (Str::startsWith($raw, ['http://', 'https://', '//'])) {
            return $raw;
        }

        $path = ltrim(str_replace('storage/', '', $raw), '/');
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset($raw);
    }
}

<?php

namespace Database\Seeders;

use App\Models\{FlashDeal, FlashDealItem, Product};
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FlashDealSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today()->toDateString();
        $slots = [
            ['date'=>$today, 'start'=>'09:00', 'end'=>'11:00'],
            ['date'=>Carbon::tomorrow()->toDateString(), 'start'=>'09:00', 'end'=>'11:00'],
        ];

        foreach ($slots as $slot) {
            $deal = FlashDeal::create([
                'deal_date'  => $slot['date'],
                'start_time' => $slot['start'],
                'end_time'   => $slot['end'],
                'is_active'  => true,
            ]);

            // Lấy 5 laptop bất kỳ (đổi category_id cho đúng schema của bạn)
            $laptops = Product::where('category_id', 1)->inRandomOrder()->take(5)->get();

            foreach ($laptops as $p) {
                FlashDealItem::create([
                    'flash_deal_id' => $deal->id,
                    'product_id'    => $p->id,
                    'stock_quota'   => 5,
                    'sold'          => 0,
                    'price_sale'    => max(8000000, $p->price * 0.85),
                    'price_list'    => $p->price,
                    'badges'        => ['i5-13420H','RTX 4050','16GB','512GB','15.6" FHD'],
                    'note'          => 'Nhập khẩu chính hãng',
                ]);
            }
        }
    }
}

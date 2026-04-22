<?php
namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderStatusService
{
    public function confirmPaidOrder(Order $order)
    {
        try {
            DB::beginTransaction();
            
            $order->status = 'confirmed';
            $order->save();
            
            if (!$order->inventory_decreased) {
                if (app(InventoryService::class)->decreaseOrderInventory($order)) {
                    $order->updateQuietly(['inventory_decreased' => true]);
                } else {
                    throw new \Exception('Không đủ tồn kho');
                }
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Lỗi xác nhận đơn #{$order->id}: " . $e->getMessage());
            return false;
        }
    }
}
<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    public static function increase($warehouseId, $productVariantId, $quantity)
    {
        try {
            $inventory = Inventory::firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $productVariantId
                ],
                ['quantity' => 0]
            );

            $inventory->quantity += $quantity;
            $inventory->save();

            return true;
        } catch (\Exception $e) {
            Log::error("Inventory increase failed: {$e->getMessage()}");
            return false;
        }
    }

    public static function decrease($warehouseId, $productVariantId, $quantity, $referenceType = null, $referenceId = null)
    {
        try {
            Log::info("Starting inventory decrease", [
                'warehouse_id' => $warehouseId,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity
            ]);
            
            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if (!$inventory) {
                Log::error("Inventory not found for warehouse_id: {$warehouseId}, product_variant_id: {$productVariantId}");
                return false;
            }

            Log::info("Found inventory", [
                'inventory_id' => $inventory->id,
                'current_quantity' => $inventory->quantity,
                'required_quantity' => $quantity
            ]);

            if ($inventory->quantity < $quantity) {
                Log::error("Insufficient stock. Available: {$inventory->quantity}, Required: {$quantity}");
                return false;
            }

            $quantityBefore = $inventory->quantity;
            $inventory->quantity -= $quantity;
            $inventory->save();

            Log::info("Successfully decreased inventory", [
                'inventory_id' => $inventory->id,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $inventory->quantity,
                'decreased_by' => $quantity
            ]);

            // Ghi lại lịch sử giao dịch kho
            if (class_exists('App\Models\InventoryTransaction')) {
                $userId = Auth::id();
                if (!$userId) {
                    // Nếu không có user đăng nhập, sử dụng user đầu tiên hoặc admin
                    $defaultUser = \App\Models\User::first();
                    $userId = $defaultUser ? $defaultUser->id : 1;
                    Log::info("Using default user for inventory transaction", [
                        'user_id' => $userId
                    ]);
                }
                
                try {
                    InventoryTransaction::create([
                        'inventory_id' => $inventory->id,
                        'type' => 'order', // Loại giao dịch là đơn hàng
                        'quantity_before' => $quantityBefore,
                        'quantity_change' => -$quantity, // Số lượng thay đổi là âm (trừ)
                        'quantity_after' => $inventory->quantity,
                        'reference_type' => $referenceType,
                        'reference_id' => $referenceId,
                        'note' => 'Trừ kho theo đơn hàng',
                        'created_by' => $userId,
                    ]);
                    
                    Log::info("Created inventory transaction", [
                        'inventory_id' => $inventory->id,
                        'reference_type' => $referenceType,
                        'reference_id' => $referenceId,
                        'created_by' => $userId
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to create inventory transaction: {$e->getMessage()}", [
                        'inventory_id' => $inventory->id,
                        'created_by' => $userId
                    ]);
                    // Không return false vì việc trừ tồn kho đã thành công
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Inventory decrease failed: {$e->getMessage()}");
            return false;
        }
    }

    public static function decreaseOrderInventory($order)
    {
        try {
            Log::info("=== STARTING decreaseOrderInventory ===", [
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'branch_id' => $order->branch_id
            ]);

            // Load order details nếu chưa được load
            if (!$order->relationLoaded('orderDetails')) {
                Log::info("Loading order details for order {$order->id}");
                $order->load('orderDetails');
            }
            
            // Kiểm tra có order details không
            if ($order->orderDetails->isEmpty()) {
                Log::error("FAILED: No order details found for order {$order->id}");
                return false;
            }
            
            Log::info("Order details found", [
                'order_id' => $order->id,
                'order_details_count' => $order->orderDetails->count(),
                'details' => $order->orderDetails->map(function($detail) {
                    return [
                        'id' => $detail->id,
                        'product_variant_id' => $detail->product_variant_id,
                        'quantity' => $detail->quantity
                    ];
                })
            ]);
            
            // Kiểm tra branch_id
            $branchId = $order->branch_id;
            if (!$branchId) {
                Log::warning("No branch_id found for order {$order->id}, trying to use default branch");
                // Nếu không có branch_id, sử dụng branch đầu tiên hoặc default
                $defaultBranch = \App\Models\Branch::first();
                if (!$defaultBranch) {
                    Log::error("FAILED: No branch found for order {$order->id}");
                    return false;
                }
                $branchId = $defaultBranch->id;
                Log::info("Using default branch for order", [
                    'order_id' => $order->id,
                    'default_branch_id' => $branchId,
                    'default_branch_name' => $defaultBranch->name
                ]);
            } else {
                // Kiểm tra branch có tồn tại không
                $branch = \App\Models\Branch::find($branchId);
                if (!$branch) {
                    Log::error("FAILED: Branch {$branchId} not found for order {$order->id}");
                    return false;
                }
                Log::info("Using order's branch", [
                    'order_id' => $order->id,
                    'branch_id' => $branchId,
                    'branch_name' => $branch->name
                ]);
            }
            
            Log::info("Starting decreaseOrderInventory", [
                'order_id' => $order->id,
                'branch_id' => $branchId,
                'order_details_count' => $order->orderDetails->count()
            ]);
            
            foreach ($order->orderDetails as $detail) {
                Log::info("Processing order detail", [
                    'order_id' => $order->id,
                    'product_variant_id' => $detail->product_variant_id,
                    'quantity' => $detail->quantity
                ]);
                
                // Kiểm tra product_variant_id có hợp lệ không
                if (!$detail->product_variant_id) {
                    Log::error("Invalid product_variant_id for order detail", [
                        'order_id' => $order->id,
                        'detail_id' => $detail->id,
                        'product_variant_id' => $detail->product_variant_id
                    ]);
                    return false;
                }
                
                // Kiểm tra quantity có hợp lệ không
                if (!$detail->quantity || $detail->quantity <= 0) {
                    Log::error("Invalid quantity for order detail", [
                        'order_id' => $order->id,
                        'detail_id' => $detail->id,
                        'quantity' => $detail->quantity
                    ]);
                    return false;
                }
                
                $success = self::decrease(
                    $branchId,
                    $detail->product_variant_id,
                    $detail->quantity,
                    'App\Models\Order',
                    $order->id
                );

                if (!$success) {
                    Log::error("Failed to decrease inventory for order {$order->id}, product_variant_id: {$detail->product_variant_id}");
                    return false;
                }
                
                Log::info("Successfully decreased inventory for detail", [
                    'order_id' => $order->id,
                    'product_variant_id' => $detail->product_variant_id
                ]);
            }

            Log::info("Completed decreaseOrderInventory successfully", [
                'order_id' => $order->id
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Decrease order inventory failed: {$e->getMessage()}");
            return false;
        }
    }

    public static function increaseOrderInventory($order)
    {
        try {
            foreach ($order->orderDetails as $detail) {
                $success = self::increase(
                    $order->branch_id,
                    $detail->product_variant_id,
                    $detail->quantity
                );

                if (!$success) {
                    Log::error("Failed to increase inventory for order {$order->id}, product_variant_id: {$detail->product_variant_id}");
                    return false;
                }

                // Ghi lại lịch sử giao dịch kho
                if (class_exists('App\Models\InventoryTransaction')) {
                    $inventory = Inventory::where('warehouse_id', $order->branch_id)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->first();

                    if ($inventory) {
                        InventoryTransaction::create([
                            'inventory_id' => $inventory->id,
                            'type' => 'order_cancellation', // Loại giao dịch là hủy đơn hàng
                            'quantity_before' => $inventory->quantity - $detail->quantity,
                            'quantity_change' => $detail->quantity, // Số lượng thay đổi là dương (cộng)
                            'quantity_after' => $inventory->quantity,
                            'reference_type' => 'App\Models\Order',
                            'reference_id' => $order->id,
                            'note' => 'Hoàn trả kho do hủy đơn hàng',
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Increase order inventory failed: {$e->getMessage()}");
            return false;
        }
    }
}
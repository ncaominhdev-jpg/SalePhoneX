<?php

namespace App\Observers;

use App\Models\Import;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ImportObserver
{
    /**
     * Handle the Import "updated" event.
     */
    public function updated(Import $import): void
    {
        // Chỉ thực thi khi trường 'status' được thay đổi VÀ giá trị mới là 'processed_warehouse'
        if ($import->wasChanged('status') && $import->status === 'processed_warehouse') {
            
            // Sử dụng DB Transaction để đảm bảo tính toàn vẹn dữ liệu
            // Nếu có lỗi ở bất kỳ sản phẩm nào, tất cả các thay đổi sẽ được rollback
            DB::transaction(function () use ($import) {
                
                // Lấy tất cả các chi tiết của phiếu nhập
                $importDetails = $import->importDetails()->get();
                
                foreach ($importDetails as $detail) {
                    // Đây là chìa khóa để giải quyết vấn đề của bạn:
                    // Dùng firstOrCreate để TÌM hoặc TẠO MỚI bản ghi tồn kho
                    // cho sản phẩm này tại kho này.
                    $inventory = Inventory::firstOrCreate(
                        [
                            'warehouse_id' => $import->warehouse_id,
                            'product_variant_id' => $detail->product_variant_id,
                        ],
                        [
                            'quantity' => 0 // Nếu tạo mới, số lượng ban đầu là 0
                        ]
                    );

                    // Lấy số lượng trước khi thay đổi để ghi log
                    $quantityBefore = $inventory->quantity;

                    // Tăng số lượng tồn kho
                    $inventory->increment('quantity', $detail->quantity);
                    
                    // Ghi lại lịch sử giao dịch kho
                    InventoryTransaction::create([
                        'inventory_id' => $inventory->id,
                        'type' => 'import', // Loại giao dịch là nhập kho
                        'quantity_before' => $quantityBefore,
                        'quantity_change' => $detail->quantity, // Số lượng thay đổi là số lượng nhập
                        'quantity_after' => $inventory->quantity, // Số lượng sau khi cập nhật
                        'reference_type' => Import::class, // Tham chiếu đến model Import
                        'reference_id' => $import->id, // ID của phiếu nhập
                        'note' => 'Nhập kho theo phiếu ' . $import->code,
                        'created_by' => $import->processed_by ?? Auth::id(), // Người xử lý phiếu
                    ]);
                }

                // Cập nhật trạng thái phiếu nhập thành "Hoàn tất"
                $import->status = 'completed';
                $import->final_approved_by = Auth::id(); // Gán người dùng cuối cùng xác nhận
                $import->saveQuietly(); // Lưu mà không kích hoạt lại observer
            });
        }
    }
    public function createdImport(ImportNote $importNote)
    {
        foreach ($importNote->items as $item) {
            $inventory = $item->product->inventory;
            $qty = $item->quantity;

            $quantityBefore = $inventory->quantity;
            $inventory->increment('quantity', $qty);

            InventoryTransaction::create([
                'inventory_id' => $inventory->id,
                'type' => 'import',
                'quantity_before' => $quantityBefore,
                'quantity_after' => $inventory->quantity,
                'quantity_change' => $qty,
                'reference_type' => 'import_note',
                'reference_id' => $importNote->id,
                'note' => 'Nhập kho từ phiếu #' . $importNote->id,
                'created_by' => auth()->id(),
            ]);
        }
    }

    // Bạn có thể để trống các phương thức khác như created, deleted...
    // ...
}
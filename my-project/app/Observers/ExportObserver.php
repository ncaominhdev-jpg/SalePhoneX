<?php

namespace App\Observers;

use App\Models\Export;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ExportObserver
{
    public function createdExport(ExportNote $exportNote)
    {
        foreach ($exportNote->items as $item) {
            $inventory = $item->product->inventory;
            $qty = $item->quantity;

            $quantityBefore = $inventory->quantity;
            $inventory->decrement('quantity', $qty);

            InventoryTransaction::create([
                'inventory_id' => $inventory->id,
                'type' => 'export',
                'quantity_before' => $quantityBefore,
                'quantity_after' => $inventory->quantity,
                'quantity_change' => -$qty,
                'reference_type' => 'export_note',
                'reference_id' => $exportNote->id,
                'note' => 'Xuất kho từ phiếu #' . $exportNote->id,
                'created_by' => auth()->id(),
            ]);
        }
    }
}
?>
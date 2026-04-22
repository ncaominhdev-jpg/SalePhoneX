<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // nếu bạn dùng barryvdh/laravel-dompdf

class InventoryTransactionController extends Controller
{
    public function generatePdf($id)
    {
        $transaction = InventoryTransaction::with('inventory.productVariant.product', 'creator')
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.inventory-transaction', compact('transaction'));

        return $pdf->download("transaction_{$transaction->id}.pdf");
    }

    public function viewPdf($id)
    {
        $transaction = InventoryTransaction::with('inventory.productVariant.product', 'creator')
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.inventory-transaction', compact('transaction'));

        return $pdf->stream("transaction_{$transaction->id}.pdf");
    }
}


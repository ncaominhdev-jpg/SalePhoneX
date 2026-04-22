<?php 
namespace App\Http\Controllers;

use App\Models\Audit;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AuditExportController extends Controller
{
    public function exportPdf(Audit $audit)
    {
        $audit->load([
            'warehouse',
            'creator',
            'reports.productVariant',
            // 'balances.productVariant',
        ]);

        $pdf = Pdf::loadView('pdf.audits', compact('audit'));

        return $pdf->download('phieu-kiem-kho-' . $audit->id . '.pdf');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Import;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Export;
class ExportImportPdfController extends Controller
{
    public function generate(Import $import)
    {
        $pdf = Pdf::loadView('pdf.import', compact('import'));
        return $pdf->download('phieu-nhap-kho-' . $import->id . '.pdf');
    }
    public function generateExport(Export $export)
    {
        $pdf = Pdf::loadView('pdf.export', [
            'export' => $export->load(['fromWarehouse', 'toWarehouse', 'user', 'exportDetails.productVariant']),
        ]);

        return $pdf->download('phieu-xuat-kho-' . $export->id . '.pdf');
    }

    public function generateAudit(\App\Models\Audit $audit)
    {
        $audit->load('warehouse', 'reports.productVariant');
        $pdf = Pdf::loadView('pdf.audit', compact('audit'));
        return $pdf->download('phieu-kiem-kho-' . $audit->id . '.pdf');
    }
}

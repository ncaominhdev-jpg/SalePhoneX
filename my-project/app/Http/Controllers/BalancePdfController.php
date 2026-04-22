<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use Barryvdh\DomPDF\Facade\Pdf;

class BalancePdfController extends Controller
{
    public function generate(Balance $balance)
    {
        $pdf = Pdf::loadView('pdf.balance', compact('balance'));

        return $pdf->stream('phieu-dieu-chinh-'.$balance->id.'.pdf');
    }
}

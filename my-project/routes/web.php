<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportImportPdfController;
use App\Http\Controllers\AuditExportController;
use App\Http\Controllers\BalancePdfController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/admin/imports/{import}/pdf', [ExportImportPdfController::class, 'generate'])
    ->name('imports.pdf');

Route::get('/exports/{export}/pdf', [ExportImportPdfController::class, 'generateExport'])->name('exports.pdf');

Route::get('/admin/audits/{audit}/pdf', [AuditExportController::class, 'generateAudit'])
    ->name('audits.pdf');
Route::get('/audits/{audit}/pdf', [AuditExportController::class, 'exportPdf'])->name('audits.pdf');


Route::get('/balances/{balance}/pdf', [BalancePdfController::class, 'generate'])
    ->name('balances.pdf');


require __DIR__.'/auth.php';

// Route::get('/order-chart-test', function () {
//     return view('order-chart-test');
// });

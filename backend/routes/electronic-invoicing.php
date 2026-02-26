<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ElectronicInvoiceDownloadController;

/**
 * Rutas para descargas y consultas de facturas electrónicas
 * Estas rutas DEBEN estar protegidas con autenticación Sanctum
 */
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Descargar PDF
    Route::get('/electronic-invoicing/pdf/{cufe}', 
        [ElectronicInvoiceDownloadController::class, 'downloadPdfByCufe'])
        ->name('electronic-invoicing.pdf');
    
    // Descargar XML
    Route::get('/electronic-invoicing/xml/{cufe}', 
        [ElectronicInvoiceDownloadController::class, 'downloadXmlByCufe'])
        ->name('electronic-invoicing.xml');
    
    // Obtener detalles de auditoría por CUFE
    Route::get('/electronic-invoicing/audit/{cufe}', 
        [ElectronicInvoiceDownloadController::class, 'getAuditByCufe'])
        ->name('electronic-invoicing.audit');
    
    // Obtener historial completo de auditorías para una factura
    Route::get('/electronic-invoicing/history/{facturaFiscalId}', 
        [ElectronicInvoiceDownloadController::class, 'getAuditHistory'])
        ->name('electronic-invoicing.history');
    
    // Descargar ZIP con PDF + XML
    Route::get('/electronic-invoicing/download-zip/{facturaFiscalId}', 
        [ElectronicInvoiceDownloadController::class, 'downloadZip'])
        ->name('electronic-invoicing.download-zip');
    
});

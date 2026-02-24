<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TerceroController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TipoIdTerceroController;
use App\Http\Controllers\TipoUnidadServicioController;
use App\Http\Controllers\OrdenServicioController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\ElectronicInvoicingController;
use App\Http\Controllers\ElectronicInvoiceDownloadController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'message' => 'API is running']);
});

// Facturación Electrónica (DataIco)
Route::post('/electronic-invoicing/send', [ElectronicInvoicingController::class, 'sendInvoice']);

// Facturación
Route::get('/facturacion/prefijos', [FacturacionController::class, 'getPrefijos']);
Route::get('/facturacion/pendientes', [FacturacionController::class, 'getPendientesFacturar']);
Route::get('/facturacion/facturas', [FacturacionController::class, 'getFacturas']);
Route::post('/facturacion/facturar', [FacturacionController::class, 'facturar']);

// Catálogos ubicación (pueden ser públicos si se requieren en el registro)
Route::get('/ubicacion/paises', [UbicacionController::class, 'getPaises']);
Route::get('/ubicacion/departamentos/{paisId}', [UbicacionController::class, 'getDepartamentos']);
Route::get('/ubicacion/municipios/{paisId}/{dptoId}', [UbicacionController::class, 'getMunicipios']);

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Facturación Electrónica - Auditoría y Descargas
    Route::get('/electronic-invoicing/audit/{cufe}', [ElectronicInvoiceDownloadController::class, 'getAuditByCufe']);
    Route::get('/electronic-invoicing/history/{facturaFiscalId}', [ElectronicInvoiceDownloadController::class, 'getAuditHistory']);
    Route::get('/electronic-invoicing/pdf/{cufe}', [ElectronicInvoiceDownloadController::class, 'downloadPdfByCufe']);
    Route::get('/electronic-invoicing/xml/{cufe}', [ElectronicInvoiceDownloadController::class, 'downloadXmlByCufe']);

    // Tipos de Identificación (catálogo)
    Route::get('/tipos-id-tercero', [TipoIdTerceroController::class, 'index']);

    // Tipos de Unidad (catálogo)
    Route::get('/tipos-unidad-servicio', [TipoUnidadServicioController::class, 'index']);

    // Terceros
    Route::get('/terceros', [TerceroController::class, 'index']);
    Route::post('/terceros', [TerceroController::class, 'store']);
    Route::get('/terceros/{tipo_id_tercero}/{tercero_id}', [TerceroController::class, 'show']);
    Route::put('/terceros/{tipo_id_tercero}/{tercero_id}', [TerceroController::class, 'update']);
    Route::delete('/terceros/{tipo_id_tercero}/{tercero_id}', [TerceroController::class, 'destroy']);
    
    // Crear tercero desde PDF
    Route::post('/terceros/upload-pdf', [TerceroController::class, 'createFromPdf']);

    // Servicios
    Route::get('/servicios', [ServicioController::class, 'index']);
    Route::post('/servicios', [ServicioController::class, 'store']);
    Route::get('/servicios/activos', [ServicioController::class, 'activos']);
    Route::get('/servicios/{id}', [ServicioController::class, 'show']);
    Route::put('/servicios/{id}', [ServicioController::class, 'update']);
    Route::delete('/servicios/{id}', [ServicioController::class, 'destroy']);

    // Órdenes de Servicio
    Route::get('/ordenes-servicio', [OrdenServicioController::class, 'index']);
    Route::post('/ordenes-servicio', [OrdenServicioController::class, 'store']);
    Route::get('/ordenes-servicio/{id}', [OrdenServicioController::class, 'show']);
    Route::put('/ordenes-servicio/{id}', [OrdenServicioController::class, 'update']);
    Route::delete('/ordenes-servicio/{id}', [OrdenServicioController::class, 'destroy']);
});

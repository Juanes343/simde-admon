<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TerceroController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TipoIdTerceroController;
use App\Http\Controllers\TipoUnidadServicioController;
use App\Http\Controllers\ImpuestoController;
use App\Http\Controllers\RetencionFuenteController;
use App\Http\Controllers\OrdenServicioController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\ElectronicInvoicingController;
use App\Http\Controllers\ElectronicInvoiceDownloadController;
use App\Http\Controllers\NotaCreditoController;
use App\Http\Controllers\FirmaDigitalController;
use App\Http\Controllers\FacturaExternaController;
use App\Http\Controllers\UsuarioModuloController;
use App\Http\Controllers\CausacionController;
use App\Http\Controllers\CotizacionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'message' => 'API is running']);
});

// Rutas Públicas de Firma Digital
Route::get('/public/ordenes-servicio/{id}/firmar/{token}', [FirmaDigitalController::class, 'verificarToken']);
Route::post('/public/ordenes-servicio/firmar', [FirmaDigitalController::class, 'firmar']);

// Facturación Electrónica (DataIco)
Route::post('/electronic-invoicing/send', [ElectronicInvoicingController::class, 'sendInvoice']);

// Notas Crédito (Catálogos públicos)
Route::get('/notas-credito-conceptos', [NotaCreditoController::class, 'conceptos']);
Route::get('/notas-credito/prefijos', [NotaCreditoController::class, 'prefijos']);

// Facturación
Route::get('/facturacion/prefijos', [FacturacionController::class, 'getPrefijos']);
Route::get('/facturacion/pendientes', [FacturacionController::class, 'getPendientesFacturar']);
Route::get('/facturacion/facturas', [FacturacionController::class, 'getFacturas']);
Route::post('/facturacion/facturar', [FacturacionController::class, 'facturar']);

// Plantilla CSV pública (no requiere autenticación)
Route::get('/facturas-externas/plantilla-csv', [FacturaExternaController::class, 'plantillaCsv']);

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

    // Módulos / Permisos de usuario
    Route::get('/modulos', [UsuarioModuloController::class, 'catalogos']);
    Route::get('/usuarios', [UsuarioModuloController::class, 'listarUsuarios']);
    Route::get('/usuarios/{id}/modulos', [UsuarioModuloController::class, 'index']);
    Route::put('/usuarios/{id}/modulos', [UsuarioModuloController::class, 'update']);
    Route::patch('/usuarios/{id}/activo', [UsuarioModuloController::class, 'toggleActivo']);
    Route::patch('/usuarios/{id}/admin', [UsuarioModuloController::class, 'toggleAdmin']);

    // Órdenes de Servicio - Firma Digital (Admin)
    Route::post('/ordenes-servicio/{id}/solicitar-firma', [FirmaDigitalController::class, 'solicitarFirma']);

    // Facturación Electrónica - Auditoría y Descargas
    Route::get('/electronic-invoicing/audit/{cufe}', [ElectronicInvoiceDownloadController::class, 'getAuditByCufe']);
    Route::get('/electronic-invoicing/history/{facturaFiscalId}', [ElectronicInvoiceDownloadController::class, 'getAuditHistory']);
    Route::get('/electronic-invoicing/pdf/{cufe}', [ElectronicInvoiceDownloadController::class, 'downloadPdfByCufe']);
    Route::get('/electronic-invoicing/xml/{cufe}', [ElectronicInvoiceDownloadController::class, 'downloadXmlByCufe']);
    Route::get('/electronic-invoicing/download-zip/{facturaFiscalId}', [ElectronicInvoiceDownloadController::class, 'downloadZip']);

    // Tipos de Identificación (catálogo)
    Route::get('/tipos-id-tercero', [TipoIdTerceroController::class, 'index']);

    // Tipos de Unidad (catálogo)
    Route::get('/tipos-unidad-servicio', [TipoUnidadServicioController::class, 'index']);

    // Impuestos (catálogo)
    Route::get('/impuestos', [ImpuestoController::class, 'index']);

    // Retenciones en la Fuente (catálogo)
    Route::get('/retencion-fuente', [RetencionFuenteController::class, 'index']);

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
    
    // Notas Crédito
    Route::get('/notas-credito', [NotaCreditoController::class, 'index']);
    Route::post('/notas-credito', [NotaCreditoController::class, 'store']);
    Route::get('/notas-credito/estadisticas', [NotaCreditoController::class, 'estadisticas']);
    Route::get('/notas-credito/{id}/descargar-zip', [NotaCreditoController::class, 'descargarZip']);
    Route::get('/notas-credito/{id}/descargar-pdf', [NotaCreditoController::class, 'descargarPdf']);
    Route::get('/notas-credito/{id}/descargar-xml', [NotaCreditoController::class, 'descargarXml']);
    Route::get('/notas-credito/{id}', [NotaCreditoController::class, 'show']);
    Route::post('/notas-credito/{id}/enviar', [NotaCreditoController::class, 'enviar']);
    Route::delete('/notas-credito/{id}', [NotaCreditoController::class, 'destroy']);

    // Notas Crédito - Conceptos (gestión)
    Route::get('/notas-credito-conceptos/all', [NotaCreditoController::class, 'conceptosAll']);
    Route::post('/notas-credito-conceptos', [NotaCreditoController::class, 'storeConcepto']);
    Route::put('/notas-credito-conceptos/{id}', [NotaCreditoController::class, 'updateConcepto']);
    Route::delete('/notas-credito-conceptos/{id}', [NotaCreditoController::class, 'destroyConcepto']);
    
    // Cambiar estado de items de orden de servicio
    Route::post('/orden-servicio-items/{id}/cambiar-estado', [OrdenServicioController::class, 'cambiarEstadoItem']);
    Route::delete('/orden-servicio-items/{id}', [OrdenServicioController::class, 'deleteItem']);

    // Facturas Externas (carga masiva por CSV)
    Route::post('/facturas-externas/upload', [FacturaExternaController::class, 'uploadCsv']);
    Route::get('/facturas-externas', [FacturaExternaController::class, 'index']);
    Route::get('/facturas-externas/{id}', [FacturaExternaController::class, 'show']);
    Route::delete('/facturas-externas/{id}', [FacturaExternaController::class, 'destroy']);

    // Causación (pagos y anticipos)
    Route::get('/causacion/anticipos',           [CausacionController::class, 'getAnticipo']);
    Route::post('/causacion/anticipos',           [CausacionController::class, 'upsertAnticipo']);
    Route::get('/causacion/facturas-pendientes',  [CausacionController::class, 'getFacturasPendientes']);
    Route::post('/causacion/registrar-pagos',     [CausacionController::class, 'registrarPagos']);
    Route::get('/causacion/pagos',                [CausacionController::class, 'getPagos']);
    Route::put('/causacion/pagos/{id}',           [CausacionController::class, 'actualizarPago']);
    Route::get('/causacion/reporte',              [CausacionController::class, 'reporte']);
    Route::get('/causacion/reporte/excel',        [CausacionController::class, 'reporteExcel']);

    // Cotizaciones
    Route::get('/cotizaciones',                          [CotizacionController::class, 'index']);
    Route::post('/cotizaciones',                         [CotizacionController::class, 'store']);
    Route::get('/cotizaciones/{id}',                     [CotizacionController::class, 'show']);
    Route::put('/cotizaciones/{id}',                     [CotizacionController::class, 'update']);
    Route::delete('/cotizaciones/{id}',                  [CotizacionController::class, 'destroy']);
    Route::patch('/cotizaciones/{id}/estado',            [CotizacionController::class, 'cambiarEstado']);
    Route::post('/cotizaciones/{id}/convertir',          [CotizacionController::class, 'convertirAOrden']);
    Route::get('/cotizaciones/{id}/pdf',                 [CotizacionController::class, 'descargarPdf']);
    Route::post('/cotizaciones/{id}/enviar-email',       [CotizacionController::class, 'enviarEmail']);
});
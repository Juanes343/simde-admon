<?php

namespace App\Http\Controllers;

use App\Services\ElectronicInvoicingService;
use Illuminate\Http\Request;

class ElectronicInvoicingController extends Controller
{
    protected $einvoicingService;

    public function __construct(ElectronicInvoicingService $einvoicingService)
    {
        $this->einvoicingService = $einvoicingService;
    }

    /**
     * Envía (o previsualiza) una factura para DataIco.
     * 
     * @param Request $request Contiene factura_fiscal_id de la factura.
     */
    public function sendInvoice(Request $request)
    {
        $request->validate([
            'factura_fiscal_id' => 'required|integer|exists:fac_facturas,factura_fiscal_id',
        ]);

        // Llamamos al servicio (Llamada real)
        $result = $this->einvoicingService->sendInvoice($request->factura_fiscal_id);

        /**
         * RESPUESTA FINAL: 
         * Si success es false, devolvemos 422 para que el Front muestre el error detallado.
         * Si success es true, devolvemos 200 con la info de éxito (CUFE, etc).
         */
        return response()->json([
            'success' => $result['success'] ?? false,
            'debug'   => $result['debug'] ?? false, 
            'message' => $result['message'] ?? ($result['success'] ? 'Enviado con éxito' : 'Error en envío'),
            'data'    => $result['data'] ?? null,
            'payload' => $result['payload'] ?? null,
            'errors'  => $result['errors'] ?? null
        ], $result['success'] ? 200 : 422);
    }
}

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
     * Envía una factura a la DIAN (vía DataIco).
     * 
     * @param Request $request Contiene id de la factura.
     */
    public function sendInvoice(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:fac_facturas,factura_fiscal_id',
        ]);

        $result = $this->einvoicingService->sendInvoice($request->id);

        // Modo Previsualización (Debug)
        if (isset($result['debug']) && $result['debug']) {
            return response()->json($result, 200);
        }

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Factura enviada exitosamente',
                'data' => $result['data'],
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al enviar la factura',
            'error' => $result['error'] ?? $result['message'] ?? 'Ocurrió un error desconocido',
            'status' => $result['status'] ?? 500,
        ], $result['status'] ?? 500);
    }
}

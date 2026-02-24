<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaDataIco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElectronicInvoiceDownloadController extends Controller
{
    /**
     * Descargar PDF de una factura via CUFE
     */
    public function downloadPdfByCufe($cufe)
    {
        try {
            $auditoria = AuditoriaDataIco::findByCufe($cufe);
            
            if (!$auditoria || !$auditoria->pdf_url) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF no encontrado'
                ], 404);
            }
            
            return redirect()->to($auditoria->pdf_url);
        } catch (\Exception $e) {
            Log::error("Error descargando PDF por CUFE $cufe: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error descargando PDF'
            ], 500);
        }
    }

    /**
     * Descargar XML de una factura via CUFE
     */
    public function downloadXmlByCufe($cufe)
    {
        try {
            $auditoria = AuditoriaDataIco::findByCufe($cufe);
            
            if (!$auditoria || !$auditoria->xml_url) {
                return response()->json([
                    'success' => false,
                    'message' => 'XML no encontrado'
                ], 404);
            }
            
            return redirect()->to($auditoria->xml_url);
        } catch (\Exception $e) {
            Log::error("Error descargando XML por CUFE $cufe: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error descargando XML'
            ], 500);
        }
    }

    /**
     * Obtener detalles de auditoría por CUFE
     */
    public function getAuditByCufe($cufe)
    {
        try {
            $auditoria = AuditoriaDataIco::findByCufe($cufe);
            
            if (!$auditoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Auditoría no encontrada'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id_auditoria_dataico' => $auditoria->id_auditoria_dataico,
                    'numero' => $auditoria->numero,
                    'cufe' => $auditoria->cufe,
                    'uuid' => $auditoria->uuid,
                    'dian_status' => $auditoria->dian_status,
                    'customer_status' => $auditoria->customer_status,
                    'email_status' => $auditoria->email_status,
                    'pdf_url' => $auditoria->pdf_url,
                    'xml_url' => $auditoria->xml_url,
                    'qrcode' => $auditoria->qrcode,
                    'fecha_registro' => $auditoria->fecha_registro,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo auditoría por CUFE $cufe: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo auditoría'
            ], 500);
        }
    }

    /**
     * Obtener historial de auditorías para una factura
     */
    public function getAuditHistory($facturaFiscalId)
    {
        try {
            $auditorias = AuditoriaDataIco::where('factura_fiscal_id', $facturaFiscalId)
                ->orderBy('fecha_registro', 'desc')
                ->get();
            
            if ($auditorias->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay auditorías para esta factura'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $auditorias->map(fn($audit) => [
                    'id_auditoria_dataico' => $audit->id_auditoria_dataico,
                    'numero' => $audit->numero,
                    'cufe' => $audit->cufe,
                    'uuid' => $audit->uuid,
                    'dian_status' => $audit->dian_status,
                    'customer_status' => $audit->customer_status,
                    'email_status' => $audit->email_status,
                    'pdf_url' => $audit->pdf_url,
                    'xml_url' => $audit->xml_url,
                    'fecha_registro' => $audit->fecha_registro,
                ])
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo historial de auditoría para factura $facturaFiscalId: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo historial'
            ], 500);
        }
    }
}

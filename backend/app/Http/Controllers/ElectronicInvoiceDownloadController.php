<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaDataIco;
use App\Models\FacFactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Descargar ZIP con PDF + XML
     */
    public function downloadZip($facturaFiscalId)
    {
        try {
            // Obtener la factura
            $factura = FacFactura::findOrFail($facturaFiscalId);
            
            // Obtener la auditoría más reciente
            $auditoria = AuditoriaDataIco::where('factura_fiscal_id', $facturaFiscalId)
                ->latest('fecha_registro')
                ->first();
            
            if (!$auditoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Auditoría no encontrada'
                ], 404);
            }
            
            if (!$auditoria->pdf_url || !$auditoria->xml_url) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF o XML no disponibles'
                ], 404);
            }
            
            // Crear directorio temporal
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $zipFileName = "{$factura->prefijo}-{$factura->factura_fiscal}.zip";
            $zipFilePath = $tempDir . '/' . $zipFileName;
            
            // Descargar PDF y XML
            $pdfContent = Http::get($auditoria->pdf_url)->body();
            $xmlContent = Http::get($auditoria->xml_url)->body();
            
            // Crear ZIP
            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE) === true) {
                $zip->addFromString(
                    "{$factura->prefijo}-{$factura->factura_fiscal}.pdf",
                    $pdfContent
                );
                $zip->addFromString(
                    "{$factura->prefijo}-{$factura->factura_fiscal}.xml",
                    $xmlContent
                );
                $zip->close();
                
                // Descargar el ZIP
                return response()->download($zipFilePath)->deleteFileAfterSend(true);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creando ZIP'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("Error descargando ZIP para factura $facturaFiscalId: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error descargando ZIP'
            ], 500);
        }
    }
}

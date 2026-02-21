<?php

namespace App\Services;

use App\Models\FacFactura;
use App\Models\Tercero;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ElectronicInvoicingService
{
    protected $dataIcoService;

    public function __construct(DataIcoService $dataIcoService)
    {
        $this->dataIcoService = $dataIcoService;
    }

    /**
     * Procesa y envía una factura a Facturación Electrónica.
     * 
     * @param int $facturaFiscalId ID de la factura local.
     * @return array
     */
    public function sendInvoice(int $facturaFiscalId)
    {
        try {
            // 1. Obtener datos de la factura con relaciones
            $factura = FacFactura::with(['items.ordenServicioItem', 'tercero'])->findOrFail($facturaFiscalId);
            
            // 2. Determinar el tipo de documento para DataIco/Legacy logic
            $documentType = $this->determineDocumentType($factura);

            // 3. Construir el payload para DataIco
            $payload = $this->buildInvoicePayload($factura, $documentType);

            // 4. Obtener configuración
            $token = config('services.dataico.token');
            
            // 5. Determinar el endpoint adecuado
            $endpoint = $this->getEndpoint($documentType);

            // MODO DEBUG: Retorna el JSON sin enviar
            if (config('services.dataico.debug_mode', true)) {
                return [
                    'success' => true,
                    'debug' => true,
                    'message' => 'MODO PREVISUALIZACION ACTIVADO',
                    'payload' => $payload,
                    'endpoint_target' => $endpoint
                ];
            }

            // 6. Enviar a DataIco
            $result = $this->dataIcoService->sendDocument($payload, $endpoint, $token);

            // 7. Auditar resultado
            $this->auditResult($factura, $result, $payload);

            return $result;

        } catch (\Exception $e) {
            Log::error("Error procesando factura electrónica (ID $facturaFiscalId): " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno procesando la factura',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mapea tipo_factura a los códigos de la lógica legacy.
     */
    protected function determineDocumentType(FacFactura $factura)
    {
        switch ($factura->tipo_factura) {
            case '1': return 1; // Factura Cliente
            case '0':
            case '2': return 6; // Factura Paciente / Particular
            case '3': return 2; // Factura Evento (Agrupada)
            case '4': return 2; // Factura Capitacion (Agrupada)
            case '5': return 8; // Factura Conceptos
            case '8': return 8; // Anticipo
            default: return 1;
        }
    }

    protected function getEndpoint($type)
    {
        if ($type == 3) return 'credit_notes';
        if ($type == 5) return 'debit_notes';
        return 'invoices';
    }

    /**
     * Construye el objeto JSON que espera DataIco.
     */
    protected function buildInvoicePayload(FacFactura $factura, int $documentType)
    {
        $tercero = $factura->tercero;
        
        $payload = [
            "actions" => [
                "send_dian" => config('services.dataico.send_dian', true),
                "send_email" => true
            ],
            "invoice" => [
                "env" => config('services.dataico.env', 'PRUEBAS'),
                "number" => $this->getPrefixedNumber($factura, $documentType),
                "issue_date" => Carbon::parse($factura->fecha_registro)->format('Y-m-d'),
                "payment_date" => $factura->fecha_vencimiento_factura 
                    ? Carbon::parse($factura->fecha_vencimiento_factura)->format('Y-m-d') 
                    : Carbon::parse($factura->fecha_registro)->format('Y-m-d'),
                "invoice_type_code" => $this->getInvoiceTypeCode($documentType),
                "payment_means" => $this->getPaymentMeans($factura),
                "payment_means_type" => "MUTUAL_AGREEMENT",
                "numbering" => [
                    "resolution_number" => config('services.dataico.resolutions.invoice', '18760000000001'),
                    "prefix" => $this->getPrefix($documentType),
                    "flexible" => true
                ],
                "customer" => [
                    "email" => $tercero->email ?? 'correo@generico.com',
                    "phone" => $tercero->telefono ?? '0000000',
                    "party_identification_type" => $this->mapIdType($factura->tipo_id_tercero),
                    "party_identification" => $factura->tercero_id,
                    "party_type" => ($factura->tipo_id_tercero == 'NIT') ? "PERSONA_JURIDICA" : "PERSONA_NATURAL",
                    "tax_level_code" => "REGIMEN_ORDINARIO",
                    "country_code" => "CO",
                    "company_name" => $tercero->nombre_tercero,
                    "first_name" => $tercero->primer_nombre ?? $tercero->nombre_tercero,
                    "family_name" => $tercero->primer_apellido ?? '',
                ],
                "items" => $this->buildInvoiceItems($factura, $documentType)
            ]
        ];

        // Lógica de Sector Salud (API_SALUD_V2)
        if ($factura->tipo_factura != '5' && $factura->tipo_factura != '6') {
            $payload['invoice']['health'] = $this->buildHealthObject($factura);
            $payload['invoice']['operation'] = $this->determineOperation($factura);
        }

        return $payload;
    }

    protected function getPrefixedNumber(FacFactura $factura, int $documentType)
    {
        $prefix = $this->getPrefix($documentType);
        return $prefix . $factura->factura_fiscal;
    }

    protected function getPrefix(int $documentType)
    {
        if ($documentType == 3) return config('services.dataico.prefixes.credit_note', 'NCSET');
        if ($documentType == 5) return config('services.dataico.prefixes.debit_note', 'NDSETT');
        return config('services.dataico.prefixes.invoice', 'FE');
    }

    protected function getInvoiceTypeCode($documentType)
    {
        if ($documentType == 3) return "CREDIT_NOTE";
        if ($documentType == 5) return "DEBIT_NOTE";
        if ($documentType == 8) return "INVOICE_ADVANCE";
        return "FACTURA_VENTA";
    }

    protected function getPaymentMeans($factura)
    {
        // En legacy, 1=CASH, 2=MUTUAL_AGREEMENT
        return ($factura->medio_pago == 1) ? "CASH" : "MUTUAL_AGREEMENT";
    }

    protected function mapIdType($type)
    {
        $map = [
            'CC' => 'CC',
            'NIT' => 'NIT',
            'TI' => 'TI',
            'RC' => 'RC',
            'PA' => 'PASAPORTE',
            'CE' => 'CE',
            'TE' => 'TE',
            'IE' => 'IE',
            'PE' => 'PEP',
            'SC' => 'PEP',
        ];
        return $map[$type] ?? 'NIT_OTRO_PAIS';
    }

    protected function buildInvoiceItems(FacFactura $factura, int $documentType)
    {
        $items = [];
        // Si hay items detallados en la factura
        if ($factura->items && $factura->items->count() > 0) {
            foreach ($factura->items as $item) {
                // Intentar obtener el detalle de la orden de servicio
                // Si la relación directa falla, buscamos manualmente por servicio_id como plan B
                $osItem = $item->ordenServicioItem;
                
                if (!$osItem && $item->item_id) {
                    $osItem = \App\Models\OrdenServicioItem::where('servicio_id', $item->item_id)
                        ->where('orden_servicio_id', $item->orden_servicio_id)
                        ->first();
                }

                $description = 'Servicios Hospitalarios';
                $price = (float) ($item->valor_total ?? 0);
                $quantity = 1;
                $sku = $item->item_id ?? 'SERV_001';

                if ($osItem) {
                    $sku = $osItem->item_id;
                    $quantity = (float) $osItem->cantidad;
                    $description = $osItem->nombre_servicio ?: $osItem->descripcion;
                    $price = (float) $osItem->precio_unitario;
                } elseif ($price == 0) {
                    // Si no existe en la orden de servicio y no tiene precio, lo omitimos
                    continue;
                }

                $items[] = [
                    "sku" => $sku,
                    "quantity" => $quantity,
                    "description" => $description,
                    "price" => $price,
                    "original_price" => $price,
                    "tax_amount" => 0,
                    "tax_category" => "01" // IVA 0 (Exento por Salud en Colombia)
                ];
            }
        } else {
            // Item genérico si no hay detalles (vía Concepto)
            $items[] = [
                "sku" => "GEN001",
                "quantity" => 1,
                "description" => $factura->concepto ?? 'Servicios de Salud Integrales',
                "price" => (float) $factura->total_factura,
                "original_price" => (float) $factura->total_factura,
                "tax_amount" => 0,
                "tax_category" => "01"
            ];
        }

        return $items;
    }

    protected function buildHealthObject(FacFactura $factura)
    {
        return [
            "version" => "API_SALUD_V2",
            "coverage" => "PLAN_DE_BENEFICIOS",
            "provider_code" => "1234567890", // TODO: Obtener del NIT de la empresa
            "payment_modality" => "PAGO_POR_EVENTO",
            "period_start_date" => Carbon::parse($factura->fecha_registro)->format('Y-m-d'),
            "period_end_date" => Carbon::parse($factura->fecha_registro)->format('Y-m-d'),
        ];
    }

    protected function determineOperation(FacFactura $factura)
    {
        // SS_RECAUDO si hay copagos/cuotas moderadoras involucradas
        if ($factura->tipo_factura == 0) return "SS_RECAUDO";
        return "SS_SIN_APORTE";
    }

    protected function auditResult(FacFactura $factura, array $result, array $payload)
    {
        if (isset($result['success']) && $result['success']) {
            Log::info("Factura ID {$factura->factura_fiscal_id} enviada exitosamente a DataIco. CUFE: " . ($result['data']['cufe'] ?? 'N/A'));
            
            // Actualizar estado en la factura
            $factura->update([
                'estado_electronico' => 'ENVIADA',
                'cufe' => $result['data']['cufe'] ?? null,
                'response_dataico' => isset($result['data']) ? json_encode($result['data']) : null
            ]);
        } else {
            Log::error("Falla en envío de factura ID {$factura->factura_fiscal_id}: " . json_encode($result['errors'] ?? $result['message'] ?? 'Error desconocido'));
        }
    }
}

<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\FacFactura;
use App\Models\OrdenServicioItem;
use App\Models\Tercero;
use App\Models\AuditoriaDataIco;

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

            // 4. Determinar endpoint
            $endpoint = $this->getEndpoint($documentType);

            /* MODO REVISIÓN FORZADO (DESACTIVADO A PEDIDO DEL USUARIO)
            return [
                'success' => true,
                'debug' => true,
                'message' => 'DEBUG PAYLOAD ACTIVADO',
                'payload' => $payload,
                'endpoint_target' => $endpoint
            ]; */

            // 5. Enviar a DataIco (Llamada real)
            $token = config('services.dataico.token');
            $result = $this->dataIcoService->sendDocument($payload, $endpoint, $token);
            Log::info('Respuesta DataIco:', ['result' => $result]);

            // 7. Auditar resultado
            $this->auditResult($factura, $result, $payload);

            // Si falló, nos aseguramos de que el mensaje de error de DataIco sea explícito
            if (!$result['success']) {
                $result['payload'] = $payload; // <-- Marcamos que esto falló
                
                if (isset($result['errors']['errors'])) {
                    $errorDetails = [];
                    foreach ($result['errors']['errors'] as $err) {
                        $path = isset($err['path']) ? implode(' > ', $err['path']) : 'Gral';
                        $errorDetails[] = "[$path]: " . ($err['error'] ?? 'Error desconocido');
                    }
                    $result['message'] = "Errores de validación DataIco: " . implode(' | ', $errorDetails);
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Error procesando factura electrónica (ID $facturaFiscalId): " . $e->getMessage());
            Log::error("Payload enviado:", ['payload' => $payload]);
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
                "send_dian" => config('services.dataico.Envio_Dian', true),
                "send_email" => true
            ],
            "invoice" => [
                "env" => config('services.dataico.tipo_envio', 'PRODUCCION'), // Variable de ambiente
                "dataico_account_id" => config('services.dataico.dataico_account_id', '936111eb-bbd2-4752-8b6e-fdc1d24f8e96'), // PRODUCCIÓN
                // Solución Definitiva: Limpieza extrema del número para evitar 500 Error
                "number" => (int) preg_replace('/[^0-9]/', '', $factura->factura_fiscal),
                "issue_date" => Carbon::parse($factura->fecha_registro)->format('d/m/Y'),
                "payment_date" => $factura->fecha_vencimiento_factura 
                    ? Carbon::parse($factura->fecha_vencimiento_factura)->format('d/m/Y') 
                    : Carbon::parse($factura->fecha_registro)->format('d/m/Y'),
                "order_reference" => "0",
                "invoice_type_code" => $this->getInvoiceTypeCode($documentType),
                "operation" => "SS_CUFE",
                "payment_means" => $this->getPaymentMeans($factura),
                "payment_means_type" => $factura->medio_pago == 1 ? "DEBITO" : "CREDITO",
                "numbering" => [
                    "resolution_number" => config('services.dataico.resolucion_produccion', '18764096453598'), // Lee de config, fallback producción
                    "prefix" => $this->getPrefix($documentType),
                    "flexible" => true
                ],
                // Campos de sector salud V2 - Formato d/m/Y estrictos
                "health" => [
                    "version" => "API_SALUD_V2",
                    "coverage" => "PLAN_DE_BENEFICIOS",
                    "provider_code" => "6300100363",
                    "payment_modality" => "PAGO_POR_EVENTO",
                    "contract_number" => $factura->contrato_id ?: '0',
                    "period_start_date" => Carbon::parse($factura->fecha_periodo_inicio ?: $factura->fecha_registro)->format('d/m/Y'),
                    "period_end_date" => Carbon::parse($factura->fecha_periodo_fin ?: $factura->fecha_registro)->format('d/m/Y'),
                    "associated_users" => [
                        "identification" => (string) ($factura->paciente->num_id ?? $tercero->tercero_id),
                        "identification_type" => $this->mapHealthIdType($factura->paciente->tipo_id ?? $factura->tipo_id_tercero),
                        "dian_identification_type" => $this->mapIdType($factura->paciente->tipo_id ?? $factura->tipo_id_tercero),
                        "identification_origin_country" => "República de Colombia",
                        "first_name" => $factura->paciente->primer_nombre ?? $tercero->primer_nombre ?? 'x',
                        "second_first_name" => $factura->paciente->segundo_nombre ?? '',
                        "last_name" => $factura->paciente->primer_apellido ?? $tercero->primer_apellido ?? 'x',
                        "second_last_name" => $factura->paciente->segundo_apellido ?? '',
                        "user_type" => "CONTRIBUTIVO_COTIZANTE",
                        "coverage" => "PLAN_DE_BENEFICIOS",
                        "provider_code" => "6300100363",
                        "payment_modality" => "PAGO_POR_EVENTO",
                        "authorization_number" => $factura->num_autorizacion ?: ''
                    ],
                    "recaudos" => $this->buildRecaudos($factura)
                ],
                // Documentos asociados
                "associated_documents" => $this->buildAssociatedDocuments($factura),
                "notes" => $this->buildNotes($factura),
                "customer" => [
                    "email" => $tercero->email ?? 'correo@generico.com',
                    "phone" => $tercero->telefono ?? '0000000',
                    "party_identification_type" => $this->mapIdType($factura->tipo_id_tercero),
                    "party_identification" => (string) $factura->tercero_id,
                    "party_type" => ($factura->tipo_id_tercero == 'NIT') ? "PERSONA_JURIDICA" : "PERSONA_NATURAL",
                    "tax_level_code" => (strpos($tercero->regimen, 'SIMPLIFICADO') !== false) ? 'SIMPLIFICADO' : 'RESPONSABLE_DE_IVA',
                    "regimen" => 'ORDINARIO',
                    "department" => substr($this->cleanDataValue($tercero->departamento, 'codigo_dpto_dian') ?: '05', 0, 2),
                    "city" => substr($this->cleanDataValue($tercero->ciudad, 'codigo_muni_dian') ?: '05001', -3),
                    "address_line" => $this->cleanDataValue($tercero->direccion) ?: 'S/D',
                    "country_code" => $this->cleanDataValue($tercero->pais, 'tipo_pais_id') ?: 'CO',
                    "company_name" => (string) ($tercero->nombre_tercero ?? 'SIN NOMBRE'),
                    "first_name" => (string) ($tercero->primer_nombre ?? 'x'),
                    "family_name" => (string) ($tercero->primer_apellido ?? 'x'),
                ],
                "items" => $this->buildInvoiceItems($factura, $documentType)
            ]
        ];
        return $payload;
    }

    protected function buildAssociatedDocuments(FacFactura $factura)
    {
        $docs = [];
        if (($factura->valor_cuota_moderadora ?? 0) > 0) {
            $docs[] = [
                "amount" => (string) $factura->valor_cuota_moderadora,
                "issue_date" => Carbon::parse($factura->fecha_registro)->format('d/m/Y'),
                "medical_fee_code" => "CUOTA_MODERADORA",
                "number" => "REC-M" . $factura->factura_fiscal,
                "description" => "CUOTA MODERADORA"
            ];
        }
        if (($factura->valor_cuota_paciente ?? 0) > 0) {
            $docs[] = [
                "amount" => (string) $factura->valor_cuota_paciente,
                "issue_date" => Carbon::parse($factura->fecha_registro)->format('d/m/Y'),
                "medical_fee_code" => "COPAGO",
                "number" => "REC-P" . $factura->factura_fiscal,
                "description" => "COPAGO"
            ];
        }
        return $docs;
    }

    protected function buildRecaudos(FacFactura $factura)
    {
        $recaudos = [];
        if (($factura->valor_cuota_moderadora ?? 0) > 0) {
            $recaudos[] = [
                "description" => "CUOTA MODERADORA",
                "amount" => (float) $factura->valor_cuota_moderadora,
                "issue_date" => Carbon::parse($factura->fecha_registro)->format('d/m/Y'),
                "medical_fee_code" => "CUOTA_MODERADORA"
            ];
        }
        if (($factura->valor_cuota_paciente ?? 0) > 0) {
            $recaudos[] = [
                "description" => "COPAGO",
                "amount" => (float) $factura->valor_cuota_paciente,
                "issue_date" => Carbon::parse($factura->fecha_registro)->format('d/m/Y'),
                "medical_fee_code" => "COPAGO"
            ];
        }
        return $recaudos;
    }

    protected function buildNotes(FacFactura $factura)
    {
        // Retorna un array de ejemplo, puedes personalizar según tu lógica
        return [
            "FACTURADOR: SISTEMA MASTER",
            "SON: DIECIOCHO MIL SEISCIENTOS TRECE PESOS MCTE"
        ];
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
            'PT' => 'PEP',
        ];
        return $map[$type] ?? 'NIT';
    }

    /**
     * Mapeo específico para el objeto health.associated_users (DataIco Salud V2)
     */
    protected function mapHealthIdType($type)
    {
        $map = [
            'CC' => 'CEDULA_CIUDADANIA',
            'CE' => 'CEDULA_EXTRANJERIA',
            'TI' => 'TARJETA_IDENTIDAD',
            'RC' => 'REGISTRO_CIVIL_NACIMIENTO',
            'PA' => 'PASAPORTE',
            'PE' => 'PERMISO_ESPECIAL_PERMANENCIA',
            'PT' => 'PERMISO_PROTECCION_TEMPORAL',
            'NIT' => 'CEDULA_CIUDADANIA', // DataIco V2 no admite NIT en personas de salud
        ];
        return $map[$type] ?? 'CEDULA_CIUDADANIA';
    }

    protected function buildInvoiceItems(FacFactura $factura, int $documentType)
    {
        $items = [];
        // Si hay items detallados en la factura
        if ($factura->items && $factura->items->count() > 0) {
            foreach ($factura->items as $item) {
                // Intentar obtener el detalle de la orden de servicio
                $osItem = $item->ordenServicioItem;
                if (!$osItem && $item->item_id) {
                    $osItem = \App\Models\OrdenServicioItem::where('servicio_id', $item->item_id)
                        ->where('orden_servicio_id', $item->orden_servicio_id)
                        ->first();
                }
                $description = 'Servicios Hospitalarios';
                $price = (float) ($item->valor_total ?? 0);
                $quantity = 1;
                // SKU nunca puede ser vacío
                $sku = $item->item_id ? $item->item_id : 'SERV_001';
                if ($osItem) {
                    $sku = $osItem->item_id ? $osItem->item_id : 'SERV_001';
                    $quantity = (float) $osItem->cantidad;
                    $description = $osItem->nombre_servicio ?: $osItem->descripcion;
                    $price = (float) $osItem->precio_unitario;
                } elseif ($price == 0) {
                    // Si no existe en la orden de servicio y no tiene precio, lo omitimos
                    continue;
                }
                $items[] = [
                    "sku" => (string) $sku,
                    "quantity" => (float) $quantity,
                    "description" => (string) $description,
                    "price" => (float) $price,
                    "original_price" => (float) $price,
                    "taxes" => [
                        [
                            "tax_category" => "IVA",
                            "tax_rate" => 0,
                            "tax_amount" => 0,
                            "taxable_amount" => (float) $price
                        ]
                    ]
                ];
            }
        } else {
            // Item genérico si no hay detalles (vía Concepto)
            $items[] = [
                "sku" => "GEN001",
                "quantity" => 1.0,
                "description" => $factura->concepto ?? 'Servicios de Salud Integrales',
                "price" => (float) $factura->total_factura,
                "original_price" => (float) $factura->total_factura,
                "taxes" => [
                    [
                        "tax_category" => "IVA",
                        "tax_rate" => 0,
                        "tax_amount" => 0,
                        "taxable_amount" => (float) $factura->total_factura
                    ]
                ]
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
            "period_start_date" => Carbon::parse($factura->fecha_periodo_inicio ?? $factura->fecha_registro)->format('d/m/Y'),
            "period_end_date" => Carbon::parse($factura->fecha_periodo_fin ?? $factura->fecha_registro)->format('d/m/Y'),
        ];
    }

    /**
     * Limpia y extrae códigos de campos que pueden venir como strings JSON o EDN de Clojure.
     */
    protected function cleanDataValue($value, $key = null)
    {
        if (empty($value)) return '';
        if (is_array($value)) return $value[$key] ?? array_values($value)[0] ?? '';
        
        $value = trim((string)$value);

        // 1. Caso JSON: {"codigo_dpto_dian":"05",...} o similar
        if (strpos($value, '{') === 0) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                if ($key && isset($decoded[$key])) return (string)$decoded[$key];
                
                // Intentar encontrar códigos DIAN en el JSON
                $dianKeys = ['codigo_dpto_dian', 'codigo_muni_dian', 'codigo', 'id'];
                foreach ($dianKeys as $dk) {
                    if (isset($decoded[$dk])) return (string)$decoded[$dk];
                }
                
                // Si es una dirección
                if (isset($decoded['direccion'])) return (string)$decoded['direccion'];
            }
        }

        // 2. Caso EDN: {:codigo-dpto-dian "05" ...}
        if (strpos($value, '{:') === 0) {
            if ($key) {
                $keyAlt = str_replace('_', '-', $key);
                if (preg_match('/:' . $key . '\s+"([^"]+)"/', $value, $matches)) return $matches[1];
                if (preg_match('/:' . $keyAlt . '\s+"([^"]+)"/', $value, $matches)) return $matches[1];
            }
            // Fallback para EDN si no hay key
            if (preg_match('/:codigo[^ ]*\s+"([^"]+)"/', $value, $matches)) return $matches[1];
            if (preg_match('/:direccion\s+"([^"]+)"/', $value, $matches)) return $matches[1];
        }

        return $value;
    }

    protected function determineOperation(FacFactura $factura)
    {
        // SS_RECAUDO si hay copagos/cuotas moderadoras involucradas
        if ($factura->tipo_factura == 0) return "SS_RECAUDO";
        return "SS_SIN_APORTE";
    }

    protected function auditResult(FacFactura $factura, array $result, array $payload)
    {
        try {
            if (isset($result['success']) && $result['success']) {
                // Extraer datos de la respuesta exitosa
                $responseData = $result['data'] ?? [];
                
                // Crear registro de auditoría
                $auditRecord = AuditoriaDataIco::create([
                    'prefijo' => $factura->prefijo,
                    'factura_fiscal_id' => $factura->factura_fiscal_id,
                    'numero' => $responseData['number'] ?? $factura->prefijo . $factura->numero_factura,
                    'dian_status' => $responseData['dian_status'] ?? 'DIAN_EN_PROCESO',
                    'customer_status' => $responseData['customer_status'] ?? null,
                    'email_status' => $responseData['email_status'] ?? null,
                    'cufe' => $responseData['cufe'] ?? null,
                    'uuid' => $responseData['uuid'] ?? null,
                    'issue_date' => $responseData['issue_date'] ?? null,
                    'payment_date' => $responseData['payment_date'] ?? null,
                    'xml_url' => $responseData['xml_url'] ?? null,
                    'pdf_url' => $responseData['pdf_url'] ?? null,
                    'qrcode' => $responseData['qrcode'] ?? null,
                    'json_respuesta' => json_encode($responseData),
                ]);
                
                // Determinar estado basado en dian_status
                $estadoElectronico = match ($responseData['dian_status'] ?? 'DIAN_EN_PROCESO') {
                    'DIAN_ACEPTADO' => 'ACEPTADA',
                    'DIAN_RECHAZADO' => 'RECHAZADA',
                    'DIAN_EN_PROCESO' => 'EN_PROCESO',
                    default => 'ENVIADA',
                };
                
                // Actualizar factura con datos de la respuesta
                $factura->update([
                    'estado_electronico' => $estadoElectronico,
                    'cufe' => $responseData['cufe'] ?? null,
                    'uuid_dataico' => $responseData['uuid'] ?? null,
                    'response_dataico' => json_encode($responseData),
                    'fecha_respuesta_dataico' => now(),
                ]);
                
                Log::info("Factura ID {$factura->factura_fiscal_id} auditada en DataIco", [
                    'cufe' => $responseData['cufe'] ?? 'N/A',
                    'dian_status' => $responseData['dian_status'] ?? 'N/A',
                    'auditoria_id' => $auditRecord->id_auditoria_dataico,
                ]);
                
            } else {
                // Crear registro de auditoría para errores
                $errorData = $result['errors'] ?? [];
                
                $auditRecord = AuditoriaDataIco::create([
                    'prefijo' => $factura->prefijo,
                    'factura_fiscal_id' => $factura->factura_fiscal_id,
                    'numero' => $factura->prefijo . $factura->numero_factura,
                    'dian_status' => 'ERROR',
                    'customer_status' => null,
                    'email_status' => null,
                    'cufe' => null,
                    'uuid' => null,
                    'json_respuesta' => json_encode([
                        'error' => true,
                        'message' => $result['message'] ?? 'Error desconocido',
                        'errors' => $errorData,
                        'sent_payload' => $payload,
                    ]),
                ]);
                
                // Actualizar factura con error
                $factura->update([
                    'estado_electronico' => 'ERROR',
                    'response_dataico' => json_encode([
                        'error' => true,
                        'message' => $result['message'] ?? 'Error en envío a DataIco',
                    ]),
                    'fecha_respuesta_dataico' => now(),
                ]);
                
                Log::error("Factura ID {$factura->factura_fiscal_id} falló en DataIco", [
                    'message' => $result['message'] ?? 'Error desconocido',
                    'auditoria_id' => $auditRecord->id_auditoria_dataico,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error creando registro de auditoría para factura {$factura->factura_fiscal_id}: " . $e->getMessage());
        }
    }
}

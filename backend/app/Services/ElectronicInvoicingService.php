<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\FacFactura;
use App\Models\OrdenServicioItem;
use App\Models\Tercero;
use App\Models\AuditoriaDataIco;
use App\Models\Impuesto;

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
        $payload = null;
        
        try {
            // 1. Obtener datos de la factura con relaciones
            $factura = FacFactura::with(['items.ordenServicioItem.ordenServicio', 'tercero', 'usuario'])->findOrFail($facturaFiscalId);
            
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
            if ($payload) {
                Log::error("Payload enviado:", ['payload' => $payload]);
            }
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
    /**
     * Convierte un número a palabras en español (colombiano)
     */
    protected function numberToWords($num)
    {
        $num = (int)$num;
        if ($num === 0) return 'CERO';

        $unidades = array('', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE');
        $decenas = array('', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA');
        $centenas = array('', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS');
        $divisores = array(
            1000000000 => 'MIL MILLONES',
            1000000 => 'MILLONES',
            1000 => 'MIL',
            1 => ''
        );

        $palabras = '';
        
        if ($num < 10) {
            return $unidades[$num];
        } elseif ($num < 20) {
            $diccionario = array('DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECIOCHO', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE');
            return $diccionario[$num - 10];
        } elseif ($num < 100) {
            $decena = (int)($num / 10);
            $unidad = $num % 10;
            if ($unidad === 0) {
                return $decenas[$decena];
            } else {
                return $decenas[$decena] . ' Y ' . $unidades[$unidad];
            }
        } elseif ($num < 1000) {
            $centena = (int)($num / 100);
            $resto = $num % 100;
            $palabras = $centenas[$centena];
            if ($resto > 0) {
                $palabras .= ' ' . $this->numberToWords($resto);
            }
            return $palabras;
        } else {
            foreach ($divisores as $divisor => $escalas) {
                if ($num >= $divisor) {
                    $cociente = (int)($num / $divisor);
                    $resto = $num % $divisor;
                    if ($escalas !== '') {
                        $palabras = $this->numberToWords($cociente) . ' ' . $escalas;
                    } else {
                        $palabras = $this->numberToWords($cociente);
                    }
                    if ($resto > 0) {
                        $palabras .= ' ' . $this->numberToWords($resto);
                    }
                    break;
                }
            }
        }
        
        return trim($palabras);
    }

    /**
     * Formatea un número a letras con denominación de moneda
     */
    protected function amountToWords($amount)
    {
        $pesos = (int)$amount;
        $centavos = round(($amount - $pesos) * 100);
        
        $palabras = $this->numberToWords($pesos) . ' PESOS';
        if ($centavos > 0) {
            $palabras .= ' CON ' . $this->numberToWords($centavos) . ' CENTAVOS';
        }
        
        return $palabras . ' MCTE';
    }

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
        try {
            $tercero = $factura->tercero;

            // Fallback first_name / family_name desde nombre_tercero si los campos individuales están vacíos
            $nombrePartes = array_values(array_filter(explode(' ', trim($tercero->nombre_tercero ?? ''))));
            $firstNameValue  = !empty($tercero->primer_nombre)
                ? $tercero->primer_nombre
                : (!empty($nombrePartes) ? $nombrePartes[0] : 'x');
            $familyNameValue = !empty($tercero->primer_apellido)
                ? $tercero->primer_apellido
                : (count($nombrePartes) > 1 ? implode(' ', array_slice($nombrePartes, 1)) : $firstNameValue);

            // Construir base del invoice
            $invoiceData = [
                "env" => config('services.dataico.tipo_envio', 'PRODUCCION'),
                "dataico_account_id" => config('services.dataico.dataico_account_id', '936111eb-bbd2-4752-8b6e-fdc1d24f8e96'),
                "number" => (int) preg_replace('/[^0-9]/', '', $factura->factura_fiscal),
                "issue_date" => Carbon::parse($factura->fecha_registro)->format('d/m/Y'),
                // Calculamos fecha vencimiento sumando los días de crédito del tercero a la fecha de registro
                "payment_date" => Carbon::parse($factura->fecha_registro)
                    ->addDays((int)($tercero->dias_credito ?? 0))
                    ->format('d/m/Y'),
                "order_reference" => "0",
                "invoice_type_code" => $this->getInvoiceTypeCode($documentType),
                "operation" => "ESTANDAR",
                "payment_means" => $this->getPaymentMeans($factura),
                "payment_means_type" => $factura->medio_pago == 1 ? "DEBITO" : "CREDITO",
                "numbering" => [
                    "resolution_number" => config('services.dataico.resolucion_produccion', '18764096453598'),
                    "prefix" => $this->getPrefix($documentType),
                    "flexible" => true
                ],
                "email" => $tercero->email ?? 'correo@generico.com',
                "phone" => $tercero->telefono ?? '0000000',
                "party_identification_type" => $this->mapIdType($factura->tipo_id_tercero),
                "party_identification" => (string) $factura->tercero_id,
                "party_type" => ($factura->tipo_id_tercero == 'NIT') ? "PERSONA_JURIDICA" : "PERSONA_NATURAL",
                "tax_level_code" => (strpos($tercero->regimen, 'SIMPLIFICADO') !== false) ? 'SIMPLIFICADO' : 'RESPONSABLE_DE_IVA',
                "regimen" => 'ORDINARIO',
                "department" => substr((string)($tercero->tipo_dpto_id ?? '05'), 0, 2),
                "city" => substr(str_pad((string)($tercero->tipo_mpio_id ?? '001'), 3, '0', STR_PAD_LEFT), -3),
                "address_line" => trim((string)($tercero->direccion ?? 'S/D')) ?: 'S/D',
                "country_code" => (string)($tercero->tipo_pais_id ?? 'CO'),
                "company_name" => (string) ($tercero->nombre_tercero ?? 'SIN NOMBRE'),
                "first_name" => (string) $firstNameValue,
                "family_name" => (string) $familyNameValue,
                "items" => $this->buildInvoiceItems($factura, $documentType),
                "retentions" => $this->buildRetentions($factura),
                "notes" => $this->buildNotes($factura)
            ];

            // NOTA: No enviamos "health" - DataIco lo requiere solo cuando está COMPLETAMENTE lleno
            // El JSON exitoso de ejemplo no incluye este bloque

            $payload = [
                "actions" => [
                    "send_dian" => config('services.dataico.Envio_Dian', false),
                    "send_email" => true
                ],
                "invoice" => $invoiceData
            ];

            return $payload;
        } catch (\Exception $e) {
            Log::error("Error en buildInvoicePayload: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Retención en la fuente de la factura: [porcentaje, valor].
     * Usa el valor guardado al facturar; en facturas anteriores (sin ese dato)
     * lo toma de la orden de servicio.
     */
    protected function getRetencionFuente(FacFactura $factura): array
    {
        if ($factura->porcentaje_ret_fuente !== null) {
            return [
                (float) $factura->porcentaje_ret_fuente,
                (float) ($factura->valor_ret_fuente ?? 0),
            ];
        }

        $porcentaje = 0.0;
        foreach ($factura->items as $item) {
            $ordenServicio = $item->ordenServicioItem?->ordenServicio;
            if (!$ordenServicio && $item->item_id) {
                $ordenServicio = \App\Models\OrdenServicioItem::with('ordenServicio')->find($item->item_id)?->ordenServicio;
            }
            $pct = (float) ($ordenServicio->porcentaje_ret_fuente ?? 0);
            if ($pct > 0) {
                $porcentaje = $pct;
                break;
            }
        }

        return [$porcentaje, round(((float) ($factura->total_factura ?? 0)) * $porcentaje / 100, 2)];
    }

    protected function buildRetentions(FacFactura $factura)
    {
        [$porcentajeRetFuente, $valorRetFuente] = $this->getRetencionFuente($factura);

        if ($porcentajeRetFuente <= 0) {
            return [];
        }

        return [[
            "tax_category" => "RET_FUENTE",
            "tax_rate" => $porcentajeRetFuente,
            "base_amount" => (float) ($factura->total_factura ?? 0),
            "amount" => $valorRetFuente
        ]];
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

    /**
     * Calcula el total a pagar incluyendo impuestos y restando retenciones.
     */
    protected function calculateTotalPayable(FacFactura $factura)
    {
        $baseTotal = (float) ($factura->total_factura ?? 0);
        $totalImpuestos = 0;

        if ($factura->items && $factura->items->count() > 0) {
            // 1. IMPUESTOS
            foreach ($factura->items as $item) {
                // Recuperar OrdenServicioItem asociado
                $osItem = $item->ordenServicioItem;
                if (!$osItem && $item->item_id) {
                    $osItem = \App\Models\OrdenServicioItem::with('ordenServicio')->find($item->item_id);
                }

                if ($osItem) {
                    $price = (float) $osItem->precio_unitario;
                    $quantity = (float) $osItem->cantidad;
                    $subtotalItem = $price * $quantity;

                    // Aplicar descuento por ítem a la base gravable
                    $descuento = (float) ($osItem->porcentaje_descuento ?? 0);
                    $baseGravable = $subtotalItem * (1 - $descuento / 100);

                    // Buscar Impuesto
                    $porcentajeImpuesto = 0;
                    $impuestoId = $osItem->impuesto_id;
                    
                    // Fallback a servicio si no está en el item
                    if (!$impuestoId && $osItem->servicio_id) {
                        $servicio = \App\Models\Servicio::find($osItem->servicio_id);
                        if ($servicio) {
                            $impuestoId = $servicio->impuesto_id;
                        }
                    }

                    if ($impuestoId) {
                        $impuesto = Impuesto::find($impuestoId);
                        if ($impuesto) {
                            $porcentajeImpuesto = (float) ($impuesto->porcentaje_impuesto ?? $impuesto->porcentaje ?? 0);
                        }
                    }

                    $totalImpuestos += round(($baseGravable * $porcentajeImpuesto) / 100, 2);
                }
            }
        }

        // 2. RETENCION (mismo valor que se envía en "retentions")
        [, $totalRetenciones] = $this->getRetencionFuente($factura);
        $totalPagar = $baseTotal + $totalImpuestos - $totalRetenciones;
        
        return max(0, $totalPagar); // Evitar negativos
    }

    /**
     * Determina si la factura contiene al menos un ítem excluido de IVA (porcentaje = 0).
     */
    protected function tieneItemsExcluidosIva(FacFactura $factura): bool
    {
        if (!$factura->items || $factura->items->count() === 0) {
            return false;
        }
        foreach ($factura->items as $item) {
            $osItem = $item->ordenServicioItem;
            if (!$osItem && $item->item_id) {
                $osItem = \App\Models\OrdenServicioItem::find($item->item_id);
            }
            if (!$osItem) {
                continue;
            }
            $impuestoId = $osItem->impuesto_id;
            if (!$impuestoId && $osItem->servicio_id) {
                $servicio = \App\Models\Servicio::find($osItem->servicio_id);
                if ($servicio) {
                    $impuestoId = $servicio->impuesto_id;
                }
            }
            if ($impuestoId) {
                $impuesto = Impuesto::find($impuestoId);
                $porcentaje = (float) ($impuesto->porcentaje_impuesto ?? $impuesto->porcentaje ?? 0);
            } else {
                $porcentaje = 0;
            }
            if ($porcentaje == 0) {
                return true;
            }
        }
        return false;
    }

    protected function buildNotes(FacFactura $factura)
    {
        $notas = [];
        
        // FACTURADOR: Tomar del usuario que crea la factura
        $facturador = $factura->usuario ? $factura->usuario->nombre : 'SISTEMA MASTER';
        $notas[] = "FACTURADOR: {$facturador}";
        
        // SON: Convertir monto NETO a letras (Base + IVA - Retenciones)
        $totalPagar = $this->calculateTotalPayable($factura);
        $montoEnLetras = $this->amountToWords($totalPagar);
        $notas[] = "SON: {$montoEnLetras}";

        // NOTA LEGAL IVA: Solo cuando uno o más ítems están excluidos del IVA (0%)
        if ($this->tieneItemsExcluidosIva($factura)) {
            $notas[] = 'El servicio de computación en la nube se encuentra excluido del IVA conforme al artículo 476 numeral 21 del Estatuto Tributario y Oficio DIAN No. 100208192-190 de 2024. Los servicios de consultoría y soporte se facturan con IVA del 19%.';
        }
        
        // 1. Observaciones directas de la factura
        if (!empty($factura->observacion)) {
            $notas[] = "OBSERVACIONES: {$factura->observacion}";
        }

        // 2. Observaciones de la Orden de Servicio
        // Obtenemos las observaciones desde la tabla 'ordenes_servicios' vinculada a los items.
        if ($factura->items && $factura->items->count() > 0) {
            foreach ($factura->items as $item) {
                $ordenServicio = null;

                // Opción A: Vía relación cargada FacFacturaItem -> OrdenServicioItem -> OrdenServicio
                if ($item->ordenServicioItem && $item->ordenServicioItem->ordenServicio) {
                    $ordenServicio = $item->ordenServicioItem->ordenServicio;
                } 
                // Opción B: Si falla la relación, intentar buscar manualmente usando el ID
                elseif ($item->item_id) {
                     $osItem = \App\Models\OrdenServicioItem::with('ordenServicio')->find($item->item_id);
                     if ($osItem) {
                         $ordenServicio = $osItem->ordenServicio;
                     }
                }

                // Si encontramos la orden y tiene observaciones, las agregamos
                if ($ordenServicio && !empty($ordenServicio->observaciones)) {
                    $obsOrden = trim($ordenServicio->observaciones);
                    
                    // Verificamos no repetir la misma observación si ya está en la factura o ya fue agregada
                    $notaFormateada = "{$obsOrden}";
                    $yaExiste = in_array($notaFormateada, $notas) || 
                                ($obsOrden === trim($factura->observacion ?? ''));
                    
                    if (!$yaExiste) {
                         $notas[] = $notaFormateada;
                    }
                    
                    // Solo tomamos la observación de la primera orden encontrada para no saturar las notas
                    break; 
                }
            }
        }
        
        return $notas;
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
        // DataIco solo acepta: FACTURA_CONTINGENCIA, FACTURA_EXPORTACION, FACTURA_VENTA, NOTA_CREDITO, NOTA_DEBITO
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
                $impuestoId = null;
                $porcentajeImpuesto = 0;
                $observaciones = '';
                $descuento = 0;
                
                // SKU nunca puede ser vacío
                $sku = $item->item_id ? $item->item_id : 'SERV_001';
                if ($osItem) {
                    $sku = $osItem->item_id ? $osItem->item_id : 'SERV_001';
                    $quantity = (float) $osItem->cantidad;
                    $description = $osItem->nombre_servicio ?: $osItem->descripcion;
                    $price = (float) $osItem->precio_unitario;
                    $descuento = (float) ($osItem->porcentaje_descuento ?? 0);
                    
                    // Buscar impuesto: Primero en el item, si no, en el servicio asociado
                    $impuestoId = $osItem->impuesto_id;
                    if (!$impuestoId && $osItem->servicio_id) {
                         $servicio = \App\Models\Servicio::find($osItem->servicio_id);
                         if ($servicio) {
                             $impuestoId = $servicio->impuesto_id;
                         }
                    }

                    $observaciones = $osItem->observaciones ?? '';
                    
                    // Obtener información del impuesto si existe
                    if ($impuestoId) {
                        $impuesto = Impuesto::find($impuestoId);
                        if ($impuesto) {
                            $porcentajeImpuesto = (float) ($impuesto->porcentaje_impuesto ?? $impuesto->porcentaje ?? 0);
                        }
                    }
                } elseif ($price == 0) {
                    // Si no existe en la orden de servicio y no tiene precio, lo omitimos
                    continue;
                }
                
                // Concatenar observaciones a la descripción
                if (!empty($observaciones)) {
                    $description .= " - {$observaciones}";
                }
                
                $subtotal = round($price * $quantity, 2);
                // Base gravable = precio * cantidad * (1 - descuento/100)
                $baseGravable = round($subtotal * (1 - $descuento / 100), 2);
                $montoImpuesto = round(($baseGravable * $porcentajeImpuesto) / 100, 2);
                
                $itemData = [
                    "sku" => (string) $sku,
                    "quantity" => (float) $quantity,
                    "description" => (string) $description,
                    "price" => (float) $price,
                    "original_price" => (float) $price,
                    "taxes" => [
                        [
                            "tax_category" => "IVA",
                            "tax_rate" => $porcentajeImpuesto,
                            "tax_amount" => $montoImpuesto,
                            "taxable_amount" => $baseGravable
                        ]
                    ]
                ];

                // Descuento por ítem: DataIco calcula sobre price con discount_rate (%)
                if ($descuento > 0) {
                    $itemData["discount_rate"] = (float) $descuento;
                }

                $items[] = $itemData;
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
            // Buscar registro existente
            $auditRecord = AuditoriaDataIco::where('factura_fiscal_id', $factura->factura_fiscal_id)->first();

            if (isset($result['success']) && $result['success']) {
                // Extraer datos de la respuesta exitosa
                $responseData = $result['data'] ?? [];
                
                $auditData = [
                    'factura_fiscal_id' => $factura->factura_fiscal_id,
                    'prefijo' => $factura->prefijo,
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
                    'json_envio' => json_encode($payload),
                ];

                if ($auditRecord) {
                    $auditRecord->update($auditData);
                } else {
                    $auditRecord = AuditoriaDataIco::create($auditData);
                }
                
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
                
                Log::info("Factura ID {$factura->factura_fiscal_id} auditada en DataIco (Update: " . ($auditRecord->wasChanged() ? 'Yes' : 'No') . ")", [
                    'cufe' => $responseData['cufe'] ?? 'N/A',
                    'dian_status' => $responseData['dian_status'] ?? 'N/A',
                    'auditoria_id' => $auditRecord->id_auditoria_dataico,
                ]);
                
            } else {
                // Crear o Actualizar registro de auditoría para errores
                $errorData = $result['errors'] ?? [];
                
                $auditData = [
                    'factura_fiscal_id' => $factura->factura_fiscal_id,
                    'prefijo' => $factura->prefijo,
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
                    'json_envio' => json_encode($payload),
                ];

                if ($auditRecord) {
                    $auditRecord->update($auditData);
                } else {
                    $auditRecord = AuditoriaDataIco::create($auditData);
                }
                
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

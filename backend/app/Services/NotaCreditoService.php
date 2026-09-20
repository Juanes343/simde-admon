<?php

namespace App\Services;

use App\Models\NotaCredito;
use App\Models\NotaCreditoConcepto;
use App\Models\NotaCreditoItem;
use App\Models\OrdenServicioItem;
use App\Models\FacFactura;
use App\Models\FacturaExterna;
use App\Models\AuditoriaDataIco;
use App\Models\AuditoriaNotaCredito;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotaCreditoService
{
    protected $dataIcoService;
    protected $electronicInvoicingService;

    public function __construct(DataIcoService $dataIcoService, ElectronicInvoicingService $electronicInvoicingService)
    {
        $this->dataIcoService = $dataIcoService;
        $this->electronicInvoicingService = $electronicInvoicingService;
    }

    /**
     * Crear una nota crédito
     * 
     * @param array $data Datos de la nota crédito
     * @return array Resultado de la creación
     */
    public function crearNotaCredito(array $data)
    {
        try {
            // 1. Obtener factura referenciada (interna o externa)
            if (($data['tipo_factura_origen'] ?? 'INTERNA') === 'EXTERNA') {
                $facturaRef    = \App\Models\FacturaExterna::where('prefijo', $data['prefijo_factura'])
                                    ->where('factura_fiscal', (int) $data['factura_fiscal'])
                                    ->firstOrFail();
                $terceroId     = $facturaRef->tercero_id;
                $tipoIdTercero = $facturaRef->tipo_id_tercero;
                $tipoFactura   = $facturaRef->tipo_factura ?? '1';
            } else {
                $facturaRef    = FacFactura::where('prefijo', $data['prefijo_factura'])
                                    ->where('factura_fiscal', $data['factura_fiscal'])
                                    ->firstOrFail();
                $terceroId     = $facturaRef->tercero_id;
                $tipoIdTercero = $facturaRef->tipo_id_tercero;
                $tipoFactura   = $facturaRef->tipo_factura;

                // Validar que la factura esté activa y tenga saldo suficiente
                if ($facturaRef->estado !== '1') {
                    return [
                        'success' => false,
                        'message' => 'La factura no está activa. Solo se pueden crear notas crédito sobre facturas en estado Generada.',
                    ];
                }
                $saldoDisponible = (float) $facturaRef->saldo;
                if ($saldoDisponible <= 0) {
                    return [
                        'success' => false,
                        'message' => 'La factura no tiene saldo disponible para aplicar una nota crédito.',
                    ];
                }
                if ((float) $data['valor_nota'] > $saldoDisponible + 0.99) {
                    return [
                        'success' => false,
                        'message' => 'El valor de la nota crédito (' . number_format((float) $data['valor_nota'], 2, '.', '') . ') supera el saldo disponible de la factura (' . number_format($saldoDisponible, 2, '.', '') . ').',
                    ];
                }
            }

            // 2. Generar número siguiente (esto también actualiza la tabla documentos)
            $numeroNC = NotaCredito::generarSiguienteNumero(
                $data['empresa_id'],
                $data['prefijo']
            );

            // 3. Crear registro de nota crédito
            $notaCredito = NotaCredito::create([
                'empresa_id'      => $data['empresa_id'],
                'prefijo'         => $data['prefijo'],
                'nota_credito_id' => $numeroNC,
                'prefijo_factura' => $data['prefijo_factura'],
                'factura_fiscal'  => $data['factura_fiscal'],
                'concepto_id'     => $data['concepto_id'] ?? null,
                'valor_nota'      => $data['valor_nota'],
                'observacion'     => $data['observacion'] ?? null,
                'usuario_id'      => $data['usuario_id'] ?? null,
                'tipo_id_tercero' => $tipoIdTercero,
                'tercero_id'      => $terceroId,
                'tipo_factura'    => $tipoFactura,
                'estado'          => 'PENDIENTE',
                'tipo_nota'       => $data['tipo_nota'] ?? 'CREDITO',
                'alcance'         => $data['alcance'] ?? 'TOTAL',
            ]);

            // 4. Guardar items de la nota crédito si existen
            // Cargar concepto para usarlo en items sin item_id (facturas externas)
            $conceptoDesc = null;
            $conceptoCod  = null;
            if (!empty($data['concepto_id'])) {
                $conceptoObj  = \App\Models\NotaCreditoConcepto::find($data['concepto_id']);
                $conceptoDesc = $conceptoObj?->descripcion;
                $conceptoCod  = 'CONC-' . $data['concepto_id'];
            }

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $osItem = null;
                    $cantidad = 1;
                    $precio = $item['valor'];
                    $descripcion = $conceptoDesc ?? 'Item Nota Crédito';
                    $sku = $conceptoCod ?? 'ITEM-NC';

                    if (isset($item['item_id'])) {
                        // Buscar item original para traer datos
                        $osItem = OrdenServicioItem::find($item['item_id']);
                        if ($osItem) {
                            $descripcion = $osItem->nombre_servicio ?? $osItem->descripcion;
                            $sku = $osItem->servicio_id;
                            
                            // Verificar si es devolución total del ítem para conservar cantidad original
                            $totalOriginal = (float)($osItem->cantidad ?? 1) * (float)($osItem->precio_unitario ?? 0);
                            
                            // Si la diferencia es mínima, asumimos que es nota total por ese ítem
                            if (abs($totalOriginal - (float)$item['valor']) < 1.0) {
                                $cantidad = (float)($osItem->cantidad ?? 1);
                                $precio = (float)($osItem->precio_unitario ?? $item['valor']);
                            }
                        }
                    }

                    NotaCreditoItem::create([
                        'nota_credito_id' => $notaCredito->id,
                        'codigo_item' => $sku,
                        'descripcion' => $descripcion,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $item['valor'], // El subtotal de la línea siempre debe sumar lo que se pidió
                        'porcentaje_impuesto' => 0,
                        'valor_impuesto' => 0,
                        'total' => $item['valor'],
                    ]);
                }
            }

            // 5. Actualizar saldo de la factura interna y marcarla como anulada si el saldo queda en cero
            if ($facturaRef instanceof FacFactura) {
                $saldoActual = (float) $facturaRef->saldo;
                $valorNota   = (float) $data['valor_nota'];
                $nuevoSaldo  = max(0.0, round($saldoActual - $valorNota, 2));
                if ($nuevoSaldo < 1.0) {
                    $facturaRef->update(['saldo' => 0, 'estado' => '3']);
                    Log::info("Factura {$facturaRef->prefijo}{$facturaRef->factura_fiscal} marcada como anulada. Saldo anterior: {$saldoActual}, valor nota: {$valorNota}.");
                } else {
                    $facturaRef->update(['saldo' => $nuevoSaldo]);
                    Log::info("Factura {$facturaRef->prefijo}{$facturaRef->factura_fiscal} saldo actualizado de {$saldoActual} a {$nuevoSaldo}.");
                }
            }

            return [
                'success' => true,
                'message' => 'Nota crédito creada exitosamente',
                'data' => $notaCredito,
            ];

        } catch (\Exception $e) {
            Log::error('Error creando nota crédito: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear la nota crédito',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enviar nota crédito a DATAICO
     * 
     * @param int $notaCreditoId ID de la nota crédito
     * @return array Resultado del envío
     */
    public function enviarNotaCredito(int $notaCreditoId)
    {
        try {
            // 1. Obtener nota crédito con relaciones
            $notaCredito = NotaCredito::with(['concepto', 'usuario'])->findOrFail($notaCreditoId);
            $token = config('services.dataico.token');

            // 2. Obtener datos de la factura referenciada (interna o externa)
            $factura = FacFactura::where('prefijo', $notaCredito->prefijo_factura)
                                  ->where('factura_fiscal', $notaCredito->factura_fiscal)
                                  ->with(['tercero'])
                                  ->first();

            if ($factura) {
                // --- Factura INTERNA ---
                // 3. Obtener auditoría (CUFE)
                $auditoria = AuditoriaDataIco::where('prefijo', $notaCredito->prefijo_factura)
                                              ->where('factura_fiscal_id', $factura->factura_fiscal_id)
                                              ->first();

                if (!$auditoria) {
                    return [
                        'success' => false,
                        'message' => 'No se encontró información de factura electrónica referenciada',
                        'payload' => [
                            'error_interno' => 'No se encontró el registro en fe_auditoria_dataico',
                            'parametros_busqueda' => [
                                'prefijo'          => $notaCredito->prefijo_factura,
                                'factura_fiscal_id' => $factura->factura_fiscal_id,
                                'numero_factura'   => $notaCredito->factura_fiscal,
                            ],
                        ],
                    ];
                }

                // 4. Construir payload interno
                $payload = $this->buildNotaCreditoPayload($notaCredito, $factura, $auditoria);

            } else {
                // --- Factura EXTERNA ---
                $facturaExterna = FacturaExterna::where('prefijo', $notaCredito->prefijo_factura)
                                    ->where('factura_fiscal', (int) $notaCredito->factura_fiscal)
                                    ->first();

                if (!$facturaExterna) {
                    return [
                        'success' => false,
                        'message' => 'No se encontró la factura referenciada (ni en facturas internas ni en facturas externas)',
                    ];
                }

                $invoiceId = $this->resolveExternalInvoiceId($facturaExterna);

                // Fallback: consultar DataIco por referencia de la factura externa.
                if (empty($invoiceId)) {
                    $consultaFactura = $this->dataIcoService->getDocumentByReference(
                        'invoices',
                        (string) $facturaExterna->prefijo,
                        (string) $facturaExterna->factura_fiscal,
                        $token
                    );

                    if (!empty($consultaFactura['success'])) {
                        $facturaDataico = $this->extractDataicoDocumentData($consultaFactura['data'] ?? []);
                        $invoiceId = $facturaDataico['uuid'] ?? null;
                    }
                }

                if (empty($invoiceId)) {
                    return [
                        'success' => false,
                        'message' => 'La factura externa no tiene UUID/DataIco invoice_id. Debe sincronizarse primero en DataIco para poder emitir la nota crédito.',
                        'payload' => [
                            'prefijo_factura' => $notaCredito->prefijo_factura,
                            'factura_fiscal' => $notaCredito->factura_fiscal,
                        ],
                    ];
                }

                // 4. Construir payload externo
                $payload = $this->buildNotaCreditoPayloadExterno($notaCredito, $facturaExterna, $invoiceId);
            }

            $endpoint = $notaCredito->tipo_nota === 'DEBITO' ? 'debit_notes' : 'credit_notes';

            // 5. Antes de reenviar, consultar estado actual en DataIco para evitar duplicados.
            $consulta = $this->dataIcoService->getDocumentByReference(
                $endpoint,
                (string) $notaCredito->prefijo,
                (string) $notaCredito->nota_credito_id,
                $token,
                $notaCredito->uuid ?? null
            );

            if (!empty($consulta['success'])) {
                $consultaData = $this->extractDataicoDocumentData($consulta['data'] ?? []);
                $dianStatusConsulta = $consultaData['dian_status'] ?? null;

                if ($dianStatusConsulta && $dianStatusConsulta !== 'DIAN_RECHAZADO') {
                    $this->sincronizarNotaConDataico($notaCredito, $payload, $consultaData, [
                        'success' => true,
                        'data' => $consultaData,
                        'consultado_previo_reenvio' => true,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'La nota ya existe en DataIco y fue sincronizada localmente, no se realizó reenvío',
                        'data' => $notaCredito->fresh(),
                        'dataico_response' => $consultaData,
                        'synced_only' => true,
                    ];
                }
            }

            // 6. Enviar a DATAICO (si no estaba aceptada/en proceso)
            $result = $this->dataIcoService->sendDocument($payload, $endpoint, $token);

            // 6.1 Registrar o actualizar auditoría
            $responseData = $this->extractDataicoDocumentData($result['data'] ?? []);
            
            $auditoriaNC = AuditoriaNotaCredito::updateOrCreate(
                [
                    'prefijo_siis' => $notaCredito->prefijo,
                    'nota_credito_siis' => $notaCredito->nota_credito_id,
                ],
                [
                    'json_siis' => $payload,
                    'dataico_response' => $result,
                    'dian_status' => $responseData['dian_status'] ?? ($result['success'] ? 'ENVIADO' : 'ERROR'),
                    'numero' => $responseData['number'] ?? null,
                    'issue_date' => $responseData['issue_date'] ?? null,
                    'xml_url' => $responseData['xml_url'] ?? null,
                    'payment_date' => $responseData['payment_date'] ?? null,
                    'customer_status' => $responseData['customer_status'] ?? null,
                    'pdf_url' => $responseData['pdf_url'] ?? null,
                    'email_status' => $responseData['email_status'] ?? null,
                    'cufe' => $responseData['cufe'] ?? null,
                    'uuid' => $responseData['uuid'] ?? null,
                    'qrcode' => $responseData['qrcode'] ?? null,
                    'prefix' => $responseData['numbering']['prefix'] ?? null,
                    'resolution_number' => $responseData['numbering']['resolution_number'] ?? null,
                    'dian_messages' => isset($responseData['dian_send_error_code']) ? [$responseData['dian_send_error_code']] : null,
                ]
            );

            // 7. Actualizar estado según respuesta
            if ($result['success']) {
                $responseData = $this->extractDataicoDocumentData($result['data'] ?? []);
                $notaCredito->update([
                    'estado' => 'ENVIADO',
                    'fecha_envio' => now(),
                    'cufe' => $responseData['cufe'] ?? null,
                    'uuid' => $responseData['uuid'] ?? null,
                    'respuesta_dataico' => $responseData,
                ]);

                // Si la respuesta indica aceptación inmediata
                if ($responseData['dian_status'] === 'DIAN_ACEPTADO') {
                    $notaCredito->update([
                        'estado' => 'ACEPTADO',
                        'fecha_aceptacion' => now(),
                    ]);
                }

                return [
                    'success' => true,
                    'message' => 'Nota crédito enviada exitosamente',
                    'data' => $notaCredito,
                    'dataico_response' => $responseData,
                ];
            }

            // Si DataIco indica que ya existe/no se puede modificar, consultar y sincronizar.
            $errorJson = json_encode($result['errors'] ?? []);
            if (str_contains((string) $errorJson, 'Solo puede modificar Nota Crédito')) {
                $consultaPostError = $this->dataIcoService->getDocumentByReference(
                    $endpoint,
                    (string) $notaCredito->prefijo,
                    (string) $notaCredito->nota_credito_id,
                    $token,
                    $notaCredito->uuid ?? null
                );

                if (!empty($consultaPostError['success'])) {
                    $consultaData = $this->extractDataicoDocumentData($consultaPostError['data'] ?? []);
                    if (!empty($consultaData)) {
                        $this->sincronizarNotaConDataico($notaCredito, $payload, $consultaData, [
                            'success' => true,
                            'data' => $consultaData,
                            'consultado_post_error' => true,
                        ]);

                        return [
                            'success' => true,
                            'message' => 'La nota ya existía en DataIco y se sincronizó el estado local',
                            'data' => $notaCredito->fresh(),
                            'dataico_response' => $consultaData,
                            'synced_only' => true,
                        ];
                    }
                }
            }

            // Error en envío
            $notaCredito->update([
                'estado' => 'RECHAZADO',
                'respuesta_dataico' => $result,
            ]);

            $errorMessage = $result['message'] ?? 'Error al enviar nota crédito';
            if (!empty($result['errors'])) {
                if (is_array($result['errors'])) {
                    $errorDetails = [];
                    foreach ($result['errors'] as $key => $err) {
                        if (is_array($err)) {
                            // Si el error es un array anidado, lo convertimos a JSON para evitar el error "Array to string conversion"
                            $errorStr = json_encode($err, JSON_UNESCAPED_UNICODE);
                        } else {
                            $errorStr = $err;
                        }
                        $errorDetails[] = "[$key]: $errorStr";
                    }
                    $errorMessage = 'Errores de validación DataIco: ' . implode(' | ', $errorDetails);
                } else {
                    $errorMessage = 'Errores de validación DataIco: ' . $result['errors'];
                }
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'errors' => $result['errors'] ?? [],
                'data' => $notaCredito,
                'payload' => $payload
            ];

        } catch (\Exception $e) {
            Log::error('Error enviando nota crédito: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al enviar la nota crédito',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Construir payload para DATAICO V2
     */
    protected function buildNotaCreditoPayload(NotaCredito $notaCredito, FacFactura $factura, AuditoriaDataIco $auditoria)
    {
        $tercero = $factura->tercero;

        // Construir items simples (según tipo_factura)
        $items = $this->buildNotaCreditoItems($notaCredito, $factura);

        // Preparar datos del cliente
        $customerData = [
            'email' => $tercero->email ?? 'correo@generico.com',
            'phone' => $tercero->telefono ?? '0000000',
            'party_identification_type' => $this->mapIdType($factura->tipo_id_tercero),
            'party_identification' => (string) $factura->tercero_id,
            'party_type' => ($factura->tipo_id_tercero == 'NIT') ? 'PERSONA_JURIDICA' : 'PERSONA_NATURAL',
            'tax_level_code' => 'SIMPLIFICADO',
            'regimen' => 'ORDINARIO',
            'department' => '05',
            'city' => '001',
            'address_line' => $tercero->direccion ?? 'S/D',
            'country_code' => 'CO',
            'company_name' => (string) ($tercero->nombre_tercero ?? 'SIN NOMBRE'),
            'first_name' => (string) ($tercero->primer_nombre ?? 'x'),
            'family_name' => (string) ($tercero->primer_apellido ?? 'x'),
        ];

        $tipoEnvio = config('services.dataico.tipo_envio', 'PRODUCCION');
        
        if ($tipoEnvio === 'PRUEBAS') {
            $prefijo = $notaCredito->tipo_nota === 'DEBITO' 
                ? config('services.dataico.prefijo_pruebas_nd', 'NDSETT') 
                : config('services.dataico.prefijo_pruebas_nc', 'NCSET');
        } else {
            $prefijo = $notaCredito->prefijo;
        }

        $facturador = $notaCredito->usuario?->nombre ?? 'SISTEMA MASTER';

        // Construir objeto credit_note o debit_note
        $documentData = [
            'env' => $tipoEnvio,
            'dataico_account_id' => config('services.dataico.dataico_account_id'),
            'invoice_id' => $auditoria->uuid ?? '',
            'number' => (int) $notaCredito->nota_credito_id,
            'issue_date' => $notaCredito->created_at->format('d/m/Y H:i:s'),
            'reason' => 'OTROS',
            'numbering' => [
                'prefix' => $prefijo,
                'flexible' => true,
            ],
            'customer' => $customerData,
            'notes' => $this->formatDataicoNotes([
                !empty($notaCredito->concepto?->descripcion) ? ('Concepto: ' . $notaCredito->concepto->descripcion) : null,
                !empty($notaCredito->observacion) ? ('Observación: ' . $notaCredito->observacion) : null,
                'Valor: $' . number_format($notaCredito->valor_nota, 0),
                'SON: ' . $this->amountToWords((float) $notaCredito->valor_nota),
                !empty($facturador) ? ('FACTURADOR: ' . $facturador) : null,
            ]),
            'items' => $items,
        ];

        // Agregar health solo si tipo_factura NO es 5 (Conceptos)
        if ($factura->tipo_factura != '5') {
            $documentData['health'] = $this->buildHealthObject($factura);
        }

        $payloadKey = $notaCredito->tipo_nota === 'DEBITO' ? 'debit_note' : 'credit_note';

        return [
            'actions' => [
                'send_dian' => config('services.dataico.Envio_Dian', false),
                'send_email' => true,
            ],
            $payloadKey => $documentData,
        ];
    }

    /**
     * Construir payload para DATAICO cuando la factura referenciada es EXTERNA
     */
    protected function buildNotaCreditoPayloadExterno(NotaCredito $notaCredito, FacturaExterna $facturaExterna, string $invoiceId)
    {
        // Intentar cargar tercero desde la tabla de terceros
        $tercero = \App\Models\Tercero::where('tipo_id_tercero', $facturaExterna->tipo_id_tercero)
                                       ->where('tercero_id', $facturaExterna->tercero_id)
                                       ->first();

        $customerData = [
            'email'                      => $tercero->email ?? 'correo@generico.com',
            'phone'                      => $tercero->telefono ?? '0000000',
            'party_identification_type'  => $this->mapIdType($facturaExterna->tipo_id_tercero),
            'party_identification'       => (string) $facturaExterna->tercero_id,
            'party_type'                 => ($facturaExterna->tipo_id_tercero === 'NIT') ? 'PERSONA_JURIDICA' : 'PERSONA_NATURAL',
            'tax_level_code'             => 'SIMPLIFICADO',
            'regimen'                    => 'ORDINARIO',
            'department'                 => '05',
            'city'                       => '001',
            'address_line'               => $tercero->direccion ?? 'S/D',
            'country_code'               => 'CO',
            'company_name'               => (string) ($tercero->nombre_tercero ?? 'SIN NOMBRE'),
            'first_name'                 => (string) ($tercero->primer_nombre ?? 'x'),
            'family_name'                => (string) ($tercero->primer_apellido ?? 'x'),
        ];

        $tipoEnvio = config('services.dataico.tipo_envio', 'PRODUCCION');
        $prefijo   = ($tipoEnvio === 'PRUEBAS')
            ? ($notaCredito->tipo_nota === 'DEBITO'
                ? config('services.dataico.prefijo_pruebas_nd', 'NDSETT')
                : config('services.dataico.prefijo_pruebas_nc', 'NCSET'))
            : $notaCredito->prefijo;

        // Construir items desde notas_credito_items (ya guardados al crear)
        $notaCredito->load('items');
        $items = [];
        if ($notaCredito->items && $notaCredito->items->count() > 0) {
            foreach ($notaCredito->items as $ncItem) {
                $items[] = [
                    'sku'            => $ncItem->codigo_item ?? 'ITEM-NC-EXT',
                    'description'    => $ncItem->descripcion ?? ($notaCredito->concepto->descripcion ?? 'Nota'),
                    'quantity'       => (float) $ncItem->cantidad,
                    'price'          => (float) $ncItem->precio_unitario,
                    'original_price' => (float) $ncItem->precio_unitario,
                ];
            }
        }
        if (empty($items)) {
            $items[] = [
                'sku'            => $notaCredito->prefijo . $notaCredito->nota_credito_id,
                'description'    => $notaCredito->concepto->descripcion ?? 'NOTA CRÉDITO FACTURA EXTERNA',
                'quantity'       => 1,
                'price'          => (float) $notaCredito->valor_nota,
                'original_price' => (float) $notaCredito->valor_nota,
            ];
        }

        $facturador = $notaCredito->usuario?->nombre ?? 'SISTEMA MASTER';

        $documentData = [
            'env'                => $tipoEnvio,
            'dataico_account_id' => config('services.dataico.dataico_account_id'),
            'invoice_id'         => $invoiceId,
            'number'             => (int) $notaCredito->nota_credito_id,
            'issue_date'         => $notaCredito->created_at->format('d/m/Y H:i:s'),
            'reason'             => 'OTROS',
            'numbering'          => [
                'prefix'   => $prefijo,
                'flexible' => true,
            ],
            'customer' => $customerData,
            'notes'    => $this->formatDataicoNotes([
                !empty($notaCredito->concepto?->descripcion) ? ('Concepto: ' . $notaCredito->concepto->descripcion) : null,
                !empty($notaCredito->observacion) ? ('Observación: ' . $notaCredito->observacion) : null,
                'SON: ' . $this->amountToWords((float) $notaCredito->valor_nota),
                'Factura externa referenciada: ' . $facturaExterna->prefijo . '-' . $facturaExterna->factura_fiscal,
                !empty($facturador) ? ('FACTURADOR: ' . $facturador) : null,
            ]),
            'items' => $items,
        ];

        $payloadKey = $notaCredito->tipo_nota === 'DEBITO' ? 'debit_note' : 'credit_note';

        return [
            'actions'    => [
                'send_dian' => config('services.dataico.Envio_Dian', false),
                'send_email' => false,
            ],
            $payloadKey  => $documentData,
        ];
    }

    /**
     * Obtiene el UUID de la factura externa para usarlo como invoice_id en DataIco.
     */
    protected function resolveExternalInvoiceId(FacturaExterna $facturaExterna): ?string
    {
        $directUuid = trim((string) ($facturaExterna->uuid_dataico ?? $facturaExterna->uuid ?? ''));
        if ($directUuid !== '') {
            return $directUuid;
        }

        $responseDataico = $facturaExterna->response_dataico ?? null;
        if (is_string($responseDataico)) {
            $decoded = json_decode($responseDataico, true);
            $responseDataico = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        if (is_array($responseDataico)) {
            $normalized = $this->extractDataicoDocumentData($responseDataico);
            $uuid = trim((string) ($normalized['uuid'] ?? $responseDataico['uuid'] ?? ''));
            return $uuid !== '' ? $uuid : null;
        }

        return null;
    }

    /**
     * Construir items de nota crédito o débito
     */
    protected function buildNotaCreditoItems(NotaCredito $notaCredito, FacFactura $factura)
    {
        $items = [];

        // Determinar si la factura original tiene IVA y calcular el porcentaje
        $ivaPercent = 0;
        $gravamen = (float) $factura->gravamen;
        $totalFactura = (float) $factura->total_factura;
        if ($gravamen > 0 && $totalFactura > 0) {
            $ivaPercent = (int) round(($gravamen / $totalFactura) * 100);
        }

        // Cargar los items de la nota crédito (ya deben existir en la tabla notas_credito_items)
        $notaCredito->load('items');

        if ($notaCredito->items && $notaCredito->items->count() > 0) {
            foreach ($notaCredito->items as $ncItem) {
                // $ncItem ya tiene la info básica guardada (descripcion, precio, etc.)
                $itemData = [
                    'sku' => $ncItem->codigo_item ?? 'ITEM-NC-' . $ncItem->id,
                    'description' => $ncItem->descripcion ?? 'Item Nota Crédito',
                    'quantity' => (float) $ncItem->cantidad,
                    'price' => (float) $ncItem->precio_unitario,
                    'original_price' => (float) $ncItem->precio_unitario,
                ];
                if ($ivaPercent > 0) {
                    $itemData['taxes'] = [['tax_category' => 'IVA', 'rate' => $ivaPercent]];
                }
                $items[] = $itemData;
            }
        }
        
        // Si no hay items guardados (ej. notas antiguas), fallback a lógica anterior
        if (empty($items)) {
            // Si la nota es TOTAL, traemos los items de la factura original
            if ($notaCredito->alcance === 'TOTAL') {
                // Cargar los items de la factura con sus detalles
                $factura->load('items.ordenServicioItem');
                
                if ($factura->items && $factura->items->count() > 0) {
                    foreach ($factura->items as $facItem) {
                        $osItem = $facItem->ordenServicioItem;
                        if ($osItem) {
                            $itemData = [
                                'sku' => $osItem->servicio_id ?? 'ITEM',
                                'description' => $osItem->nombre_servicio ?? $osItem->descripcion ?? 'Item de factura',
                                'quantity' => (float) ($osItem->cantidad ?? 1),
                                'price' => (float) ($osItem->precio_unitario ?? 0),
                                'original_price' => (float) ($osItem->precio_unitario ?? 0),
                            ];
                            if ($ivaPercent > 0) {
                                $itemData['taxes'] = [['tax_category' => 'IVA', 'rate' => $ivaPercent]];
                            }
                            $items[] = $itemData;
                        }
                    }
                }
            }
            // Fallback final
            if (empty($items)) {
                $descripcion = $notaCredito->tipo_nota === 'DEBITO' ? 'NOTA DÉBITO' : 'NOTA CRÉDITO';
                $itemData = [
                    'sku' => $notaCredito->prefijo . $notaCredito->nota_credito_id,
                    'description' => $notaCredito->concepto->descripcion ?? $descripcion,
                    'quantity' => 1,
                    'price' => (float) $notaCredito->valor_nota,
                    'original_price' => (float) $notaCredito->valor_nota,
                ];
                if ($ivaPercent > 0) {
                    $itemData['taxes'] = [['tax_category' => 'IVA', 'rate' => $ivaPercent]];
                }
                $items[] = $itemData;
            }
        }
        
        return $items;
    }

    /**
     * Construir objeto health (solo si no es tipo_factura 5)
     */
    protected function buildHealthObject(FacFactura $factura)
    {
        return [
            'version' => 'API_SALUD_V2',
            'coverage' => 'PLAN_DE_BENEFICIOS',
            'provider_code' => substr($factura->codigo_prestador ?? '0000000000', 0, 10),
            'payment_modality' => 'PAGO_POR_EVENTO',
        ];
    }

    /**
     * Mapear tipo de identificación
     */
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
        ];
        return $map[$type] ?? 'NIT';
    }

    /**
     * Convierte un numero a palabras en espanol (colombiano)
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

        if ($num < 10) {
            return $unidades[$num];
        } elseif ($num < 20) {
            $diccionario = array('DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE');
            return $diccionario[$num - 10];
        } elseif ($num < 100) {
            $decena = (int)($num / 10);
            $unidad = $num % 10;
            return $unidad === 0 ? $decenas[$decena] : ($decenas[$decena] . ' Y ' . $unidades[$unidad]);
        } elseif ($num < 1000) {
            $centena = (int)($num / 100);
            $resto = $num % 100;
            $palabras = $centenas[$centena];
            if ($resto > 0) {
                $palabras .= ' ' . $this->numberToWords($resto);
            }
            return $palabras;
        }

        foreach ($divisores as $divisor => $escalas) {
            if ($num >= $divisor) {
                $cociente = (int)($num / $divisor);
                $resto = $num % $divisor;
                $palabras = $escalas !== ''
                    ? ($this->numberToWords($cociente) . ' ' . $escalas)
                    : $this->numberToWords($cociente);
                if ($resto > 0) {
                    $palabras .= ' ' . $this->numberToWords($resto);
                }
                return trim($palabras);
            }
        }

        return '';
    }

    /**
     * Formatea un numero a letras con denominacion de moneda
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

    /**
     * DataIco para notas credito acepta notes como arreglo plano de strings.
     * Reindexamos para evitar que JSON salga como objeto por huecos de indices.
     */
    protected function formatDataicoNotes(array $notes): array
    {
        return array_values(array_filter(array_map(function ($note) {
            return is_string($note) ? trim($note) : '';
        }, $notes), function ($note) {
            return $note !== '';
        }));
    }

    /**
     * Normaliza la respuesta de DataIco sin importar si viene anidada
     * en credit_note/debit_note/invoice o en raíz.
     */
    protected function extractDataicoDocumentData(array $responseData): array
    {
        if (isset($responseData['credit_note']) && is_array($responseData['credit_note'])) {
            return $responseData['credit_note'];
        }
        if (isset($responseData['debit_note']) && is_array($responseData['debit_note'])) {
            return $responseData['debit_note'];
        }
        if (isset($responseData['invoice']) && is_array($responseData['invoice'])) {
            return $responseData['invoice'];
        }
        return $responseData;
    }

    /**
     * Sincroniza estado local y auditoría con una respuesta obtenida de DataIco.
     */
    protected function sincronizarNotaConDataico(NotaCredito $notaCredito, array $payload, array $dataicoData, array $rawResult = []): void
    {
        AuditoriaNotaCredito::updateOrCreate(
            [
                'prefijo_siis' => $notaCredito->prefijo,
                'nota_credito_siis' => $notaCredito->nota_credito_id,
            ],
            [
                'json_siis' => $payload,
                'dataico_response' => !empty($rawResult) ? $rawResult : $dataicoData,
                'dian_status' => $dataicoData['dian_status'] ?? 'ENVIADO',
                'numero' => $dataicoData['number'] ?? null,
                'issue_date' => $dataicoData['issue_date'] ?? null,
                'xml_url' => $dataicoData['xml_url'] ?? null,
                'payment_date' => $dataicoData['payment_date'] ?? null,
                'customer_status' => $dataicoData['customer_status'] ?? null,
                'pdf_url' => $dataicoData['pdf_url'] ?? null,
                'email_status' => $dataicoData['email_status'] ?? null,
                'cufe' => $dataicoData['cufe'] ?? null,
                'uuid' => $dataicoData['uuid'] ?? null,
                'qrcode' => $dataicoData['qrcode'] ?? null,
                'prefix' => $dataicoData['numbering']['prefix'] ?? null,
                'resolution_number' => $dataicoData['numbering']['resolution_number'] ?? null,
                'dian_messages' => isset($dataicoData['dian_send_error_code']) ? [$dataicoData['dian_send_error_code']] : null,
            ]
        );

        $estado = 'ENVIADO';
        if (($dataicoData['dian_status'] ?? null) === 'DIAN_ACEPTADO') {
            $estado = 'ACEPTADO';
        } elseif (($dataicoData['dian_status'] ?? null) === 'DIAN_RECHAZADO') {
            $estado = 'RECHAZADO';
        }

        $notaCredito->update([
            'estado' => $estado,
            'fecha_envio' => now(),
            'fecha_aceptacion' => (($dataicoData['dian_status'] ?? null) === 'DIAN_ACEPTADO') ? now() : $notaCredito->fecha_aceptacion,
            'cufe' => $dataicoData['cufe'] ?? null,
            'uuid' => $dataicoData['uuid'] ?? null,
            'respuesta_dataico' => $dataicoData,
        ]);
    }

    /**
     * Obtener lista de notas crédito con filtros
     */
    public function obtenerNotas(array $filtros = [])
    {
        $query = NotaCredito::query();

        if ($filtros['empresa_id'] ?? false) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if ($filtros['estado'] ?? false) {
            $query->where('estado', $filtros['estado']);
        }

        if ($filtros['prefijo'] ?? false) {
            $query->where('prefijo', $filtros['prefijo']);
        }

        if ($filtros['fecha_desde'] ?? false) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if ($filtros['fecha_hasta'] ?? false) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        return $query->with(['concepto'])
                     ->orderByDesc('id')
                     ->paginate(15);
    }

    /**
     * Obtener detalle de una nota crédito
     */
    public function obtenerDetalle(int $notaCreditoId)
    {
        return NotaCredito::with(['concepto'])->findOrFail($notaCreditoId);
    }
}

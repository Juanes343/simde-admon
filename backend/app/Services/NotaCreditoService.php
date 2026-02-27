<?php

namespace App\Services;

use App\Models\NotaCredito;
use App\Models\NotaCreditoConcepto;
use App\Models\FacFactura;
use App\Models\AuditoriaDataIco;
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
            // 1. Validar factura referenciada
            $factura = FacFactura::where('prefijo', $data['prefijo_factura'])
                                  ->where('factura_fiscal', $data['factura_fiscal'])
                                  ->firstOrFail();

            // 2. Generar número siguiente (esto también actualiza la tabla documentos)
            $numeroNC = NotaCredito::generarSiguienteNumero(
                $data['empresa_id'],
                $data['prefijo']
            );

            // 3. Crear registro de nota crédito
            $notaCredito = NotaCredito::create([
                'empresa_id' => $data['empresa_id'],
                'prefijo' => $data['prefijo'],
                'nota_credito_id' => $numeroNC,
                'prefijo_factura' => $data['prefijo_factura'],
                'factura_fiscal' => $data['factura_fiscal'],
                'concepto_id' => $data['concepto_id'] ?? 1, // Valor por defecto si no viene
                'valor_nota' => $data['valor_nota'],
                'observacion' => $data['observacion'] ?? null,
                'tipo_id_tercero' => $factura->tipo_id_tercero,
                'tercero_id' => $factura->tercero_id,
                'tipo_factura' => $factura->tipo_factura,
                'estado' => 'PENDIENTE',
                'tipo_nota' => $data['tipo_nota'] ?? 'CREDITO',
                'alcance' => $data['alcance'] ?? 'TOTAL',
            ]);

            // 4. Guardar items de la nota crédito si existen
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    // Aquí deberías guardar los items en la tabla correspondiente
                    // Ejemplo: NotaCreditoDetalle::create([...])
                    // Por ahora solo lo registramos en el log
                    Log::info("Item de nota crédito: ", $item);
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
            $notaCredito = NotaCredito::with(['concepto'])->findOrFail($notaCreditoId);

            // 2. Obtener datos de la factura referenciada
            $factura = FacFactura::where('prefijo', $notaCredito->prefijo_factura)
                                  ->where('factura_fiscal', $notaCredito->factura_fiscal)
                                  ->with(['tercero'])
                                  ->firstOrFail();

            // 3. Obtener auditoría de la factura (CUFE)
            // La tabla fe_auditoria_dataico se relaciona con fac_facturas a través de factura_fiscal_id (que es el ID interno de la factura, no el número de factura)
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
                            'prefijo' => $notaCredito->prefijo_factura,
                            'factura_fiscal_id' => $factura->factura_fiscal_id,
                            'numero_factura' => $notaCredito->factura_fiscal
                        ]
                    ]
                ];
            }

            // 4. Construir payload
            $payload = $this->buildNotaCreditoPayload($notaCredito, $factura, $auditoria);

            // 5. Enviar a DATAICO
            $token = config('services.dataico.token');
            $endpoint = $notaCredito->tipo_nota === 'DEBITO' ? 'debit_notes' : 'credit_notes';
            $result = $this->dataIcoService->sendDocument($payload, $endpoint, $token);

            // 6. Actualizar estado según respuesta
            if ($result['success']) {
                $responseData = $result['data'];
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
                        $errorStr = is_array($err) ? implode(', ', $err) : $err;
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
            'notes' => [
                'Concepto: ' . ($notaCredito->concepto->descripcion ?? 'OTRO'),
                'Observación: ' . ($notaCredito->observacion ?? 'N/A'),
                'Valor: $' . number_format($notaCredito->valor_nota, 0),
            ],
            'items' => $items,
        ];

        // Agregar health solo si tipo_factura NO es 5 (Conceptos)
        if ($factura->tipo_factura != '5') {
            $documentData['health'] = $this->buildHealthObject($factura);
        }

        $payloadKey = $notaCredito->tipo_nota === 'DEBITO' ? 'debit_note' : 'credit_note';

        return [
            'actions' => [
                'send_dian' => true,
                'send_email' => true,
            ],
            $payloadKey => $documentData,
        ];
    }

    /**
     * Construir items de nota crédito o débito
     */
    protected function buildNotaCreditoItems(NotaCredito $notaCredito, FacFactura $factura)
    {
        $descripcion = $notaCredito->tipo_nota === 'DEBITO' ? 'NOTA DÉBITO' : 'NOTA CRÉDITO';
        
        return [
            [
                'sku' => $notaCredito->prefijo . $notaCredito->nota_credito_id,
                'description' => $notaCredito->concepto->descripcion ?? $descripcion,
                'quantity' => 1,
                'price' => (float) $notaCredito->valor_nota,
                'original_price' => (float) $notaCredito->valor_nota,
            ],
        ];
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

        return $query->with(['concepto'])->paginate(15);
    }

    /**
     * Obtener detalle de una nota crédito
     */
    public function obtenerDetalle(int $notaCreditoId)
    {
        return NotaCredito::with(['concepto'])->findOrFail($notaCreditoId);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\NotaCredito;
use App\Models\NotaCreditoConcepto;
use App\Services\NotaCreditoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NotaCreditoController extends Controller
{
    protected $notaCreditoService;

    public function __construct(NotaCreditoService $notaCreditoService)
    {
        $this->notaCreditoService = $notaCreditoService;
    }

    /**
     * GET /api/notas-credito
     * Listar notas crédito con filtros
     */
    public function index(Request $request)
    {
        try {
            $filtros = [
                'empresa_id' => $request->query('empresa_id'),
                'estado' => $request->query('estado'),
                'prefijo' => $request->query('prefijo'),
                'fecha_desde' => $request->query('fecha_desde'),
                'fecha_hasta' => $request->query('fecha_hasta'),
            ];

            $notas = $this->notaCreditoService->obtenerNotas($filtros);

            return response()->json([
                'success' => true,
                'data' => $notas->items(),
                'pagination' => [
                    'total' => $notas->total(),
                    'per_page' => $notas->perPage(),
                    'current_page' => $notas->currentPage(),
                    'last_page' => $notas->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo notas crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notas crédito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/notas-credito
     * Crear nueva nota crédito
     */
    public function store(Request $request)
    {
        try {
            // Validar datos
            $validator = Validator::make($request->all(), [
                'empresa_id'          => 'required|string|max:10',
                'prefijo'             => 'required|string|max:10',
                'prefijo_factura'     => 'required|string|max:10',
                'factura_fiscal'      => 'required|integer',
                'concepto_id'         => 'nullable|integer|required_if:tipo_factura_origen,EXTERNA',
                'valor_nota'          => 'required|numeric|min:0.01',
                'observacion'         => 'nullable|string|max:500',
                'usuario_id'          => 'nullable|integer',
                'tipo_nota'           => 'required|string|in:CREDITO,DEBITO',
                'alcance'             => 'required|string|in:TOTAL,PARCIAL',
                'tipo_factura_origen' => 'nullable|string|in:INTERNA,EXTERNA',
                'items'               => 'required|array|min:1',
                'items.*.item_id'     => 'nullable|integer',
                'items.*.valor'       => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Crear nota crédito
            $data = $validator->validated();
            $authUser = $request->user();
            if (empty($data['usuario_id']) && $authUser) {
                $data['usuario_id'] = $authUser->usuario_id ?? $authUser->id ?? null;
            }
            $result = $this->notaCreditoService->crearNotaCredito($data);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result, 201);

        } catch (\Exception $e) {
            Log::error('Error creando nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear nota crédito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito/{id}
     * Obtener detalle de una nota crédito
     */
    public function show(int $id)
    {
        try {
            $notaCredito = $this->notaCreditoService->obtenerDetalle($id);

            return response()->json([
                'success' => true,
                'data' => $notaCredito,
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Nota crédito no encontrada',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * POST /api/notas-credito/{id}/enviar
     * Enviar nota crédito a DATAICO
     */
    public function enviar(int $id)
    {
        try {
            $result = $this->notaCreditoService->enviarNotaCredito($id);

            $statusCode = $result['success'] ? 200 : 400;

            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            Log::error('Error enviando nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar nota crédito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito/{id}/descargar-zip
     * Descargar ZIP con PDF y XML de la nota crédito
     */
    public function descargarZip(Request $request, int $id)
    {
        try {
            $notaCredito = $this->resolverNotaCreditoDescarga($request, $id);
            $docDataico = $this->extraerDocumentoDataico($notaCredito->respuesta_dataico ?? []);

            $pdfSource = $docDataico['pdf_url']
                ?? $docDataico['pdf']
                ?? $notaCredito->respuesta_dataico['pdf']
                ?? $notaCredito->respuesta_dataico['pdf_url']
                ?? null;

            $xmlSource = $docDataico['xml_url']
                ?? $docDataico['xml']
                ?? $notaCredito->respuesta_dataico['xml']
                ?? $notaCredito->respuesta_dataico['xml_url']
                ?? null;

            if (!$pdfSource && !$xmlSource) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay archivos disponibles para descargar',
                ], 404);
            }

            $zip = new \ZipArchive();
            $zipFileName = "Nota_{$notaCredito->prefijo}_{$notaCredito->nota_credito_id}.zip";
            $zipPath = storage_path("app/public/{$zipFileName}");

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                // Agregar PDF (URL o base64)
                $pdfContent = false;
                if ($pdfSource) {
                    if (preg_match('/^https?:\/\//i', (string) $pdfSource)) {
                        $pdfContent = @file_get_contents($pdfSource);
                    } else {
                        $decodedPdf = base64_decode((string) $pdfSource, true);
                        $pdfContent = $decodedPdf !== false ? $decodedPdf : (string) $pdfSource;
                    }
                }

                if ($pdfContent !== false && trim((string) $pdfContent) !== '') {
                    $zip->addFromString("{$notaCredito->prefijo}_{$notaCredito->nota_credito_id}.pdf", $pdfContent);
                }

                // Agregar XML (URL o base64/texto)
                $xmlContent = false;
                if ($xmlSource) {
                    if (preg_match('/^https?:\/\//i', (string) $xmlSource)) {
                        $xmlContent = @file_get_contents($xmlSource);
                    } else {
                        $decodedXml = base64_decode((string) $xmlSource, true);
                        $xmlContent = $decodedXml !== false ? $decodedXml : (string) $xmlSource;
                    }
                }

                if ($xmlContent !== false && trim((string) $xmlContent) !== '') {
                    $zip->addFromString("{$notaCredito->prefijo}_{$notaCredito->nota_credito_id}.xml", $xmlContent);
                }

                // Si no se pudo agregar ningún archivo, no retornamos ZIP vacío.
                if ($zip->numFiles === 0) {
                    $zip->close();
                    @unlink($zipPath);
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo obtener PDF/XML desde DataIco para armar el ZIP',
                    ], 502);
                }

                $zip->close();

                return response()->download($zipPath)->deleteFileAfterSend(true);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el archivo ZIP',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error descargando ZIP de nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar archivos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito/{id}/descargar-pdf
     * Descargar PDF de la nota crédito
     */
    public function descargarPdf(Request $request, int $id)
    {
        try {
            $notaCredito = $this->resolverNotaCreditoDescarga($request, $id);
            $docDataico = $this->extraerDocumentoDataico($notaCredito->respuesta_dataico ?? []);

            $pdfUrl = $docDataico['pdf_url']
                ?? $docDataico['pdf']
                ?? $notaCredito->respuesta_dataico['pdf']
                ?? $notaCredito->respuesta_dataico['pdf_url']
                ?? null;

            if (!$pdfUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF no disponible para esta nota',
                ], 404);
            }

            $pdfContent = @file_get_contents($pdfUrl);
            if ($pdfContent === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el PDF desde DataIco',
                ], 502);
            }

            $fileName = "nota_{$notaCredito->prefijo}_{$notaCredito->nota_credito_id}.pdf";

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename={$fileName}");

        } catch (\Exception $e) {
            Log::error('Error descargando PDF de nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito/{id}/descargar-xml
     * Descargar XML de la nota crédito
     */
    public function descargarXml(Request $request, int $id)
    {
        try {
            $notaCredito = $this->resolverNotaCreditoDescarga($request, $id);
            $docDataico = $this->extraerDocumentoDataico($notaCredito->respuesta_dataico ?? []);

            $xmlSource = $docDataico['xml_url']
                ?? $docDataico['xml']
                ?? $notaCredito->respuesta_dataico['xml']
                ?? $notaCredito->respuesta_dataico['xml_url']
                ?? null;

            if (!$xmlSource) {
                return response()->json([
                    'success' => false,
                    'message' => 'XML no disponible para esta nota',
                ], 404);
            }

            // DataIco puede devolver xml_url (URL) o xml (contenido base64 / texto).
            if (preg_match('/^https?:\/\//i', (string) $xmlSource)) {
                $xmlContent = @file_get_contents($xmlSource);
            } else {
                $decoded = base64_decode((string) $xmlSource, true);
                $xmlContent = $decoded !== false ? $decoded : (string) $xmlSource;
            }

            if ($xmlContent === false || trim((string) $xmlContent) === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el XML desde DataIco',
                ], 502);
            }

            $fileName = "nota_{$notaCredito->prefijo}_{$notaCredito->nota_credito_id}.xml";

            return response($xmlContent, 200)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', "attachment; filename={$fileName}");

        } catch (\Exception $e) {
            Log::error('Error descargando XML de nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar XML',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolver nota para descargas por consecutivo (nota_credito_id)
     * con prefijo/empresa opcionales para desambiguar.
     */
    private function resolverNotaCreditoDescarga(Request $request, int $id): NotaCredito
    {
        $query = NotaCredito::query()->where('nota_credito_id', $id);

        if ($request->filled('prefijo')) {
            $query->where('prefijo', $request->query('prefijo'));
        }

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->query('empresa_id'));
        }

        $notaCredito = $query->first();

        // Compatibilidad: si no se encuentra por consecutivo, intentar por ID técnico.
        if (!$notaCredito) {
            $notaCredito = NotaCredito::findOrFail($id);
        }

        if (!$notaCredito->respuesta_dataico) {
            abort(404, 'No hay respuesta de DataIco para esta nota');
        }

        return $notaCredito;
    }

    /**
     * Normaliza la estructura devuelta por DataIco.
     */
    private function extraerDocumentoDataico(array $respuesta): array
    {
        if (isset($respuesta['credit_note']) && is_array($respuesta['credit_note'])) {
            return $respuesta['credit_note'];
        }
        if (isset($respuesta['debit_note']) && is_array($respuesta['debit_note'])) {
            return $respuesta['debit_note'];
        }
        if (isset($respuesta['data']) && is_array($respuesta['data'])) {
            if (isset($respuesta['data']['credit_note']) && is_array($respuesta['data']['credit_note'])) {
                return $respuesta['data']['credit_note'];
            }
            if (isset($respuesta['data']['debit_note']) && is_array($respuesta['data']['debit_note'])) {
                return $respuesta['data']['debit_note'];
            }
            return $respuesta['data'];
        }
        return $respuesta;
    }

    /**
     * DELETE /api/notas-credito/{id}
     * Eliminar nota crédito (solo si está PENDIENTE)
     */
    public function destroy(int $id)
    {
        try {
            $notaCredito = NotaCredito::findOrFail($id);

            if ($notaCredito->estado !== 'PENDIENTE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar notas crédito PENDIENTES',
                ], 400);
            }

            $notaCredito->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nota crédito eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error eliminando nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar nota crédito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito-conceptos
     * Obtener lista de conceptos de notas crédito
     */
    public function conceptos(Request $request)
    {
        try {
            $empresaId = $request->query('empresa_id');
            
            $query = NotaCreditoConcepto::query();

            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }

            // Solo mostrar conceptos de crédito (sw_naturaleza = 'C') y activos (sw_activo = 1)
            $conceptos = $query->where('sw_naturaleza', 'C')
                               ->where('sw_activo', 1)
                               ->orderBy('descripcion')
                               ->get();

            return response()->json([
                'success' => true,
                'data' => $conceptos,
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo conceptos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener conceptos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito/estadisticas
     * Obtener estadísticas de notas crédito
     */
    public function estadisticas(Request $request)
    {
        try {
            $empresaId = $request->query('empresa_id');

            $query = NotaCredito::query();
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }

            $stats = [
                'total' => (clone $query)->count(),
                'pendientes' => (clone $query)->where('estado', 'PENDIENTE')->count(),
                'enviadas' => (clone $query)->where('estado', 'ENVIADO')->count(),
                'aceptadas' => (clone $query)->where('estado', 'ACEPTADO')->count(),
                'rechazadas' => (clone $query)->where('estado', 'RECHAZADO')->count(),
                'valor_total' => (clone $query)->sum('valor_nota'),
                'valor_aceptado' => (clone $query)->where('estado', 'ACEPTADO')->sum('valor_nota'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito/prefijos
     * Obtener prefijos disponibles para notas crédito/débito
     */
    public function prefijos(Request $request)
    {
        try {
            $empresaId = $request->query('empresa_id');
            $tipo = $request->query('tipo', 'C'); // 'C' para crédito, 'D' para débito

            // En la tabla documentos, el tipo_doc_general_id es el que indica si es NC o ND
            $tipoDoc = $tipo === 'D' ? 'ND01' : 'NC01'; 
            
            $prefijos = DB::table('documentos')
                ->where('empresa_id', '01') // Forzamos '01' porque en la BD el registro tiene empresa_id = '01'
                ->where('tipo_doc_general_id', $tipoDoc)
                ->select('documento_id', 'prefijo', 'descripcion')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $prefijos,
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo prefijos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener prefijos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito-conceptos/all
     * Listar todos los conceptos para administración (incluye inactivos)
     */
    public function conceptosAll(Request $request)
    {
        try {
            $empresaId = $request->query('empresa_id');
            $query = NotaCreditoConcepto::query();
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
            $conceptos = $query->orderBy('descripcion')->get();
            return response()->json(['success' => true, 'data' => $conceptos]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo conceptos (all): ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener conceptos', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/notas-credito-conceptos
     * Crear nuevo concepto
     */
    public function storeConcepto(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'empresa_id'    => 'required|string|max:3',
                'descripcion'   => 'required|string|max:255',
                'sw_naturaleza' => 'nullable|string|in:C,D',
                'sw_activo'     => 'nullable|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validación fallida', 'errors' => $validator->errors()], 422);
            }
            $data = $validator->validated();
            $data['sw_naturaleza'] = $data['sw_naturaleza'] ?? 'C';
            $data['sw_activo']     = isset($data['sw_activo']) ? $data['sw_activo'] : true;
            $concepto = NotaCreditoConcepto::create($data);
            return response()->json(['success' => true, 'data' => $concepto, 'message' => 'Concepto creado correctamente'], 201);
        } catch (\Exception $e) {
            Log::error('Error creando concepto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al crear concepto', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/notas-credito-conceptos/{id}
     * Actualizar concepto
     */
    public function updateConcepto(Request $request, int $id)
    {
        try {
            $concepto = NotaCreditoConcepto::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'descripcion'   => 'required|string|max:255',
                'sw_naturaleza' => 'nullable|string|in:C,D',
                'sw_activo'     => 'nullable|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validación fallida', 'errors' => $validator->errors()], 422);
            }
            $concepto->update($validator->validated());
            return response()->json(['success' => true, 'data' => $concepto, 'message' => 'Concepto actualizado correctamente']);
        } catch (\Exception $e) {
            Log::error('Error actualizando concepto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar concepto', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/notas-credito-conceptos/{id}
     * Eliminar concepto (soft delete)
     */
    public function destroyConcepto(int $id)
    {
        try {
            $concepto = NotaCreditoConcepto::findOrFail($id);
            $concepto->delete();
            return response()->json(['success' => true, 'message' => 'Concepto eliminado correctamente']);
        } catch (\Exception $e) {
            Log::error('Error eliminando concepto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar concepto', 'error' => $e->getMessage()], 500);
        }
    }
}

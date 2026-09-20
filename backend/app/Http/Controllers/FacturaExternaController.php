<?php

namespace App\Http\Controllers;

use App\Models\FacturaExterna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FacturaExternaController extends Controller
{
    /**
     * Lista facturas externas con filtros opcionales y paginación.
     */
    public function index(Request $request)
    {
        try {
            // Sólo se seleccionan las columnas necesarias para evitar errores de
            // serialización en columnas nullable con casts decimal/date.
            $query = FacturaExterna::select([
                'id', 'empresa_id', 'prefijo', 'factura_fiscal', 'estado',
                'tipo_id_tercero', 'tercero_id', 'tipo_factura',
                'total_factura', 'fecha_registro',
            ]);

            if ($request->filled('empresa_id')) {
                $query->where('empresa_id', $request->empresa_id);
            }
            if ($request->filled('prefijo')) {
                $query->where('prefijo', $request->prefijo);
            }
            if ($request->filled('tercero_id')) {
                $query->where('tercero_id', $request->tercero_id);
            }
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }
            if ($request->filled('factura_fiscal')) {
                $query->where('factura_fiscal', 'like', '%' . $request->factura_fiscal . '%');
            }
            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_registro', '>=', $request->fecha_desde);
            }
            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_registro', '<=', $request->fecha_hasta);
            }

            $perPage  = min((int) $request->get('per_page', 50), 500);
            $facturas = $query->orderByDesc('fecha_registro')->paginate($perPage);

            return response()->json($facturas);

        } catch (\Exception $e) {
            // Si la tabla no existe en esta BD, devolver colección vacía en lugar de 500
            $msg = $e->getMessage();
            if (str_contains($msg, 'does not exist') || str_contains($msg, "doesn't exist") || str_contains($msg, 'SQLSTATE[42')) {
                return response()->json([
                    'data'          => [],
                    'current_page'  => 1,
                    'last_page'     => 1,
                    'total'         => 0,
                    'per_page'      => 50,
                ]);
            }
            \Log::error('Error listando facturas externas: ' . $msg);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener facturas externas',
                'error'   => $msg,
            ], 500);
        }
    }

    /**
     * Detalle de una factura externa.
     */
    public function show($id)
    {
        $factura = FacturaExterna::findOrFail($id);
        return response()->json($factura);
    }

    /**
     * Elimina una factura externa.
     */
    public function destroy($id)
    {
        $factura = FacturaExterna::findOrFail($id);
        $factura->delete();
        return response()->json(['message' => 'Factura eliminada correctamente.']);
    }

    /**
     * Sube y procesa un archivo CSV para cargar facturas externas.
     *
     * Formato esperado: primera fila = cabecera con nombres de columna.
     * Columnas requeridas: empresa_id, prefijo, factura_fiscal, estado,
     *                      tipo_id_tercero, tercero_id, tipo_factura.
     */
    public function uploadCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path     = $request->file('archivo')->getRealPath();
        $handle   = fopen($path, 'r');
        $cabecera = fgetcsv($handle, 0, ';');

        if ($cabecera === false) {
            fclose($handle);
            return response()->json(['message' => 'El archivo está vacío o no tiene cabecera.'], 422);
        }

        // Normalizar cabecera: minúsculas y sin espacios
        $cabecera = array_map(fn($c) => strtolower(trim($c)), $cabecera);

        $required = ['empresa_id', 'prefijo', 'factura_fiscal', 'estado',
                     'tipo_id_tercero', 'tercero_id', 'tipo_factura'];

        $missing = array_diff($required, $cabecera);
        if (!empty($missing)) {
            fclose($handle);
            return response()->json([
                'message' => 'Faltan columnas requeridas: ' . implode(', ', $missing),
            ], 422);
        }

        $userId    = Auth::id();
        $inserted  = 0;
        $skipped   = 0;
        $errors    = [];
        $batchSize = 100;
        $batch     = [];
        $lineNum   = 1;

        // Columnas numéricas con valor por defecto 0
        $numerics = [
            'total_factura', 'gravamen', 'valor_cargos', 'valor_cuota_paciente',
            'valor_cuota_moderadora', 'descuento', 'total_capitacion_real', 'saldo',
            'retencion_fuente', 'impuesto_cree', 'reteica', 'impuesto_4x100',
        ];

        // Columnas char(1) con valor por defecto '0'
        $flags = ['sw_clase_factura', 'sw_imp_copia', 'sw_factory',
                  'sw_dificil_cobro', 'sw_proceso_juridico', 'sw_deterioro'];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lineNum++;

            if (count($row) !== count($cabecera)) {
                $errors[] = "Línea $lineNum: número de columnas incorrecto.";
                $skipped++;
                continue;
            }

            $data = array_combine($cabecera, $row);
            $data = array_map('trim', $data);

            // Validar requeridos no vacíos
            foreach ($required as $col) {
                if (!isset($data[$col]) || $data[$col] === '') {
                    $errors[] = "Línea $lineNum: columna '$col' vacía.";
                    $skipped++;
                    continue 2;
                }
            }

            // Sanitizar numéricos
            foreach ($numerics as $col) {
                if (isset($data[$col])) {
                    $data[$col] = is_numeric(str_replace(',', '.', $data[$col]))
                        ? (float) str_replace(',', '.', $data[$col])
                        : 0;
                } else {
                    $data[$col] = 0;
                }
            }

            // Sanitizar flags char(1)
            foreach ($flags as $col) {
                $data[$col] = isset($data[$col]) && in_array($data[$col], ['0', '1'])
                    ? $data[$col]
                    : '0';
            }

            // Campos de control
            $data['usuario_id']    = $userId;
            $data['created_at']    = now();
            $data['updated_at']    = now();

            // Fecha registro: si viene en el CSV úsala, sino now()
            if (empty($data['fecha_registro'])) {
                $data['fecha_registro'] = now();
            }

            // Eliminar columnas que no existen en la tabla
            $allowed = array_merge($required, $numerics, $flags, [
                'plan_id', 'concepto', 'total_capitacion_real', 'documento_id',
                'documento_contable_id', 'saldo', 'fecha_vencimiento_factura',
                'retencion_fuente', 'sw_proceso', 'rango', 'observacion',
                'fecha_registro', 'usuario_id', 'created_at', 'updated_at',
            ]);
            $data = array_intersect_key($data, array_flip($allowed));

            $batch[] = $data;

            if (count($batch) >= $batchSize) {
                try {
                    DB::table('facturas_externas')->insertOrIgnore($batch);
                    $inserted += count($batch);
                } catch (\Throwable $e) {
                    $errors[] = "Error al insertar lote terminado en línea $lineNum: " . $e->getMessage();
                    $skipped  += count($batch);
                }
                $batch = [];
            }
        }

        fclose($handle);

        // Insertar último lote
        if (!empty($batch)) {
            try {
                DB::table('facturas_externas')->insertOrIgnore($batch);
                $inserted += count($batch);
            } catch (\Throwable $e) {
                $errors[] = 'Error al insertar lote final: ' . $e->getMessage();
                $skipped  += count($batch);
            }
        }

        return response()->json([
            'message'  => "Proceso completado. Insertadas: $inserted, Omitidas: $skipped.",
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ]);
    }

    /**
     * Devuelve una plantilla CSV de ejemplo para descargar.
     */
    public function plantillaCsv()
    {
        $columnas = [
            'empresa_id', 'prefijo', 'factura_fiscal', 'estado',
            'tipo_id_tercero', 'tercero_id', 'tipo_factura',
            'fecha_registro', 'total_factura', 'gravamen', 'valor_cargos',
            'valor_cuota_paciente', 'valor_cuota_moderadora', 'descuento',
            'saldo', 'fecha_vencimiento_factura', 'retencion_fuente',
            'impuesto_cree', 'reteica', 'impuesto_4x100',
            'sw_clase_factura', 'sw_factory', 'sw_dificil_cobro',
            'sw_proceso_juridico', 'sw_deterioro', 'observacion',
        ];

        $ejemplo = [
            '01', 'FV', '1001', 'A',
            'NIT', '900123456', '1',
            '2026-01-15 00:00:00', '1500000.00', '0.00', '0.00',
            '0.00', '0.00', '0.00',
            '1500000.00', '2026-02-15', '0.00',
            '0.00', '0.00', '0.00',
            '0', '0', '0',
            '0', '0', 'Factura importada',
        ];

        ob_start();
        $handle = fopen('php://output', 'w');
        fputcsv($handle, $columnas, ';');
        fputcsv($handle, $ejemplo, ';');
        fclose($handle);
        $csv = ob_get_clean();

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_facturas_externas.csv"',
        ]);
    }
}

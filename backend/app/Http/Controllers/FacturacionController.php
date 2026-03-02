<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\FacFactura;
use App\Models\FacFacturaItem;
use App\Models\OrdenServicio;
use App\Models\OrdenServicioItem;
use App\Services\ElectronicInvoicingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FacturacionController extends Controller
{
    protected $invoicingService;

    public function __construct(ElectronicInvoicingService $invoicingService)
    {
        $this->invoicingService = $invoicingService;
    }
    /**
     * Obtiene los prefijos de facturación habilitados.
     */
    public function getPrefijos()
    {
        $prefijos = Documento::where('sw_estado', '1')->get();
        return response()->json($prefijos);
    }

    /**
     * Obtiene las órdenes de servicio pendientes por facturar.
     * Filtro por fecha opcional (lapso).
     */
    public function getPendientesFacturar(Request $request)
    {
        $lapsoInicio = $request->query('lapso_inicio');
        $lapsoFin = $request->query('lapso_fin');
        $tercero = $request->query('tercero');

        // Iniciar query con órdenes activas
        $query = OrdenServicio::where('sw_estado', '1');

        if ($lapsoInicio && $lapsoFin) {
            // La orden debe estar vigente (cruzarse) con el lapso seleccionado
            $query->where(function($q) use ($lapsoInicio, $lapsoFin) {
                $q->where('fecha_inicio', '<=', $lapsoFin)
                  ->where('fecha_fin', '>=', $lapsoInicio);
            });
        } else {
             // Si no hay filtro, por defecto mostrar vigentes a la fecha actual
             $now = date('Y-m-d');
             $query->where('fecha_inicio', '<=', $now)
                   ->where('fecha_fin', '>=', $now);
        }
        
        if ($tercero) {
            $query->where(function($q) use ($tercero) {
                $q->where('tercero_id', 'like', "%$tercero%")
                  ->orWhereExists(function ($sub) use ($tercero) {
                      $sub->select(DB::raw(1))
                          ->from('terceros')
                          ->whereColumn('terceros.tercero_id', 'ordenes_servicio.tercero_id')
                          ->whereColumn('terceros.tipo_id_tercero', 'ordenes_servicio.tipo_id_tercero')
                          ->where('terceros.nombre_tercero', 'like', "%$tercero%");
                  });
            });
        }

        // Definir el rango de chequeo para verificar duplicados.
        // Si el usuario no filtra, asumimos que intenta facturar el mes actual.
        $checkInicio = $lapsoInicio ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $checkFin = $lapsoFin ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        // Callback para determinar qué Items mostrar (Excluir los que ya estan facturados en ese periodo)
        $itemFilter = function ($q) use ($checkInicio, $checkFin) {
            $q->whereNotExists(function ($sub) use ($checkInicio, $checkFin) {
                $sub->select(DB::raw(1))
                    ->from('fac_facturas_items')
                    ->join('fac_facturas', 'fac_facturas.factura_fiscal_id', '=', 'fac_facturas_items.factura_fiscal_id')
                    ->whereColumn('fac_facturas_items.item_id', 'orden_servicio_items.item_id')
                    ->where('fac_facturas.estado', '!=', '0') // No contar anuladas
                    ->where(function ($dateQ) use ($checkInicio, $checkFin) {
                        // Verificar si el periodo de la factura se cruza con el rango chequeado
                        $dateQ->whereDate('fac_facturas.fecha_periodo_inicio', '<=', $checkFin)
                              ->whereDate('fac_facturas.fecha_periodo_fin', '>=', $checkInicio);
                    });
            });
        };

        // Cargar items filtrados y filtrar la orden principal
        $ordenes = $query->with(['items' => $itemFilter])
                         ->whereHas('items', $itemFilter)
                         ->get();

        return response()->json($ordenes);
    }

    /**
     * Obtiene el listado de facturas generadas.
     */
    public function getFacturas(Request $request)
    {
        try {
            $lapsoInicio = $request->query('lapso_inicio');
            $lapsoFin = $request->query('lapso_fin');
            $tercero = $request->query('tercero');

            $query = FacFactura::with(['items.ordenServicioItem', 'tercero']);

            if ($lapsoInicio && !empty($lapsoInicio)) {
                $query->whereDate('fecha_registro', '>=', $lapsoInicio);
            }
            if ($lapsoFin && !empty($lapsoFin)) {
                $query->whereDate('fecha_registro', '<=', $lapsoFin);
            }

            if ($tercero && !empty($tercero)) {
                $query->where(function($q) use ($tercero) {
                    $q->where('tercero_id', 'like', "%$tercero%")
                      ->orWhereExists(function ($sub) use ($tercero) {
                          $sub->select(DB::raw(1))
                              ->from('terceros')
                              ->whereColumn('terceros.tercero_id', 'fac_facturas.tercero_id')
                              ->whereColumn('terceros.tipo_id_tercero', 'fac_facturas.tipo_id_tercero')
                              ->where('terceros.nombre_tercero', 'like', "%$tercero%");
                      });
                });
            }

            $facturas = $query->orderBy('fecha_registro', 'desc')->paginate(10);
            
            return response()->json($facturas);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error obteniendo facturas',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Crea una factura a partir de una selección de ítems de órdenes de servicio.
     */
    public function facturar(Request $request)
    {
        $validated = $request->validate([
            'documento_id' => 'required|exists:documentos,documento_id',
            'tercero_id' => 'required',
            'tipo_id_tercero' => 'required',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:orden_servicio_items,item_id',
            'observacion' => 'nullable|string',
            'fecha_periodo_inicio' => 'nullable|date',
            'fecha_periodo_fin' => 'nullable|date',
        ]);

        try {
            return DB::transaction(function () use ($validated, $request) {
                $prefijoDoc = Documento::findOrFail($validated['documento_id']);
                
                // Incrementar numeración
                $numeroFactura = $prefijoDoc->numeracion + 1;
                $prefijoDoc->numeracion = $numeroFactura;
                $prefijoDoc->save();

                // Calcular totales a partir de los items enviados
                $itemsIds = collect($validated['items'])->pluck('item_id');
                $ordenItems = OrdenServicioItem::whereIn('item_id', $itemsIds)->get();
                $totalFactura = $ordenItems->sum('subtotal');

                // Crear Factura
                $factura = FacFactura::create([
                    'empresa_id' => $prefijoDoc->empresa_id,
                    'prefijo' => $prefijoDoc->prefijo,
                    'factura_fiscal' => $numeroFactura,
                    'estado' => '1',
                    'usuario_id' => $request->user()->usuario_id ?? 1,
                    'total_factura' => $totalFactura,
                    'tipo_id_tercero' => $validated['tipo_id_tercero'],
                    'tercero_id' => $validated['tercero_id'],
                    'documento_id' => $prefijoDoc->documento_id,
                    'tipo_factura' => '5', // Tipo 5: Sin datos de salud
                    'saldo' => $totalFactura,
                    'fecha_vencimiento_factura' => Carbon::now()->addDays(30),
                    'observacion' => $validated['observacion'],
                    'fecha_registro' => Carbon::now(),
                    'fecha_periodo_inicio' => $validated['fecha_periodo_inicio'] ?? Carbon::now()->startOfMonth(),
                    'fecha_periodo_fin' => $validated['fecha_periodo_fin'] ?? Carbon::now()->endOfMonth(),
                ]);

                // Registrar los items relacionados
                foreach ($ordenItems as $item) {
                    FacFacturaItem::create([
                        'factura_fiscal_id' => $factura->factura_fiscal_id,
                        'orden_servicio_id' => $item->orden_servicio_id,
                        'item_id' => $item->item_id,
                    ]);
                }

                // Cargar factura con items e inmediatamente enviar a DataIco
                $factura = $factura->load('items.ordenServicioItem', 'tercero');
                
                // Enviar a DataIco automáticamente
                $dataIcoResult = $this->invoicingService->sendInvoice($factura->factura_fiscal_id);

                return response()->json([
                    'message' => 'Factura creada con éxito',
                    'factura' => $factura,
                    'dataIco' => $dataIcoResult
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server Error',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}

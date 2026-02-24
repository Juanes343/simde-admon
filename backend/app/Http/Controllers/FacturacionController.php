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

        $query = OrdenServicio::with(['items' => function ($q) {
            // Solo traer items que NO estén en fac_facturas_items
            $q->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('fac_facturas_items')
                    ->whereColumn('fac_facturas_items.item_id', 'orden_servicio_items.item_id');
            });
        }]);

        if ($lapsoInicio) {
            $query->where('fecha_inicio', '>=', $lapsoInicio);
        }
        if ($lapsoFin) {
            $query->where('fecha_inicio', '<=', $lapsoFin);
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

        // Solo ordenes que tengan al menos un item pendiente
        $ordenes = $query->whereHas('items', function ($q) {
            $q->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('fac_facturas_items')
                    ->whereColumn('fac_facturas_items.item_id', 'orden_servicio_items.item_id');
            });
        })->get();

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
                    'usuario_id' => $request->user()->usuario_id ?? 1, // fallback por si no hay auth
                    'total_factura' => $totalFactura,
                    'tipo_id_tercero' => $validated['tipo_id_tercero'],
                    'tercero_id' => $validated['tercero_id'],
                    'documento_id' => $prefijoDoc->documento_id,
                    'tipo_factura' => 'F', // Factura fiscal
                    'saldo' => $totalFactura,
                    'fecha_vencimiento_factura' => Carbon::now()->addDays(30),
                    'observacion' => $validated['observacion'],
                    'fecha_registro' => Carbon::now(),
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

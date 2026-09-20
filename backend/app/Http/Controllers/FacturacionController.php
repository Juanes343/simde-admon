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
        $prefijos = Documento::where('sw_estado', '1')
                             ->where('tipo_doc_general_id', 'FV01')
                             ->get();
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

        // Callback para determinar qué Items mostrar
        // Órdenes especiales que deben mostrar todos sus meses sin facturar (no solo el mes actual)
        $ordenesMultiPeriodo = [92, 93];

        $checkInicio = $lapsoInicio ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $checkFin    = $lapsoFin    ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        // Callback para determinar qué Items mostrar (Excluir los que ya estan facturados en ese periodo)
        $itemFilter = function ($q) use ($checkInicio, $checkFin, $ordenesMultiPeriodo) {
            $q->where('estado', '1')
              ->where(function ($outer) use ($checkInicio, $checkFin, $ordenesMultiPeriodo) {
                // Caso normal: ítem no facturado en el período chequeado
                $outer->whereNotExists(function ($sub) use ($checkInicio, $checkFin) {
                    $sub->select(DB::raw(1))
                        ->from('fac_facturas_items')
                        ->join('fac_facturas', 'fac_facturas.factura_fiscal_id', '=', 'fac_facturas_items.factura_fiscal_id')
                        ->whereColumn('fac_facturas_items.item_id', 'orden_servicio_items.item_id')
                        ->where('fac_facturas.estado', '!=', '3')
                        ->where(function ($dateQ) use ($checkInicio, $checkFin) {
                            $dateQ->whereDate('fac_facturas.fecha_periodo_inicio', '<=', $checkFin)
                                  ->whereDate('fac_facturas.fecha_periodo_fin', '>=', $checkInicio);
                        });
                })
                // Caso especial: órdenes 92/93 — mostrar si tienen meses sin facturar
                ->orWhere(function ($special) use ($ordenesMultiPeriodo) {
                    $special->whereIn('orden_servicio_id', $ordenesMultiPeriodo)
                            ->whereRaw('(
                                SELECT COUNT(DISTINCT DATE_TRUNC(\'month\', ff.fecha_periodo_inicio))
                                FROM fac_facturas_items fi2
                                JOIN fac_facturas ff ON fi2.factura_fiscal_id = ff.factura_fiscal_id
                                WHERE fi2.item_id = orden_servicio_items.item_id
                                  AND ff.estado != \'3\'
                                  AND ff.fecha_periodo_inicio IS NOT NULL
                            ) < (
                                SELECT (
                                    (EXTRACT(YEAR FROM CURRENT_DATE)::int - EXTRACT(YEAR FROM os.fecha_inicio)::int) * 12 +
                                    (EXTRACT(MONTH FROM CURRENT_DATE)::int - EXTRACT(MONTH FROM os.fecha_inicio)::int) + 1
                                )
                                FROM ordenes_servicio os
                                WHERE os.orden_servicio_id = orden_servicio_items.orden_servicio_id
                            )');
                });
              })
              ->with('servicio.impuesto');
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
            $lapsoInicio   = $request->query('lapso_inicio');
            $lapsoFin      = $request->query('lapso_fin');
            $tercero       = $request->query('tercero');
            $facturaFiscal = $request->query('factura_fiscal');

            $with = [
                'tercero:tipo_id_tercero,tercero_id,nombre_tercero',
                'ultimaAuditoriaRel',
            ];
            if ($request->boolean('include_items')) {
                $with[] = 'items.ordenServicioItem';
            }
            $query = FacFactura::with($with);

            if ($lapsoInicio && !empty($lapsoInicio)) {
                $query->whereDate('fecha_registro', '>=', $lapsoInicio);
            }
            if ($lapsoFin && !empty($lapsoFin)) {
                $query->whereDate('fecha_registro', '<=', $lapsoFin);
            }

            if ($facturaFiscal && !empty($facturaFiscal)) {
                $query->where('factura_fiscal', 'like', '%' . $facturaFiscal . '%');
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

            $estadoFiltro = $request->query('estado');
            if ($estadoFiltro !== null && $estadoFiltro !== '') {
                $query->where('estado', $estadoFiltro);
            }

            if ($request->boolean('con_saldo')) {
                $query->where('saldo', '>', 0);
            }

            // Filtro especial para búsqueda desde Nota Crédito/Débito:
            // solo facturas activas (estado=1) cuyas notas crédito no cubren aún el total
            if ($request->boolean('para_nota_credito')) {
                $query->where('estado', '1');
                $query->whereRaw(
                    '(SELECT COALESCE(SUM(nc.valor_nota), 0)
                       FROM notas_credito nc
                      WHERE nc.empresa_id      = fac_facturas.empresa_id
                        AND nc.prefijo_factura = fac_facturas.prefijo
                        AND nc.factura_fiscal  = fac_facturas.factura_fiscal
                        AND nc.estado        != ?) < fac_facturas.total_factura',
                    ['ANULADA']
                );
            }

            $perPage  = min((int) $request->get('per_page', 20), 500);
            $facturas = $query->orderBy('fecha_registro', 'desc')->paginate($perPage);

            return response()->json($facturas);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Error obteniendo facturas',
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
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
            'usuario_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:orden_servicio_items,item_id',
            'items.*.cantidad' => 'nullable|numeric|min:0.01',
            'items.*.precio_unitario' => 'nullable|numeric|min:0',
            'items.*.observacion' => 'nullable|string|max:500',
            'items.*.impuesto_porcentaje' => 'nullable|numeric|min:0|max:100',
            'items.*.porcentaje_descuento' => 'nullable|numeric|min:0|max:100',
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
                $itemOverrides = collect($validated['items'])->keyBy('item_id');
                $ordenItems = OrdenServicioItem::whereIn('item_id', $itemsIds)->with('servicio.impuesto', 'ordenServicio')->get();

                // Aplicar overrides de cantidad/precio/observación si el usuario los editó
                foreach ($ordenItems as $ordenItem) {
                    $override = $itemOverrides->get($ordenItem->item_id);
                    if ($override) {
                        $changed = false;
                        if (isset($override['cantidad']) && $override['cantidad'] !== null) {
                            $ordenItem->cantidad = (float) $override['cantidad'];
                            $changed = true;
                        }
                        if (isset($override['precio_unitario']) && $override['precio_unitario'] !== null) {
                            $ordenItem->precio_unitario = (float) $override['precio_unitario'];
                            $changed = true;
                        }
                        if (isset($override['observacion']) && $override['observacion'] !== null) {
                            $ordenItem->observaciones = $override['observacion'];
                            $changed = true;
                        }
                        if (isset($override['porcentaje_descuento']) && $override['porcentaje_descuento'] !== null) {
                            $ordenItem->porcentaje_descuento = (float) $override['porcentaje_descuento'];
                            $changed = true;
                        }
                        if ($changed) {
                            $ordenItem->subtotal = $ordenItem->cantidad * $ordenItem->precio_unitario;
                            $ordenItem->save();
                        }
                    }
                }

                $totalFactura = $ordenItems->sum(function ($ordenItem) {
                    $descuento = (float) ($ordenItem->porcentaje_descuento ?? 0);
                    return round((float) $ordenItem->subtotal * (1 - $descuento / 100), 2);
                });

                // Calcular gravamen (IVA total): base gravable = subtotal * (1 - descuento/100)
                $totalGravamen = $ordenItems->sum(function ($ordenItem) use ($itemOverrides) {
                    $override = $itemOverrides->get($ordenItem->item_id);
                    $porcentaje = 0;
                    if (isset($override['impuesto_porcentaje']) && $override['impuesto_porcentaje'] !== null) {
                        $porcentaje = (float) $override['impuesto_porcentaje'];
                    } elseif ($ordenItem->servicio && $ordenItem->servicio->impuesto) {
                        $porcentaje = (float) ($ordenItem->servicio->impuesto->porcentaje ?? 0);
                    }
                    $descuento = (float) ($ordenItem->porcentaje_descuento ?? 0);
                    $baseGravable = (float) $ordenItem->subtotal * (1 - $descuento / 100);
                    return round($baseGravable * $porcentaje / 100, 2);
                });
                $totalGravamen = round($totalGravamen, 2);

                // Retención en la fuente: el porcentaje sale de la orden de servicio y se calcula
                // solo sobre los ítems que se están facturando (total_factura ya excluye los demás).
                $porcentajeRetFuente = 0.0;
                foreach ($ordenItems as $ordenItem) {
                    $pct = (float) ($ordenItem->ordenServicio->porcentaje_ret_fuente ?? 0);
                    if ($pct > 0) {
                        $porcentajeRetFuente = $pct;
                        break;
                    }
                }
                $valorRetFuente = round($totalFactura * $porcentajeRetFuente / 100, 2);

                $authUser = $request->user()
                    ?? auth('sanctum')->user()
                    ?? auth('web')->user();

                $usuarioId = $authUser
                    ? ($authUser->usuario_id ?? $authUser->id ?? null)
                    : ($validated['usuario_id'] ?? null);

                if (empty($usuarioId)) {
                    return response()->json([
                        'message' => 'No se pudo identificar el usuario autenticado para registrar la factura',
                    ], 401);
                }

                // Crear Factura
                $factura = FacFactura::create([
                    'empresa_id' => $prefijoDoc->empresa_id,
                    'prefijo' => $prefijoDoc->prefijo,
                    'factura_fiscal' => $numeroFactura,
                    'estado' => '1',
                    'usuario_id' => $usuarioId,
                    'total_factura' => $totalFactura,
                    'gravamen' => $totalGravamen,
                    'porcentaje_ret_fuente' => $porcentajeRetFuente,
                    'valor_ret_fuente' => $valorRetFuente,
                    'tipo_id_tercero' => $validated['tipo_id_tercero'],
                    'tercero_id' => $validated['tercero_id'],
                    'documento_id' => $prefijoDoc->documento_id,
                    'tipo_factura' => '5', // Tipo 5: Sin datos de salud
                    // Saldo por cobrar = base + IVA - retención en la fuente
                    'saldo' => round($totalFactura + $totalGravamen - $valorRetFuente, 2),
                    'fecha_vencimiento_factura' => Carbon::now('America/Bogota')->addDays(30),
                    'observacion' => $validated['observacion'],
                    'fecha_registro' => Carbon::now('America/Bogota'),
                    'fecha_periodo_inicio' => $validated['fecha_periodo_inicio'] ?? Carbon::now('America/Bogota')->startOfMonth(),
                    'fecha_periodo_fin' => $validated['fecha_periodo_fin'] ?? Carbon::now('America/Bogota')->endOfMonth(),
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

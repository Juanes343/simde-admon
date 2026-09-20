<?php

namespace App\Http\Controllers;

use App\Models\ControlAnticipo;
use App\Models\ControlPago;
use App\Models\FacFactura;
use App\Models\FacturaExterna;
use App\Models\Tercero;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CausacionController extends Controller
{
    /**
     * GET /api/causacion/anticipos?empresa_id=&tipo_id_tercero=&tercero_id=
     * Retorna el anticipo de un tercero. Si no existe, retorna saldo 0.
     */
    public function getAnticipo(Request $request)
    {
        try {
            $empresaId     = $request->query('empresa_id');
            $tipoIdTercero = $request->query('tipo_id_tercero');
            $terceroId     = $request->query('tercero_id');

            $anticipo = ControlAnticipo::where('empresa_id', $empresaId)
                ->where('tipo_id_tercero', $tipoIdTercero)
                ->where('tercero_id', $terceroId)
                ->first();

            return response()->json([
                'success'  => true,
                'anticipo' => $anticipo ?? [
                    'empresa_id'     => $empresaId,
                    'tipo_id_tercero' => $tipoIdTercero,
                    'tercero_id'     => $terceroId,
                    'saldo'          => 0,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CausacionController@getAnticipo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/causacion/facturas-pendientes?empresa_id=&tipo_id_tercero=&tercero_id=
     * Retorna facturas con saldo > 0 de un tercero.
     */
    public function getFacturasPendientes(Request $request)
    {
        try {
            $empresaId     = $request->query('empresa_id');
            $tipoIdTercero = $request->query('tipo_id_tercero');
            $terceroId     = $request->query('tercero_id');

            $facturas = FacFactura::where('empresa_id', $empresaId)
                ->where('tipo_id_tercero', $tipoIdTercero)
                ->where('tercero_id', $terceroId)
                ->where('estado', '1')
                ->where('saldo', '>', 0)
                ->orderBy('fecha_registro')
                ->get([
                    'factura_fiscal_id',
                    'prefijo',
                    'factura_fiscal',
                    'fecha_registro',
                    'fecha_vencimiento_factura',
                    'total_factura',
                    'gravamen',
                    'porcentaje_ret_fuente',
                    'valor_ret_fuente',
                    'saldo',
                    'concepto',
                ]);

            return response()->json(['success' => true, 'data' => $facturas]);
        } catch (\Exception $e) {
            Log::error('CausacionController@getFacturasPendientes: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/causacion/registrar-pagos
     * Registra pagos para una o varias facturas y actualiza saldos.
     *
     * Body: {
     *   empresa_id, tipo_id_tercero, tercero_id,
     *   fecha_pago, tipo_pago, observaciones,
     *   pagos: [{ factura_fiscal_id, valor_pago, valor_retencion }],
     *   usar_anticipo: boolean
     * }
     */
    public function registrarPagos(Request $request)
    {
        $request->validate([
            'empresa_id'       => 'required|string',
            'tipo_id_tercero'  => 'required|string',
            'tercero_id'       => 'required|string',
            'fecha_pago'       => 'required|date',
            'tipo_pago'        => 'required|string|in:EFECTIVO,TRANSFERENCIA,CHEQUE,ANTICIPO,MIXTO',
            'pagos'            => 'required|array|min:1',
            'pagos.*.factura_fiscal_id' => 'required|integer',
            'pagos.*.valor_pago'        => 'required|numeric|min:0.01',
            'pagos.*.valor_retencion'   => 'nullable|numeric|min:0',
            'pagos.*.valor_ica'         => 'nullable|numeric|min:0',
            'pagos.*.valor_reteiva'     => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $empresaId     = $request->empresa_id;
            $tipoIdTercero = $request->tipo_id_tercero;
            $terceroId     = $request->tercero_id;
            $usuarioId     = $request->user()->usuario_id;
            $usarAnticipo  = $request->boolean('usar_anticipo', false);

            $totalPagado = collect($request->pagos)->sum(
                fn($p) => ($p['valor_pago'] ?? 0) + ($p['valor_retencion'] ?? 0) + ($p['valor_ica'] ?? 0) + ($p['valor_reteiva'] ?? 0)
            );

            // Validar anticipo si aplica
            if ($usarAnticipo) {
                $anticipo = ControlAnticipo::where('empresa_id', $empresaId)
                    ->where('tipo_id_tercero', $tipoIdTercero)
                    ->where('tercero_id', $terceroId)
                    ->lockForUpdate()
                    ->first();

                // TODO: validación de saldo de anticipo comentada temporalmente
                // if (!$anticipo || $anticipo->saldo < $totalPagado) {
                //     DB::rollBack();
                //     return response()->json([
                //         'success' => false,
                //         'message' => 'Saldo de anticipo insuficiente. Disponible: ' . ($anticipo->saldo ?? 0),
                //     ], 422);
                // }
            }

            $pagosCreados = [];

            foreach ($request->pagos as $pagoData) {
                $factura = FacFactura::lockForUpdate()->find($pagoData['factura_fiscal_id']);

                if (!$factura) {
                    throw new \Exception("Factura {$pagoData['factura_fiscal_id']} no encontrada.");
                }
                if ($factura->estado !== '1') {
                    throw new \Exception("La factura {$factura->prefijo}{$factura->factura_fiscal} no está en estado activo.");
                }

                $valorRetencion = $pagoData['valor_retencion'] ?? 0;
                $valorIca       = $pagoData['valor_ica'] ?? 0;
                $valorReteiva   = $pagoData['valor_reteiva'] ?? 0;
                $totalAplicado  = $pagoData['valor_pago'] + $valorRetencion + $valorIca + $valorReteiva;

                if ($totalAplicado > ($factura->saldo + 0.01)) {
                    throw new \Exception(
                        "El valor a aplicar ({$totalAplicado}) supera el saldo de la factura {$factura->prefijo}{$factura->factura_fiscal} ({$factura->saldo})."
                    );
                }

                // Registrar pago
                $pago = ControlPago::create([
                    'empresa_id'        => $empresaId,
                    'factura_fiscal_id' => $factura->factura_fiscal_id,
                    'tipo_id_tercero'   => $tipoIdTercero,
                    'tercero_id'        => $terceroId,
                    'valor_pago'        => $pagoData['valor_pago'],
                    'valor_retencion'   => $valorRetencion,
                    'valor_ica'         => $valorIca,
                    'valor_reteiva'     => $valorReteiva,
                    'fecha_pago'        => $request->fecha_pago,
                    'tipo_pago'         => $request->tipo_pago,
                    'observaciones'     => $request->observaciones,
                    'estado'            => '1',
                    'usuario_id'        => $usuarioId,
                ]);

                // Actualizar saldo de factura
                $nuevoSaldo = max(0, round($factura->saldo - $totalAplicado, 2));
                $factura->saldo = $nuevoSaldo;
                if ($nuevoSaldo < 1) {
                    $factura->estado = '2'; // Pagada
                    $factura->saldo  = 0;
                }
                $factura->save();

                $pagosCreados[] = $pago;
            }

            // Descontar del anticipo si aplica
            if ($usarAnticipo) {
                $anticipo = ControlAnticipo::where('empresa_id', $empresaId)
                    ->where('tipo_id_tercero', $tipoIdTercero)
                    ->where('tercero_id', $terceroId)
                    ->first();
                if ($anticipo) {
                    $anticipo->saldo = max(0, round($anticipo->saldo - $totalPagado, 2));
                    $anticipo->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($pagosCreados) . ' pago(s) registrado(s) correctamente.',
                'pagos'   => $pagosCreados,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CausacionController@registrarPagos: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/causacion/pagos?empresa_id=&tercero_id=&desde=&hasta=
     * Historial de pagos registrados.
     */
    public function getPagos(Request $request)
    {
        try {
            $query = ControlPago::with(['factura:factura_fiscal_id,prefijo,factura_fiscal,total_factura'])
                ->where('empresa_id', $request->query('empresa_id'));

            if ($request->query('tercero_id')) {
                $query->where('tercero_id', $request->query('tercero_id'));
            }
            if ($request->query('desde')) {
                $query->whereDate('fecha_pago', '>=', $request->query('desde'));
            }
            if ($request->query('hasta')) {
                $query->whereDate('fecha_pago', '<=', $request->query('hasta'));
            }

            $pagos = $query->orderByDesc('fecha_pago')->orderByDesc('id')->paginate(50);

            return response()->json(['success' => true, 'data' => $pagos]);
        } catch (\Exception $e) {
            Log::error('CausacionController@getPagos: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/causacion/pagos/{id}
     * Edita un pago existente: restaura el saldo anterior de la factura y aplica los nuevos valores.
     * Permite reasignar a otra factura (del mismo u otro tercero).
     */
    public function actualizarPago(Request $request, $id)
    {
        $request->validate([
            'factura_fiscal_id' => 'nullable|integer',
            'valor_pago'        => 'required|numeric|min:0.01',
            'valor_retencion'   => 'nullable|numeric|min:0',
            'valor_ica'         => 'nullable|numeric|min:0',
            'valor_reteiva'     => 'nullable|numeric|min:0',
            'fecha_pago'        => 'required|date',
            'tipo_pago'         => 'required|string|in:EFECTIVO,TRANSFERENCIA,CHEQUE,ANTICIPO,MIXTO',
            'observaciones'     => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $pago = ControlPago::lockForUpdate()->findOrFail($id);

            $valorRetencion  = $request->valor_retencion ?? 0;
            $valorIca        = $request->valor_ica ?? 0;
            $valorReteiva    = $request->valor_reteiva ?? 0;
            $nuevoTotal      = $request->valor_pago + $valorRetencion + $valorIca + $valorReteiva;
            $totalAnterior   = $pago->valor_pago + $pago->valor_retencion + $pago->valor_ica + $pago->valor_reteiva;
            $nuevaFacturaId  = $request->factura_fiscal_id ?? $pago->factura_fiscal_id;

            // 1. Restaurar saldo en la factura original
            $facturaAnterior = FacFactura::lockForUpdate()->findOrFail($pago->factura_fiscal_id);
            $facturaAnterior->saldo = round($facturaAnterior->saldo + $totalAnterior, 2);
            if ($facturaAnterior->estado === '2') {
                $facturaAnterior->estado = '1'; // Reabrir si estaba pagada
            }
            $facturaAnterior->save();

            // 2. Cargar factura destino (puede ser la misma ya restaurada)
            if ($nuevaFacturaId == $pago->factura_fiscal_id) {
                $facturaNueva = $facturaAnterior; // misma, ya restaurada
            } else {
                $facturaNueva = FacFactura::lockForUpdate()->find($nuevaFacturaId);
                if (!$facturaNueva) {
                    throw new \Exception("La factura destino #{$nuevaFacturaId} no existe.");
                }
                if ($facturaNueva->estado !== '1') {
                    throw new \Exception("La factura {$facturaNueva->prefijo}{$facturaNueva->factura_fiscal} no está en estado activo.");
                }
            }

            // 3. Validar saldo disponible
            if ($nuevoTotal > ($facturaNueva->saldo + 0.01)) {
                throw new \Exception(
                    "El total a aplicar (" . number_format($nuevoTotal, 2) . ") supera el saldo disponible de la factura " .
                    "{$facturaNueva->prefijo}{$facturaNueva->factura_fiscal} (" . number_format($facturaNueva->saldo, 2) . ")."
                );
            }

            // 4. Aplicar nuevo total a factura destino
            $nuevoSaldo = max(0, round($facturaNueva->saldo - $nuevoTotal, 2));
            $facturaNueva->saldo = $nuevoSaldo;
            if ($nuevoSaldo < 1) {
                $facturaNueva->estado = '2';
                $facturaNueva->saldo  = 0;
            }
            $facturaNueva->save();

            // 5. Actualizar el registro de pago
            $pago->update([
                'factura_fiscal_id' => $nuevaFacturaId,
                'tipo_id_tercero'   => $facturaNueva->tipo_id_tercero ?? $pago->tipo_id_tercero,
                'tercero_id'        => $facturaNueva->tercero_id ?? $pago->tercero_id,
                'valor_pago'        => $request->valor_pago,
                'valor_retencion'   => $valorRetencion,
                'valor_ica'         => $valorIca,
                'valor_reteiva'     => $valorReteiva,
                'fecha_pago'        => $request->fecha_pago,
                'tipo_pago'         => $request->tipo_pago,
                'observaciones'     => $request->observaciones,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago actualizado correctamente.',
                'pago'    => $pago->fresh(['factura']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CausacionController@actualizarPago: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/causacion/anticipos
     * Crea o actualiza el anticipo de un tercero.
     */
    public function upsertAnticipo(Request $request)
    {
        $request->validate([
            'empresa_id'      => 'required|string',
            'tipo_id_tercero' => 'required|string',
            'tercero_id'      => 'required|string',
            'monto'           => 'required|numeric|min:0.01',
        ]);

        try {
            $anticipo = ControlAnticipo::firstOrNew([
                'empresa_id'      => $request->empresa_id,
                'tipo_id_tercero' => $request->tipo_id_tercero,
                'tercero_id'      => $request->tercero_id,
            ]);
            // Acumular el monto al saldo existente
            $anticipo->saldo = round(($anticipo->saldo ?? 0) + $request->monto, 2);
            $anticipo->save();

            return response()->json([
                'success'  => true,
                'message'  => 'Anticipo registrado correctamente.',
                'anticipo' => $anticipo,
            ]);
        } catch (\Throwable $e) {
            Log::error('CausacionController@upsertAnticipo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTE DE FACTURAS Y PAGOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Encabezados del reporte, en el orden exacto de la plantilla de Excel.
     * Las columnas en blanco se mantienen para conservar la estructura.
     */
    private function reporteHeaders(): array
    {
        return [
            'ENTIDAD',
            'AUDITORIA',
            'CONVENIO SOLTEC',
            '%',
            'NIT',
            'CIUDAD',
            'FAC S-FE',
            'FECHA',
            'CANT',
            'PRECIO UNIDAD',
            'VALOR',
            'DTO',
            'VALOR-DCTO',
            'IVA',
            'TOTAL +IVA',
            'BANCO',
            'FECHA PAGO',
            'VALOR PAGO',
            'BANCO',
            'FECHA PAGO ABONO 2',
            'ABONO 2',
            'BANCO',
            'FECHA PAGO ABONO 3',
            'ABONO 3',
            'BANCO',
            'FECHA PAGO ABONO 4',
            'ABONO 4',
            'BANCO',
            'CONCEPTO',
            'RTE',
            'ICA',
            'OTRA RTE',
            'TOTAL A PAGAR',
            'SALDO',
            'OBSERVACIONES',
            'PAGO A SOLTEC',
        ];
    }

    /**
     * GET /api/causacion/reporte
     * Previsualización del reporte de facturas y pagos (JSON).
     */
    public function reporte(Request $request)
    {
        try {
            $data = $this->construirReporte($request);
            return response()->json([
                'success' => true,
                'headers' => $this->reporteHeaders(),
                'rows'    => $data,
                'total'   => count($data),
            ]);
        } catch (\Throwable $e) {
            Log::error('CausacionController@reporte: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/causacion/reporte/excel
     * Descarga el reporte de facturas y pagos en formato Excel (.xls).
     */
    public function reporteExcel(Request $request)
    {
        try {
            $rows    = $this->construirReporte($request);
            $headers = $this->reporteHeaders();
            $xls     = $this->generarXls($headers, $rows);
            $nombre  = 'reporte_facturas_pagos_' . Carbon::now('America/Bogota')->format('Ymd_His') . '.xls';

            return response($xls, 200, [
                'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
            ]);
        } catch (\Throwable $e) {
            Log::error('CausacionController@reporteExcel: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Construye las filas del reporte a partir de las facturas internas
     * (fac_facturas + control_pagos) y, opcionalmente, las facturas externas.
     *
     * Filtros (query params):
     *  - fecha_factura_desde / fecha_factura_hasta : rango sobre fecha_registro
     *  - fecha_pago_desde / fecha_pago_hasta       : rango sobre control_pagos.fecha_pago
     *  - tercero                                    : nombre o NIT (opcional)
     *  - incluir_externas                           : bool (default true)
     */
    private function construirReporte(Request $request): array
    {
        $ffDesde         = $request->query('fecha_factura_desde');
        $ffHasta         = $request->query('fecha_factura_hasta');
        $fpDesde         = $request->query('fecha_pago_desde');
        $fpHasta         = $request->query('fecha_pago_hasta');
        $tercero         = trim((string) $request->query('tercero', ''));
        $incluirExternas = $request->boolean('incluir_externas', true);

        // ── 1. Facturas internas ────────────────────────────────────────────
        $queryFF = FacFactura::query()->where('estado', '!=', '3'); // excluir anuladas

        if ($ffDesde) {
            $queryFF->whereDate('fecha_registro', '>=', $ffDesde);
        }
        if ($ffHasta) {
            $queryFF->whereDate('fecha_registro', '<=', $ffHasta);
        }
        if ($tercero !== '') {
            $queryFF->where(function ($q) use ($tercero) {
                $q->where('tercero_id', 'like', "%$tercero%")
                  ->orWhereExists(function ($s) use ($tercero) {
                      $s->select(DB::raw(1))->from('terceros')
                        ->whereColumn('terceros.tercero_id', 'fac_facturas.tercero_id')
                        ->whereColumn('terceros.tipo_id_tercero', 'fac_facturas.tipo_id_tercero')
                        ->where('terceros.nombre_tercero', 'like', "%$tercero%");
                  });
            });
        }
        if ($fpDesde || $fpHasta) {
            $queryFF->whereExists(function ($s) use ($fpDesde, $fpHasta) {
                $s->select(DB::raw(1))->from('control_pagos')
                  ->whereColumn('control_pagos.factura_fiscal_id', 'fac_facturas.factura_fiscal_id');
                if ($fpDesde) {
                    $s->whereDate('control_pagos.fecha_pago', '>=', $fpDesde);
                }
                if ($fpHasta) {
                    $s->whereDate('control_pagos.fecha_pago', '<=', $fpHasta);
                }
            });
        }

        $facturas = $queryFF->orderBy('fecha_registro')->get();

        // Pagos agrupados por factura
        $pagosPorFactura = ControlPago::whereIn('factura_fiscal_id', $facturas->pluck('factura_fiscal_id')->all())
            ->orderBy('fecha_pago')
            ->get()
            ->groupBy('factura_fiscal_id');

        // ── 2. Facturas externas (opcional) ─────────────────────────────────
        $externas = collect();
        if ($incluirExternas) {
            $queryFE = FacturaExterna::query()->where('estado', '!=', '3');

            if ($ffDesde) {
                $queryFE->whereDate('fecha_registro', '>=', $ffDesde);
            }
            if ($ffHasta) {
                $queryFE->whereDate('fecha_registro', '<=', $ffHasta);
            }
            if ($tercero !== '') {
                $queryFE->where(function ($q) use ($tercero) {
                    $q->where('tercero_id', 'like', "%$tercero%")
                      ->orWhereExists(function ($s) use ($tercero) {
                          $s->select(DB::raw(1))->from('terceros')
                            ->whereColumn('terceros.tercero_id', 'facturas_externas.tercero_id')
                            ->whereColumn('terceros.tipo_id_tercero', 'facturas_externas.tipo_id_tercero')
                            ->where('terceros.nombre_tercero', 'like', "%$tercero%");
                      });
                });
            }
            // Si se filtra por fecha de pago, las externas (sin pagos) se excluyen
            if (!($fpDesde || $fpHasta)) {
                $externas = $queryFE->orderBy('fecha_registro')->get();
            }
        }

        // ── 3. Precargar terceros + municipio ───────────────────────────────
        $terceroIds = $facturas->pluck('tercero_id')
            ->merge($externas->pluck('tercero_id'))
            ->filter()
            ->unique()
            ->values();

        $terceros = Tercero::with('municipio')
            ->whereIn('tercero_id', $terceroIds)
            ->get()
            ->keyBy('tercero_id');

        $resolverTercero = function ($terceroId) use ($terceros) {
            $t = $terceros->get($terceroId);
            $nombre = $t->nombre_tercero ?? '';
            $nit    = (string) $terceroId;
            if ($t && !empty($t->dv)) {
                $nit .= '-' . $t->dv;
            }
            $ciudad = $t && $t->municipio
                ? ($t->municipio->municipio ?? $t->municipio->nombre_mpio_dian ?? '')
                : '';
            return [$nombre, $nit, $ciudad];
        };

        $rows = [];

        // ── 4. Mapear facturas internas ─────────────────────────────────────
        foreach ($facturas as $f) {
            [$nombre, $nit, $ciudad] = $resolverTercero($f->tercero_id);

            $base     = (float) ($f->total_factura ?? 0);
            $iva      = (float) ($f->gravamen ?? 0);
            $totalIva = round($base + $iva, 2);

            $pagos       = $pagosPorFactura->get($f->factura_fiscal_id, collect());
            // RTE = retención de la factura (ya descontada del saldo al facturar) + retenciones registradas en pagos
            $rte         = round((float) ($f->valor_ret_fuente ?? 0) + (float) $pagos->sum('valor_retencion'), 2);
            $ica         = round((float) $pagos->sum('valor_ica'), 2);
            $otraRte     = round((float) $pagos->sum('valor_reteiva'), 2);
            $valorPago   = round((float) $pagos->sum('valor_pago'), 2);
            $primerPago  = $pagos->first();
            $fechaPago   = $primerPago ? Carbon::parse($primerPago->fecha_pago)->format('d/m/Y') : '';
            $obsPago     = $primerPago->observaciones ?? '';

            $totalPagar  = round($totalIva - $rte - $ica - $otraRte, 2);
            $saldo       = round($totalPagar - $valorPago, 2);

            $rows[] = $this->reporteRow([
                'entidad'     => $nombre,
                'nit'         => $nit,
                'ciudad'      => $ciudad,
                'fac_sfe'     => trim(($f->prefijo ?? '') . ($f->factura_fiscal ?? '')),
                'fecha'       => $f->fecha_registro ? Carbon::parse($f->fecha_registro)->format('d/m/Y') : '',
                'valor'       => $base,
                'dto'         => 0,
                'iva'         => $iva,
                'total_iva'   => $totalIva,
                'fecha_pago'  => $fechaPago,
                'valor_pago'  => $valorPago,
                'concepto'    => $f->concepto ?: ($f->observacion ?? ''),
                'rte'         => $rte,
                'ica'         => $ica,
                'otra_rte'    => $otraRte,
                'total_pagar' => $totalPagar,
                'saldo'       => $saldo,
                'obs'         => $f->observacion ?: $obsPago,
            ]);
        }

        // ── 5. Mapear facturas externas ─────────────────────────────────────
        foreach ($externas as $f) {
            [$nombre, $nit, $ciudad] = $resolverTercero($f->tercero_id);

            $base     = (float) ($f->total_factura ?? 0);
            $iva      = (float) ($f->gravamen ?? 0);
            $dto      = (float) ($f->descuento ?? 0);
            $totalIva = round($base + $iva, 2);

            $rte        = round((float) ($f->retencion_fuente ?? 0), 2);
            $ica        = round((float) ($f->reteica ?? 0), 2);
            $otraRte    = 0.0;
            $totalPagar = round($totalIva - $rte - $ica - $otraRte, 2);
            $saldo      = $f->saldo !== null ? round((float) $f->saldo, 2) : $totalPagar;

            $rows[] = $this->reporteRow([
                'entidad'     => $nombre,
                'nit'         => $nit,
                'ciudad'      => $ciudad,
                'fac_sfe'     => trim(($f->prefijo ?? '') . ($f->factura_fiscal ?? '')),
                'fecha'       => $f->fecha_registro ? Carbon::parse($f->fecha_registro)->format('d/m/Y') : '',
                'valor'       => $base,
                'dto'         => $dto,
                'iva'         => $iva,
                'total_iva'   => $totalIva,
                'fecha_pago'  => '',
                'valor_pago'  => 0,
                'concepto'    => $f->concepto ?: ($f->observacion ?? ''),
                'rte'         => $rte,
                'ica'         => $ica,
                'otra_rte'    => $otraRte,
                'total_pagar' => $totalPagar,
                'saldo'       => $saldo,
                'obs'         => $f->observacion ?? '',
            ]);
        }

        return $rows;
    }

    /**
     * Arma una fila completa del reporte respetando el orden de columnas.
     * Las columnas acordadas se dejan en blanco ('').
     */
    private function reporteRow(array $d): array
    {
        return [
            $d['entidad'],      // ENTIDAD
            '',                 // AUDITORIA
            '',                 // CONVENIO SOLTEC
            '',                 // %
            $d['nit'],          // NIT
            $d['ciudad'],       // CIUDAD
            $d['fac_sfe'],      // FAC S-FE
            $d['fecha'],        // FECHA
            '',                 // CANT
            '',                 // PRECIO UNIDAD
            $d['valor'],        // VALOR
            $d['dto'],          // DTO
            '',                 // VALOR-DCTO
            $d['iva'],          // IVA
            $d['total_iva'],    // TOTAL +IVA
            '',                 // BANCO
            $d['fecha_pago'],   // FECHA PAGO
            $d['valor_pago'],   // VALOR PAGO
            '',                 // BANCO
            '',                 // FECHA PAGO ABONO 2
            '',                 // ABONO 2
            '',                 // BANCO
            '',                 // FECHA PAGO ABONO 3
            '',                 // ABONO 3
            '',                 // BANCO
            '',                 // FECHA PAGO ABONO 4
            '',                 // ABONO 4
            '',                 // BANCO
            $d['concepto'],     // CONCEPTO
            $d['rte'],          // RTE
            $d['ica'],          // ICA
            $d['otra_rte'],     // OTRA RTE
            $d['total_pagar'],  // TOTAL A PAGAR
            $d['saldo'],        // SALDO
            $d['obs'],          // OBSERVACIONES
            '',                 // PAGO A SOLTEC
        ];
    }

    /**
     * Genera un archivo Excel (.xls) a partir de encabezados y filas,
     * usando una tabla HTML compatible con Microsoft Excel (sin dependencias).
     */
    private function generarXls(array $headers, array $rows): string
    {
        $esc = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $html  = '<html xmlns:o="urn:schemas-microsoft-com:office:office" ';
        $html .= 'xmlns:x="urn:schemas-microsoft-com:office:excel" ';
        $html .= 'xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
        $html .= '<body><table border="1">';

        // Encabezados
        $html .= '<tr>';
        foreach ($headers as $h) {
            $html .= '<th style="background:#1f4e78;color:#fff;font-weight:bold;">' . $esc($h) . '</th>';
        }
        $html .= '</tr>';

        // Columnas que deben tratarse como texto (evitar notación científica)
        $textCols = [4]; // NIT

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $i => $cell) {
                if (in_array($i, $textCols, true)) {
                    $html .= '<td style="mso-number-format:\'\@\';">' . $esc($cell) . '</td>';
                } else {
                    $html .= '<td>' . $esc($cell) . '</td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        return $html;
    }
}

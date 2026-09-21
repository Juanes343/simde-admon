<?php

namespace App\Http\Controllers;

use App\Mail\SolicitudFirmaMailable;
use App\Models\OrdenServicio;
use App\Services\OrdenFirmaService;
use App\Services\OrdenServicioPdfService;
use App\Support\FirmaImagen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Firma del cliente sobre una orden de servicio que no nació de una cotización aprobada
 * (las órdenes de cotizaciones aprobadas ya quedan firmadas). El cliente recibe un enlace por correo,
 * revisa la orden, y la firma con su nombre y documento.
 */
class FirmaDigitalController extends Controller
{
    /**
     * Genera el enlace de firma (con token y vigencia) y lo envía al correo del tercero, con la orden en PDF.
     */
    public function solicitarFirma(Request $request, $id, OrdenServicioPdfService $pdfService)
    {
        $orden = OrdenServicio::find($id);

        if (!$orden) {
            return response()->json(['message' => 'Orden de servicio no encontrada.'], 404);
        }

        if ($orden->sw_estado !== '1') {
            return response()->json(['message' => 'La orden no está activa.'], 400);
        }

        if ($orden->fecha_firma) {
            return response()->json(['message' => 'La orden ya está firmada.'], 400);
        }

        $email = $orden->tercero?->email;
        if (!$email) {
            return response()->json([
                'message' => 'El tercero no tiene un correo registrado. Regístrelo para poder solicitar la firma.',
            ], 422);
        }

        // En BD solo se guarda el hash del token; el token en claro va únicamente en el enlace del correo
        $token    = Str::random(64);
        $expiraEn = now()->addDays(config('ordenes_servicio.dias_vigencia_firma', 7));

        $orden->update([
            'signature_token'            => hash('sha256', $token),
            'signature_token_expires_at' => $expiraEn,
        ]);

        $enlace  = $this->urlFrontend($request) . "/#/firmar-orden/{$orden->orden_servicio_id}/{$token}";
        $pdfPath = null;

        try {
            $doc = $pdfService->datos($orden);

            $pdfPath = tempnam(sys_get_temp_dir(), 'os_');
            file_put_contents($pdfPath, $pdfService->generar($orden, $doc)->output());

            Mail::to($email)->send(new SolicitudFirmaMailable($orden, $enlace, $doc, $pdfPath, $expiraEn));

            return response()->json(['message' => "Solicitud de firma enviada al correo: {$email}"]);
        } catch (\Throwable $e) {
            Log::error("Error solicitando firma de {$orden->numero_orden}: " . $e->getMessage());
            return response()->json(['message' => 'Error al enviar la solicitud de firma: ' . $e->getMessage()], 500);
        } finally {
            if ($pdfPath) {
                @unlink($pdfPath);
            }
        }
    }

    /**
     * (Público) Datos de la orden para mostrar en la página de firma.
     */
    public function verificarToken($id, $token, OrdenServicioPdfService $pdfService)
    {
        $orden = OrdenServicio::find($id);

        if ($error = $this->validarEnlace($orden, $token)) {
            return $error;
        }

        $doc = $pdfService->datos($orden);

        return response()->json([
            'numero_documento' => $doc['numero_orden'],
            'cliente'          => $doc['cliente']['nombre'],
            'fecha_inicio'     => \Carbon\Carbon::parse($doc['fecha_inicio'])->format('Y-m-d'),
            'fecha_fin'        => \Carbon\Carbon::parse($doc['fecha_fin'])->format('Y-m-d'),
            'periodo_dias'     => $doc['periodo_dias'],
            'prorroga'         => $doc['prorroga'],
            'metodo_pago'      => $doc['metodo_pago'],
            'tipo_pago'        => $doc['tipo_pago'],
            'orden_compra'     => $doc['orden_compra'],
            'notas'            => $doc['notas'],
            'subtotal'         => $doc['totales']['subtotal'],
            'descuento_total'  => $doc['totales']['descuento'],
            'impuestos_total'  => $doc['totales']['impuestos'],
            'retencion_total'  => round($doc['totales']['retencion'], 2),
            'total'            => $doc['totales']['total'],
            'items'            => array_map(fn ($item) => [
                'descripcion'          => $item['descripcion'],
                'observaciones'        => $item['observaciones'],
                'cantidad'             => $item['cantidad'],
                'tipo_unidad'          => $item['unidad'],
                'precio_unitario'      => $item['precio'],
                'porcentaje_descuento' => $item['descuento'],
                'impuesto_porcentaje'  => $item['impuesto_pct'],
                'porcentaje_ret_fuente'=> $item['retencion'],
                'subtotal'             => $item['subtotal'],
                'total'                => $item['total'],
            ], $doc['items']),
            'enlace_expira_en' => $orden->signature_token_expires_at?->toIso8601String(),
        ]);
    }

    /**
     * (Público) Guarda la firma del cliente y le envía la orden firmada (PDF), también a los correos internos.
     */
    public function firmar(Request $request, $id, $token, OrdenFirmaService $firmaService)
    {
        $validator = Validator::make($request->all(), [
            'nombre'    => 'required|string|max:150',
            'documento' => 'required|string|max:50',
            'firma'     => 'required|string|max:700000',
        ], [
            'nombre.required'    => 'Ingrese su nombre completo.',
            'documento.required' => 'Ingrese su número de documento.',
            'firma.required'     => 'Por favor firme en el recuadro antes de continuar.',
            'firma.max'          => 'La imagen de la firma es demasiado grande.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $firma = $request->input('firma');
        if (!FirmaImagen::esValida($firma)) {
            return response()->json(['message' => 'La firma no tiene un formato válido.'], 422);
        }

        try {
            $resultado = DB::transaction(function () use ($id, $token, $request, $firma) {
                // Bloqueo de fila: un doble clic no puede firmar dos veces
                $orden = OrdenServicio::lockForUpdate()->find($id);

                if ($error = $this->validarEnlace($orden, $token)) {
                    return ['error' => $error];
                }

                $orden->update([
                    'firma_tercero'      => $firma,
                    'fecha_firma'        => now(),
                    'firmante_nombre'    => trim($request->input('nombre')),
                    'firmante_documento' => trim($request->input('documento')),
                    'firma_ip'           => $request->ip(),
                ]);

                return ['orden' => $orden];
            });
        } catch (\Throwable $e) {
            Log::error("Error guardando firma de la orden {$id}: " . $e->getMessage());
            return response()->json(['message' => 'No se pudo guardar la firma. Intente nuevamente.'], 500);
        }

        if (isset($resultado['error'])) {
            return $resultado['error'];
        }

        // Ya confirmada la firma: los correos no pueden revertirla
        $firmaService->notificarFirma($resultado['orden']);

        return response()->json([
            'message'      => 'Orden de servicio firmada correctamente.',
            'numero_orden' => $resultado['orden']->numero_orden,
        ]);
    }

    /**
     * Devuelve una respuesta de error si el enlace no es utilizable, o null si todo está bien.
     */
    private function validarEnlace(?OrdenServicio $orden, string $token): ?JsonResponse
    {
        $hash = $orden?->signature_token;

        if (!$orden || !$hash || !hash_equals($hash, hash('sha256', $token))) {
            return response()->json(['message' => 'El enlace no es válido.'], 404);
        }

        if ($orden->fecha_firma) {
            return response()->json(['message' => 'Esta orden de servicio ya fue firmada.', 'estado' => 'firmada'], 409);
        }

        if ($orden->sw_estado !== '1') {
            return response()->json(['message' => 'Esta orden de servicio ya no está disponible para firma.'], 410);
        }

        if ($orden->signature_token_expires_at && $orden->signature_token_expires_at->isPast()) {
            return response()->json(['message' => 'El enlace de firma expiró. Solicite uno nuevo a SIMDE.'], 410);
        }

        return null;
    }
}

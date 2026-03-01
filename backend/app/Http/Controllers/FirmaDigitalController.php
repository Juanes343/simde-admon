<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SolicitudFirmaMailable;
use App\Mail\OrdenFirmadaMailable; // Asumiremos que creas este mailable luego o reutilizas lógica
use Barryvdh\DomPDF\Facade\Pdf;

class FirmaDigitalController extends Controller
{
    /**
     * Genera un token de firma y envía el correo al tercero.
     */
    public function solicitarFirma(Request $request, $id)
    {
        try {
            $orden = OrdenServicio::findOrFail($id);

            // Validar que la orden esté activa y sin firmar
            if ($orden->sw_estado !== '1') {
                return response()->json(['message' => 'La orden no está activa.'], 400);
            }
            if ($orden->fecha_firma) {
                return response()->json(['message' => 'La orden ya está firmada.'], 400);
            }

            // Generar Token
            $token = Str::random(64);
            $orden->signature_token = $token;
            // El token expira en 48 horas, por ejemplo
            $orden->signature_token_expires_at = Carbon::now()->addHours(48);
            $orden->save();

            // Enviar Correo
            // Obtener email del tercero
            $email = $orden->tercero->email ?? null;
            
            // Construir link con el frontend URL
            // Usamos la variable de entorno o una url por defecto basada en la del usuario
            $frontendUrl = env('FRONTEND_URL', 'https://devel82els.simde.com.co/simde-admon/frontend/build'); 
            
            // Si usas HashRouter (#), asegúrate de incluir el #
            $link = "{$frontendUrl}/#/firmar-orden/{$id}/{$token}";

            if ($email) {
                Mail::to($email)->send(new SolicitudFirmaMailable($orden, $link));
                $mensaje = 'Solicitud de firma enviada al correo: ' . $email;
            } else {
                $mensaje = 'Token generado, pero el tercero no tiene email registrado.';
            }

            return response()->json([
                'message' => $mensaje,
                'link_debug' => $link // Útil para pruebas si no sale el correo
            ]);

        } catch (\Exception $e) {
            Log::error('Error solicitando firma: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno al solicitar firma: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verifica si el token es válido antes de mostrar la pantalla de firma (opcional pero recomendado)
     */
    public function verificarToken(Request $request, $id, $token)
    {
        $orden = OrdenServicio::find($id);

        if (!$orden || $orden->signature_token !== $token) {
            return response()->json(['message' => 'Token inválido o expirado.'], 404);
        }

        if ($orden->fecha_firma) {
             return response()->json(['message' => 'La orden ya ha sido firmada.'], 400);
        }

        /* 
        // Si usas expiración:
        if (Carbon::now()->greaterThan($orden->signature_token_expires_at)) {
            return response()->json(['message' => 'El enlace ha expirado.'], 400);
        }
        */

        return response()->json([
            'id' => $orden->id,
            'cliente' => $orden->tercero->nombre_completo ?? 'Cliente',
            'fecha' => $orden->created_at->format('Y-m-d'),
            'valid' => true
        ]);
    }

    /**
     * Guarda la firma enviada desde el frontend
     */
    public function firmar(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'token' => 'required|string',
            'firma' => 'required|string', // Base64 string
        ]);

        try {
            $orden = OrdenServicio::findOrFail($request->id);

            if ($orden->signature_token !== $request->token) {
                return response()->json(['message' => 'Token inválido.'], 403);
            }

            // Guardar firma y fecha
            $orden->firma_tercero = $request->firma;
            $orden->fecha_firma = Carbon::now();
            $orden->signature_token = null; // Invalidar token tras uso
            $orden->signature_token_expires_at = null;
            $orden->save();

            // Generar PDF Firmado (Opcional: Adjuntar y enviar por correo)
            // $pdf = Pdf::loadView('pdf.orden_firmada', ['orden' => $orden]);
            // Mail::to($orden->tercero->email)->send(new OrdenFirmadaMailable($orden, $pdf));

            return response()->json(['message' => 'Orden firmada correctamente.']);

        } catch (\Exception $e) {
            Log::error('Error guardando firma: ' . $e->getMessage());
            return response()->json(['message' => 'Error al guardar la firma.'], 500);
        }
    }
} 

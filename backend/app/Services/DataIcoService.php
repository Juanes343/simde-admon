<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataIcoService
{
    /**
     * Envía un documento (Factura/Nota) a DataIco.
     * 
     * @param array $payload Objeto JSON construido según v2.
     * @param string $endpoint Tipo de documento (invoices, credit_notes, debit_notes).
     * @param string $token Token de autenticación Auth-Token.
     */
    public function sendDocument(array $payload, string $endpoint, string $token)
    {
        $baseUrl = config('services.dataico.base_url', 'https://api.dataico.com/direct/dataico_api/v2');
        $url = "{$baseUrl}/{$endpoint}";

        try {
            $response = Http::withHeaders([
                'Auth-Token' => $token,
                'Content-Type' => 'application/json'
            ])->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'errors' => $response->json(),
                'message' => 'Error en la respuesta de DataIco'
            ];

        } catch (\Exception $e) {
            Log::error("Error de conexión con DataIco: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error de conexión con el proveedor de facturación',
                'error' => $e->getMessage()
            ];
        }
    }
}

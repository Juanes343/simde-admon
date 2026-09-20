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
        // URLs de producción
        $baseUrl = config('services.dataico.base_url', 'https://api.dataico.com/direct/dataico_api/v2');
        $url = "{$baseUrl}/{$endpoint}";

        try {
            $response = Http::withHeaders([
                'Auth-Token' => $token,
                'Content-Type' => 'application/json'
            ])->asJson()->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            // En caso de error 500 o similar, logueamos el body crudo para debug
            if ($response->status() >= 500) {
                Log::error("ERROR CRITICO DATAICO (500). URL: $url");
                Log::error("Payload enviado:", ['payload' => $payload]);
                Log::error("Respuesta cruda de DataIco: " . $response->body());
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'errors' => $response->json(),
                'message' => 'Error en la respuesta de DataIco: ' . ($response->json()['message'] ?? 'Error desconocido'),
                'raw_body' => $response->body()
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

    /**
     * Consulta un documento en DataIco por UUID o referencia (prefijo+numero).
     */
    public function getDocumentByReference(string $endpoint, string $prefix, string $number, string $token, ?string $uuid = null): array
    {
        $baseUrl = config('services.dataico.base_url', 'https://api.dataico.com/direct/dataico_api/v2');

        $candidates = [];
        if (!empty($uuid)) {
            $candidates[] = "{$baseUrl}/{$endpoint}/{$uuid}";
        }
        $candidates[] = "{$baseUrl}/{$endpoint}/{$prefix}{$number}";
        $candidates[] = "{$baseUrl}/{$endpoint}/{$prefix}-{$number}";

        $lastError = null;

        foreach ($candidates as $url) {
            try {
                $response = Http::withHeaders([
                    'Auth-Token' => $token,
                    'Content-Type' => 'application/json',
                ])->acceptJson()->get($url);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->json(),
                        'source_url' => $url,
                    ];
                }

                if ($response->status() !== 404) {
                    $lastError = [
                        'status' => $response->status(),
                        'errors' => $response->json(),
                        'raw_body' => $response->body(),
                        'source_url' => $url,
                    ];
                }
            } catch (\Exception $e) {
                $lastError = [
                    'status' => 0,
                    'message' => $e->getMessage(),
                    'source_url' => $url,
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Documento no encontrado en DataIco por referencia',
            'error_detail' => $lastError,
        ];
    }
}

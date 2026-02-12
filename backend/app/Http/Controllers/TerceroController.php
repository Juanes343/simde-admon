<?php

namespace App\Http\Controllers;

use App\Models\Tercero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Smalot\PdfParser\Parser as PdfParser;

class TerceroController extends Controller
{
    /**
     * Display a listing of terceros
     */
    public function index(Request $request)
    {
        $query = Tercero::with('usuario');

        // Filtros opcionales
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre_tercero', 'ILIKE', "%{$search}%")
                  ->orWhere('tercero_id', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->has('sw_estado')) {
            $query->where('sw_estado', $request->sw_estado);
        }

        $perPage = $request->get('per_page', 15);
        $terceros = $query->paginate($perPage);

        return response()->json($terceros);
    }

    /**
     * Store a newly created tercero
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_id_tercero' => 'required|string|max:3',
            'tercero_id' => 'required|string|max:32',
            'tipo_pais_id' => 'required|string|max:4',
            'tipo_dpto_id' => 'required|string|max:4',
            'tipo_mpio_id' => 'required|string|max:4',
            'direccion' => 'required|string|max:100',
            'nombre_tercero' => 'required|string|max:100',
            'email' => 'nullable|email|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar si ya existe
        $exists = Tercero::where('tipo_id_tercero', $request->tipo_id_tercero)
                         ->where('tercero_id', $request->tercero_id)
                         ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'El tercero ya existe'
            ], 409);
        }

        $tercero = Tercero::create(array_merge(
            $request->all(),
            ['usuario_id' => $request->user()->usuario_id]
        ));

        return response()->json([
            'message' => 'Tercero creado exitosamente',
            'tercero' => $tercero
        ], 201);
    }

    /**
     * Display the specified tercero
     */
    public function show($tipo_id_tercero, $tercero_id)
    {
        $tercero = Tercero::where('tipo_id_tercero', $tipo_id_tercero)
                          ->where('tercero_id', $tercero_id)
                          ->with('usuario')
                          ->first();

        if (!$tercero) {
            return response()->json(['message' => 'Tercero no encontrado'], 404);
        }

        return response()->json($tercero);
    }

    /**
     * Update the specified tercero
     */
    public function update(Request $request, $tipo_id_tercero, $tercero_id)
    {
        $tercero = Tercero::where('tipo_id_tercero', $tipo_id_tercero)
                          ->where('tercero_id', $tercero_id)
                          ->first();

        if (!$tercero) {
            return response()->json(['message' => 'Tercero no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'tipo_pais_id' => 'sometimes|required|string|max:4',
            'tipo_dpto_id' => 'sometimes|required|string|max:4',
            'tipo_mpio_id' => 'sometimes|required|string|max:4',
            'direccion' => 'sometimes|required|string|max:100',
            'nombre_tercero' => 'sometimes|required|string|max:100',
            'email' => 'nullable|email|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tercero->update($request->all());

        return response()->json([
            'message' => 'Tercero actualizado exitosamente',
            'tercero' => $tercero
        ]);
    }

    /**
     * Remove the specified tercero (soft delete)
     */
    public function destroy($tipo_id_tercero, $tercero_id)
    {
        $tercero = Tercero::where('tipo_id_tercero', $tipo_id_tercero)
                          ->where('tercero_id', $tercero_id)
                          ->first();

        if (!$tercero) {
            return response()->json(['message' => 'Tercero no encontrado'], 404);
        }

        $tercero->update(['sw_estado' => '0']);

        return response()->json([
            'message' => 'Tercero desactivado exitosamente'
        ]);
    }

    /**
     * Extract RUT data from PDF and create tercero
     */
    public function createFromPdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pdf' => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $pdfParser = new PdfParser();
            $pdf = $pdfParser->parseFile($request->file('pdf')->getRealPath());
            $text = $pdf->getText();

            // Extraer información del RUT (esto debe ajustarse según el formato del RUT)
            $data = $this->extractRutData($text);

            if (!$data) {
                // Devolver información de debug para ayudar a identificar el problema
                // Intentar detectar qué faltó
                $debugData = [];
                $textNormalized = preg_replace('/\s+/', ' ', $text);
                
                // Verificar si hay NIT en el texto
                if (preg_match('/(\d{9,10})/', $textNormalized, $matches)) {
                    $debugData['nit_candidate'] = $matches[1];
                }
                
                return response()->json([
                    'message' => 'No se pudo extraer información válida del PDF',
                    'debug' => array_merge([
                        'text_length' => strlen($text),
                        'text_preview' => substr($text, 0, 2000),
                        'hint' => 'El PDF debe ser un RUT de la DIAN con NIT y Razón Social. Si el PDF es una imagen escaneada, no se puede leer.'
                    ], $debugData)
                ], 400);
            }

            // Obtener el usuario autenticado
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar si el tercero ya existe
            $exists = Tercero::where('tipo_id_tercero', $data['tipo_id_tercero'])
                             ->where('tercero_id', $data['tercero_id'])
                             ->first();

            if ($exists) {
                return response()->json([
                    'message' => 'El tercero ya existe en el sistema',
                    'tercero' => $exists,
                    'info' => 'NIT: ' . $data['tercero_id'] . '-' . ($data['dv'] ?? '') . ' - ' . $data['nombre_tercero']
                ], 409);
            }

            // Crear el tercero con los datos extraídos
            $tercero = Tercero::create(array_merge(
                $data,
                ['usuario_id' => $user->usuario_id]
            ));

            return response()->json([
                'message' => 'Tercero creado exitosamente desde PDF',
                'tercero' => $tercero
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract RUT data from text
     */
    private function extractRutData($text)
    {
        // Preservar los saltos de línea originales para mejor parsing
        $textOriginal = $text;
        // También crear versión normalizada
        $textNormalized = preg_replace('/\s+/', ' ', $text);
        
        $data = [];

        // BUSCAR NIT - Formato RUT DIAN Colombia
        // El RUT tiene "5. Número de Identificación Tributaria (NIT)" seguido del número
        $nitPatterns = [
            // Buscar después del campo 5 del formulario RUT
            '/5\.\s*N[úu]mero\s+de\s+Identificaci[óo]n\s+Tributaria\s*\(NIT\)[^\d]*(\d{9,10})[^\d]*6\.\s*DV[^\d]*(\d)/',
            '/NIT[^\d]+(\d{9,10})[^\d]+DV[^\d]*(\d)/',
            '/Identificaci[óo]n\s+Tributaria[^\d]+(\d{9,10})[^\d]+(\d)/',
            // Patterns más flexibles
            '/(\d{9,10})[\s\-]+(\d)(?=\s|$)/', // Formato: 900123456-7 o 900123456 7
        ];

        foreach ($nitPatterns as $pattern) {
            if (preg_match($pattern, $textNormalized, $matches)) {
                $data['tercero_id'] = $matches[1];
                $data['dv'] = $matches[2] ?? '';
                $data['tipo_id_tercero'] = '31'; // NIT
                break;
            }
        }

        // Si no encontró NIT, buscar secuencia de 9-10 dígitos + 1 dígito (DV)
        if (empty($data['tercero_id'])) {
            if (preg_match('/\b(\d{9,10})\b/', $textNormalized, $matches)) {
                $data['tercero_id'] = $matches[1];
                $data['tipo_id_tercero'] = '31';
                // Buscar DV cerca
                if (preg_match('/\b' . preg_quote($matches[1]) . '\D+(\d)\b/', $textNormalized, $dvMatch)) {
                    $data['dv'] = $dvMatch[1];
                }
            }
        }

        // BUSCAR RAZÓN SOCIAL - Formato RUT DIAN
        $razonSocialPatterns = [
            // Buscar después del campo 35 del RUT
            '/35\.\s*Raz[óo]n\s+social[^\w]*([A-ZÁ-Ú0-9][A-ZÁ-Ú0-9\s\.\-&,\(\)]{3,80})/',
            '/Raz[óo]n\s+social[\s:\.]+([A-ZÁ-Ú0-9][A-ZÁ-Ú0-9\s\.\-&,\(\)]{3,80}?)(?:\s*\d{1,2}\.|31\.|32\.|33\.|Primer|Segundo)/i',
            // Buscar entre campo 35 y campos 31-34 (nombres y apellidos)
            '/35[^\w]+([A-ZÁ-Ú0-9][A-ZÁ-Ú0-9\s\.\-&,\(\)]{3,80}?)(?:\s+3[1-4]\.)/i',
        ];

        foreach ($razonSocialPatterns as $pattern) {
            if (preg_match($pattern, $textNormalized, $matches)) {
                $nombre = trim($matches[1]);
                // Limpiar caracteres extraños al final
                $nombre = preg_replace('/[\s\-\.]+$/', '', $nombre);
                if (strlen($nombre) >= 3) {
                    $data['nombre_tercero'] = $nombre;
                    break;
                }
            }
        }

        // BUSCAR DIRECCIÓN
        $direccionPatterns = [
            '/Direcci[óo]n\s+seccional[^\w]*([A-Z0-9][A-Z0-9\s\#\-\.]{5,80}?)(?:\s*14\.|Buz[óo]n)/i',
            '/12\.\s*Direcci[óo]n[^\w]*([A-Z0-9\#\-\.\s]{5,80}?)(?:\s*14\.|$)/i',
        ];

        foreach ($direccionPatterns as $pattern) {
            if (preg_match($pattern, $textNormalized, $matches)) {
                $data['direccion'] = trim($matches[1]);
                break;
            }
        }

        // Si no encontró dirección, usar valor por defecto
        if (empty($data['direccion'])) {
            $data['direccion'] = 'NO REGISTRA';
        }

        // BUSCAR EMAIL
        if (preg_match('/14\.\s*Buz[óo]n\s+electr[óo]nico[^\w]*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $textNormalized, $matches)) {
            $data['email'] = strtolower(trim($matches[1]));
        } elseif (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $textNormalized, $matches)) {
            $data['email'] = strtolower(trim($matches[1]));
        }

        // Valores predeterminados
        $data['tipo_pais_id'] = '169'; // Colombia
        $data['tipo_dpto_id'] = '0001';
        $data['tipo_mpio_id'] = '0001';
        $data['sw_persona_juridica'] = '1';
        $data['sw_estado'] = '1'; // Activo

        return !empty($data['tercero_id']) && !empty($data['nombre_tercero']) ? $data : null;
    }
}

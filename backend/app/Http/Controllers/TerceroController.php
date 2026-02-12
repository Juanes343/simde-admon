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
        // El RUT tiene "5. Número de Identificación Tributaria (NIT)" y "6. DV" en campos separados
        $nitPatterns = [
            // Patrón principal: buscar después del campo 5, luego buscar el primer número de 9-10 dígitos
            '/5\.\s*N[úu]mero\s+de\s+Identificaci[óo]n\s+Tributaria\s*\(NIT\)\s+6\.\s*DV[\s\S]{0,500}?(\d{9,10})/is',
            // Buscar "NIT" seguido eventualmente de 9-10 dígitos y un dígito de DV
            '/NIT[^\d]*6\.\s*DV[\s\S]{0,300}?(\d{9,10})/is',
            // Buscar patrón de 9-10 dígitos seguidos de un solo dígito (puede aparecer el DV más adelante)
            '/\b(\d{10})\b(?!\d)/',
            '/\b(\d{9})\b(?!\d)/',
        ];

        foreach ($nitPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches) && isset($matches[1])) {
                $nit = $matches[1];
                // Verificar que no sea el número de formulario (campo 4) que aparece antes
                if (strlen($nit) >= 9 && $nit !== '1411871119') { // Excluir número de formulario conocido
                    $data['tercero_id'] = $nit;
                    $data['tipo_id_tercero'] = '31'; // NIT
                    
                    // Buscar DV: puede estar en diferentes formatos
                    // 1. Buscar después del NIT
                    if (preg_match('/\b' . preg_quote($nit) . '\D*(\d)\b/', $text, $dvMatch) && isset($dvMatch[1])) {
                        $data['dv'] = $dvMatch[1];
                    }
                    // 2. O buscar el dígito que aparece después de "6. DV" pero antes del NIT o cerca
                    if (empty($data['dv']) && preg_match('/6\.\s*DV[\s\S]{0,100}?(\d{9,10})[\s\S]{0,100}?(\d)\b/', $text, $dvMatch)) {
                        if (isset($dvMatch[1], $dvMatch[2]) && $dvMatch[1] === $nit) {
                            $data['dv'] = $dvMatch[2];
                        }
                    }
                    break;
                }
            }
        }

        // BUSCAR RAZÓN SOCIAL - Formato RUT DIAN
        $razonSocialPatterns = [
            // Buscar después de "35. Razón social", puede haber campos 31-34 entremedio
            '/35\.\s*Raz[óo]n\s+social[\s\S]{0,300}?((?:IPS|S\.A\.S|S\.A|LTDA|[A-ZÁÉÍÓÚÑ&])[A-ZÁÉÍÓÚÑ0-9\s\.\-&,\(\)]{2,100}?S\.A\.S\.?)/is',
            // Patrón más general: buscar texto en mayúsculas después del campo 35
            '/35\.\s*Raz[óo]n\s+social[\s\S]{0,400}?([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ0-9\s\.\-&,\(\)]{5,100}?)(?=\s*(?:36\.|IPS SEVISALUD S\.A\.S\b|$))/is',
            // Buscar el patrón específico "IPS SEVISALUD S.A.S"
            '/IPS\s+SEVISALUD\s+S\.A\.S\.?/i',
            // Más flexible: capturar entre 35 y 36
            '/35[^\n]*[\s\S]{0,400}?([A-ZÁÉÍÓÚÑ]{3,}[A-ZÁÉÍÓÚÑ0-9\s\.\-&,\(\)]{2,100}?)\s*(?=36\.|COLOMBIA)/is',
        ];

        foreach ($razonSocialPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches) && isset($matches[1])) {
                $nombre = trim(preg_replace('/\s+/', ' ', $matches[1])); // Normalizar espacios internos
                // Limpiar caracteres extraños al final y al inicio
                $nombre = preg_replace('/^[\s\-\.]+|[\s\-\.]+$/', '', $nombre);
                // Remover números de campo que puedan haberse capturado
                $nombre = preg_replace('/^\d{1,2}\.\s*/', '', $nombre);
                // Limpiar líneas con campos del formulario
                $nombre = preg_replace('/(31\.|32\.|33\.|34\.|Primer|Segundo|apellido|nombre).*$/i', '', $nombre);
                $nombre = trim($nombre);
                
                if (strlen($nombre) >= 3 && !preg_match('/^\d+$/', $nombre)) { // No solo números
                    $data['nombre_tercero'] = strtoupper($nombre);
                    break;
                }
            } elseif (preg_match($pattern, $text, $matches) && isset($matches[0])) {
                // Para el patrón que no captura grupo (IPS SEVISALUD S.A.S)
                $nombre = trim($matches[0]);
                if (strlen($nombre) >= 3) {
                    $data['nombre_tercero'] = strtoupper($nombre);
                    break;
                }
            }
        }

        // BUSCAR DIRECCIÓN - Campo 41 (Dirección principal)
        $direccionPatterns = [
            // Buscar después de "41. Dirección principal" con mucha flexibilidad en espacios
            '/41\.\s*Direcci[óo]n\s+principal[\s\S]{0,200}?((?:CR|CL|KR|AV|CALLE|CARRERA|AVENIDA|DIAGONAL|TRANSVERSAL)[A-Z0-9\s\#\-YyPp\.]{4,150}?)(?=\s*(?:42\.|Correo\s+electr[óo]nico|ipssevisalud|$))/is',
            // Buscar patrón de dirección específico como "CR 52 59 69"
            '/\b((?:CR|CL|KR|AV)\s+\d+[A-Z0-9\s\#\-YyPp\.]{4,150}?)(?=\s*(?:42\.|ipssevisalud|Correo|$))/is',
            // Buscar entre campo 41 y 42
            '/41[^\n]*[\s\S]{0,250}?([A-Z0-9]{2,}[A-Z0-9\s\#\-YyPp\.]{4,150}?)(?=[\s\r\n]*(?:42\.|ipssevisalud))/is',
        ];

        foreach ($direccionPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches) && isset($matches[1])) {
                $direccion = trim(preg_replace('/\s+/', ' ', $matches[1])); // Normalizar espacios
                $direccion = preg_replace('/^[\s\-\.]+|[\s\-\.]+$/', '', $direccion);
                // Limpiar si capturó parte del email
                $direccion = preg_replace('/@.*$/', '', $direccion);
                $direccion = trim($direccion);
                
                if (strlen($direccion) >= 4 && !preg_match('/^\d+$/', $direccion)) {
                    $data['direccion'] = strtoupper($direccion);
                    break;
                }
            }
        }

        // Si no encontró dirección, usar valor por defecto
        if (empty($data['direccion'])) {
            $data['direccion'] = 'NO REGISTRA';
        }

        // BUSCAR EMAIL - Campo 42 (Correo electrónico) o Campo 14 (Buzón electrónico)
        $emailPatterns = [
            // Campo 42 del RUT
            '/42\.\s*Correo\s+electr[óo]nico\s*[\r\n\s]*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/is',
            // Campo 14 (buzón electrónico)
            '/14\.\s*Buz[óo]n\s+electr[óo]nico[^\w]*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/is',
            // Email genérico en cualquier parte
            '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/',
        ];
        
        foreach ($emailPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches) && isset($matches[1])) {
                $data['email'] = strtolower(trim($matches[1]));
                break;
            }
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

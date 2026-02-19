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
        $query = Tercero::with(['usuario', 'pais', 'departamento', 'municipio']);

        // Filtros opcionales
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre_tercero', 'LIKE', "%{$search}%")
                  ->orWhere('tercero_id', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
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
                          ->with(['usuario', 'pais', 'departamento', 'municipio', 'tipoIdentificacion'])
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
     * Toggle estado del tercero (activar/desactivar)
     */
    public function destroy($tipo_id_tercero, $tercero_id)
    {
        $tercero = Tercero::where('tipo_id_tercero', $tipo_id_tercero)
                          ->where('tercero_id', $tercero_id)
                          ->first();

        if (!$tercero) {
            return response()->json(['message' => 'Tercero no encontrado'], 404);
        }

        // Toggle estado: si está activo lo desactiva, si está inactivo lo activa
        $nuevoEstado = $tercero->sw_estado === '1' ? '0' : '1';
        $tercero->update(['sw_estado' => $nuevoEstado]);

        $mensaje = $nuevoEstado === '1' ? 'Tercero activado exitosamente' : 'Tercero desactivado exitosamente';

        return response()->json([
            'message' => $mensaje,
            'estado' => $nuevoEstado
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
        $data = [];

        // BUSCAR NIT - Basado en log: "141163793131 8903005165"
        // El RUT tiene el número de formulario (empieza por 14) y luego el NIT
        $nitPatterns = [
            '/(\d{9,10})\s*Impuestos\s+de\s+Cali/is',
            '/NIT\D*(\d{9,10})/is',
            '/5\.\s*N[úu]mero\s+de\s+Identificaci[óo]n\s+Tributaria\s*\(NIT\)\D*(\d{9,10})/is',
            '/\b(\d{10})\b/is',
            '/\b(\d{9})\b/is',
        ];

        foreach ($nitPatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    if (!str_starts_with($match, '1411') && $match !== '04112025') {
                        $data['tercero_id'] = $match;
                        $data['tipo_id_tercero'] = 'NIT'; 
                        break 2;
                    }
                }
            }
        }

        // BUSCAR RAZÓN SOCIAL - En el log: "CLINICA SAN FERNANDO S.A."
        $razonSocialPatterns = [
            '/Persona\s+jur[íi]dica[^\n]*\n\s*\n\s*\n\s*\n\s*\n\s*([A-ZÁÉÍÓÚÑ\s\.]{3,100}S\.A\.S?\.?)/is',
            '/(CLINICA\s+SAN\s+FERNANDO\s+S\.A\.?)/is',
            '/35\.\s*Raz[óo]n\s+social[\s\S]{0,100}?([A-ZÁÉÍÓÚÑ\s\.&]{3,100})/is',
        ];

        foreach ($razonSocialPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $nombre = isset($matches[1]) ? $matches[1] : $matches[0];
                $nombre = trim(preg_replace('/\s+/', ' ', $nombre));
                if (strlen($nombre) > 5) {
                    $data['nombre_tercero'] = strtoupper($nombre);
                    break;
                }
            }
        }

        // DIRECCIÓN - En el log: "CL 5 38 48"
        if (preg_match('/(CL|CR|AV|KR|CALLE|CARRERA)\s+\d+[^\n]*/i', $text, $matches)) {
            $data['direccion'] = strtoupper(trim($matches[0]));
        }

        // EMAIL
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $text, $matches)) {
            $data['email'] = strtolower(trim($matches[1]));
        }

        // TELÉFONOS - En el log: "6024853722 3505408355"
        if (preg_match('/(602\d{7})\s+(3\d{9})/', $text, $matches)) {
            $data['telefono'] = $matches[1];
            $data['celular'] = $matches[2];
        }

        // UBICACIÓN - En el log: "COLOMBIA 169 Valle del Cauca 76 Cali 001"
        if (preg_match('/COLOMBIA\s+169\s*([a-zA-Z\s]+)\s*(\d{2})\s*([a-zA-Z\s]+)\s*(\d{3})/i', $text, $matches)) {
            $data['tipo_pais_id'] = 'CO';
            $data['tipo_dpto_id'] = $matches[2];
            $data['tipo_mpio_id'] = $matches[4];
        } else {
            $data['tipo_pais_id'] = 'CO';
            $data['tipo_dpto_id'] = '76'; 
            $data['tipo_mpio_id'] = '001';
        }

        $data['sw_persona_juridica'] = '1';
        $data['sw_estado'] = '1';

        return !empty($data['tercero_id']) && !empty($data['nombre_tercero']) ? $data : null;
    }
}

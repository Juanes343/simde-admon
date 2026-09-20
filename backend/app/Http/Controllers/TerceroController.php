<?php

namespace App\Http\Controllers;

use App\Models\Tercero;
use App\Models\TipoDpto;
use App\Models\TipoMpio;
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
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(nombre_tercero) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(tercero_id) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
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

            // Resolver departamento/municipio a partir de los códigos DIAN extraídos del RUT
            $dptoCodigo = $data['_dpto_codigo_dian'] ?? null;
            $dptoNombre = $data['_dpto_nombre'] ?? null;
            $mpioCodigo = $data['_mpio_codigo_dian'] ?? null;
            $mpioNombre = $data['_mpio_nombre'] ?? null;
            unset($data['_dpto_codigo_dian'], $data['_dpto_nombre'], $data['_mpio_codigo_dian'], $data['_mpio_nombre']);

            $dpto = null;
            if ($dptoCodigo) {
                $dpto = TipoDpto::where('tipo_pais_id', 'CO')
                    ->where(function ($q) use ($dptoCodigo) {
                        $q->where('codigo_dpto_dian', $dptoCodigo)
                          ->orWhere('codigo_dpto_dian', ltrim($dptoCodigo, '0'));
                    })
                    ->first();
            }
            if (!$dpto && $dptoNombre) {
                $dpto = TipoDpto::where('tipo_pais_id', 'CO')
                    ->where('departamento', 'ILIKE', '%' . $dptoNombre . '%')
                    ->first();
            }
            if ($dpto) {
                $data['tipo_dpto_id'] = $dpto->tipo_dpto_id;
            }

            $mpio = null;
            if ($dpto && $mpioCodigo) {
                // El código DIAN de municipio puede estar guardado como el código corto (ej. "001")
                // o como el código completo departamento+municipio (ej. "11001")
                $dptoCodePadded = str_pad(ltrim($dptoCodigo, '0') ?: '0', 2, '0', STR_PAD_LEFT);
                $fullCode = $dptoCodePadded . $mpioCodigo;
                $mpio = TipoMpio::where('tipo_dpto_id', $dpto->tipo_dpto_id)
                    ->where(function ($q) use ($mpioCodigo, $fullCode) {
                        $q->where('codigo_mpio_dian', $mpioCodigo)
                          ->orWhere('codigo_mpio_dian', ltrim($mpioCodigo, '0'))
                          ->orWhere('codigo_mpio_dian', $fullCode)
                          ->orWhere('codigo_mpio_dian', ltrim($fullCode, '0'));
                    })
                    ->first();
            }
            if (!$mpio && $dpto && $mpioNombre) {
                $mpioNombreLimpio = preg_replace('/[.,]/', '', $mpioNombre);
                $mpio = TipoMpio::where('tipo_dpto_id', $dpto->tipo_dpto_id)
                    ->where(function ($q) use ($mpioNombre, $mpioNombreLimpio) {
                        $q->where('municipio', 'ILIKE', '%' . $mpioNombre . '%')
                          ->orWhereRaw("REPLACE(REPLACE(municipio, '.', ''), ',', '') ILIKE ?", ['%' . $mpioNombreLimpio . '%']);
                    })
                    ->first();
            }
            if (!$mpio && $dpto) {
                // Algunos departamentos (ej. distritos como Bogotá D.C.) solo tienen un municipio registrado
                $candidatos = TipoMpio::where('tipo_dpto_id', $dpto->tipo_dpto_id)->get();
                if ($candidatos->count() === 1) {
                    $mpio = $candidatos->first();
                }
            }
            if ($mpio) {
                $data['tipo_mpio_id'] = $mpio->tipo_mpio_id;
            }

            if (empty($data['tipo_dpto_id']) || empty($data['tipo_mpio_id'])) {
                return response()->json([
                    'message' => 'Se extrajo la información del RUT, pero no fue posible determinar automáticamente el departamento/municipio. Por favor complete o cree el tercero manualmente e ingrese la ubicación.',
                    'data' => $data,
                ], 422);
            }

            if (empty($data['direccion'])) {
                return response()->json([
                    'message' => 'Se extrajo la información del RUT, pero no fue posible determinar automáticamente la dirección. Por favor complete o cree el tercero manualmente e ingrese la dirección.',
                    'data' => $data,
                ], 422);
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
        $textNormalized = preg_replace('/\s+/', ' ', $text);

        // 1. BUSCAR NIT + DV
        $nitFound = false;

        // Estrategia 1: bloque de 9-10 dígitos contiguos (sin espacios), ej: "8300941761"
        // Se prueba primero porque es la forma en que el NIT real suele salir del PDF;
        // las numeraciones de casillas del formulario (ej. "1 2 3 4 5 6 7 8 9 10 ...")
        // siempre llevan espacio entre cada dígito y no producen un bloque contiguo,
        // así que esta estrategia evita falsos positivos con esos listados.
        if (preg_match_all('/\b(\d{9,10})\b/', $text, $matches)) {
            foreach ($matches[1] as $match) {
                if (!str_starts_with($match, '141') && !preg_match('/^20\d{2}/', $match)) {
                    if (strlen($match) === 10) {
                        $data['tercero_id']      = substr($match, 0, 9);
                        $data['dv']              = substr($match, 9, 1);
                    } else {
                        $data['tercero_id'] = $match;
                        $data['dv']         = null;
                    }
                    $data['tipo_id_tercero'] = 'NIT';
                    $nitFound = true;
                    break;
                }
            }
        }

        // Estrategia 2 (fallback): Detectar 10 dígitos que pueden tener espacios o guiones entre sí
        // Ejemplo: "9 0 1 0 9 2 1 3 1  9" o "901092131-9"
        if (!$nitFound && preg_match_all('/\b(?:\d\s*[\-–]?\s*){10}\b/', $textNormalized, $matches)) {
            foreach ($matches[0] as $match) {
                // Eliminar cualquier espacio o guión
                $cleaned = preg_replace('/[\s\-–]+/', '', $match);
                // Evitar números de formulario DIAN (empiezan por 141 o 1412)
                if (strlen($cleaned) === 10 && !str_starts_with($cleaned, '141') && !preg_match('/^20\d{2}/', $cleaned)) {
                    $data['tercero_id']      = substr($cleaned, 0, 9);
                    $data['dv']              = substr($cleaned, 9, 1);
                    $data['tipo_id_tercero'] = 'NIT';
                    $nitFound = true;
                    break;
                }
            }
        }

        // 2. BUSCAR RAZÓN SOCIAL (Persona Jurídica) - Con soporte para UTF-8 y tildes
        if (preg_match('/Persona\s+jur.*?(\d+)\s+(.*?)\s+COLOMBIA/iu', $textNormalized, $matches)) {
            $nombreBloque = trim($matches[2]);
            
            // Buscar si contiene un sufijo legal (S.A.S, S.A., LTDA, etc)
            if (preg_match('/([A-ZÁÉÍÓÚÑ0-9\s\.&]{3,100}?\s+(?:S\.A\.S\.?|S\.A\.?|LTDA\.?|INC\.?|S\.C\.S\.?|SUCURSAL))/iu', $nombreBloque, $nameMatches)) {
                $nombre = strtoupper(trim($nameMatches[0]));
            } else {
                 $nombre = strtoupper(trim($nombreBloque));
            }
            
            // Si el nombre se repite (ej: "EMPRESA S.A.S EMPRESA S.A.S"), tomamos la primera instancia
            $palabras = explode(' ', $nombre);
            if (count($palabras) >= 4) {
                $mitad = floor(count($palabras) / 2);
                $primeraMitad = implode(' ', array_slice($palabras, 0, $mitad));
                $segundaMitad = implode(' ', array_slice($palabras, $mitad));
                if (trim($primeraMitad) === trim($segundaMitad)) {
                    $nombre = trim($primeraMitad);
                }
            }

            $data['nombre_tercero'] = $nombre;
            $data['sw_persona_juridica'] = '1';
        } 
        
        // 3. BUSCAR NOMBRES (Si es natural y no se detectó jurídica)
        if (empty($data['nombre_tercero'])) {
            // Suelen aparecer 4 palabras en mayúscula seguidas antes de la dirección
            if (preg_match('/([A-ZÑ\s]{3,30})\s+([A-ZÑ\s]{3,30})\s+([A-ZÑ\s]{3,30})\s+([A-ZÑ\s]{3,30})/u', $text, $matches)) {
                // ... lógica para natural (opcional según requerimientos)
            }
        }

        // 4. DIRECCIÓN
        if (preg_match('/(CL|CR|AV|KR|AK|AC|CALLE|CARRERA|AVENIDA|Diagonal|Transversal|DG|TV|MZ|CQ)\s+\d+[^\n]*/iu', $text, $matches)) {
            $data['direccion'] = strtoupper(trim(preg_replace('/\s+/', ' ', $matches[0])));
        }

        // 5. EMAIL
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $text, $matches)) {
            $data['email'] = strtolower(trim($matches[1]));
        }

        // 6. TELÉFONOS/CELULAR
        if (preg_match_all('/\b(3\d{9}|[6][0-9]\d{7})\b/', $text, $matches)) {
            foreach ($matches[1] as $tel) {
                if (str_starts_with($tel, '3')) {
                    $data['celular'] = $tel;
                } else if (!isset($data['telefono'])) {
                    $data['telefono'] = $tel;
                }
            }
        }

        // 7. UBICACIÓN
        // El RUT trae el código de cada casilla pegado al nombre siguiente, ej:
        // "COLOMBIA 169Bogotá D.C. 11Bogotá, D.C. 001 AK 58 138 40 IN 5 601"
        // (a veces los dígitos de un mismo código vienen separados por espacios)
        if (preg_match('/COLOMBIA\s+(?:\d\s*){3}\s*([a-zA-ZáéíóúñÁÉÍÓÚÑ.,\s]+?)\s*((?:\d\s*){2})\s*([a-zA-ZáéíóúñÁÉÍÓÚÑ.,\s]+?)\s*((?:\d\s*){3,5})\b/iu', $textNormalized, $matches)) {
            $data['_dpto_nombre']       = trim(preg_replace('/\s+/', ' ', $matches[1]));
            $data['_dpto_codigo_dian']  = preg_replace('/\s+/', '', $matches[2]);
            $data['_mpio_nombre']       = trim(preg_replace('/\s+/', ' ', $matches[3]));
            $data['_mpio_codigo_dian']  = preg_replace('/\s+/', '', $matches[4]);
        }
        $data['tipo_pais_id'] = 'CO';

        $data['sw_estado'] = '1';

        // Validar campos obligatorios mínimos
        if (empty($data['tercero_id']) || empty($data['nombre_tercero'])) {
            return null;
        }

        return $data;
    }
}

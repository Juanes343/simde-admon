<?php

namespace App\Http\Controllers;

use App\Models\SystemUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string|max:25|unique:system_usuarios',
            'email' => 'required|string|email|max:255|unique:system_usuarios',
            'passwd' => 'required|string|min:8',
            'nombre' => 'required|string|max:60',
            'primer_nombre' => 'required|string|max:20',
            'primer_apellido' => 'required|string|max:20',
        ]);

        $usuario = SystemUsuario::create([
            'usuario' => $request->usuario,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion ?? '',
            'passwd' => Hash::make($request->passwd),
            'email' => $request->email,
            'primer_nombre' => $request->primer_nombre,
            'segundo_nombre' => $request->segundo_nombre,
            'primer_apellido' => $request->primer_apellido,
            'segundo_apellido' => $request->segundo_apellido,
            'telefono' => $request->telefono,
            'tel_celular' => $request->tel_celular,
            'sw_admin' => '0',
            'activo' => '1',
        ]);

        // Crear token con Sanctum
        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $usuario,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'passwd' => 'required|string',
        ]);

        $usuario = SystemUsuario::where('usuario', $request->usuario)->first();

        if (!$usuario || !Hash::check($request->passwd, $usuario->passwd)) {
            throw ValidationException::withMessages([
                'usuario' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if ($usuario->activo !== '1') {
            throw ValidationException::withMessages([
                'usuario' => ['El usuario está inactivo.'],
            ]);
        }

        // Revocar tokens anteriores
        $usuario->tokens()->delete();

        // Crear nuevo token con Sanctum
        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $usuario,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // Revocar el token actual con Sanctum
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}

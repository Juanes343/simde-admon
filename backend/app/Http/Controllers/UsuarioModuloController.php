<?php

namespace App\Http\Controllers;

use App\Models\SystemUsuario;
use App\Models\SystemUsuarioModulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UsuarioModuloController extends Controller
{
    /**
     * Catálogo fijo de módulos disponibles en el sistema.
     */
    public static function catalogoModulos(): array
    {
        return [
            ['id' => 'terceros',      'titulo' => 'Terceros',             'icono' => 'fa-users'],
            ['id' => 'servicios',     'titulo' => 'Servicios',            'icono' => 'fa-cogs'],
            ['id' => 'ordenes',       'titulo' => 'Órdenes de Servicio',  'icono' => 'fa-file-contract'],
            ['id' => 'facturacion',   'titulo' => 'Facturación',          'icono' => 'fa-file-invoice-dollar'],
            ['id' => 'notas',         'titulo' => 'Notas Crédito/Débito', 'icono' => 'fa-file-signature'],
            ['id' => 'usuarios',      'titulo' => 'Gestión de Usuarios',  'icono' => 'fa-user-shield'],
            ['id' => 'causacion',     'titulo' => 'Causación',            'icono' => 'fa-money-bill-wave'],
            ['id' => 'cotizaciones',  'titulo' => 'Cotizaciones',         'icono' => 'fa-file-alt'],
        ];
    }

    /**
     * GET /api/modulos
     * Devuelve el catálogo completo de módulos.
     */
    public function catalogos()
    {
        return response()->json([
            'success' => true,
            'data'    => self::catalogoModulos(),
        ]);
    }

    /**
     * GET /api/usuarios/{id}/modulos
     * Devuelve los módulos asignados a un usuario.
     */
    public function index(int $usuarioId)
    {
        try {
            $usuario = SystemUsuario::findOrFail($usuarioId);
            $asignados = SystemUsuarioModulo::where('usuario_id', $usuarioId)
                ->pluck('modulo_id')
                ->toArray();

            return response()->json([
                'success'   => true,
                'usuario'   => $usuario->only(['usuario_id', 'usuario', 'nombre', 'sw_admin']),
                'modulos'   => $asignados,
                'catalogo'  => self::catalogoModulos(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo módulos de usuario: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/usuarios/{id}/modulos
     * Reemplaza completamente los módulos asignados al usuario.
     * Body: { "modulos": ["terceros", "facturacion", ...] }
     */
    public function update(Request $request, int $usuarioId)
    {
        try {
            $request->validate([
                'modulos'   => 'required|array',
                'modulos.*' => 'string|in:terceros,servicios,ordenes,facturacion,notas,usuarios,causacion,cotizaciones',
            ]);

            SystemUsuario::findOrFail($usuarioId);

            // Eliminar asignaciones anteriores y recrear
            SystemUsuarioModulo::where('usuario_id', $usuarioId)->delete();

            foreach ($request->modulos as $moduloId) {
                SystemUsuarioModulo::create([
                    'usuario_id' => $usuarioId,
                    'modulo_id'  => $moduloId,
                    'activo'     => true,
                ]);
            }

            Log::info("Módulos actualizados para usuario {$usuarioId}: " . implode(', ', $request->modulos));

            return response()->json([
                'success' => true,
                'message' => 'Módulos actualizados correctamente',
                'modulos' => $request->modulos,
            ]);
        } catch (\Exception $e) {
            Log::error('Error actualizando módulos: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/usuarios/{id}/activo
     * Alterna el campo activo ('1' ↔ '0') de un usuario.
     */
    public function toggleActivo(Request $request, int $usuarioId)
    {
        try {
            $solicitante = $request->user();
            if (!($solicitante->sw_admin === '1' || $solicitante->sw_admin === 1)) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }
            if ($solicitante->usuario_id === $usuarioId) {
                return response()->json(['success' => false, 'message' => 'No puedes inactivarte a ti mismo'], 422);
            }

            $usuario = SystemUsuario::findOrFail($usuarioId);
            $usuario->activo = ($usuario->activo === '1' || $usuario->activo === 1) ? '0' : '1';
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'activo'  => $usuario->activo,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en toggleActivo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/usuarios/{id}/admin
     * Alterna el campo sw_admin ('1' ↔ '0') de un usuario.
     */
    public function toggleAdmin(Request $request, int $usuarioId)
    {
        try {
            $solicitante = $request->user();
            if (!($solicitante->sw_admin === '1' || $solicitante->sw_admin === 1)) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }
            if ($solicitante->usuario_id === $usuarioId && ($solicitante->sw_admin === '1' || $solicitante->sw_admin === 1)) {
                return response()->json(['success' => false, 'message' => 'No puedes quitarte el rol de administrador a ti mismo'], 422);
            }

            $usuario = SystemUsuario::findOrFail($usuarioId);
            $usuario->sw_admin = ($usuario->sw_admin === '1' || $usuario->sw_admin === 1) ? '0' : '1';
            $usuario->save();

            return response()->json([
                'success'  => true,
                'message'  => 'Rol Admin actualizado correctamente',
                'sw_admin' => $usuario->sw_admin,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en toggleAdmin: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/usuarios
     * Lista todos los usuarios con sus módulos asignados.
     */
    public function listarUsuarios()
    {
        try {
            $usuarios = SystemUsuario::select('usuario_id', 'usuario', 'nombre', 'sw_admin', 'activo')
                ->with(['modulos' => function ($q) {
                    $q->select('usuario_id', 'modulo_id');
                }])
                ->orderBy('nombre')
                ->get()
                ->map(function ($u) {
                    return [
                        'usuario_id' => $u->usuario_id,
                        'usuario'    => $u->usuario,
                        'nombre'     => $u->nombre,
                        'sw_admin'   => $u->sw_admin,
                        'activo'     => $u->activo,
                        'modulos'    => $u->modulos->pluck('modulo_id')->toArray(),
                    ];
                });

            return response()->json(['success' => true, 'data' => $usuarios]);
        } catch (\Exception $e) {
            Log::error('Error listando usuarios: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

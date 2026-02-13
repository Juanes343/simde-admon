<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoIdTercero;

class TipoIdTerceroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $tiposId = TipoIdTercero::orderBy('indice_de_orden')
                ->orderBy('descripcion')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tiposId
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de identificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

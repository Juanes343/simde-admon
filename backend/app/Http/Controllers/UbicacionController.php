<?php

namespace App\Http\Controllers;

use App\Models\TipoPais;
use App\Models\TipoDpto;
use App\Models\TipoMpio;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    public function getPaises()
    {
        return response()->json(TipoPais::all());
    }

    public function getDepartamentos($paisId)
    {
        return response()->json(TipoDpto::where('tipo_pais_id', $paisId)->get());
    }

    public function getMunicipios($paisId, $dptoId)
    {
        return response()->json(TipoMpio::where('tipo_pais_id', $paisId)
            ->where('tipo_dpto_id', $dptoId)
            ->get());
    }
}

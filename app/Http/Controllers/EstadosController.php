<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estados;
use App\Models\ObjResponse;

class EstadosController extends Controller
{
    public function index(Response $response)
    {
        try {
            $res = ObjResponse::default();
            $list = Estados::all();
            $response->data = ObjResponse::CorrectResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de estados.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $response->data = ObjResponse::error($ex->getMessage());
        }
        return response()->json($response, $response->data["status_code"]);
    }

    public function estadosFind(Response $response, $id)
    {
        try {
            $res = ObjResponse::default();
            $list = Estados::where('value', $id)->first();
            // $response->data = ObjResponse::CorrectResponse();
            $response->data["message"] = 'Peticion satisfactoria | Estado encontrado.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $response->data = ObjResponse::error($ex->getMessage());
        }
        return response()->json($response);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegistroController extends Controller
{
    public function store(Request $request)
    {
        $payload = [
            'Carnet' => $request->input('Carnet', $request->input('carnet')),
            'MisionID' => $request->input('MisionID', $request->input('mision_id', $request->input('id'))),
            'Estado' => $request->input('Estado', $request->input('estado', true)),
        ];

        $data = Validator::make($payload, [
            'Carnet' => ['required', 'string', 'max:25', Rule::exists('Estudiantes', 'Carnet')],
            'MisionID' => ['required', 'integer', Rule::exists('Misiones', 'MisionID')],
            'Estado' => ['sometimes', 'boolean'],
        ])->validate();

        DB::table('EstudianteMisiones')->updateOrInsert(
            [
                'Carnet' => $data['Carnet'],
                'MisionID' => $data['MisionID'],
            ],
            [
                'Estado' => (bool) $data['Estado'],
            ]
        );

        $registro = DB::table('EstudianteMisiones')
            ->where('Carnet', $data['Carnet'])
            ->where('MisionID', $data['MisionID'])
            ->first();

        return response()->json($registro, 201);
    }
}

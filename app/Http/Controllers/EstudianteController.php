<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EstudianteController extends Controller
{
    public function index()
    {
        return response()->json(
            Estudiante::query()->orderBy('Carnet')->get()
        );
    }

    public function show(string $carnet)
    {
        $estudiante = Estudiante::find($carnet);

        if (! $estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado.'], 404);
        }

        return response()->json($estudiante);
    }

    public function store(Request $request)
    {
        $data = Validator::make($this->payload($request), [
            'Carnet' => ['required', 'string', 'max:25', Rule::unique('Estudiantes', 'Carnet')],
            'Nombre' => ['required', 'string', 'max:150'],
            'Correo' => ['required', 'email', 'max:150'],
        ])->validate();

        $estudiante = Estudiante::create($data);

        return response()->json($estudiante, 201);
    }

    public function update(Request $request, string $carnet)
    {
        $estudiante = Estudiante::find($carnet);

        if (! $estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado.'], 404);
        }

        $data = Validator::make($this->payload($request, false), [
            'Nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'Correo' => ['sometimes', 'required', 'email', 'max:150'],
        ])->validate();

        if ($data === []) {
            return response()->json(['message' => 'Envie Nombre, Correo o ambos para modificar el estudiante.'], 422);
        }

        $estudiante->fill($data)->save();

        return response()->json($estudiante->fresh());
    }

    public function destroy(string $carnet)
    {
        $estudiante = Estudiante::find($carnet);

        if (! $estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado.'], 404);
        }

        DB::transaction(function () use ($estudiante, $carnet) {
            DB::table('EstudianteMisiones')->where('Carnet', $carnet)->delete();
            $estudiante->delete();
        });

        return response()->noContent();
    }

    private function payload(Request $request, bool $includeCarnet = true): array
    {
        $data = [];

        if ($includeCarnet) {
            $data['Carnet'] = $request->input('Carnet', $request->input('carnet'));
        }

        if ($request->hasAny(['Nombre', 'nombre'])) {
            $data['Nombre'] = $request->input('Nombre', $request->input('nombre'));
        }

        if ($request->hasAny(['Correo', 'correo'])) {
            $data['Correo'] = $request->input('Correo', $request->input('correo'));
        }

        return $data;
    }
}

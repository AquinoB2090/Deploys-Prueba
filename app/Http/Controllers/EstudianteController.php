<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Mision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EstudianteController extends Controller
{
    public function index()
    {
        $misiones = Mision::query()
            ->orderBy('MisionID')
            ->get();

        $detalles = DB::table('EstudianteMisiones')
            ->get()
            ->groupBy('Carnet')
            ->map(fn ($items) => $items->keyBy('MisionID'));

        $estudiantes = Estudiante::query()
            ->orderBy('Carnet')
            ->get()
            ->map(function (Estudiante $estudiante) use ($misiones, $detalles) {
                $detallesEstudiante = $detalles->get($estudiante->Carnet, collect());

                return [
                    'Carnet' => $estudiante->Carnet,
                    'Nombre' => $estudiante->Nombre,
                    'Correo' => $estudiante->Correo,
                    'misiones' => $misiones->map(function (Mision $mision) use ($detallesEstudiante) {
                        $detalle = $detallesEstudiante->get($mision->MisionID);

                        return [
                            'MisionID' => $mision->MisionID,
                            'misionId' => $mision->MisionID,
                            'Nombre' => $mision->Nombre,
                            'Descripcion' => $mision->Descripcion,
                            'Estado' => (bool) ($detalle->Estado ?? false),
                            'estado' => (bool) ($detalle->Estado ?? false),
                        ];
                    })->values(),
                ];
            });

        return response()->json($estudiantes->values());
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

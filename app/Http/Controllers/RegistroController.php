<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegistroController extends Controller
{
    public function store(Request $request)
    {
        $payload = [
            'Carnet' => $request->input('Carnet', $request->input('carnet')),
            'Nombre' => $request->input('Nombre', $request->input('nombre')),
            'Correo' => $request->input('Correo', $request->input('correo')),
            'misiones' => $this->misionesPayload($request),
        ];

        $data = Validator::make($payload, [
            'Carnet' => ['required', 'string', 'max:25'],
            'Nombre' => ['required', 'string', 'max:150'],
            'Correo' => ['required', 'email', 'max:150'],
            'misiones' => ['required', 'array', 'min:1'],
        ])->validate();

        $misiones = collect($data['misiones'])
            ->map(fn ($mision, $index) => $this->normalizeMision($mision, $index))
            ->values();

        $misionIds = $misiones->pluck('MisionID')->unique()->values();
        $existingMisionIds = DB::table('Misiones')
            ->whereIn('MisionID', $misionIds)
            ->pluck('MisionID')
            ->map(fn ($id) => (int) $id);

        $missingMisionIds = $misionIds->diff($existingMisionIds)->values();

        if ($missingMisionIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'misiones' => [
                    'Referencia invalida. No existen las misiones: '.$missingMisionIds->implode(', '),
                ],
            ]);
        }

        DB::transaction(function () use ($data, $misiones) {
            Estudiante::updateOrCreate(
                ['Carnet' => $data['Carnet']],
                [
                    'Nombre' => $data['Nombre'],
                    'Correo' => $data['Correo'],
                ]
            );

            $misiones->each(function (array $mision) use ($data) {
                DB::table('EstudianteMisiones')->updateOrInsert(
                    [
                        'Carnet' => $data['Carnet'],
                        'MisionID' => $mision['MisionID'],
                    ],
                    [
                        'Estado' => $mision['Estado'],
                    ]
                );
            });
        });

        $detalles = DB::table('EstudianteMisiones')
            ->where('Carnet', $data['Carnet'])
            ->orderBy('MisionID')
            ->get();

        return response()->json([
            'Carnet' => $data['Carnet'],
            'Nombre' => $data['Nombre'],
            'Correo' => $data['Correo'],
            'misiones' => $detalles,
        ], 201);
    }

    private function normalizeMision(mixed $mision, int $index): array
    {
        if (! is_array($mision)) {
            throw ValidationException::withMessages([
                "misiones.$index" => ['Cada mision debe ser un objeto JSON.'],
            ]);
        }

        $misionId = $mision['misionId']
            ?? $mision['MisionID']
            ?? $mision['mision_id']
            ?? $mision['id']
            ?? null;

        $validator = Validator::make([
            'MisionID' => $misionId,
            'Estado' => $mision['estado'] ?? $mision['Estado'] ?? false,
        ], [
            'MisionID' => ['required', 'integer'],
            'Estado' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                "misiones.$index" => $validator->errors()->all(),
            ]);
        }

        $data = $validator->validated();

        return [
            'MisionID' => (int) $data['MisionID'],
            'Estado' => (bool) ($data['Estado'] ?? false),
        ];
    }

    private function misionesPayload(Request $request): mixed
    {
        $misiones = $request->input('misiones', $request->input('Misiones'));

        if ($misiones !== null) {
            return $misiones;
        }

        $misionId = $request->input('misionId', $request->input('MisionID', $request->input('mision_id', $request->input('id'))));

        if ($misionId === null) {
            return null;
        }

        return [[
            'misionId' => $misionId,
            'estado' => $request->input('estado', $request->input('Estado', false)),
        ]];
    }
}

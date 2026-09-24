<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Mision;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
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

                $detalleMisiones = $misiones->map(function (Mision $mision) use ($detallesEstudiante) {
                    $detalle = $detallesEstudiante->get($mision->MisionID);

                    return [
                        'MisionID' => $mision->MisionID,
                        'Nombre' => $mision->Nombre,
                        'Estado' => (bool) ($detalle->Estado ?? false),
                    ];
                });

                $total = $detalleMisiones->count();
                $completadas = $detalleMisiones->where('Estado', true)->count();

                return [
                    'Carnet' => $estudiante->Carnet,
                    'Nombre' => $estudiante->Nombre,
                    'Correo' => $estudiante->Correo,
                    'progreso' => [
                        'completadas' => $completadas,
                        'total' => $total,
                        'porcentaje' => $total === 0 ? 0 : round(($completadas / $total) * 100, 2),
                    ],
                    'misiones' => $detalleMisiones->values(),
                ];
            });

        return response()->json([
            'estudiantes' => $estudiantes->values(),
            'misiones' => $misiones->values(),
            'totales' => [
                'estudiantes' => $estudiantes->count(),
                'misiones' => $misiones->count(),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Mision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MisionController extends Controller
{
    public function index()
    {
        return response()->json(
            Mision::query()->orderBy('MisionID')->get()
        );
    }

    public function store(Request $request)
    {
        $data = Validator::make($this->payload($request), [
            'MisionID' => ['required', 'integer', Rule::unique('Misiones', 'MisionID')],
            'Nombre' => ['required', 'string', 'max:100'],
        ])->validate();

        $mision = Mision::create($data);

        return response()->json($mision, 201);
    }

    public function update(Request $request, int $id)
    {
        $mision = Mision::find($id);

        if (! $mision) {
            return response()->json(['message' => 'Mision no encontrada.'], 404);
        }

        $data = Validator::make($this->payload($request, false), [
            'Nombre' => ['sometimes', 'required', 'string', 'max:100'],
        ])->validate();

        if ($data === []) {
            return response()->json(['message' => 'Envie Nombre para modificar la mision.'], 422);
        }

        $mision->fill($data)->save();

        return response()->json($mision->fresh());
    }

    public function destroy(int $id)
    {
        $mision = Mision::find($id);

        if (! $mision) {
            return response()->json(['message' => 'Mision no encontrada.'], 404);
        }

        DB::transaction(function () use ($mision, $id) {
            DB::table('EstudianteMisiones')->where('MisionID', $id)->delete();
            $mision->delete();
        });

        return response()->noContent();
    }

    private function payload(Request $request, bool $includeId = true): array
    {
        $data = [];

        if ($includeId) {
            $data['MisionID'] = $request->input('MisionID', $request->input('mision_id', $request->input('id')));
        }

        if ($request->hasAny(['Nombre', 'nombre'])) {
            $data['Nombre'] = $request->input('Nombre', $request->input('nombre'));
        }

        return $data;
    }
}

<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Direccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DireccionController extends Controller {

    public function index(Request $request): Response {
        $direcciones = Direccion::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('predeterminada')
            ->latest('id')
            ->get()
            ->map(function (Direccion $direccion) {
                return [
                    'id' => $direccion->id,
                    'alias' => $direccion->alias,
                    'nombre_receptor' => $direccion->nombre_receptor,
                    'telefono' => $direccion->telefono,
                    'calle' => $direccion->calle,
                    'numero_exterior' => $direccion->numero_exterior,
                    'numero_interior' => $direccion->numero_interior,
                    'colonia' => $direccion->colonia,
                    'municipio' => $direccion->municipio,
                    'estado' => $direccion->estado,
                    'pais' => $direccion->pais,
                    'codigo_postal' => $direccion->codigo_postal,
                    'referencias' => $direccion->referencias,
                    'predeterminada' => (bool) $direccion->predeterminada,
                    'created_at' => $direccion->created_at?->toDateTimeString(),
                ];
            })
            ->values();
        return Inertia::render('Cliente/Direcciones/Index', [
            'direcciones' => $direcciones,
            'endpoints' => [
                'store' => route('cliente.direcciones.store'),
                'updateBase' => url('/mi-cuenta/direcciones'),
                'destroyBase' => url('/mi-cuenta/direcciones'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse {
        $data = $this->validateData($request);
        if ($request->boolean('predeterminada')) {
            Direccion::query()
                ->where('user_id', $request->user()->id)
                ->update(['predeterminada' => false]);
        }
        Direccion::create([
            ...$data,
            'user_id' => $request->user()->id,
            'predeterminada' => $request->boolean('predeterminada'),
        ]);
        return back()->with('success', 'Dirección guardada correctamente.');
    }

    public function update(Request $request, Direccion $direccion): RedirectResponse
    {
        abort_unless((int) $direccion->user_id === (int) $request->user()->id, 404);
        $data = $this->validateData($request);
        if ($request->boolean('predeterminada')) {
            Direccion::query()
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $direccion->id)
                ->update(['predeterminada' => false]);
        }
        $direccion->update([
            ...$data,
            'predeterminada' => $request->boolean('predeterminada'),
        ]);
        return back()->with('success', 'Dirección actualizada correctamente.');
    }

    public function destroy(Request $request, Direccion $direccion): RedirectResponse
    {
        abort_unless((int) $direccion->user_id === (int) $request->user()->id, 404);
        $direccion->delete();
        return back()->with('success', 'Dirección eliminada correctamente.');
    }

    private function validateData(Request $request): array {
        return $request->validate([
            'alias' => ['required', 'string', 'max:100'],
            'nombre_receptor' => ['required', 'string', 'max:180'],
            'telefono' => ['required', 'string', 'max:30'],
            'calle' => ['required', 'string', 'max:180'],
            'numero_exterior' => ['required', 'string', 'max:50'],
            'numero_interior' => ['nullable', 'string', 'max:50'],
            'colonia' => ['required', 'string', 'max:150'],
            'municipio' => ['required', 'string', 'max:150'],
            'estado' => ['required', 'string', 'max:150'],
            'pais' => ['required', 'string', 'max:150'],
            'codigo_postal' => ['required', 'string', 'max:20'],
            'referencias' => ['nullable', 'string', 'max:500'],
            'predeterminada' => ['nullable', 'boolean'],
        ]);
    }

}

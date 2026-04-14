<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController extends Controller {

    public function index(Request $request): Response {
        $search = trim((string) $request->string('search'));
        $configuraciones = Configuracion::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('clave', 'like', "%{$search}%")
                        ->orWhere('valor', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->through(function (Configuracion $configuracion) {
                return [
                    'id' => $configuracion->id,
                    'clave' => $configuracion->clave,
                    'valor' => $configuracion->valor,
                    'created_at' => $configuracion->created_at?->toDateTimeString(),
                    'updated_at' => $configuracion->updated_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();
        return Inertia::render('Admin/Configuraciones/Index', [
            'configuraciones' => $configuraciones,
            'filters' => [
                'search' => $search,
            ],
            'endpoints' => [
                'index' => route('admin.configuraciones.index'),
                'store' => route('admin.configuraciones.store'),
                'updateBase' => url('/admin/configuraciones'),
                'destroyBase' => url('/admin/configuraciones'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse {
        $data = $request->validate([
            'clave' => ['required', 'string', 'max:120', 'unique:configuraciones,clave'],
            'valor' => ['nullable', 'string'],
        ]);
        Configuracion::create([
            'clave' => trim($data['clave']),
            'valor' => isset($data['valor']) ? trim((string) $data['valor']) : null,
        ]);
        return back()->with('success', 'Configuración creada.');
    }

    public function update(Request $request, Configuracion $configuracion): RedirectResponse
    {
        $data = $request->validate([
            'clave' => [
                'required',
                'string',
                'max:120',
                Rule::unique('configuraciones', 'clave')->ignore($configuracion->id),
            ],
            'valor' => ['nullable', 'string'],
        ]);
        $configuracion->update([
            'clave' => trim($data['clave']),
            'valor' => isset($data['valor']) ? trim((string) $data['valor']) : null,
        ]);
        return back()->with('success', 'Configuración actualizada.');
    }

    public function destroy(Configuracion $configuracion): RedirectResponse
    {
        $configuracion->delete();
        return back()->with('success', 'Configuración eliminada.');
    }

}

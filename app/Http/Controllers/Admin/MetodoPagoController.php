<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetodoPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MetodoPagoController extends Controller {

    public function index(Request $request): Response {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->input('status', 'all');

        $metodos = MetodoPago::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('clave', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('activo', true))
            ->when($status === 'inactive', fn ($query) => $query->where('activo', false))
            ->latest('id')
            ->paginate(12)
            ->through(function (MetodoPago $metodo) {
                return [
                    'id' => $metodo->id,
                    'nombre' => $metodo->nombre,
                    'clave' => $metodo->clave,
                    'activo' => (bool) $metodo->activo,
                    'configuracion' => $metodo->configuracion ?? [],
                    'created_at' => $metodo->created_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();
        return Inertia::render('Admin/MetodosPago/Index', [
            'metodos' => $metodos,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'endpoints' => [
                'index' => route('admin.metodos-pago.index'),
                'store' => route('admin.metodos-pago.store'),
                'updateBase' => url('/admin/metodos-pago'),
                'destroyBase' => url('/admin/metodos-pago'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'clave' => ['required', 'string', 'max:50', 'unique:metodos_pagos,clave'],
            'activo' => ['nullable', 'boolean'],
            'configuracion' => ['nullable', 'array'],
        ]);
        $data['nombre'] = trim($data['nombre']);
        $data['clave'] = Str::slug(trim($data['clave']), '_');
        $data['activo'] = $request->boolean('activo', true);
        $data['configuracion'] = $data['configuracion'] ?? null;
        MetodoPago::create($data);
        return back()->with('success', 'Método de pago creado.');
    }

    public function show(MetodoPago $metodoPago): RedirectResponse {
        return redirect()
            ->route('admin.metodos-pago.index')
            ->with('success', 'Detalle de método: ' . $metodoPago->nombre);
    }

    public function update(Request $request, MetodoPago $metodoPago): RedirectResponse {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'clave' => [
                'required',
                'string',
                'max:50',
                Rule::unique('metodos_pagos', 'clave')->ignore($metodoPago->id),
            ],
            'activo' => ['nullable', 'boolean'],
            'configuracion' => ['nullable', 'array'],
        ]);
        $data['nombre'] = trim($data['nombre']);
        $data['clave'] = Str::slug(trim($data['clave']), '_');
        $data['activo'] = $request->boolean('activo', true);
        $data['configuracion'] = $data['configuracion'] ?? null;
        $metodoPago->update($data);
        return back()->with('success', 'Método de pago actualizado.');
    }

    public function destroy(MetodoPago $metodoPago): RedirectResponse {
        $metodoPago->delete();
        return back()->with('success', 'Método de pago eliminado.');
    }

}

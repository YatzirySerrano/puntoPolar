<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CuponController extends Controller {

    public function index(Request $request): Response {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->input('status', 'all');
        $tipo = (string) $request->input('tipo', 'all');

        $cupones = Cupon::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('codigo', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%")
                        ->orWhere('tipo', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('activo', true))
            ->when($status === 'inactive', fn ($query) => $query->where('activo', false))
            ->when($tipo !== 'all', fn ($query) => $query->where('tipo', $tipo))
            ->latest('id')
            ->paginate(12)
            ->through(function (Cupon $cupon) {
                return [
                    'id' => $cupon->id,
                    'codigo' => $cupon->codigo,
                    'nombre' => $cupon->nombre,
                    'tipo' => $cupon->tipo,
                    'valor' => is_numeric($cupon->valor) ? (float) $cupon->valor : $cupon->valor,
                    'activo' => (bool) $cupon->activo,
                    'created_at' => $cupon->created_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();
        $tipos = Cupon::query()
            ->select('tipo')
            ->distinct()
            ->orderBy('tipo')
            ->pluck('tipo')
            ->values();
        return Inertia::render('Admin/Cupones/Index', [
            'cupones' => $cupones,
            'tipos' => $tipos,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'tipo' => $tipo,
            ],
            'endpoints' => [
                'index' => route('admin.cupones.index'),
                'store' => route('admin.cupones.store'),
                'updateBase' => url('/admin/cupones'),
                'destroyBase' => url('/admin/cupones'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:60', 'unique:cupones,codigo'],
            'nombre' => ['required', 'string', 'max:160'],
            'tipo' => ['required', 'string', 'max:30'],
            'valor' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['codigo'] = mb_strtoupper(trim($data['codigo']));
        $data['activo'] = $request->boolean('activo', true);
        Cupon::create($data);
        return back()->with('success', 'Cupón creado.');
    }

    public function show(Cupon $cupon): RedirectResponse {
        return redirect()
            ->route('admin.cupones.index')
            ->with('success', 'Detalle de cupón: ' . $cupon->codigo);
    }

    public function update(Request $request, Cupon $cupon): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:60',
                Rule::unique('cupones', 'codigo')->ignore($cupon->id),
            ],
            'nombre' => ['required', 'string', 'max:160'],
            'tipo' => ['required', 'string', 'max:30'],
            'valor' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['codigo'] = mb_strtoupper(trim($data['codigo']));
        $data['activo'] = $request->boolean('activo', true);
        $cupon->update($data);
        return back()->with('success', 'Cupón actualizado.');
    }

    public function destroy(Cupon $cupon): RedirectResponse {
        $cupon->delete();
        return back()->with('success', 'Cupón eliminado.');
    }

}

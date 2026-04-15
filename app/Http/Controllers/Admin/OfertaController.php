<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Oferta;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OfertaController extends Controller {

    public function index(Request $request): Response {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->input('status', 'all');
        $tipo = (string) $request->input('tipo', 'all');
        $aplicaA = (string) $request->input('aplica_a', 'all');
        $ofertas = Oferta::query()
            ->with([
                'categoria:id,nombre',
                'marca:id,nombre',
                'productos:id,nombre',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('tipo', 'like', "%{$search}%")
                        ->orWhere('aplica_a', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('activa', true))
            ->when($status === 'inactive', fn ($query) => $query->where('activa', false))
            ->when($tipo !== 'all', fn ($query) => $query->where('tipo', $tipo))
            ->when($aplicaA !== 'all', fn ($query) => $query->where('aplica_a', $aplicaA))
            ->latest('id')
            ->paginate(12)
            ->through(function (Oferta $oferta) {
                return [
                    'id' => $oferta->id,
                    'nombre' => $oferta->nombre,
                    'tipo' => $oferta->tipo,
                    'valor' => (float) $oferta->valor,
                    'aplica_a' => $oferta->aplica_a,
                    'categoria_id' => $oferta->categoria_id,
                    'categoria_nombre' => $oferta->categoria?->nombre,
                    'marca_id' => $oferta->marca_id,
                    'marca_nombre' => $oferta->marca?->nombre,
                    'productos_ids' => $oferta->productos->pluck('id')->map(fn ($id) => (int) $id)->values(),
                    'productos_nombres' => $oferta->productos->pluck('nombre')->values(),
                    'inicia_en' => $oferta->inicia_en?->format('Y-m-d\TH:i'),
                    'termina_en' => $oferta->termina_en?->format('Y-m-d\TH:i'),
                    'activa' => (bool) $oferta->activa,
                    'created_at' => $oferta->created_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();
        $tipos = Oferta::query()
            ->select('tipo')
            ->distinct()
            ->orderBy('tipo')
            ->pluck('tipo')
            ->values();
        return Inertia::render('Admin/Ofertas/Index', [
            'ofertas' => $ofertas,
            'tipos' => $tipos,
            'aplicaOptions' => [
                ['value' => 'productos', 'label' => 'Productos seleccionados'],
                ['value' => 'categoria', 'label' => 'Categoría'],
                ['value' => 'marca', 'label' => 'Marca'],
            ],
            'categorias' => Categoria::query()
                ->where('activa', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'marcas' => Marca::query()
                ->where('activa', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'productos' => Producto::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'tipo' => $tipo,
                'aplica_a' => $aplicaA,
            ],
            'endpoints' => [
                'index' => route('admin.ofertas.index'),
                'store' => route('admin.ofertas.store'),
                'updateBase' => url('/admin/ofertas'),
                'destroyBase' => url('/admin/ofertas'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => ['required', 'string', 'max:30'],
            'valor' => ['required', 'numeric', 'min:0'],
            'aplica_a' => ['required', Rule::in(['productos', 'categoria', 'marca'])],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'productos_ids' => ['nullable', 'array'],
            'productos_ids.*' => ['integer', 'exists:productos,id'],
            'inicia_en' => ['nullable', 'date'],
            'termina_en' => ['nullable', 'date', 'after_or_equal:inicia_en'],
            'activa' => ['nullable', 'boolean'],
        ]);
        $this->validateTargetRules($request, $data);
        $oferta = Oferta::create([
            'nombre' => $data['nombre'],
            'tipo' => $data['tipo'],
            'valor' => $data['valor'],
            'aplica_a' => $data['aplica_a'],
            'categoria_id' => $data['aplica_a'] === 'categoria' ? ($data['categoria_id'] ?? null) : null,
            'marca_id' => $data['aplica_a'] === 'marca' ? ($data['marca_id'] ?? null) : null,
            'inicia_en' => $data['inicia_en'] ?? null,
            'termina_en' => $data['termina_en'] ?? null,
            'activa' => $request->boolean('activa', true),
        ]);
        $oferta->productos()->sync(
            $data['aplica_a'] === 'productos' ? ($data['productos_ids'] ?? []) : []
        );
        return back()->with('success', 'Oferta creada.');
    }

    public function show(Oferta $oferta): RedirectResponse
    {
        return redirect()
            ->route('admin.ofertas.index')
            ->with('success', 'Detalle de oferta: ' . $oferta->nombre);
    }

    public function update(Request $request, Oferta $oferta): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => ['required', 'string', 'max:30'],
            'valor' => ['required', 'numeric', 'min:0'],
            'aplica_a' => ['required', Rule::in(['productos', 'categoria', 'marca'])],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'productos_ids' => ['nullable', 'array'],
            'productos_ids.*' => ['integer', 'exists:productos,id'],
            'inicia_en' => ['nullable', 'date'],
            'termina_en' => ['nullable', 'date', 'after_or_equal:inicia_en'],
            'activa' => ['nullable', 'boolean'],
        ]);
        $this->validateTargetRules($request, $data);
        $oferta->update([
            'nombre' => $data['nombre'],
            'tipo' => $data['tipo'],
            'valor' => $data['valor'],
            'aplica_a' => $data['aplica_a'],
            'categoria_id' => $data['aplica_a'] === 'categoria' ? ($data['categoria_id'] ?? null) : null,
            'marca_id' => $data['aplica_a'] === 'marca' ? ($data['marca_id'] ?? null) : null,
            'inicia_en' => $data['inicia_en'] ?? null,
            'termina_en' => $data['termina_en'] ?? null,
            'activa' => $request->boolean('activa', true),
        ]);
        $oferta->productos()->sync(
            $data['aplica_a'] === 'productos' ? ($data['productos_ids'] ?? []) : []
        );
        return back()->with('success', 'Oferta actualizada.');
    }

    public function destroy(Oferta $oferta): RedirectResponse
    {
        $oferta->delete();
        return back()->with('success', 'Oferta eliminada.');
    }

    private function validateTargetRules(Request $request, array $data): void
    {
        if ($data['aplica_a'] === 'productos' && empty($data['productos_ids'])) {
            $request->validate([
                'productos_ids' => ['required', 'array', 'min:1'],
            ], [
                'productos_ids.required' => 'Debes seleccionar al menos un producto.',
                'productos_ids.min' => 'Debes seleccionar al menos un producto.',
            ]);
        }
        if ($data['aplica_a'] === 'categoria' && empty($data['categoria_id'])) {
            $request->validate([
                'categoria_id' => ['required', 'exists:categorias,id'],
            ], [
                'categoria_id.required' => 'Debes seleccionar una categoría.',
            ]);
        }
        if ($data['aplica_a'] === 'marca' && empty($data['marca_id'])) {
            $request->validate([
                'marca_id' => ['required', 'exists:marcas,id'],
            ], [
                'marca_id.required' => 'Debes seleccionar una marca.',
            ]);
        }
    }

}

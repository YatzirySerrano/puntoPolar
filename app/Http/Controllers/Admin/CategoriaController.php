<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->input('status', 'all');

        $categorias = Categoria::query()
            ->withCount('productos')
            ->with('categoriaPadre:id,nombre')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('activa', true))
            ->when($status === 'inactive', fn ($query) => $query->where('activa', false))
            ->latest('id')
            ->paginate(12)
            ->through(function (Categoria $categoria) {
                return [
                    'id' => $categoria->id,
                    'categoria_padre_id' => $categoria->categoria_padre_id,
                    'categoria_padre_nombre' => $categoria->categoriaPadre?->nombre,
                    'nombre' => $categoria->nombre,
                    'slug' => $categoria->slug,
                    'descripcion' => $categoria->descripcion,
                    'imagen' => $categoria->imagen ? Storage::url($categoria->imagen) : null,
                    'activa' => (bool) $categoria->activa,
                    'orden' => (int) $categoria->orden,
                    'productos_count' => (int) ($categoria->productos_count ?? 0),
                    'created_at' => $categoria->created_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();

        $categoriasPadre = Categoria::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (Categoria $categoria) => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
            ]);

        return Inertia::render('Admin/Categorias/Index', [
            'categorias' => $categorias,
            'categoriasPadre' => $categoriasPadre,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'endpoints' => [
                'index' => route('admin.categorias.index'),
                'store' => route('admin.categorias.store'),
                'updateBase' => url('/admin/categorias'),
                'destroyBase' => url('/admin/categorias'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'categoria_padre_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'unique:categorias,slug'],
            'descripcion' => ['nullable', 'string'],
            'imagen_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'activa' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'integer', 'min:0'],
        ]);

        $categoria = new Categoria();
        $categoria->categoria_padre_id = $data['categoria_padre_id'] ?? null;
        $categoria->nombre = $data['nombre'];
        $categoria->slug = $this->resolveSlug($data['slug'] ?? null, $data['nombre']);
        $categoria->descripcion = $data['descripcion'] ?? null;
        $categoria->activa = $request->boolean('activa', true);
        $categoria->orden = $data['orden'] ?? 0;

        if ($request->hasFile('imagen_file')) {
            $categoria->imagen = $request->file('imagen_file')->store('categorias', 'public');
        }

        $categoria->save();

        return back()->with('success', 'Categoría creada.');
    }

    public function show(Categoria $categoria): RedirectResponse
    {
        return redirect()
            ->route('admin.categorias.index')
            ->with('success', 'Detalle de categoría: ' . $categoria->nombre);
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $data = $request->validate([
            'categoria_padre_id' => [
                'nullable',
                'integer',
                'exists:categorias,id',
                Rule::notIn([$categoria->id]),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'slug' => [
                'nullable',
                'string',
                'max:170',
                Rule::unique('categorias', 'slug')->ignore($categoria->id),
            ],
            'descripcion' => ['nullable', 'string'],
            'imagen_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'remove_image' => ['nullable', 'boolean'],
            'activa' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'integer', 'min:0'],
        ]);

        $removeImage = $request->boolean('remove_image');
        $hasNewImage = $request->hasFile('imagen_file');

        $categoria->categoria_padre_id = $data['categoria_padre_id'] ?? null;
        $categoria->nombre = $data['nombre'];
        $categoria->slug = $this->resolveSlug(
            $data['slug'] ?? null,
            $data['nombre'],
            $categoria->id
        );
        $categoria->descripcion = $data['descripcion'] ?? null;
        $categoria->activa = $request->boolean('activa', true);
        $categoria->orden = $data['orden'] ?? 0;

        if ($removeImage && $categoria->imagen) {
            if (Storage::disk('public')->exists($categoria->imagen)) {
                Storage::disk('public')->delete($categoria->imagen);
            }
            $categoria->imagen = null;
        }

        if ($hasNewImage) {
            if ($categoria->imagen && Storage::disk('public')->exists($categoria->imagen)) {
                Storage::disk('public')->delete($categoria->imagen);
            }

            $categoria->imagen = $request->file('imagen_file')->store('categorias', 'public');
        }

        $categoria->save();

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        if ($categoria->imagen && Storage::disk('public')->exists($categoria->imagen)) {
            Storage::disk('public')->delete($categoria->imagen);
        }

        $categoria->delete();

        return back()->with('success', 'Categoría eliminada.');
    }

    private function resolveSlug(?string $slug, string $nombre, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $nombre);

        if ($base === '') {
            $base = 'categoria';
        }

        $candidate = $base;
        $counter = 2;

        while (
            Categoria::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }
}

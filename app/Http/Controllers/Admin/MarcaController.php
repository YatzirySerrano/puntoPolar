<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MarcaController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->input('status', 'all');

        $marcas = Marca::query()
            ->withCount('productos')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('activa', true))
            ->when($status === 'inactive', fn ($query) => $query->where('activa', false))
            ->latest('id')
            ->paginate(12)
            ->through(function (Marca $marca) {
                return [
                    'id' => $marca->id,
                    'nombre' => $marca->nombre,
                    'slug' => $marca->slug,
                    'logo' => $marca->logo ? Storage::url($marca->logo) : null,
                    'activa' => (bool) $marca->activa,
                    'productos_count' => (int) $marca->productos_count,
                    'created_at' => $marca->created_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();

        return Inertia::render('Admin/Marcas/Index', [
            'marcas' => $marcas,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'endpoints' => [
                'index' => route('admin.marcas.index'),
                'store' => route('admin.marcas.store'),
                'updateBase' => url('/admin/marcas'),
                'destroyBase' => url('/admin/marcas'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120', 'unique:marcas,nombre'],
            'slug' => ['nullable', 'string', 'max:140', 'unique:marcas,slug'],
            'logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'activa' => ['nullable', 'boolean'],
        ]);

        $marca = new Marca();
        $marca->nombre = $data['nombre'];
        $marca->slug = $this->resolveSlug($data['slug'] ?? null, $data['nombre']);
        $marca->activa = $request->boolean('activa', true);

        if ($request->hasFile('logo_file')) {
            $marca->logo = $request->file('logo_file')->store('marcas', 'public');
        }

        $marca->save();

        return back()->with('success', 'Marca creada correctamente.');
    }

    public function update(Request $request, Marca $marca): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('marcas', 'nombre')->ignore($marca->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:140',
                Rule::unique('marcas', 'slug')->ignore($marca->id),
            ],
            'logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'remove_logo' => ['nullable', 'boolean'],
            'activa' => ['nullable', 'boolean'],
        ]);

        $removeLogo = $request->boolean('remove_logo');
        $hasNewLogo = $request->hasFile('logo_file');

        $marca->nombre = $data['nombre'];
        $marca->slug = $this->resolveSlug(
            $data['slug'] ?? null,
            $data['nombre'],
            $marca->id
        );
        $marca->activa = $request->boolean('activa', true);

        if ($removeLogo && $marca->logo) {
            if (Storage::disk('public')->exists($marca->logo)) {
                Storage::disk('public')->delete($marca->logo);
            }
            $marca->logo = null;
        }

        if ($hasNewLogo) {
            if ($marca->logo && Storage::disk('public')->exists($marca->logo)) {
                Storage::disk('public')->delete($marca->logo);
            }

            $marca->logo = $request->file('logo_file')->store('marcas', 'public');
        }

        $marca->save();

        return back()->with('success', 'Marca actualizada correctamente.');
    }

    public function destroy(Marca $marca): RedirectResponse
    {
        if ($marca->productos()->exists()) {
            throw ValidationException::withMessages([
                'general' => 'No puedes eliminar una marca que ya está asignada a productos.',
            ]);
        }

        if ($marca->logo && Storage::disk('public')->exists($marca->logo)) {
            Storage::disk('public')->delete($marca->logo);
        }

        $marca->delete();

        return back()->with('success', 'Marca eliminada correctamente.');
    }

    private function resolveSlug(?string $slug, string $nombre, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $nombre);

        if ($base === '') {
            $base = 'marca';
        }

        $candidate = $base;
        $counter = 2;

        while (
            Marca::query()
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

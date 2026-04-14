<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(): Response
    {
        $banners = Banner::query()
            ->select('id', 'titulo', 'descripcion', 'imagen', 'activo', 'orden', 'created_at')
            ->orderBy('orden')
            ->orderBy('id')
            ->paginate(50)
            ->through(function (Banner $banner) {
                return [
                    'id' => $banner->id,
                    'titulo' => $banner->titulo,
                    'descripcion' => $banner->descripcion,
                    'imagen' => $banner->imagen ? Storage::url($banner->imagen) : null,
                    'activo' => (bool) $banner->activo,
                    'orden' => (int) $banner->orden,
                    'created_at' => $banner->created_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();

        return Inertia::render('Admin/Banners/Index', [
            'banners' => $banners,
            'endpoints' => [
                'index' => route('admin.banners.index'),
                'store' => route('admin.banners.store'),
                'reorder' => route('admin.banners.reorder'),
                'updateBase' => url('/admin/banners'),
                'destroyBase' => url('/admin/banners'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'imagen_file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $banner = new Banner();
        $banner->titulo = $data['titulo'];
        $banner->descripcion = $data['descripcion'] ?? null;
        $banner->activo = $request->boolean('activo', true);
        $banner->url = null;
        $banner->orden = ((int) Banner::max('orden')) + 1;
        $banner->imagen = $request->file('imagen_file')->store('banners', 'public');
        $banner->save();

        return back()->with('success', 'Banner creado correctamente.');
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'imagen_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'remove_image' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $removeImage = $request->boolean('remove_image');
        $hasNewImage = $request->hasFile('imagen_file');

        if ($removeImage && !$hasNewImage) {
            throw ValidationException::withMessages([
                'imagen_file' => 'Debes seleccionar una imagen para el banner.',
            ]);
        }

        $banner->titulo = $data['titulo'];
        $banner->descripcion = $data['descripcion'] ?? null;
        $banner->activo = $request->boolean('activo', true);
        $banner->url = null;

        if ($removeImage) {
            if ($banner->imagen && Storage::disk('public')->exists($banner->imagen)) {
                Storage::disk('public')->delete($banner->imagen);
            }
            $banner->imagen = null;
        }

        if ($hasNewImage) {
            if ($banner->imagen && Storage::disk('public')->exists($banner->imagen)) {
                Storage::disk('public')->delete($banner->imagen);
            }

            $banner->imagen = $request->file('imagen_file')->store('banners', 'public');
        }

        $banner->save();

        return back()->with('success', 'Banner actualizado correctamente.');
    }

    public function destroy(Banner $banner): RedirectResponse {
        if ($banner->imagen && Storage::disk('public')->exists($banner->imagen)) {
            Storage::disk('public')->delete($banner->imagen);
        }
        $banner->delete();
        $ordered = Banner::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id']);
        foreach ($ordered as $index => $item) {
            Banner::where('id', $item->id)->update([
                'orden' => $index + 1,
            ]);
        }
        return back()->with('success', 'Banner eliminado correctamente.');
    }

    public function reorder(Request $request): RedirectResponse {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:banners,id'],
        ]);
        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $index => $item) {
                Banner::where('id', $item['id'])->update([
                    'orden' => $index + 1,
                ]);
            }
        });
        return back()->with('success', 'Orden de banners actualizado correctamente.');
    }

}

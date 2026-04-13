<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Banners/Index', [
            'banners' => Banner::query()->latest()->paginate(12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'imagen' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
            'orden' => ['nullable', 'integer'],
            'activo' => ['boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);
        Banner::create($data);

        return back()->with('success', 'Banner creado.');
    }

    public function show(Banner $banner): RedirectResponse
    {
        return redirect()->route('admin.banners.index')->with('success', 'Detalle de banner: '.$banner->titulo);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'imagen' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
            'orden' => ['nullable', 'integer'],
            'activo' => ['boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);
        $banner->update($data);

        return back()->with('success', 'Banner actualizado.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return back()->with('success', 'Banner eliminado.');
    }
}

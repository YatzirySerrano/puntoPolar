<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminCreatedUserMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $rol = (string) $request->input('rol', 'all');
        $estado = (string) $request->input('estado', 'all');

        $usuarios = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('rol', 'like', "%{$search}%");
                });
            })
            ->when($rol !== 'all', fn ($query) => $query->where('rol', $rol))
            ->when($estado === 'verified', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($estado === 'unverified', fn ($query) => $query->whereNull('email_verified_at'))
            ->latest('id')
            ->paginate(12)
            ->through(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'rol' => $user->rol,
                    'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                    'created_at' => $user->created_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $usuarios,
            'roles' => [
                User::ROL_CLIENTE,
                User::ROL_VENDEDOR,
                User::ROL_ADMIN,
            ],
            'filters' => [
                'search' => $search,
                'rol' => $rol,
                'estado' => $estado,
            ],
            'endpoints' => [
                'index' => route('admin.usuarios.index'),
                'store' => route('admin.usuarios.store'),
                'updateBase' => url('/admin/usuarios'),
                'destroyBase' => url('/admin/usuarios'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'rol' => [
                'required',
                Rule::in([
                    User::ROL_CLIENTE,
                    User::ROL_VENDEDOR,
                    User::ROL_ADMIN,
                ]),
            ],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $temporaryPassword = Str::password(12, letters: true, numbers: true, symbols: false, spaces: false);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => Str::lower(trim($data['email'])),
            'rol' => $data['rol'],
            'password' => $temporaryPassword,
        ]);

        $resetToken = Password::broker()->createToken($user);
        $resetUrl = route('password.reset', [
            'token' => $resetToken,
            'email' => $user->email,
        ]);

        if ($request->boolean('send_email', true)) {
            Mail::to($user->email)->send(new AdminCreatedUserMail(
                userName: $user->name,
                email: $user->email,
                temporaryPassword: $temporaryPassword,
                resetUrl: $resetUrl,
                role: $user->rol,
            ));
        }

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $user): RedirectResponse
    {
        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Detalle de usuario: ' . $user->name);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'rol' => [
                'required',
                Rule::in([
                    User::ROL_CLIENTE,
                    User::ROL_VENDEDOR,
                    User::ROL_ADMIN,
                ]),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $payload = [
            'name' => trim($data['name']),
            'email' => Str::lower(trim($data['email'])),
            'rol' => $data['rol'],
        ];

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }
}

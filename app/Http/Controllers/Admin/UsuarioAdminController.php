<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

/** Gestión de usuarios internos del CORE (crear, editar, activar/desactivar). */
class UsuarioAdminController extends Controller
{
    public function index()
    {
        return View::make('admin.usuarios.index', [
            'usuarios' => Usuario::with('rol')->orderBy('nombre')->paginate(20),
        ]);
    }

    public function crear()
    {
        return View::make('admin.usuarios.form', [
            'usuario' => new Usuario,
            'roles' => Rol::orderBy('nombre')->get(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'password' => ['required', 'string', 'min:10'],
            'rol_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $datos['activo'] = true;
        Usuario::create($datos);

        return redirect('/admin/usuarios')->with('exito', 'Usuario creado correctamente.');
    }

    public function editar(Usuario $usuario)
    {
        return View::make('admin.usuarios.form', [
            'usuario' => $usuario,
            'roles' => Rol::orderBy('nombre')->get(),
        ]);
    }

    public function actualizar(Request $request, Usuario $usuario): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('usuarios', 'email')->ignore($usuario->id)],
            'password' => ['nullable', 'string', 'min:10'],
            'rol_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect('/admin/usuarios')->with('exito', 'Usuario actualizado correctamente.');
    }

    /** Activa o desactiva la cuenta. Nadie puede desactivarse a sí mismo. */
    public function alternarActivo(Request $request, Usuario $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No puedes desactivar tu propia cuenta.']);
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        return back()->with('exito', $usuario->activo
            ? "Cuenta de {$usuario->nombre} activada."
            : "Cuenta de {$usuario->nombre} desactivada.");
    }
}

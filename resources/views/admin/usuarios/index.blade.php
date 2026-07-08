@extends('admin.layout')

@section('titulo', 'Usuarios')

@section('contenido')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 style="margin: 0;">Usuarios</h1>
        <a class="btn" href="/admin/usuarios/crear">Nuevo usuario</a>
    </div>

    <div class="tarjeta">
        <table>
            <thead>
                <tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($usuarios as $usuario)
                    <tr>
                        <td>{{ $usuario->nombre }}</td>
                        <td>{{ $usuario->email }}</td>
                        <td>{{ $usuario->rol?->nombre ?? '—' }}</td>
                        <td>
                            <span class="insignia {{ $usuario->activo ? 'ok' : 'mal' }}">
                                {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td>
                            <div class="acciones">
                                <a class="btn chico secundario" href="/admin/usuarios/{{ $usuario->id }}/editar">Editar</a>
                                @unless ($usuario->is(auth()->user()))
                                    <form method="POST" action="/admin/usuarios/{{ $usuario->id }}/alternar-activo">
                                        @csrf
                                        <button type="submit" class="btn chico {{ $usuario->activo ? 'peligro' : 'secundario' }}">
                                            {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $usuarios->links('admin.paginacion') }}</div>
@endsection

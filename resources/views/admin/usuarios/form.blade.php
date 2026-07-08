@extends('admin.layout')

@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('contenido')
    <h1>{{ $usuario->exists ? "Editar: {$usuario->nombre}" : 'Nuevo usuario' }}</h1>

    <div class="tarjeta" style="max-width: 640px;">
        <form method="POST" action="{{ $usuario->exists ? "/admin/usuarios/{$usuario->id}" : '/admin/usuarios' }}">
            @csrf
            @if ($usuario->exists)
                @method('PUT')
            @endif

            <div class="fila-campos">
                <div class="campo">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $usuario->nombre) }}" required>
                </div>
                <div class="campo">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $usuario->email) }}" required>
                </div>
            </div>

            <div class="fila-campos">
                <div class="campo">
                    <label for="password">
                        {{ $usuario->exists ? 'Nueva contraseña (dejar en blanco para no cambiarla)' : 'Contraseña (mínimo 10 caracteres)' }}
                    </label>
                    <input type="password" id="password" name="password" autocomplete="new-password" @unless ($usuario->exists) required @endunless>
                </div>
                <div class="campo">
                    <label for="rol_id">Rol</label>
                    <select id="rol_id" name="rol_id" required>
                        <option value="" disabled {{ old('rol_id', $usuario->rol_id) ? '' : 'selected' }}>Selecciona un rol…</option>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->id }}" @selected(old('rol_id', $usuario->rol_id) == $rol->id)>
                                {{ $rol->nombre }} ({{ $rol->codigo }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="acciones" style="margin-top: 1.5rem;">
                <button type="submit" class="btn">{{ $usuario->exists ? 'Guardar cambios' : 'Crear usuario' }}</button>
                <a class="btn secundario" href="/admin/usuarios">Cancelar</a>
            </div>
        </form>
    </div>
@endsection

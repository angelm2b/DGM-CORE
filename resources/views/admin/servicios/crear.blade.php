@extends('admin.layout')

@section('titulo', 'Nuevo servicio')

@section('contenido')
    <h1>Nuevo servicio</h1>

    <div class="tarjeta" style="max-width: 640px;">
        <form method="POST" action="/admin/servicios">
            @csrf

            <div class="fila-campos">
                <div class="campo">
                    <label for="codigo">Código (p. ej. SRV-013)</label>
                    <input type="text" id="codigo" name="codigo" maxlength="20" value="{{ old('codigo') }}" required>
                </div>
                <div class="campo">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                </div>
            </div>

            <div class="campo">
                <label for="categoria_migratoria_id">Categoría migratoria (opcional)</label>
                <select id="categoria_migratoria_id" name="categoria_migratoria_id">
                    <option value="">Sin categoría</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected(old('categoria_migratoria_id') == $categoria->id)>
                            {{ $categoria->codigo }} — {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="fila-campos">
                <div class="campo">
                    <label for="canal">Canal</label>
                    <select id="canal" name="canal" required>
                        @foreach ($canales as $canal)
                            <option value="{{ $canal }}" @selected(old('canal', 'AMBOS') === $canal)>{{ $canal }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="campo">
                    <label for="dias_sla">SLA (días)</label>
                    <input type="number" id="dias_sla" name="dias_sla" min="0" value="{{ old('dias_sla', 0) }}" required>
                </div>
            </div>

            <div class="campo">
                <label>
                    <input type="checkbox" name="requiere_cita" value="1" @checked(old('requiere_cita'))>
                    Requiere cita
                </label>
            </div>

            <div class="acciones" style="margin-top: 1.5rem;">
                <button type="submit" class="btn">Crear servicio</button>
                <a class="btn secundario" href="/admin/servicios">Cancelar</a>
            </div>
        </form>
    </div>
@endsection

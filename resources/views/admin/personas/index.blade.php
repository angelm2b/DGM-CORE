@extends('admin.layout')

@section('titulo', 'Personas')

@section('contenido')
    <h1>Personas</h1>

    <form method="GET" action="/admin/personas" class="filtros">
        <div class="campo">
            <label for="estatus">Estatus migratorio</label>
            <select id="estatus" name="estatus" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach (['REGULAR', 'IRREGULAR', 'EN_TRAMITE'] as $estatus)
                    <option value="{{ $estatus }}" @selected($estatusFiltro === $estatus)>{{ $estatus }}</option>
                @endforeach
            </select>
        </div>
        <div class="campo" style="min-width: 260px;">
            <label for="buscar">Documento, nombres o apellidos</label>
            <input type="text" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Buscar…">
        </div>
        <button type="submit" class="btn">Filtrar</button>
    </form>

    <div class="tarjeta">
        <table>
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre</th>
                    <th>Nacionalidad</th>
                    <th>Categoría</th>
                    <th>Estatus</th>
                    <th>Solicitudes</th>
                    <th>Movimientos</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($personas as $persona)
                    <tr>
                        <td><code>{{ $persona->tipo_documento }} {{ $persona->numero_documento }}</code></td>
                        <td>{{ $persona->nombres }} {{ $persona->apellidos }}</td>
                        <td>{{ $persona->nacionalidad }}</td>
                        <td>{{ $persona->categoriaMigratoria?->nombre ?? '—' }}</td>
                        <td><span class="insignia {{ $persona->estatus_migratorio === 'REGULAR' ? 'info' : '' }}">{{ $persona->estatus_migratorio }}</span></td>
                        <td>{{ $persona->solicitudes_count }}</td>
                        <td>{{ $persona->movimientos_count }}</td>
                        <td><a href="/admin/personas/{{ $persona->id }}">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="color: var(--muted);">No hay personas para el filtro seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $personas->links('admin.paginacion') }}</div>
@endsection

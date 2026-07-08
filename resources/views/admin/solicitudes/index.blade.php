@extends('admin.layout')

@section('titulo', 'Solicitudes')

@section('contenido')
    <h1>Solicitudes</h1>

    <form method="GET" action="/admin/solicitudes" class="filtros">
        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach ($estados as $estado)
                    <option value="{{ $estado }}" @selected($estadoFiltro === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
        </div>
        <div class="campo" style="min-width: 260px;">
            <label for="buscar">Expediente, documento o apellidos</label>
            <input type="text" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Buscar…">
        </div>
        <button type="submit" class="btn">Filtrar</button>
    </form>

    <div class="tarjeta">
        <table>
            <thead>
                <tr>
                    <th>Expediente</th>
                    <th>Servicio</th>
                    <th>Persona</th>
                    <th>Canal</th>
                    <th>Estado</th>
                    <th>Última acción</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($solicitudes as $solicitud)
                    <tr>
                        <td><code>{{ $solicitud->expediente?->numero_expediente }}</code></td>
                        <td>{{ $solicitud->servicio?->codigo }}</td>
                        <td>{{ $solicitud->persona?->nombres }} {{ $solicitud->persona?->apellidos }}</td>
                        <td>{{ $solicitud->canal_origen }}</td>
                        <td><span class="insignia info">{{ $solicitud->estado_actual->getValue() }}</span></td>
                        <td>{{ $solicitud->fecha_ultima_accion?->format('d/m/Y H:i') }}</td>
                        <td><a href="/admin/solicitudes/{{ $solicitud->id }}">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="color: var(--muted);">No hay solicitudes para el filtro seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $solicitudes->links('admin.paginacion') }}</div>
@endsection

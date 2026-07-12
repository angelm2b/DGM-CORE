@extends('admin.layout')

@section('titulo', 'Movimientos migratorios')

@section('contenido')
    <h1>Movimientos migratorios</h1>

    <form method="GET" action="/admin/movimientos" class="filtros">
        <div class="campo">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="E" @selected($tipoFiltro === 'E')>Entrada</option>
                <option value="S" @selected($tipoFiltro === 'S')>Salida</option>
            </select>
        </div>
        <div class="campo">
            <label for="punto">Punto de control</label>
            <select id="punto" name="punto" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach ($puntosControl as $punto)
                    <option value="{{ $punto->id }}" @selected($puntoFiltro === (string) $punto->id)>{{ $punto->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="campo" style="min-width: 260px;">
            <label for="buscar">Documento o apellidos</label>
            <input type="text" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Buscar…">
        </div>
        <button type="submit" class="btn">Filtrar</button>
    </form>

    <div class="tarjeta">
        <table>
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Tipo</th>
                    <th>Persona</th>
                    <th>Documento</th>
                    <th>Punto de control</th>
                    <th>Medio</th>
                    <th>Días aut.</th>
                    <th>Oficial</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movimientos as $movimiento)
                    <tr>
                        <td>{{ $movimiento->fecha_hora?->format('d/m/Y H:i') }}</td>
                        <td>
                            @if ($movimiento->tipo === 'E')
                                <span class="insignia info">Entrada</span>
                            @else
                                <span class="insignia">Salida</span>
                            @endif
                        </td>
                        <td>{{ $movimiento->persona?->nombres }} {{ $movimiento->persona?->apellidos }}</td>
                        <td><code>{{ $movimiento->persona?->numero_documento }}</code></td>
                        <td>{{ $movimiento->puntoControl?->nombre }}</td>
                        <td>{{ $movimiento->medio ?? '—' }}</td>
                        <td>{{ $movimiento->dias_autorizados ?? '—' }}</td>
                        <td>{{ $movimiento->oficial?->nombre ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="color: var(--muted);">No hay movimientos para el filtro seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $movimientos->links('admin.paginacion') }}</div>
@endsection

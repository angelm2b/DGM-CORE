@extends('admin.layout')

@section('titulo', 'Tarifas')

@section('contenido')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 style="margin: 0;">Tarifas</h1>
        <a class="btn" href="/admin/tarifas/crear">Nueva tarifa</a>
    </div>

    <form method="GET" action="/admin/tarifas" class="filtros">
        <div class="campo">
            <label for="servicio">Servicio</label>
            <select id="servicio" name="servicio" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach ($servicios as $servicio)
                    <option value="{{ $servicio->id }}" @selected($servicioFiltro === $servicio->id)>
                        {{ $servicio->codigo }} — {{ $servicio->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="tarjeta">
        <table>
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Concepto</th>
                    <th>Monto</th>
                    <th>Vigente desde</th>
                    <th>Vigente hasta</th>
                    <th>Resolución</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tarifas as $tarifa)
                    <tr>
                        <td><code>{{ $tarifa->servicio?->codigo }}</code></td>
                        <td>{{ $tarifa->concepto }}</td>
                        <td>{{ number_format((float) $tarifa->monto, 2) }} {{ $tarifa->moneda }}</td>
                        <td>{{ $tarifa->vigente_desde?->format('d/m/Y') }}</td>
                        <td>{{ $tarifa->vigente_hasta?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $tarifa->resolucion ?? '—' }}</td>
                        <td><a href="/admin/tarifas/{{ $tarifa->id }}/editar">Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="color: var(--muted);">No hay tarifas para el filtro seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $tarifas->links('admin.paginacion') }}</div>
@endsection

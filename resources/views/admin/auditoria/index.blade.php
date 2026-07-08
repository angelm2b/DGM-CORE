@extends('admin.layout')

@section('titulo', 'Auditoría')

@section('contenido')
    <h1>Registro de auditoría</h1>

    <form method="GET" action="/admin/auditoria" class="filtros">
        <div class="campo">
            <label for="evento">Evento</label>
            <select id="evento" name="evento" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach (['created' => 'Creación', 'updated' => 'Actualización', 'deleted' => 'Eliminación'] as $valor => $nombre)
                    <option value="{{ $valor }}" @selected($eventoFiltro === $valor)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="campo">
            <label for="modelo">Modelo</label>
            <input type="text" id="modelo" name="modelo" value="{{ $modeloFiltro }}" placeholder="Persona, Solicitud…">
        </div>
        <button type="submit" class="btn">Filtrar</button>
    </form>

    <div class="tarjeta">
        <table>
            <thead>
                <tr><th>Fecha</th><th>Evento</th><th>Modelo</th><th>Registro</th><th>Usuario</th><th>Cambios</th></tr>
            </thead>
            <tbody>
                @forelse ($auditorias as $auditoria)
                    <tr>
                        <td>{{ $auditoria->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td>
                            <span class="insignia {{ ['created' => 'ok', 'deleted' => 'mal'][$auditoria->event] ?? 'info' }}">
                                {{ $auditoria->event }}
                            </span>
                        </td>
                        <td>{{ class_basename($auditoria->auditable_type) }}</td>
                        <td><code>{{ $auditoria->auditable_id }}</code></td>
                        <td>{{ $auditoria->user?->nombre ?? 'Sistema' }}</td>
                        <td style="max-width: 380px; overflow-wrap: anywhere; font-size: 0.8rem; color: var(--muted);">
                            {{ json_encode($auditoria->new_values, JSON_UNESCAPED_UNICODE) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color: var(--muted);">Sin registros de auditoría para el filtro seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $auditorias->links('admin.paginacion') }}</div>
@endsection

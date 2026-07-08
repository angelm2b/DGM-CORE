@extends('admin.layout')

@section('titulo', 'Panel')

@section('contenido')
    <h1>Panel de administración</h1>

    <div class="cuadricula">
        <div class="tarjeta metrica">
            <div class="valor">{{ number_format($totales['personas']) }}</div>
            <div class="nombre">Personas</div>
        </div>
        <div class="tarjeta metrica">
            <div class="valor">{{ number_format($totales['solicitudes']) }}</div>
            <div class="nombre">Solicitudes</div>
        </div>
        <div class="tarjeta metrica">
            <div class="valor">{{ number_format($totales['pagos']) }}</div>
            <div class="nombre">Pagos registrados</div>
        </div>
        <div class="tarjeta metrica">
            <div class="valor">{{ number_format($totales['documentos']) }}</div>
            <div class="nombre">Documentos emitidos</div>
        </div>
        <div class="tarjeta metrica">
            <div class="valor">{{ number_format($totales['movimientos']) }}</div>
            <div class="nombre">Movimientos migratorios</div>
        </div>
        <div class="tarjeta metrica">
            <div class="valor">{{ number_format($totales['usuarios_activos']) }}</div>
            <div class="nombre">Usuarios activos</div>
        </div>
    </div>

    <h2>Solicitudes por estado</h2>
    <div class="tarjeta">
        @if ($solicitudesPorEstado->isEmpty())
            <p style="color: var(--muted);">Aún no hay solicitudes registradas.</p>
        @else
            <table>
                <thead>
                    <tr><th>Estado</th><th>Total</th></tr>
                </thead>
                <tbody>
                    @foreach ($solicitudesPorEstado as $estado => $total)
                        <tr>
                            <td><span class="insignia info">{{ $estado }}</span></td>
                            <td>{{ number_format($total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <h2>Últimas solicitudes</h2>
    <div class="tarjeta">
        @if ($ultimasSolicitudes->isEmpty())
            <p style="color: var(--muted);">Sin actividad reciente.</p>
        @else
            <table>
                <thead>
                    <tr><th>Servicio</th><th>Persona</th><th>Estado</th><th>Última acción</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($ultimasSolicitudes as $solicitud)
                        <tr>
                            <td>{{ $solicitud->servicio?->codigo }}</td>
                            <td>{{ $solicitud->persona?->nombres }} {{ $solicitud->persona?->apellidos }}</td>
                            <td><span class="insignia info">{{ $solicitud->estado_actual->getValue() }}</span></td>
                            <td>{{ $solicitud->fecha_ultima_accion?->format('d/m/Y H:i') }}</td>
                            <td><a href="/admin/solicitudes/{{ $solicitud->id }}">Ver</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

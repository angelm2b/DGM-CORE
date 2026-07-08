@extends('admin.layout')

@section('titulo', 'Detalle de solicitud')

@section('contenido')
    <h1>Solicitud <code>{{ $solicitud->id }}</code></h1>

    <div class="tarjeta">
        <dl class="detalle">
            <dt>Estado actual</dt>
            <dd><span class="insignia info">{{ $solicitud->estado_actual->getValue() }}</span></dd>
            <dt>Expediente</dt>
            <dd><code>{{ $solicitud->expediente?->numero_expediente }}</code></dd>
            <dt>Servicio</dt>
            <dd>{{ $solicitud->servicio?->codigo }} — {{ $solicitud->servicio?->nombre }}</dd>
            <dt>Persona</dt>
            <dd>
                {{ $solicitud->persona?->nombres }} {{ $solicitud->persona?->apellidos }}
                ({{ $solicitud->persona?->tipo_documento }} {{ $solicitud->persona?->numero_documento }})
            </dd>
            <dt>Oficina</dt>
            <dd>{{ $solicitud->oficina?->nombre ?? '—' }}</dd>
            <dt>Canal de origen</dt>
            <dd>{{ $solicitud->canal_origen }}</dd>
            <dt>Fecha de creación</dt>
            <dd>{{ $solicitud->fecha_creacion?->format('d/m/Y H:i') }}</dd>
            <dt>Última acción</dt>
            <dd>{{ $solicitud->fecha_ultima_accion?->format('d/m/Y H:i') }}</dd>
            <dt>Cita</dt>
            <dd>{{ $solicitud->fecha_cita?->format('d/m/Y H:i') ?? '—' }}</dd>
            <dt>Observaciones</dt>
            <dd>{{ $solicitud->observaciones ?? '—' }}</dd>
        </dl>
    </div>

    <h2>Historial de estados</h2>
    <div class="tarjeta">
        <table>
            <thead>
                <tr><th>Fecha</th><th>De</th><th>A</th><th>Usuario</th><th>Origen</th><th>Motivo</th></tr>
            </thead>
            <tbody>
                @forelse ($solicitud->estados as $cambio)
                    <tr>
                        <td>{{ $cambio->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $cambio->estado_anterior ?? '—' }}</td>
                        <td><span class="insignia info">{{ $cambio->estado_nuevo }}</span></td>
                        <td>{{ $cambio->usuario?->nombre ?? '—' }}</td>
                        <td>{{ $cambio->sistema_origen ?? '—' }}</td>
                        <td>{{ $cambio->motivo ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color: var(--muted);">Sin transiciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2>Órdenes de pago</h2>
    <div class="tarjeta">
        <table>
            <thead>
                <tr><th>Monto</th><th>Estado</th><th>Emisión</th><th>Vencimiento</th></tr>
            </thead>
            <tbody>
                @forelse ($solicitud->ordenesPago as $orden)
                    <tr>
                        <td>{{ number_format((float) $orden->monto_total, 2) }} {{ $orden->moneda }}</td>
                        <td><span class="insignia">{{ $orden->estado }}</span></td>
                        <td>{{ $orden->fecha_emision?->format('d/m/Y H:i') }}</td>
                        <td>{{ $orden->fecha_vencimiento?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="color: var(--muted);">Sin órdenes de pago.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2>Documentos emitidos</h2>
    <div class="tarjeta">
        <table>
            <thead>
                <tr><th>Tipo</th><th>Número de serie</th><th>Emisión</th><th>Vencimiento</th><th>Estado</th></tr>
            </thead>
            <tbody>
                @forelse ($solicitud->documentosEmitidos as $documento)
                    <tr>
                        <td>{{ $documento->tipo }}</td>
                        <td><code>{{ $documento->numero_serie }}</code></td>
                        <td>{{ $documento->fecha_emision?->format('d/m/Y') }}</td>
                        <td>{{ $documento->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                        <td><span class="insignia">{{ $documento->estado }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color: var(--muted);">Sin documentos emitidos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p style="margin-top: 1.5rem;"><a href="/admin/solicitudes">← Volver a solicitudes</a></p>
@endsection

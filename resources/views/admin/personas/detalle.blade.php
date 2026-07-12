@extends('admin.layout')

@section('titulo', 'Detalle de persona')

@section('contenido')
    <h1>{{ $persona->nombres }} {{ $persona->apellidos }}</h1>

    <div class="tarjeta">
        <dl class="detalle">
            <dt>Documento</dt>
            <dd><code>{{ $persona->tipo_documento }} {{ $persona->numero_documento }}</code></dd>
            <dt>Nacionalidad</dt>
            <dd>{{ $persona->nacionalidad }}</dd>
            <dt>Fecha de nacimiento</dt>
            <dd>{{ $persona->fecha_nacimiento?->format('d/m/Y') }} ({{ $persona->edad() }} años)</dd>
            <dt>Sexo</dt>
            <dd>{{ $persona->sexo }}</dd>
            <dt>Email</dt>
            <dd>{{ $persona->email ?? '—' }}</dd>
            <dt>Teléfono</dt>
            <dd>{{ $persona->telefono ?? '—' }}</dd>
            <dt>Pasaporte vence</dt>
            <dd>{{ $persona->pasaporte_vence?->format('d/m/Y') ?? '—' }}</dd>
            <dt>Categoría migratoria</dt>
            <dd>{{ $persona->categoriaMigratoria?->nombre ?? '—' }}</dd>
            <dt>Estatus migratorio</dt>
            <dd><span class="insignia {{ $persona->estatus_migratorio === 'REGULAR' ? 'info' : '' }}">{{ $persona->estatus_migratorio }}</span></dd>
        </dl>
    </div>

    <h2>Expedientes</h2>
    <div class="tarjeta">
        <table>
            <thead>
                <tr><th>Número</th><th>Apertura</th><th>Oficina</th></tr>
            </thead>
            <tbody>
                @forelse ($persona->expedientes as $expediente)
                    <tr>
                        <td><code>{{ $expediente->numero_expediente }}</code></td>
                        <td>{{ $expediente->fecha_apertura?->format('d/m/Y') }}</td>
                        <td>{{ $expediente->oficina?->nombre ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="color: var(--muted);">Sin expedientes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2>Solicitudes</h2>
    <div class="tarjeta">
        <table>
            <thead>
                <tr><th>Expediente</th><th>Servicio</th><th>Estado</th><th>Última acción</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($persona->solicitudes as $solicitud)
                    <tr>
                        <td><code>{{ $solicitud->expediente?->numero_expediente }}</code></td>
                        <td>{{ $solicitud->servicio?->codigo }}</td>
                        <td><span class="insignia info">{{ $solicitud->estado_actual->getValue() }}</span></td>
                        <td>{{ $solicitud->fecha_ultima_accion?->format('d/m/Y H:i') }}</td>
                        <td><a href="/admin/solicitudes/{{ $solicitud->id }}">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color: var(--muted);">Sin solicitudes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2>Movimientos migratorios</h2>
    <div class="tarjeta">
        <table>
            <thead>
                <tr><th>Fecha y hora</th><th>Tipo</th><th>Punto de control</th><th>Medio</th><th>Días aut.</th><th>Oficial</th></tr>
            </thead>
            <tbody>
                @forelse ($persona->movimientos as $movimiento)
                    <tr>
                        <td>{{ $movimiento->fecha_hora?->format('d/m/Y H:i') }}</td>
                        <td>
                            @if ($movimiento->tipo === 'E')
                                <span class="insignia info">Entrada</span>
                            @else
                                <span class="insignia">Salida</span>
                            @endif
                        </td>
                        <td>{{ $movimiento->puntoControl?->nombre }}</td>
                        <td>{{ $movimiento->medio ?? '—' }}</td>
                        <td>{{ $movimiento->dias_autorizados ?? '—' }}</td>
                        <td>{{ $movimiento->oficial?->nombre ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color: var(--muted);">Sin movimientos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p style="margin-top: 1.5rem;"><a href="/admin/personas">← Volver a personas</a></p>
@endsection

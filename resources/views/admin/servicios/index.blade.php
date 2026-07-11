@extends('admin.layout')

@section('titulo', 'Servicios')

@section('contenido')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 style="margin: 0;">Servicios</h1>
        <a class="btn" href="/admin/servicios/crear">Nuevo servicio</a>
    </div>

    <div class="tarjeta">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Canal</th>
                    <th>SLA (días)</th>
                    <th>Tarifas</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($servicios as $servicio)
                    <tr>
                        <td><code>{{ $servicio->codigo }}</code></td>
                        <td>{{ $servicio->nombre }}</td>
                        <td>{{ $servicio->categoriaMigratoria?->codigo ?? '—' }}</td>
                        <td>{{ $servicio->canal }}</td>
                        <td>{{ $servicio->dias_sla }}</td>
                        <td><a href="/admin/tarifas?servicio={{ $servicio->id }}">{{ $servicio->tarifas_count }}</a></td>
                        <td>
                            <span class="insignia {{ $servicio->activo ? 'ok' : 'mal' }}">
                                {{ $servicio->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="/admin/servicios/{{ $servicio->id }}/alternar-activo">
                                @csrf
                                <button type="submit" class="btn chico {{ $servicio->activo ? 'peligro' : 'secundario' }}">
                                    {{ $servicio->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $servicios->links('admin.paginacion') }}</div>
@endsection

@extends('admin.layout')

@section('titulo', 'Sistema')

@section('contenido')
    <h1>Sistema</h1>

    <div class="tarjeta" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
        <div>
            <strong>API interna (/core/v1)</strong>
            <span class="insignia {{ $apiEncendida ? 'ok' : 'mal' }}" style="margin-left: 0.5rem;">
                {{ $apiEncendida ? 'Encendida' : 'Apagada' }}
            </span>
            <div style="color: var(--muted); font-size: 0.85rem; margin-top: 0.35rem;">
                @if ($apiEncendida)
                    El integrador tiene servicio. Al apagarla, toda petición recibe 503 como si el servidor estuviera fuera de línea.
                @else
                    Toda petición del integrador recibe 503. El panel admin no se ve afectado.
                @endif
            </div>
        </div>
        <form method="POST" action="/admin/sistema/alternar-api"
              onsubmit="return confirm('{{ $apiEncendida ? '¿Apagar la API? El integrador perderá el servicio de inmediato.' : '¿Encender la API? El integrador recupera el servicio de inmediato.' }}');">
            @csrf
            <button type="submit" class="btn {{ $apiEncendida ? 'peligro' : 'secundario' }}">
                {{ $apiEncendida ? 'Apagar API' : 'Encender API' }}
            </button>
        </form>
    </div>
@endsection

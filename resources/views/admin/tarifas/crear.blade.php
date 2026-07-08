@extends('admin.layout')

@section('titulo', 'Nueva tarifa')

@section('contenido')
    <h1>Nueva tarifa</h1>

    <div class="tarjeta" style="max-width: 640px;">
        <form method="POST" action="/admin/tarifas">
            @csrf

            <div class="campo">
                <label for="servicio_id">Servicio</label>
                <select id="servicio_id" name="servicio_id" required>
                    <option value="" disabled {{ old('servicio_id') ? '' : 'selected' }}>Selecciona un servicio…</option>
                    @foreach ($servicios as $servicio)
                        <option value="{{ $servicio->id }}" @selected(old('servicio_id') == $servicio->id)>
                            {{ $servicio->codigo }} — {{ $servicio->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="concepto">Concepto</label>
                <select id="concepto" name="concepto" required>
                    <option value="" disabled {{ old('concepto') ? '' : 'selected' }}>Selecciona un concepto…</option>
                    @foreach ($conceptos as $concepto)
                        <option value="{{ $concepto }}" @selected(old('concepto') === $concepto)>{{ $concepto }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fila-campos">
                <div class="campo">
                    <label for="monto">Monto</label>
                    <input type="number" id="monto" name="monto" step="0.01" min="0" value="{{ old('monto') }}" required>
                </div>
                <div class="campo">
                    <label for="moneda">Moneda</label>
                    <input type="text" id="moneda" name="moneda" maxlength="3" value="{{ old('moneda', config('dgm.moneda', 'DOP')) }}" required>
                </div>
            </div>

            <div class="fila-campos">
                <div class="campo">
                    <label for="vigente_desde">Vigente desde</label>
                    <input type="date" id="vigente_desde" name="vigente_desde" value="{{ old('vigente_desde') }}" required>
                </div>
                <div class="campo">
                    <label for="vigente_hasta">Vigente hasta (opcional)</label>
                    <input type="date" id="vigente_hasta" name="vigente_hasta" value="{{ old('vigente_hasta') }}">
                </div>
            </div>

            <div class="campo">
                <label for="resolucion">Resolución (opcional)</label>
                <input type="text" id="resolucion" name="resolucion" value="{{ old('resolucion') }}">
            </div>

            <div class="acciones" style="margin-top: 1.5rem;">
                <button type="submit" class="btn">Registrar tarifa</button>
                <a class="btn secundario" href="/admin/tarifas">Cancelar</a>
            </div>
        </form>
    </div>
@endsection

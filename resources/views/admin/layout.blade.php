<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel') · {{ config('app.name', 'DGM CORE') }}</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --accent: #38bdf8;
            --ok: #4ade80;
            --bad: #f87171;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --borde: rgba(148, 163, 184, 0.15);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }
        aside {
            width: 230px;
            min-height: 100vh;
            background: #0b1220;
            border-right: 1px solid var(--borde);
            padding: 1.5rem 1rem;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        aside .marca { font-size: 1.2rem; font-weight: 700; margin-bottom: 2rem; padding: 0 0.5rem; }
        aside .marca span { color: var(--accent); }
        aside nav { display: flex; flex-direction: column; gap: 0.25rem; flex: 1; }
        aside nav a {
            color: var(--muted);
            text-decoration: none;
            padding: 0.55rem 0.75rem;
            border-radius: 8px;
            font-size: 0.92rem;
        }
        aside nav a:hover { background: var(--card); color: var(--text); }
        aside nav a.activo { background: var(--card); color: var(--accent); font-weight: 600; }
        aside .sesion { border-top: 1px solid var(--borde); padding-top: 1rem; font-size: 0.8rem; color: var(--muted); }
        aside .sesion strong { display: block; color: var(--text); font-size: 0.85rem; }
        aside .sesion button {
            margin-top: 0.6rem;
            background: none;
            border: 1px solid var(--borde);
            color: var(--muted);
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        aside .sesion button:hover { color: var(--bad); border-color: var(--bad); }
        main { flex: 1; padding: 2rem 2.5rem; max-width: 1200px; }
        h1 { font-size: 1.5rem; margin-bottom: 1.5rem; }
        h2 { font-size: 1.05rem; margin: 1.5rem 0 0.75rem; color: var(--muted); font-weight: 600; }
        .tarjeta {
            background: var(--card);
            border: 1px solid var(--borde);
            border-radius: 12px;
            padding: 1.25rem;
        }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th, td { text-align: left; padding: 0.6rem 0.75rem; border-bottom: 1px solid var(--borde); }
        th { color: var(--muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em; }
        tr:last-child td { border-bottom: none; }
        a { color: var(--accent); }
        .acciones { display: flex; gap: 0.5rem; align-items: center; }
        .btn {
            display: inline-block;
            text-decoration: none;
            background: var(--accent);
            color: var(--bg);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
        }
        .btn.secundario { background: transparent; color: var(--accent); border: 1px solid var(--accent); }
        .btn.chico { padding: 0.3rem 0.7rem; font-size: 0.8rem; }
        .btn.peligro { background: transparent; color: var(--bad); border: 1px solid var(--bad); }
        .btn:hover { opacity: 0.85; }
        .insignia {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid var(--borde);
            color: var(--muted);
        }
        .insignia.ok { color: var(--ok); border-color: var(--ok); }
        .insignia.mal { color: var(--bad); border-color: var(--bad); }
        .insignia.info { color: var(--accent); border-color: var(--accent); }
        .aviso { border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.9rem; }
        .aviso.exito { background: rgba(74, 222, 128, 0.1); border: 1px solid var(--ok); color: var(--ok); }
        .aviso.error { background: rgba(248, 113, 113, 0.1); border: 1px solid var(--bad); color: var(--bad); }
        label { display: block; font-size: 0.85rem; color: var(--muted); margin-bottom: 0.35rem; }
        input, select {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border-radius: 8px;
            border: 1px solid var(--borde);
            background: #0b1220;
            color: var(--text);
            font-size: 0.95rem;
        }
        input:focus, select:focus { outline: 2px solid var(--accent); border-color: transparent; }
        .campo { margin-bottom: 1rem; }
        .fila-campos { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .filtros { display: flex; gap: 0.6rem; margin-bottom: 1rem; align-items: flex-end; flex-wrap: wrap; }
        .filtros .campo { margin-bottom: 0; min-width: 180px; }
        .paginacion { margin-top: 1rem; font-size: 0.9rem; }
        .paginacion ul { list-style: none; display: flex; gap: 0.5rem; }
        .paginacion li a, .paginacion li span {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border: 1px solid var(--borde);
            border-radius: 8px;
            text-decoration: none;
        }
        .paginacion li span { color: var(--muted); }
        .cuadricula { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .metrica .valor { font-size: 1.9rem; font-weight: 700; color: var(--accent); }
        .metrica .nombre { color: var(--muted); font-size: 0.85rem; margin-top: 0.25rem; }
        dl.detalle { display: grid; grid-template-columns: 220px 1fr; gap: 0.4rem 1rem; font-size: 0.92rem; }
        dl.detalle dt { color: var(--muted); }
        code { background: #0b1220; padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.85em; }
    </style>
</head>
<body>
    <aside>
        <div class="marca">DGM <span>CORE</span></div>
        <nav>
            <a href="/admin" class="{{ request()->is('admin') ? 'activo' : '' }}">Panel</a>
            <a href="/admin/usuarios" class="{{ request()->is('admin/usuarios*') ? 'activo' : '' }}">Usuarios</a>
            <a href="/admin/servicios" class="{{ request()->is('admin/servicios*') ? 'activo' : '' }}">Servicios</a>
            <a href="/admin/tarifas" class="{{ request()->is('admin/tarifas*') ? 'activo' : '' }}">Tarifas</a>
            <a href="/admin/solicitudes" class="{{ request()->is('admin/solicitudes*') ? 'activo' : '' }}">Solicitudes</a>
            <a href="/admin/auditoria" class="{{ request()->is('admin/auditoria*') ? 'activo' : '' }}">Auditoría</a>
            <a href="/admin/sistema" class="{{ request()->is('admin/sistema*') ? 'activo' : '' }}">Sistema</a>
        </nav>
        <div class="sesion">
            <strong>{{ auth()->user()?->nombre }}</strong>
            {{ auth()->user()?->rol?->nombre }}
            <form method="POST" action="/admin/salir">
                @csrf
                <button type="submit">Cerrar sesión</button>
            </form>
        </div>
    </aside>
    <main>
        @if (session('exito'))
            <div class="aviso exito">{{ session('exito') }}</div>
        @endif
        @if ($errors->any())
            <div class="aviso error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        @yield('contenido')
    </main>
</body>
</html>

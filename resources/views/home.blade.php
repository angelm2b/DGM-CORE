<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'DGM CORE') }}</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --accent: #38bdf8;
            --text: #e2e8f0;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            background: var(--card);
            border-radius: 16px;
            padding: 3rem;
            max-width: 560px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        h1 span { color: var(--accent); }
        p.subtitle {
            color: var(--muted);
            margin-bottom: 2rem;
        }
        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        a.btn {
            text-decoration: none;
            color: var(--bg);
            background: var(--accent);
            padding: 0.65rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        a.btn.secondary {
            background: transparent;
            color: var(--accent);
            border: 1px solid var(--accent);
        }
        a.btn:hover { opacity: 0.85; }
        .logout {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--muted);
            font-size: 0.85rem;
            text-decoration: none;
        }
        .logout:hover { color: var(--accent); }
        .meta {
            margin-top: 2rem;
            font-size: 0.85rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>DGM <span>CORE</span></h1>
        <p class="subtitle">¿Qué deseas ver?</p>
        <div class="links">
            <a class="btn" href="/docs/api">Documentación</a>
            <a class="btn secondary" href="/docs/api.json">API (OpenAPI JSON)</a>
        </div>
        @unless (app()->environment('local'))
            <a class="logout" href="/docs-acceso/salir">← Cerrar sesión</a>
        @endunless
        <p class="meta">Laravel {{ app()->version() }} · Entorno: {{ app()->environment() }}</p>
    </div>
</body>
</html>

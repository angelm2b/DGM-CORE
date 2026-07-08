<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso al panel · {{ config('app.name', 'DGM CORE') }}</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --accent: #38bdf8;
            --bad: #f87171;
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
            padding: 2.5rem;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
        h1 span { color: var(--accent); }
        p.subtitle { color: var(--muted); margin-bottom: 1.5rem; font-size: 0.9rem; }
        label { display: block; font-size: 0.85rem; color: var(--muted); margin: 1rem 0 0.4rem; }
        input {
            width: 100%;
            padding: 0.7rem 0.9rem;
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            background: #0b1220;
            color: var(--text);
            font-size: 1rem;
        }
        input:focus { outline: 2px solid var(--accent); border-color: transparent; }
        button {
            margin-top: 1.5rem;
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            background: var(--accent);
            color: var(--bg);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
        }
        button:hover { opacity: 0.85; }
        .error { color: var(--bad); font-size: 0.85rem; margin-top: 0.75rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>DGM <span>CORE</span></h1>
        <p class="subtitle">Panel de administración</p>
        <form method="POST" action="/admin/login">
            @csrf
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>

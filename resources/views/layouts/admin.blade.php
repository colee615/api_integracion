<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Integracion' }}</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --surface: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --line: #d7deea;
            --brand: #0f766e;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #eff6ff 0%, var(--bg) 240px);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        .container { max-width: 1180px; margin: 0 auto; padding: 24px; }
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; margin-bottom: 24px;
        }
        .brand { font-size: 24px; font-weight: 700; color: var(--brand); }
        .nav { display: flex; gap: 12px; align-items: center; }
        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
        }
        .grid { display: grid; gap: 18px; }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .button, button {
            border: 0; border-radius: 10px; padding: 10px 14px;
            background: var(--brand); color: white; cursor: pointer;
        }
        .button-secondary { background: #1f2937; }
        .button-danger { background: var(--danger); }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            background: white;
        }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; }
        .field { margin-bottom: 14px; }
        .stats { font-size: 30px; font-weight: 700; margin-top: 8px; }
        .muted { color: var(--muted); }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px 10px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        .badge {
            display: inline-block; padding: 4px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 700; background: #d1fae5; color: #065f46;
        }
        .badge-muted { background: #e5e7eb; color: #374151; }
        .flash {
            background: #dcfce7; color: #166534; border: 1px solid #86efac;
            padding: 12px 14px; border-radius: 12px; margin-bottom: 18px;
        }
        .token-box {
            background: #111827; color: #f9fafb; border-radius: 12px;
            padding: 14px; font-family: Consolas, monospace; word-break: break-all;
        }
        .error { color: var(--danger); font-size: 14px; margin-top: 6px; }
        @media (max-width: 900px) {
            .grid-3, .grid-2 { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <div>
                <div class="brand">Integracion</div>
                <div class="muted">Backend multiempresa para trazabilidad de paquetes</div>
            </div>
            @auth
                <div class="nav">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                        <a href="{{ route('admin.companies.index') }}">Empresas</a>
                    @endif
                    @if (auth()->user()->isCompany())
                        <a href="{{ route('company.dashboard') }}">Mi portal</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="button-secondary">Cerrar sesion</button>
                    </form>
                </div>
            @endauth
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @yield('content')
    </div>
</body>
</html>

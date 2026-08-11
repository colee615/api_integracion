<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Correos de Bolivia</title>
    <style>
        :root {
            --bg: #1f1f1f;
            --text: #ffffff;
            --muted: #f2f2f2;
            --brand-yellow: #f8c63d;
            --brand-blue: #1657b8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .welcome-shell {
            width: 100%;
            max-width: 760px;
            text-align: center;
        }

        .brand-mark {
            width: 200px;
            margin: 0 auto 26px;
            padding: 12px 14px 10px;
            background: var(--brand-yellow);
            color: var(--brand-blue);
            font-weight: 800;
            line-height: 1;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
        }

        .brand-mark .top {
            display: block;
            font-size: 15px;
            letter-spacing: 0.08em;
        }

        .brand-mark .main {
            display: block;
            font-size: 27px;
            font-style: italic;
            letter-spacing: -0.03em;
        }

        .brand-mark .bottom {
            display: block;
            margin-top: 2px;
            font-size: 16px;
            font-style: italic;
        }

        h1 {
            margin: 0 0 16px;
            font-size: clamp(2rem, 3vw, 3.1rem);
            font-weight: 700;
        }

        p {
            margin: 0;
            font-size: clamp(1rem, 1.5vw, 1.15rem);
            color: var(--muted);
        }
    </style>
</head>
<body>
    <main class="welcome-shell">
        <div class="brand-mark" aria-label="Correos de Bolivia">
            <span class="main">CORREOS</span>
            <span class="bottom">DE BOLIVIA</span>
        </div>
        <h1>Bienvenido a Correos de Bolivia.</h1>
        <p>Estamos trabajando para ofrecerle el mejor servicio.</p>
    </main>
</body>
</html>

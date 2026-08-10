@extends('adminlte::master')

@section('title', 'Iniciar sesion')

@section('adminlte_css')
    @include('admin.partials.enterprise-theme')
    <style>
        .corp-login-page {
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: #163f9d;
            background: #ffffff;
            position: relative;
            overflow-x: hidden;
        }

        .corp-login-page::before,
        .corp-login-page::after {
            content: "";
            position: fixed;
            inset: auto;
            pointer-events: none;
            z-index: 0;
        }

        .corp-login-page::before {
            left: -18vw;
            bottom: -28vh;
            width: 34vw;
            height: 34vw;
            border-radius: 50%;
            background:
                linear-gradient(140deg, rgba(255, 197, 23, 0.98) 0%, rgba(243, 184, 0, 0.96) 72%, rgba(216, 154, 0, 0.94) 100%);
            box-shadow: inset 0 0 0 2px rgba(31, 79, 184, 0.28);
            transform: rotate(-12deg);
        }

        .corp-login-page::after {
            right: -8vw;
            top: -18vh;
            width: 28vw;
            height: 28vw;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.88) 0%, rgba(255, 255, 255, 0.18) 70%, transparent 100%);
        }

        .corp-login-shell {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 26px 28px 0;
        }

        .corp-login-topbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 14px;
        }

        .corp-login-help {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #1f4fb8;
            font-weight: 700;
            text-decoration: none;
            font-size: 1.05rem;
        }

        .corp-login-help:hover {
            color: #143b99;
            text-decoration: none;
        }

        .corp-login-grid {
            flex: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(430px, .9fr);
            gap: 42px;
            align-items: center;
            max-width: 1540px;
            width: 100%;
            margin: 0 auto;
            padding: 0 18px 18px;
        }

        .corp-login-hero {
            position: relative;
            min-height: 660px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 8px 24px 120px 24px;
        }

        .corp-hero-title {
            max-width: 470px;
            margin: 0;
            color: #173f9f;
            font-size: clamp(3.5rem, 5vw, 5.4rem);
            line-height: .9;
            font-weight: 900;
            letter-spacing: -.06em;
            text-transform: uppercase;
            text-shadow: 0 8px 22px rgba(191, 144, 0, 0.12);
        }

        .corp-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            color: #4969b3;
            font-size: .9rem;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .corp-hero-eyebrow::before {
            content: "";
            width: 40px;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, #ffc517 0%, #ffe07a 100%);
        }

        .corp-hero-accent {
            width: 72px;
            height: 6px;
            border-radius: 999px;
            background: linear-gradient(90deg, #ffc517 0%, #ffcf33 100%);
            margin: 18px 0 20px;
        }

        .corp-hero-copy {
            max-width: 420px;
            margin: 0;
            color: #355291;
            font-size: 1.16rem;
            line-height: 1.65;
            font-weight: 500;
        }

        .corp-hero-map {
            position: absolute;
            right: 4%;
            top: 9%;
            width: min(420px, 45%);
            opacity: .72;
            color: #d6b23b;
        }

        .corp-hero-map svg {
            width: 100%;
            height: auto;
            display: block;
        }

        .corp-hero-badges {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 26px;
            max-width: 640px;
            margin-top: 34px;
        }

        .corp-badge {
            text-align: center;
            color: #1f4fb8;
        }

        .corp-badge-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 245, 205, 0.96);
            box-shadow: inset 0 0 0 1px rgba(31, 79, 184, 0.08);
            font-size: 1.7rem;
        }

        .corp-badge strong {
            display: block;
            font-size: 1.25rem;
            margin-bottom: 6px;
        }

        .corp-badge span {
            display: block;
            color: #4f6b9e;
            font-size: .98rem;
            line-height: 1.35;
        }

        .corp-cityline {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 40px;
            height: 112px;
            background:
                linear-gradient(180deg, rgba(255, 247, 217, 0) 0%, rgba(255, 226, 128, 0.55) 24%, rgba(255, 205, 61, 0.84) 100%);
            clip-path: polygon(0 68%, 7% 60%, 16% 66%, 22% 55%, 29% 59%, 38% 39%, 43% 50%, 50% 35%, 57% 46%, 65% 31%, 74% 44%, 79% 37%, 87% 50%, 94% 43%, 100% 55%, 100% 100%, 0 100%);
            opacity: .52;
        }

        .corp-login-panel {
            position: relative;
            background: rgba(255, 252, 242, 0.94);
            border-radius: 28px;
            padding: 34px 46px 28px;
            box-shadow: 0 28px 68px rgba(191, 144, 0, 0.16);
            border: 1px solid rgba(242, 223, 157, 0.96);
            backdrop-filter: blur(8px);
        }

        .corp-lock-avatar {
            width: 180px;
            margin: 0 auto 22px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .corp-lock-avatar img {
            display: block;
            width: 100%;
            max-width: 180px;
            height: auto;
        }

        .corp-panel-title {
            margin: 0;
            color: #173f9f;
            font-size: clamp(2rem, 3vw, 3rem);
            font-weight: 800;
            letter-spacing: -.04em;
            text-align: center;
        }

        .corp-panel-subtitle {
            margin: 10px 0 28px;
            text-align: center;
            color: #6a84bb;
            font-size: 1.05rem;
        }

        .corp-form-group {
            margin-bottom: 18px;
        }

        .corp-form-label {
            display: block;
            margin-bottom: 8px;
            color: #173f9f;
            font-size: 1rem;
            font-weight: 800;
        }

        .corp-input-wrap {
            position: relative;
        }

        .corp-input-icon,
        .corp-input-toggle {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #6f88b6;
            font-size: 1.15rem;
        }

        .corp-input-icon {
            left: 18px;
        }

        .corp-input-toggle {
            right: 18px;
            border: 0;
            background: transparent;
            padding: 0;
            cursor: pointer;
        }

        .corp-input {
            width: 100%;
            min-height: 54px;
            border-radius: 12px;
            border: 1px solid #f0dd9d;
            background: #fffef9;
            color: #163f9d;
            font-size: 1rem;
            padding: 14px 52px 14px 48px;
            box-shadow: inset 0 1px 3px rgba(191, 144, 0, 0.04);
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .corp-input::placeholder {
            color: #8aa0cc;
        }

        .corp-input:focus {
            outline: none;
            border-color: #f2c94c;
            box-shadow: 0 0 0 .18rem rgba(255, 197, 23, 0.2);
        }

        .corp-error {
            margin-top: 8px;
            color: #c53030;
            font-size: .92rem;
            font-weight: 600;
        }

        .corp-form-row {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: center;
            margin: 8px 0 18px;
            color: #4f6b9e;
            flex-wrap: wrap;
        }

        .corp-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
        }

        .corp-checkbox input {
            width: 18px;
            height: 18px;
            accent-color: #f0b700;
        }

        .corp-link {
            color: #1f4fb8;
            text-decoration: none;
            font-weight: 600;
        }

        .corp-link:hover {
            color: #163f9d;
            text-decoration: none;
        }

        .corp-primary-btn {
            width: 100%;
            min-height: 56px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(90deg, #ffc517 0%, #f0b700 100%);
            color: #123fa9;
            font-size: 1.18rem;
            font-weight: 800;
            letter-spacing: -.01em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            box-shadow: 0 16px 30px rgba(191, 144, 0, 0.24);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .corp-primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 34px rgba(191, 144, 0, 0.28);
        }

        .corp-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: #7d90bc;
            font-weight: 600;
            justify-content: center;
        }

        .corp-divider::before,
        .corp-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #f2dd93;
        }

        .corp-secondary-link {
            width: 100%;
            min-height: 50px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border-radius: 12px;
            border: 1px solid #f0dd9d;
            color: #1f4fb8;
            background: #fffef9;
            font-size: 1.05rem;
            font-weight: 800;
            text-decoration: none;
        }

        .corp-secondary-link:hover {
            color: #163f9d;
            text-decoration: none;
            background: #fff9e8;
        }

        .corp-restricted-note {
            margin-top: 18px;
            border-radius: 16px;
            padding: 18px 20px;
            background: linear-gradient(180deg, #fffdf5 0%, #fff3d0 100%);
            border: 1px solid #f0dd9d;
            display: flex;
            gap: 14px;
            color: #5e78ae;
        }

        .corp-restricted-note i {
            font-size: 1.45rem;
            color: #1f4fb8;
            margin-top: 2px;
        }

        .corp-restricted-note strong {
            display: block;
            color: #173f9f;
            margin-bottom: 4px;
            font-size: 1.08rem;
        }

        .corp-login-footer {
            position: relative;
            z-index: 1;
            margin: 0 -28px;
            padding: 18px 28px;
            background: linear-gradient(90deg, #ffc517 0%, #f0b700 100%);
            color: #123fa9;
        }

        .corp-footer-items {
            max-width: 1320px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .corp-footer-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            font-size: 1.02rem;
        }

        .corp-footer-item i {
            font-size: 1.45rem;
            color: #1f4fb8;
        }

        @media (max-width: 1199.98px) {
            .corp-login-page::before {
                left: -22vw;
                bottom: -18vh;
                width: 46vw;
                height: 46vw;
            }

            .corp-login-grid {
                grid-template-columns: 1fr;
                gap: 20px;
                align-items: start;
                padding-bottom: 24px;
            }

            .corp-login-hero {
                min-height: auto;
                padding: 4px 6px 6px;
            }

            .corp-hero-map,
            .corp-cityline {
                display: none;
            }

            .corp-login-panel {
                max-width: 760px;
                width: 100%;
                margin: 0 auto;
            }
        }

        @media (max-width: 767.98px) {
            .corp-login-page::before {
                left: -34vw;
                bottom: -8vh;
                width: 58vw;
                height: 58vw;
            }

            .corp-login-shell {
                padding: 18px 16px 0;
            }

            .corp-login-grid {
                gap: 14px;
                padding: 0 0 18px;
            }

            .corp-hero-title {
                max-width: 100%;
                font-size: 3rem;
            }

            .corp-hero-copy {
                max-width: 100%;
                font-size: 1rem;
            }

            .corp-hero-badges {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .corp-badge span {
                max-width: 280px;
                margin: 0 auto;
            }

            .corp-login-panel {
                padding: 26px 18px 22px;
                border-radius: 22px;
            }

            .corp-form-row {
                align-items: flex-start;
                flex-direction: column;
            }

            .corp-footer-items {
                grid-template-columns: 1fr;
                justify-items: start;
            }
        }
    </style>
@stop

@section('classes_body', 'hold-transition corp-login-page')

@section('body')
    <div class="corp-login-shell">
        <div class="corp-login-topbar">
            <a href="#soporte" class="corp-login-help" aria-label="Necesitas ayuda">
                <i class="far fa-question-circle"></i>
                <span>&iquest;Necesitas ayuda?</span>
            </a>
        </div>

        <main class="corp-login-grid">
            <section class="corp-login-hero" aria-label="Presentacion de la plataforma">
                <div class="corp-hero-eyebrow">Plataforma interna de integracion</div>
                <h1 class="corp-hero-title">SITRA</h1>
                <div class="corp-hero-accent" aria-hidden="true"></div>
                <p class="corp-hero-copy">
                    Sistema de integracion de trackeo orientado a paqueteria, trazabilidad operativa y monitoreo centralizado de procesos.
                </p>

                <div class="corp-hero-badges">
                    <article class="corp-badge">
                        <div class="corp-badge-icon"><i class="fas fa-shield-alt"></i></div>
                        <strong>Seguridad</strong>
                        <span>Conexiones cifradas y acceso autorizado.</span>
                    </article>
                    <article class="corp-badge">
                        <div class="corp-badge-icon"><i class="fas fa-route"></i></div>
                        <strong>Tracking</strong>
                        <span>Trazabilidad en tiempo real de envios y eventos.</span>
                    </article>
                    <article class="corp-badge">
                        <div class="corp-badge-icon"><i class="far fa-clock"></i></div>
                        <strong>Disponibilidad</strong>
                        <span>Plataforma disponible 24/7 para tus integraciones.</span>
                    </article>
                </div>

                <div class="corp-hero-map" aria-hidden="true">
                    <svg viewBox="0 0 360 520" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M116 28L156 56L182 38L228 68L271 61L316 97L294 148L310 210L286 250L296 295L275 330L282 389L249 425L219 432L189 474L151 470L122 432L89 405L72 360L53 338L61 290L43 263L56 228L38 201L46 154L66 127L72 90L97 75L116 28Z" fill="currentColor" fill-opacity=".12"/>
                        <path d="M116 28L156 56L182 38L228 68L271 61L316 97L294 148L310 210L286 250L296 295L275 330L282 389L249 425L219 432L189 474L151 470L122 432L89 405L72 360L53 338L61 290L43 263L56 228L38 201L46 154L66 127L72 90L97 75L116 28Z" stroke="currentColor" stroke-opacity=".22" stroke-width="10" stroke-dasharray="1 11"/>
                        <path d="M42 171C86 128 160 120 207 170C251 216 252 290 209 329C162 372 95 365 53 323" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="1 9"/>
                        <path d="M112 204C182 152 255 190 278 255C297 308 276 373 232 413" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="1 9"/>
                        <path d="M162 62C170 62 176 68 176 76C176 92 162 100 162 100C162 100 148 92 148 76C148 68 154 62 162 62Z" fill="#5D88EF"/>
                        <circle cx="162" cy="76" r="5" fill="#fff"/>
                        <path d="M214 300C223 300 230 307 230 316C230 334 214 342 214 342C214 342 198 334 198 316C198 307 205 300 214 300Z" fill="#FFC517"/>
                        <circle cx="214" cy="316" r="5" fill="#fff"/>
                    </svg>
                </div>

                <div class="corp-cityline" aria-hidden="true"></div>
            </section>

            <section class="corp-login-panel" aria-label="Formulario de inicio de sesion">
                <div class="corp-lock-avatar">
                    <img src="http://correos.gob.bo/frontstorage/cms/theme/CNOoA5H41DHjoZ4YmlAMyHqNHNeSpKwd5nrr7Lze.png" alt="Correos de Bolivia">
                </div>

                <h2 class="corp-panel-title">Iniciar sesi&oacute;n</h2>
                <p class="corp-panel-subtitle">Accede a la plataforma de integraci&oacute;n de sistemas</p>

                <form action="{{ route('login.store') }}" method="post" novalidate>
                    @csrf

                    <div class="corp-form-group">
                        <label class="corp-form-label" for="email">Email</label>
                        <div class="corp-input-wrap">
                            <i class="far fa-user corp-input-icon" aria-hidden="true"></i>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="corp-input @error('email') is-invalid @enderror"
                                placeholder="Ingresa tu email"
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                        @error('email')
                            <div class="corp-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="corp-form-group">
                        <label class="corp-form-label" for="password">Contrase&ntilde;a</label>
                        <div class="corp-input-wrap">
                            <i class="fas fa-lock corp-input-icon" aria-hidden="true"></i>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="corp-input @error('password') is-invalid @enderror"
                                placeholder="Ingresa tu contrase&ntilde;a"
                                autocomplete="current-password"
                            >
                            <button type="button" class="corp-input-toggle" data-toggle-password aria-label="Mostrar u ocultar contrase&ntilde;a">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="corp-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="corp-form-row">
                        <label class="corp-checkbox" for="remember">
                            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>Recordarme</span>
                        </label>

                        <a href="#recuperar" class="corp-link">&iquest;Olvidaste tu contrase&ntilde;a?</a>
                    </div>

                    <button type="submit" class="corp-primary-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Iniciar sesi&oacute;n</span>
                    </button>
                </form>

                <div class="corp-divider">o</div>

                <a href="#api-docs" class="corp-secondary-link">
                    <i class="far fa-file-alt"></i>
                    <span>Documentaci&oacute;n de la API</span>
                    <i class="fas fa-chevron-right"></i>
                </a>

                <div class="corp-restricted-note">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <strong>Acceso restringido</strong>
                        <div>Este portal es &uacute;nicamente para sistemas autorizados. El acceso no autorizado ser&aacute; registrado.</div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="corp-login-footer">
            <div class="corp-footer-items">
                <div class="corp-footer-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Plataforma segura</span>
                </div>
                <div class="corp-footer-item">
                    <i class="far fa-clock"></i>
                    <span>Disponibilidad 24/7</span>
                </div>
                <div class="corp-footer-item">
                    <i class="fas fa-headset"></i>
                    <span>Soporte t&eacute;cnico</span>
                </div>
            </div>
        </footer>
    </div>
@stop

@section('adminlte_js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.querySelector('[data-toggle-password]');
            var passwordInput = document.getElementById('password');

            if (!toggle || !passwordInput) {
                return;
            }

            toggle.addEventListener('click', function () {
                var isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggle.innerHTML = isPassword
                    ? '<i class="far fa-eye-slash"></i>'
                    : '<i class="far fa-eye"></i>';
            });
        });
    </script>
@stop

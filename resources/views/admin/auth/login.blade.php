@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@section('title', 'Iniciar sesion')

@section('css')
    @include('admin.partials.enterprise-theme')
@stop

@section('auth_header')
    <div class="text-center">
        <div class="page-kicker" style="color:#475569;border-color:#dbe4ee;background:#f8fafc;">
            <i class="fas fa-shield-alt"></i> Acceso interno
        </div>
        <div style="font-size: 1.25rem; font-weight: 700; color: #0f172a;">Administración corporativa</div>
    </div>
@endsection

@section('auth_body')
    <p class="login-box-msg">Acceso reservado para el equipo interno que supervisa empresas, credenciales e integraciones de la plataforma.</p>

    <div class="corp-auth-note mb-3">
        Este portal no es para clientes finales. El acceso empresarial se consumirá desde Nuxt.js usando credenciales y tokens asignados.
    </div>

    <form action="{{ route('login.store') }}" method="post">
        @csrf

        <div class="input-group mb-3">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', 'eynar345@gmail.com') }}" placeholder="Correo corporativo" autofocus>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope"></span>
                </div>
            </div>
            @error('email')
                <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="input-group mb-4">
            <input type="password" name="password" class="form-control" value="123456789" placeholder="Contrasena">
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Entrar al panel</button>
    </form>
@endsection

@section('auth_footer')
    <small class="text-muted">Integracion | Entorno interno de administración</small>
@endsection

@extends('adminlte::page')

@section('title', 'Usuarios')

@section('css')
    @include('admin.partials.enterprise-theme')
@stop

@section('content_header')
    <section class="page-hero">
        <span class="page-kicker"><i class="fas fa-users-cog"></i> Seguridad interna</span>
        <h1 class="page-title">Gestión de usuarios internos</h1>
        <p class="page-subtitle">Controla el acceso del equipo interno, define cuentas activas y mantén trazabilidad sobre quién administra la plataforma.</p>
    </section>
@stop

@section('content')
    <div class="page-shell">
        <div class="row">
            <div class="col-md-4">
                <x-adminlte-card title="Alta de usuario interno" theme="light" icon="fas fa-user-plus" class="panel-card form-zone">
                    <div class="section-note mb-3">
                        Crea nuevas cuentas administrativas para operación, soporte o supervisión.
                    </div>

                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf
                        <x-adminlte-input name="name" label="Nombre completo" value="{{ old('name') }}" required />
                        <x-adminlte-input name="email" type="email" label="Correo corporativo" value="{{ old('email') }}" required />
                        <x-adminlte-input name="password" type="text" label="Contrasena inicial" value="{{ old('password') }}" required />
                        <x-adminlte-select name="status" label="Estado operativo">
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                        </x-adminlte-select>
                        <x-adminlte-button type="submit" theme="primary" label="Crear usuario" icon="fas fa-save"/>
                    </form>
                </x-adminlte-card>
            </div>

            <div class="col-md-8">
                <x-adminlte-card title="Directorio interno" theme="light" icon="fas fa-id-badge" class="panel-card">
                    <div class="section-note mb-3">
                        Lista de usuarios con acceso al panel administrativo y su estado actual de disponibilidad.
                    </div>

                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Correo</th>
                                    <th>Estado</th>
                                    <th>Ultimo ingreso</th>
                                    <th>Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td>
                                            <strong>{{ $user->name }}</strong><br>
                                            <small class="text-muted">Administrador interno</small>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'secondary' }}">{{ $user->status }}</span>
                                        </td>
                                        <td>{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Sin ingresos' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.users.status', $user) }}" class="inline-actions">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $user->status === 'active' ? 'inactive' : 'active' }}">
                                                <x-adminlte-button
                                                    type="submit"
                                                    theme="{{ $user->status === 'active' ? 'secondary' : 'success' }}"
                                                    label="{{ $user->status === 'active' ? 'Desactivar' : 'Activar' }}"
                                                    size="xs"
                                                    icon="fas fa-exchange-alt"/>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">Todavia no hay usuarios internos creados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-adminlte-card>
            </div>
        </div>
    </div>
@stop

@section('footer')
    <strong>API Integracion.</strong> Gestion interna de usuarios.
@stop

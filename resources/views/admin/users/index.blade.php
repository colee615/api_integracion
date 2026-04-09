@extends('adminlte::page')

@section('title', 'Usuarios')

@section('css')
    @include('admin.partials.enterprise-theme')
@stop

@section('content_header')
    <section class="page-hero">
        <span class="page-kicker"><i class="fas fa-users-cog"></i> Seguridad interna</span>
        <h1 class="page-title">Gestión de usuarios internos</h1>
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
                        <x-adminlte-button type="submit" theme="primary" label="Guardar usuario" icon="fas fa-save" class="table-style-submit"/>
                    </form>
                </x-adminlte-card>
            </div>

            <div class="col-md-8">
                <x-adminlte-card title="Directorio interno" theme="light" icon="fas fa-id-badge" class="panel-card">
                    <div class="table-responsive">
                        <table class="table corp-table user-table">
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
                                            <div class="inline-actions">
                                                <button
                                                    type="button"
                                                    class="btn btn-xs btn-info js-open-user-modal"
                                                    data-toggle="modal"
                                                    data-target="#user-edit-modal"
                                                    data-action="{{ route('admin.users.status', $user) }}"
                                                    data-name="{{ $user->name }}"
                                                    data-email="{{ $user->email }}"
                                                    data-status="{{ $user->status }}">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                            </div>
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

    <div class="modal fade" id="user-edit-modal" tabindex="-1" aria-labelledby="user-edit-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="user-edit-form">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header">
                        <h5 class="modal-title" id="user-edit-modal-label">Editar usuario interno</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label for="user-edit-status">Estado operativo</label>
                            <select name="status" id="user-edit-status" class="form-control" required>
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('user-edit-form');
    const nameField = document.getElementById('user-edit-name');
    const emailField = document.getElementById('user-edit-email');
    const statusField = document.getElementById('user-edit-status');

    document.querySelectorAll('.js-open-user-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            form.action = button.dataset.action;
            nameField.textContent = button.dataset.name || 'Usuario';
            emailField.textContent = button.dataset.email || '';
            statusField.value = button.dataset.status || 'active';
        });
    });
});
</script>
@stop

@section('footer')
    <strong>Integracion.</strong> Gestion interna de usuarios.
@stop

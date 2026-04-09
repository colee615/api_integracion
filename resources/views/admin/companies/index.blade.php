@extends('adminlte::page')

@section('title', 'Empresas')

@section('css')
    @include('admin.partials.enterprise-theme')
@stop

@section('content_header')
    <section class="page-hero">
        <span class="page-kicker"><i class="fas fa-building"></i> Gestion comercial</span>
        <h1 class="page-title">Gestion de empresas</h1>
    </section>
@stop

@section('content')
    <div class="page-shell">
        <div class="row">
            <div class="col-md-4">
                <x-adminlte-card title="Alta de empresa" theme="light" icon="fas fa-plus-circle" class="panel-card form-zone">
                    <div class="section-note mb-3">
                        Registra una nueva empresa cliente con sus datos de contacto, credenciales base de acceso e idioma operativo del portal.
                    </div>

                    <form method="POST" action="{{ route('admin.companies.store') }}">
                        @csrf
                        <x-adminlte-input name="name" label="Nombre comercial" value="{{ old('name') }}" required />
                        <x-adminlte-input name="slug" label="Slug corporativo" value="{{ old('slug') }}" placeholder="empresa-demo" />
                        <x-adminlte-input name="contact_name" label="Responsable de contacto" value="{{ old('contact_name') }}" />
                        <x-adminlte-input name="contact_email" type="email" label="Correo de contacto" value="{{ old('contact_email') }}" />
                        <x-adminlte-input name="contact_phone" label="Telefono" value="{{ old('contact_phone') }}" />
                        <x-adminlte-select name="status" label="Estado de servicio">
                            <option value="active">Activa</option>
                            <option value="inactive">Inactiva</option>
                        </x-adminlte-select>
                        <x-adminlte-select name="locale" label="Idioma del portal">
                            <option value="es" @selected(old('locale', 'es') === 'es')>Espanol</option>
                            <option value="en" @selected(old('locale') === 'en')>English</option>
                        </x-adminlte-select>
                        <hr>
                        <x-adminlte-input name="login_email" type="email" label="Correo de acceso empresa" value="{{ old('login_email') }}" required />
                        <x-adminlte-input name="login_password" type="text" label="Contrasena inicial empresa" value="{{ old('login_password') }}" required />
                        <x-adminlte-button type="submit" theme="primary" label="Guardar empresa" icon="fas fa-save" class="table-style-submit"/>
                    </form>
                </x-adminlte-card>
            </div>

            <div class="col-md-8">
                <x-adminlte-card title="Cartera empresarial" theme="light" icon="fas fa-briefcase" class="panel-card">
                    <div class="table-responsive">
                        <table class="table corp-table company-table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Correo</th>
                                    <th>Idioma</th>
                                    <th>Estado</th>
                                    <th>Ultimo ingreso</th>
                                    <th>Sesiones</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($companies as $company)
                                    <tr>
                                        <td>
                                            <strong>{{ $company->name }}</strong><br>
                                            <small class="text-muted">{{ $company->slug }}</small><br>
                                            <small class="text-muted">Contacto: {{ $company->contact_name ?: 'Sin dato' }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $company->user?->email ?? 'Sin usuario' }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-light company-locale-badge">
                                                {{ ($company->locale ?? 'es') === 'en' ? 'English' : 'Espanol' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $company->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ $company->status === 'active' ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </td>
                                        <td>{{ $company->user?->last_login_at?->format('Y-m-d H:i') ?? 'Sin ingresos' }}</td>
                                        <td>
                                            <span class="detail-chip company-session-chip">
                                                <i class="fas fa-user-clock"></i>
                                                {{ $sessionCounts[$company->user?->id] ?? 0 }} activas
                                            </span>
                                        </td>
                                        <td>
                                            <div class="company-actions">
                                                <button
                                                    type="button"
                                                class="btn btn-xs btn-info js-open-company-modal"
                                                data-toggle="modal"
                                                data-target="#company-edit-modal"
                                                data-action="{{ route('admin.companies.settings', $company) }}"
                                                data-company="{{ $company->name }}"
                                                data-locale="{{ $company->locale ?? 'es' }}"
                                                data-status="{{ $company->status }}">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>

                                                <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-xs btn-info">
                                                    <i class="fas fa-eye"></i> Ver detalle
                                                </a>
                                                @if ($company->user)
                                                    <form method="POST" action="{{ route('admin.companies.sessions.revoke', $company) }}">
                                                        @csrf
                                                        <x-adminlte-button
                                                            type="submit"
                                                            theme="dark"
                                                            label="Cerrar sesiones"
                                                            size="xs"
                                                            icon="fas fa-user-lock"/>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">Todavia no hay empresas registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-adminlte-card>
            </div>
        </div>
    </div>

    <div class="modal fade" id="company-edit-modal" tabindex="-1" aria-labelledby="company-edit-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="company-edit-form">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header">
                        <h5 class="modal-title" id="company-edit-modal-label">Editar empresa</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label for="company-edit-locale">Idioma del portal</label>
                            <select name="locale" id="company-edit-locale" class="form-control" required>
                                <option value="es">Espanol</option>
                                <option value="en">English</option>
                            </select>
                        </div>

                        <div class="form-group mt-3 mb-0">
                            <label for="company-edit-status">Estado de servicio</label>
                            <select name="status" id="company-edit-status" class="form-control" required>
                                <option value="active">Activa</option>
                                <option value="inactive">Inactiva</option>
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
    const form = document.getElementById('company-edit-form');
    const nameField = document.getElementById('company-edit-name');
    const localeField = document.getElementById('company-edit-locale');
    const statusField = document.getElementById('company-edit-status');

    document.querySelectorAll('.js-open-company-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            form.action = button.dataset.action;
            nameField.textContent = button.dataset.company || 'Empresa';
            localeField.value = button.dataset.locale || 'es';
            statusField.value = button.dataset.status || 'active';
        });
    });
});
</script>
@stop

@section('footer')
    <strong>Integracion.</strong> Gestion interna de empresas.
@stop

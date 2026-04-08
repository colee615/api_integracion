@extends('adminlte::page')

@section('title', 'Empresas')

@section('css')
    @include('admin.partials.enterprise-theme')
@stop

@section('content_header')
    <section class="page-hero">
        <span class="page-kicker"><i class="fas fa-building"></i> Gestion comercial</span>
        <h1 class="page-title">Gestion de empresas</h1>
        <p class="page-subtitle">Administra clientes, credenciales de acceso, estado operativo, idioma del portal y sesiones activas con una vista clara para supervision y soporte.</p>
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
                        <x-adminlte-button type="submit" theme="primary" label="Crear empresa" icon="fas fa-save"/>
                    </form>
                </x-adminlte-card>
            </div>

            <div class="col-md-8">
                <x-adminlte-card title="Cartera empresarial" theme="light" icon="fas fa-briefcase" class="panel-card">
                    <div class="section-note mb-3">
                        Vista consolidada para atencion interna, monitoreo de cuentas, control de idioma y accesos empresariales.
                    </div>

                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Acceso</th>
                                    <th>Idioma</th>
                                    <th>Operacion</th>
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
                                            <strong>{{ $company->user?->email ?? 'Sin usuario' }}</strong><br>
                                            <small class="text-muted">Ultimo ingreso: {{ $company->user?->last_login_at?->format('Y-m-d H:i') ?? 'Sin ingresos' }}</small>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.companies.locale', $company) }}">
                                                @csrf
                                                @method('PATCH')
                                                <div class="d-flex align-items-center" style="gap:8px;">
                                                    <select name="locale" class="form-control form-control-sm">
                                                        <option value="es" @selected(($company->locale ?? 'es') === 'es')>Espanol</option>
                                                        <option value="en" @selected(($company->locale ?? 'es') === 'en')>English</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-xs btn-primary">Guardar</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="stat-pair">
                                                <div class="stat-box">
                                                    <span class="label">Estado</span>
                                                    <span class="value">{{ $company->status === 'active' ? 'Activa' : 'Inactiva' }}</span>
                                                </div>
                                                <div class="stat-box">
                                                    <span class="label">Carga</span>
                                                    <span class="value">{{ $company->packages_count }}/{{ $company->movements_count }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="detail-chip"><i class="fas fa-user-clock"></i> {{ $sessionCounts[$company->user?->id] ?? 0 }} activas</span>
                                        </td>
                                        <td>
                                            <div class="action-stack">
                                                <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-xs btn-info">
                                                    <i class="fas fa-eye"></i> Ver detalle
                                                </a>
                                                <form method="POST" action="{{ route('admin.companies.status', $company) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="{{ $company->status === 'active' ? 'inactive' : 'active' }}">
                                                    <x-adminlte-button type="submit" theme="{{ $company->status === 'active' ? 'secondary' : 'success' }}" label="{{ $company->status === 'active' ? 'Desactivar' : 'Activar' }}" size="xs" icon="fas fa-exchange-alt"/>
                                                </form>
                                                @if ($company->user)
                                                    <form method="POST" action="{{ route('admin.companies.sessions.revoke', $company) }}">
                                                        @csrf
                                                        <x-adminlte-button type="submit" theme="dark" label="Cerrar sesiones" size="xs" icon="fas fa-user-lock"/>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted">Todavia no hay empresas registradas.</td>
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
    <strong>API Integracion.</strong> Gestion interna de empresas.
@stop

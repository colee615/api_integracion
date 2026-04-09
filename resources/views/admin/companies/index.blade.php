@extends('adminlte::page')

@section('title', 'Empresas')

@section('css')
    @include('admin.partials.enterprise-theme')
    <style>
        .company-delete-note {
            margin-top: .75rem;
            padding: .85rem 1rem;
            border-radius: .9rem;
            border: 1px solid #fecaca;
            background: linear-gradient(180deg, #fff5f5 0%, #fff 100%);
            color: #991b1b;
        }

        .company-delete-note .title {
            display: block;
            font-weight: 800;
            margin-bottom: .2rem;
        }

        .company-delete-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
            margin-top: 1rem;
        }

        .company-delete-stat {
            padding: .85rem .95rem;
            border-radius: .85rem;
            border: 1px solid #f5d0d0;
            background: #fff;
        }

        .company-delete-stat .label {
            display: block;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #7f1d1d;
            margin-bottom: .25rem;
        }

        .company-delete-stat .value {
            display: block;
            font-size: 1.1rem;
            font-weight: 800;
            color: #111827;
        }

        .company-delete-hint {
            margin-top: 1rem;
            color: #64748b;
            font-size: .9rem;
        }

        @media (max-width: 768px) {
            .company-delete-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@stop

@section('content_header')
    <section class="page-hero">
        <span class="page-kicker"><i class="fas fa-building"></i> Gestion comercial</span>
        <h1 class="page-title">Gestion de empresas</h1>
    </section>
@stop

@section('content')
    <div class="page-shell">
        @if (session('company_delete_error'))
            @php($deleteError = session('company_delete_error'))
            <div class="company-delete-note mb-3">
                <span class="title">Eliminacion bloqueada: {{ $deleteError['company'] ?? 'Empresa' }}</span>
                <span>{{ $deleteError['message'] ?? 'No se pudo eliminar la empresa.' }}</span>

                @if (! empty($deleteError['summary']))
                    <div class="company-delete-grid">
                        <div class="company-delete-stat">
                            <span class="label">CN31</span>
                            <span class="value">{{ $deleteError['summary']['manifests'] ?? 0 }}</span>
                        </div>
                        <div class="company-delete-stat">
                            <span class="label">Sacas</span>
                            <span class="value">{{ $deleteError['summary']['bags'] ?? 0 }}</span>
                        </div>
                        <div class="company-delete-stat">
                            <span class="label">CN33</span>
                            <span class="value">{{ $deleteError['summary']['cn33_packages'] ?? 0 }}</span>
                        </div>
                        <div class="company-delete-stat">
                            <span class="label">Paquetes</span>
                            <span class="value">{{ $deleteError['summary']['packages'] ?? 0 }}</span>
                        </div>
                        <div class="company-delete-stat">
                            <span class="label">Movimientos</span>
                            <span class="value">{{ $deleteError['summary']['movements'] ?? 0 }}</span>
                        </div>
                        <div class="company-delete-stat">
                            <span class="label">Resultado</span>
                            <span class="value">Bloqueado</span>
                        </div>
                    </div>
                @endif
            </div>
        @endif

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
                                                <button
                                                    type="button"
                                                    class="btn btn-xs btn-danger js-open-company-delete-modal"
                                                    data-toggle="modal"
                                                    data-target="#company-delete-modal"
                                                    data-company="{{ $company->name }}"
                                                    data-action="{{ route('admin.companies.destroy', $company) }}"
                                                    data-manifests="{{ $company->cn31_manifests_count ?? 0 }}"
                                                    data-bags="{{ $company->cn31_bags_count ?? 0 }}"
                                                    data-cn33="{{ $company->cn33_packages_count ?? 0 }}"
                                                    data-packages="{{ $company->packages_count ?? 0 }}"
                                                    data-movements="{{ $company->movements_count ?? 0 }}">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
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

    <div class="modal fade" id="company-delete-modal" tabindex="-1" aria-labelledby="company-delete-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="company-delete-form">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title" id="company-delete-modal-label">Eliminar empresa</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div id="company-delete-allowed">
                            <p class="mb-0">
                                Esta accion eliminara la empresa <strong id="company-delete-name"></strong>, su usuario portal y sus tokens.
                            </p>
                            <p class="company-delete-hint mb-0">
                                Solo se permite cuando no existe ningun paquete ni carga operativa registrada.
                            </p>
                        </div>

                        <div id="company-delete-blocked" class="company-delete-note d-none">
                            <span class="title">No se puede eliminar esta empresa.</span>
                            <span>Ya tiene carga operativa registrada y debe conservarse para trazabilidad.</span>

                            <div class="company-delete-grid">
                                <div class="company-delete-stat">
                                    <span class="label">CN31</span>
                                    <span class="value" id="company-delete-manifests">0</span>
                                </div>
                                <div class="company-delete-stat">
                                    <span class="label">Sacas</span>
                                    <span class="value" id="company-delete-bags">0</span>
                                </div>
                                <div class="company-delete-stat">
                                    <span class="label">CN33</span>
                                    <span class="value" id="company-delete-cn33">0</span>
                                </div>
                                <div class="company-delete-stat">
                                    <span class="label">Paquetes</span>
                                    <span class="value" id="company-delete-packages">0</span>
                                </div>
                                <div class="company-delete-stat">
                                    <span class="label">Movimientos</span>
                                    <span class="value" id="company-delete-movements">0</span>
                                </div>
                                <div class="company-delete-stat">
                                    <span class="label">Estado</span>
                                    <span class="value">Bloqueado</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="company-delete-submit">
                            <i class="fas fa-trash-alt"></i> Eliminar empresa
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
    const localeField = document.getElementById('company-edit-locale');
    const statusField = document.getElementById('company-edit-status');
    const deleteForm = document.getElementById('company-delete-form');
    const deleteName = document.getElementById('company-delete-name');
    const deleteSubmit = document.getElementById('company-delete-submit');
    const deleteAllowed = document.getElementById('company-delete-allowed');
    const deleteBlocked = document.getElementById('company-delete-blocked');
    const deleteManifests = document.getElementById('company-delete-manifests');
    const deleteBags = document.getElementById('company-delete-bags');
    const deleteCn33 = document.getElementById('company-delete-cn33');
    const deletePackages = document.getElementById('company-delete-packages');
    const deleteMovements = document.getElementById('company-delete-movements');

    document.querySelectorAll('.js-open-company-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            form.action = button.dataset.action;
            localeField.value = button.dataset.locale || 'es';
            statusField.value = button.dataset.status || 'active';
        });
    });

    document.querySelectorAll('.js-open-company-delete-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            const manifests = Number(button.dataset.manifests || 0);
            const bags = Number(button.dataset.bags || 0);
            const cn33 = Number(button.dataset.cn33 || 0);
            const packages = Number(button.dataset.packages || 0);
            const movements = Number(button.dataset.movements || 0);
            const canDelete = manifests === 0 && bags === 0 && cn33 === 0 && packages === 0 && movements === 0;

            deleteForm.action = button.dataset.action;
            deleteName.textContent = button.dataset.company || 'esta empresa';
            deleteManifests.textContent = manifests;
            deleteBags.textContent = bags;
            deleteCn33.textContent = cn33;
            deletePackages.textContent = packages;
            deleteMovements.textContent = movements;
            deleteSubmit.disabled = !canDelete;
            deleteAllowed.classList.toggle('d-none', !canDelete);
            deleteBlocked.classList.toggle('d-none', canDelete);
        });
    });
});
</script>
@stop

@section('footer')
    <strong>Integracion.</strong> Gestion interna de empresas.
@stop

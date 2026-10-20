@extends('adminlte::page')

@section('title', 'Credenciales API')

@section('content_header')
    <section class="page-hero">
        <span class="page-kicker"><i class="fas fa-plug"></i> Configuración y accesos</span>
        <h1 class="page-title">Credenciales API</h1>
        <p class="text-muted mb-0">Consulta las rutas disponibles, revisa qué permisos necesita cada API y administra los tokens por empresa.</p>
    </section>
@stop

@section('content')
    <div class="page-shell">
        <div class="row mb-3">
            <div class="col-12">
                <x-adminlte-card title="Catálogo de APIs disponibles" theme="light" icon="fas fa-code" class="panel-card">
                    <div class="mb-3 text-right">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.tokens.openapi') }}">
                            <i class="fas fa-file-download mr-1"></i> Descargar OpenAPI
                        </a>
                    </div>
                    <div class="section-note mb-3">
                        Base de integración: <code>{{ url('/api/v1') }}</code>.
                        Las rutas del catálogo se autentican con <code>Authorization: Bearer {token}</code>; cada token queda limitado a su empresa y a sus permisos.
                    </div>
                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Grupo</th>
                                    <th>Método</th>
                                    <th>URL para consultar o integrar</th>
                                    <th>Descripción</th>
                                    <th>Permiso requerido</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($apiCatalog as $api)
                                    <tr>
                                        <td>{{ $api['group'] }}</td>
                                        <td>
                                            <span class="badge badge-{{ $api['method'] === 'GET' ? 'success' : ($api['method'] === 'POST' ? 'warning' : 'secondary') }}">
                                                {{ $api['method'] }}
                                            </span>
                                        </td>
                                        <td><code>{{ url($api['path']) }}</code></td>
                                        <td>{{ $api['description'] }}</td>
                                        <td><span class="badge badge-light">{{ $api['ability'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Las rutas del portal de empresa y las rutas internas del sistema usan autenticación distinta y no forman parte de estas credenciales Bearer.</small>
                </x-adminlte-card>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <x-adminlte-card title="Crear credencial" theme="light" icon="fas fa-key" class="panel-card form-zone">
                    <div class="section-note mb-3">
                        Este es el unico mecanismo habilitado para integracion. El mismo token se usa en <code>Authorization: Bearer {token}</code> para todas las APIs.
                    </div>

                    <form method="POST" action="{{ route('admin.tokens.store') }}">
                        @csrf
                        <x-adminlte-select name="company_id" label="Empresa">
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </x-adminlte-select>
                        <x-adminlte-input name="name" label="Nombre del token" value="{{ old('name') }}" placeholder="Token Produccion 360 Lions" required />
                        <x-adminlte-select name="token_type" label="Tipo de token">
                            <option value="production" @selected(old('token_type', 'production') === 'production')>Produccion</option>
                            <option value="trial" @selected(old('token_type') === 'trial')>Prueba aislada</option>
                        </x-adminlte-select>
                        <x-adminlte-input name="starts_at" type="datetime-local" label="Fecha inicio" value="{{ old('starts_at', now()->format('Y-m-d\\TH:i')) }}" required />
                        <x-adminlte-input name="expires_at" type="datetime-local" label="Fecha fin" value="{{ old('expires_at', now()->addDays(30)->format('Y-m-d\\TH:i')) }}" required />
                        <div class="form-group">
                            <label>Permisos del token</label>
                            @php($selectedAbilities = is_array(old('abilities')) ? old('abilities') : ['tracking:read', 'tracking:write'])
                            @foreach ([
                                'tracking:read' => 'Consultar envios e historial',
                                'tracking:write' => 'Registrar envios',
                                'tracking:events:write' => 'Agregar eventos de tracking',
                                'documents:read' => 'Consultar CN31/CN33',
                                'documents:write' => 'Crear documentos y manifiestos',
                                'webhooks:manage' => 'Administrar webhooks',
                            ] as $ability => $label)
                                <div class="custom-control custom-checkbox mb-1">
                                    <input class="custom-control-input" type="checkbox" name="abilities[]" id="ability-{{ str_replace(':', '-', $ability) }}" value="{{ $ability }}" @checked(in_array($ability, $selectedAbilities, true))>
                                    <label class="custom-control-label" for="ability-{{ str_replace(':', '-', $ability) }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        <x-adminlte-button type="submit" theme="primary" label="Guardar token" icon="fas fa-save" class="table-style-submit"/>
                    </form>
                </x-adminlte-card>
            </div>

            <div class="col-md-8">
                <x-adminlte-card title="Inventario de tokens emitidos" theme="light" icon="fas fa-list" class="panel-card">
                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Token</th>
                                    <th>Vigencia</th>
                                    <th>Uso</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tokens as $token)
                                    <tr>
                                        <td>
                                            <strong>{{ $token->company->name }}</strong><br>
                                            <small class="text-muted">ID token #{{ $token->id }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $token->name }}</strong><br>
                                            <small class="text-muted">{{ $token->isTrial() ? 'Token de prueba con purga automatica' : 'Token principal de integracion' }}</small>
                                            <br><small class="text-muted">Permisos: {{ implode(', ', $token->abilities ?? []) }}</small>
                                            @php($tokenApis = collect($apiCatalog)->filter(fn ($api) => $token->allows($api['ability']))->values())
                                            <details class="mt-2">
                                                <summary class="small">APIs habilitadas ({{ $tokenApis->count() }})</summary>
                                                <ul class="small pl-3 mt-2 mb-0">
                                                    @forelse ($tokenApis as $api)
                                                        <li><code>{{ $api['method'] }}</code> {{ url($api['path']) }}</li>
                                                    @empty
                                                        <li class="text-muted">Este token no habilita rutas del catálogo.</li>
                                                    @endforelse
                                                </ul>
                                            </details>
                                        </td>
                                        <td>
                                            <div><strong>Desde:</strong> {{ $token->starts_at?->format('Y-m-d H:i') ?? 'Inmediato' }}</div>
                                            <div><strong>Hasta:</strong> {{ $token->expires_at?->format('Y-m-d H:i') }}</div>
                                        </td>
                                        <td>
                                            <strong>Ultimo uso:</strong><br>
                                            <span class="text-muted">{{ $token->last_used_at?->format('Y-m-d H:i') ?? 'Sin uso' }}</span>
                                        </td>
                                        <td>
                                            @if ($token->revoked_at)
                                                <span class="badge badge-secondary">Revocado</span>
                                            @elseif ($token->isExpired())
                                                <span class="badge badge-danger">Expirado</span>
                                            @elseif (! $token->hasStarted())
                                                <span class="badge badge-info">Programado</span>
                                            @else
                                                <span class="badge badge-success">Activo</span>
                                            @endif
                                            <div class="mt-2">
                                                <span class="badge badge-{{ $token->isTrial() ? 'warning' : 'primary' }}">
                                                    {{ $token->isTrial() ? 'Prueba' : 'Produccion' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-stack">
                                                <button
                                                    type="button"
                                                    class="btn btn-xs btn-info js-open-token-view-modal"
                                                    data-company="{{ $token->company->name }}"
                                                    data-token-name="{{ $token->name }}"
                                                    data-token-secret="{{ $token->token_secret }}"
                                                    @disabled(blank($token->token_secret))>
                                                    <i class="fas fa-eye"></i> Ver token
                                                </button>

                                                <form method="POST" action="{{ route('admin.tokens.rotate', $token) }}" onsubmit="return confirm('{{ $token->isTrial() ? 'Rotar esta credencial revocará el token anterior y purgará los datos que creó. ¿Continuar?' : 'Rotar esta credencial revocará el token anterior de inmediato. ¿Continuar?' }}')">
                                                    @csrf
                                                    <x-adminlte-button type="submit" theme="warning" label="Rotar credencial" size="xs" icon="fas fa-sync-alt"/>
                                                </form>

                                                <button
                                                    type="button"
                                                    class="btn btn-xs btn-info js-open-token-modal"
                                                    data-toggle="modal"
                                                    data-target="#token-vigencia-modal"
                                                    data-action="{{ route('admin.tokens.settings', $token) }}"
                                                    data-company="{{ $token->company->name }}"
                                                    data-token-name="{{ $token->name }}"
                                                    data-starts-at="{{ $token->starts_at?->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i') }}"
                                                    data-expires-at="{{ $token->expires_at?->format('Y-m-d\\TH:i') }}"
                                                    data-status="{{ $token->revoked_at ? 'inactive' : 'active' }}">
                                                    <i class="fas fa-calendar-alt"></i> Editar vigencia
                                                </button>

                                                <form method="POST" action="{{ route('admin.tokens.destroy', $token) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-adminlte-button type="submit" theme="danger" label="Eliminar" size="xs" icon="fas fa-trash"/>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted">Todavia no hay tokens registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-adminlte-card>
            </div>
        </div>
    </div>

    <div class="modal fade" id="token-vigencia-modal" tabindex="-1" aria-labelledby="token-vigencia-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="token-vigencia-form">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header">
                        <h5 class="modal-title" id="token-vigencia-modal-label">Editar vigencia del token</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="token-vigencia-starts-at">Fecha inicio</label>
                            <input type="datetime-local" name="starts_at" id="token-vigencia-starts-at" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="token-vigencia-expires-at">Fecha fin</label>
                            <input type="datetime-local" name="expires_at" id="token-vigencia-expires-at" class="form-control" required>
                        </div>

                        <div class="form-group mb-0">
                            <label for="token-vigencia-status">Estado del token</label>
                            <select name="status" id="token-vigencia-status" class="form-control" required>
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save"></i> Guardar vigencia
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop

@section('js')
<script>
function initTokenPage() {
    const modalForm = document.getElementById('token-vigencia-form');
    const startsAtField = document.getElementById('token-vigencia-starts-at');
    const expiresAtField = document.getElementById('token-vigencia-expires-at');
    const statusField = document.getElementById('token-vigencia-status');

    document.querySelectorAll('.js-open-token-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            modalForm.action = button.dataset.action;
            startsAtField.value = button.dataset.startsAt || '';
            expiresAtField.value = button.dataset.expiresAt || '';
            statusField.value = button.dataset.status || 'active';
        });
    });

    document.querySelectorAll('.js-open-token-view-modal').forEach(function (button) {
        button.addEventListener('click', async function () {
            const tokenSecret = button.dataset.tokenSecret || '';
            const tokenName = button.dataset.tokenName || 'Token de integracion';
            const companyName = button.dataset.company || 'Empresa';

            if (! tokenSecret) {
                return;
            }

            if (window.Swal) {
                const result = await window.Swal.fire({
                    title: 'Ver token',
                    width: 760,
                    padding: '2.2rem 2rem 1.8rem',
                    background: '#ffffff',
                    confirmButtonText: 'Cerrar',
                    confirmButtonColor: '#1f4fb8',
                    showCancelButton: true,
                    cancelButtonText: 'Copiar token',
                    cancelButtonColor: '#17a2b8',
                    customClass: {
                        popup: 'corp-swal-popup',
                        title: 'corp-swal-title',
                        confirmButton: 'corp-swal-confirm'
                    },
                    html: `
                        <div style="display:flex;flex-direction:column;gap:18px;text-align:left;">
                            <div style="display:flex;align-items:center;gap:14px;padding:16px 18px;border:1px solid #d9e4f5;border-radius:18px;background:linear-gradient(180deg,#f8fbff 0%,#eef4ff 100%);">
                                <div style="width:58px;height:58px;border-radius:18px;display:flex;align-items:center;justify-content:center;background:#1f4fb8;color:#fff;font-size:24px;font-weight:800;">#</div>
                                <div>
                                    <div style="font-size:14px;text-transform:uppercase;letter-spacing:.08em;color:#6b87bd;font-weight:800;">Token activo</div>
                                    <div style="font-size:18px;font-weight:800;color:#173f9f;">${tokenName}</div>
                                    <div style="margin-top:2px;color:#5a7bbd;">Empresa: ${companyName}</div>
                                </div>
                            </div>
                            <div>
                                <label style="display:block;margin-bottom:8px;font-weight:800;color:#173f9f;">Token</label>
                                <textarea readonly style="width:100%;min-height:120px;padding:14px 16px;border:1px solid #d9e4f5;border-radius:16px;background:#f8fbff;color:#163f9d;resize:none;line-height:1.5;">${tokenSecret}</textarea>
                                <p style="margin:12px 0 0;color:#5a7bbd;font-size:.94rem;line-height:1.5;">Usa este valor en <code>Authorization: Bearer {token}</code>.</p>
                            </div>
                        </div>
                    `
                });

                if (result.dismiss === window.Swal.DismissReason.cancel) {
                    if (navigator.clipboard?.writeText) {
                        await navigator.clipboard.writeText(tokenSecret);
                    } else {
                        window.prompt('Copia el token:', tokenSecret);
                    }
                }

                return;
            }

            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(tokenSecret);
            }

            window.prompt('Copia el token:', tokenSecret);
        });
    });

    @if (session('new_token'))
        if (window.Swal) {
            window.Swal.fire({
                title: 'Token generado',
                width: 760,
                padding: '2.2rem 2rem 1.8rem',
                background: '#ffffff',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#1f4fb8',
                customClass: {
                    popup: 'corp-swal-popup',
                    title: 'corp-swal-title',
                    confirmButton: 'corp-swal-confirm'
                },
                html: `
                    <div style="display:flex;flex-direction:column;gap:18px;text-align:left;">
                        <div style="display:flex;align-items:center;gap:14px;padding:16px 18px;border:1px solid #d9e4f5;border-radius:18px;background:linear-gradient(180deg,#f8fbff 0%,#eef4ff 100%);">
                            <div style="width:58px;height:58px;border-radius:18px;display:flex;align-items:center;justify-content:center;background:#1f4fb8;color:#fff;font-size:24px;font-weight:800;">#</div>
                            <div>
                                <div style="font-size:14px;text-transform:uppercase;letter-spacing:.08em;color:#6b87bd;font-weight:800;">Nuevo token de integracion</div>
                                <div style="font-size:18px;font-weight:800;color:#173f9f;">{{ session('new_token.name') }}</div>
                                <div style="margin-top:2px;color:#5a7bbd;">Empresa: {{ session('new_token.company') }}</div>
                                <div style="margin-top:2px;color:#5a7bbd;">Tipo: {{ session('new_token.token_type') === 'trial' ? 'Prueba aislada' : 'Produccion' }}</div>
                                <div style="margin-top:2px;color:#5a7bbd;">Permisos: {{ implode(', ', session('new_token.abilities', [])) }}</div>
                            </div>
                        </div>
                        <div>
                            <label style="display:block;margin-bottom:8px;font-weight:800;color:#173f9f;">Token</label>
                            <textarea readonly style="width:100%;min-height:120px;padding:14px 16px;border:1px solid #d9e4f5;border-radius:16px;background:#f8fbff;color:#163f9d;resize:none;line-height:1.5;">{{ session('new_token.token') }}</textarea>
                            <p style="margin:12px 0 0;color:#5a7bbd;font-size:.94rem;line-height:1.5;">Usa este valor en <code>Authorization: Bearer {token}</code>. @if (session('new_token.token_type') === 'trial') Al revocarlo se eliminaran automaticamente los datos creados por esa prueba. @else Guarda este dato de forma segura antes de continuar. @endif</p>
                        </div>
                    </div>
                `,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp animate__faster'
                }
            });
        }
    @endif
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTokenPage, { once: true });
} else {
    initTokenPage();
}
</script>
@stop

@section('footer')
    <strong>Integracion.</strong> Gestion interna de tokens de integracion.
@stop

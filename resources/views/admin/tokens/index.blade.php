@extends('adminlte::page')

@section('title', 'Tokens de Integracion')

@section('css')
    @include('admin.partials.enterprise-theme')
@stop

@section('content_header')
    <section class="page-hero">
        <span class="page-kicker"><i class="fas fa-key"></i> Seguridad de integraciones</span>
        <h1 class="page-title">Gestion de tokens de integracion</h1>
    </section>
@stop

@section('content')
    <div class="page-shell">
        @if (session('new_token'))
            <x-adminlte-alert theme="success" title="Token generado" dismissable class="panel-card">
                <p class="mb-2">Empresa: {{ session('new_token.company') }} | Nombre: {{ session('new_token.name') }}</p>
                <code style="word-break: break-all;">{{ session('new_token.token') }}</code>
            </x-adminlte-alert>
        @endif

        <div class="row">
            <div class="col-md-4">
                <x-adminlte-card title="Emitir token" theme="light" icon="fas fa-key" class="panel-card form-zone">
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
                        <x-adminlte-input name="starts_at" type="datetime-local" label="Fecha inicio" value="{{ old('starts_at', now()->format('Y-m-d\\TH:i')) }}" required />
                        <x-adminlte-input name="expires_at" type="datetime-local" label="Fecha fin" value="{{ old('expires_at', now()->addDays(30)->format('Y-m-d\\TH:i')) }}" required />
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
                                            <small class="text-muted">Token principal de integracion</small>
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
                                        </td>
                                        <td>
                                            <div class="action-stack">
                                                @if ($token->token_secret)
                                                    <button
                                                    type="button"
                                                    class="btn btn-xs btn-info js-open-token-view-modal"
                                                    data-toggle="modal"
                                                    data-target="#token-view-modal"
                                                    data-company="{{ $token->company->name }}"
                                                    data-token-name="{{ $token->name }}"
                                                    data-token-secret="{{ $token->token_secret }}">
                                                        <i class="fas fa-eye"></i> Ver token
                                                    </button>
                                                @endif

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

    <div class="modal fade" id="token-view-modal" tabindex="-1" aria-labelledby="token-view-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="token-view-modal-label">Ver token</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="token-view-secret">Token</label>
                        <div class="input-group">
                            <textarea id="token-view-secret" class="form-control" rows="4" readonly></textarea>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary" id="token-view-copy-button" title="Copiar token">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">Usa este valor en <code>Authorization: Bearer {token}</code>.</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalForm = document.getElementById('token-vigencia-form');
    const startsAtField = document.getElementById('token-vigencia-starts-at');
    const expiresAtField = document.getElementById('token-vigencia-expires-at');
    const statusField = document.getElementById('token-vigencia-status');
    const tokenViewSecretField = document.getElementById('token-view-secret');
    const tokenViewCopyButton = document.getElementById('token-view-copy-button');

    document.querySelectorAll('.js-open-token-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            modalForm.action = button.dataset.action;
            startsAtField.value = button.dataset.startsAt || '';
            expiresAtField.value = button.dataset.expiresAt || '';
            statusField.value = button.dataset.status || 'active';
        });
    });

    document.querySelectorAll('.js-open-token-view-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            tokenViewSecretField.value = button.dataset.tokenSecret || '';
        });
    });

    tokenViewCopyButton?.addEventListener('click', async function () {
        await navigator.clipboard.writeText(tokenViewSecretField.value);
    });
});
</script>
@stop

@section('footer')
    <strong>Integracion.</strong> Gestion interna de tokens de integracion.
@stop

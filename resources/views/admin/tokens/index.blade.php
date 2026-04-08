@extends('adminlte::page')

@section('title', 'Tokens de Integracion')

@section('css')
    @include('admin.partials.enterprise-theme')
@stop

@section('content_header')
    <section class="page-hero">
        <span class="page-kicker"><i class="fas fa-key"></i> Seguridad de integraciones</span>
        <h1 class="page-title">Gestion de tokens de integracion</h1>
        <p class="page-subtitle">Cada empresa consume todas las APIs con un solo token manual. Desde aqui puedes emitirlo, revisar su uso y administrarlo.</p>
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
                        <x-adminlte-button type="submit" theme="primary" label="Generar token" icon="fas fa-key"/>
                    </form>
                </x-adminlte-card>
            </div>

            <div class="col-md-8">
                <x-adminlte-card title="Inventario de tokens emitidos" theme="light" icon="fas fa-list" class="panel-card">
                    <div class="section-note mb-3">
                        Cada fila representa el token real que la empresa debe usar en todas las APIs.
                    </div>

                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Token</th>
                                    <th>Valor</th>
                                    <th>Vigencia</th>
                                    <th>Uso</th>
                                    <th>Estado</th>
                                    <th>Edicion</th>
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
                                        <td class="token-cell" style="min-width: 290px;">
                                            <div class="input-group input-group-sm">
                                                <input type="password"
                                                       class="form-control js-token-secret"
                                                       value="{{ $token->token_secret }}"
                                                       data-masked="{{ $token->maskedToken() }}"
                                                       readonly>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary js-toggle-token" title="Ver token">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary js-copy-token" title="Copiar token">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
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
                                        <td style="min-width: 210px;">
                                            <form method="POST" action="{{ route('admin.tokens.extend', $token) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="datetime-local" name="starts_at" class="form-control form-control-sm mb-2" value="{{ $token->starts_at?->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i') }}" required>
                                                <input type="datetime-local" name="expires_at" class="form-control form-control-sm mb-2" value="{{ $token->expires_at?->format('Y-m-d\\TH:i') }}" required>
                                                <x-adminlte-button type="submit" theme="info" label="Guardar vigencia" size="xs" icon="fas fa-calendar-check"/>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="action-stack">
                                                @if (! $token->revoked_at)
                                                    <form method="POST" action="{{ route('admin.tokens.revoke', $token) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <x-adminlte-button type="submit" theme="warning" label="Revocar" size="xs" icon="fas fa-ban"/>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.tokens.reactivate', $token) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <x-adminlte-button type="submit" theme="success" label="Activar" size="xs" icon="fas fa-check"/>
                                                    </form>
                                                @endif

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
                                        <td colspan="8" class="text-muted">Todavia no hay tokens registrados.</td>
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

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-token-secret').forEach(function (input) {
        input.dataset.realValue = input.value;
        input.value = input.dataset.masked;
    });

    document.querySelectorAll('.js-toggle-token').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = button.closest('.input-group').querySelector('.js-token-secret');
            const icon = button.querySelector('i');
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            input.value = isHidden ? input.dataset.realValue : input.dataset.masked;
            icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    });

    document.querySelectorAll('.js-copy-token').forEach(function (button) {
        button.addEventListener('click', async function () {
            const input = button.closest('.input-group').querySelector('.js-token-secret');
            await navigator.clipboard.writeText(input.dataset.realValue);
        });
    });
});
</script>
@stop

@section('footer')
    <strong>API Integracion.</strong> Gestion interna de tokens de integracion.
@stop

@extends('adminlte::page')

@section('title', 'Dashboard')

@section('css')
    @include('admin.partials.enterprise-theme')
@stop

@section('content_header')
    <div class="page-shell">
        <section class="page-hero">
            <span class="page-kicker"><i class="fas fa-chart-line"></i> Centro de control</span>
            <h1 class="page-title">Dashboard ejecutivo</h1>
            <p class="page-subtitle">Supervisa la operación interna, el estado de tus clientes, la actividad logística y la salud general de las integraciones desde un solo lugar.</p>
        </section>
    </div>
@stop

@section('content')
    <div class="page-shell">
        <div class="row">
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $usersCount }}" text="Usuarios" icon="fas fa-users-cog" theme="white"/>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $companiesCount }}" text="Empresas" icon="fas fa-building" theme="white"/>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $tokensCount }}" text="Tokens" icon="fas fa-key" theme="white"/>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $packagesCount }}" text="Paquetes" icon="fas fa-box" theme="white"/>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $movementsCount }}" text="Movimientos" icon="fas fa-route" theme="white"/>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $activeSessionsCount }}" text="Sesiones" icon="fas fa-user-clock" theme="white"/>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <x-adminlte-card title="Resumen empresarial" theme="light" icon="fas fa-building" class="panel-card">
                    <div class="section-note mb-3">
                        Vista rápida del comportamiento de tus clientes y su volumen operativo actual.
                    </div>

                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Tokens</th>
                                    <th>Paquetes</th>
                                    <th>Movimientos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($companies as $company)
                                    <tr>
                                        <td>
                                            <strong>{{ $company->name }}</strong><br>
                                            <span class="badge badge-{{ $company->status === 'active' ? 'success' : 'secondary' }}">{{ $company->status }}</span>
                                        </td>
                                        <td>{{ $company->api_tokens_count }}</td>
                                        <td>{{ $company->packages_count }}</td>
                                        <td>{{ $company->movements_count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">Todavia no hay empresas registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-adminlte-card>
            </div>

            <div class="col-md-6">
                <x-adminlte-card title="Actividad reciente" theme="light" icon="fas fa-stream" class="panel-card">
                    <div class="section-note mb-3">
                        Últimos eventos reportados por la plataforma para seguimiento operativo inmediato.
                    </div>

                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Paquete</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentMovements as $movement)
                                    <tr>
                                        <td>{{ $movement->company->name }}</td>
                                        <td>{{ $movement->package->tracking_code }}</td>
                                        <td><span class="detail-chip">{{ $movement->status }}</span></td>
                                        <td>{{ $movement->occurred_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">Todavia no hay movimientos cargados.</td>
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
    <strong>API Integracion.</strong> Panel interno de monitoreo y administracion.
@stop

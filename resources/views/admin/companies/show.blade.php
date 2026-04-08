@extends('adminlte::page')

@section('title', 'Detalle de Empresa')

@section('css')
    @include('admin.partials.enterprise-theme')
    <style>
        .interactive-row {
            cursor: pointer;
        }

        .interactive-row:hover {
            background: #f3f8ff !important;
        }

        .interactive-trigger {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .4rem .7rem;
            border: 1px solid #d8e1eb;
            border-radius: .75rem;
            background: #fff;
            color: #1d4f91;
            font-size: .8rem;
            font-weight: 600;
        }

        .modal-panel {
            border: 1px solid #e2e8f0;
            border-radius: .9rem;
            background: #f8fbfd;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .detail-grid .item {
            border: 1px solid #e2e8f0;
            border-radius: .85rem;
            background: #fff;
            padding: .85rem .95rem;
        }

        .detail-grid .item .label {
            display: block;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            margin-bottom: .25rem;
        }

        .detail-grid .item .value {
            color: #0f172a;
            font-weight: 600;
            word-break: break-word;
        }

        .modal-table {
            margin-bottom: 0;
        }

        .modal-table td,
        .modal-table th {
            vertical-align: middle;
        }

        .progress-panel {
            border: 1px solid #d9e6f2;
            border-radius: 1rem;
            background: linear-gradient(135deg, #eef7ff 0%, #ffffff 100%);
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
        }

        .progress-panel .headline {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .6rem;
            flex-wrap: wrap;
        }

        .progress-panel .headline .title {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #52708f;
            font-weight: 700;
        }

        .progress-panel .headline .value {
            color: #0f172a;
            font-size: 1.9rem;
            font-weight: 800;
        }

        .progress-panel .headline .subtitle {
            width: 100%;
            color: #5f7895;
            font-size: .95rem;
            margin-top: -.25rem;
        }

        .progress-bar-shell {
            height: .95rem;
            background: #dbeafe;
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #0f766e 0%, #22c55e 100%);
            border-radius: 999px;
        }

        .package-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .8rem;
            margin-bottom: .85rem;
        }

        .package-metric {
            border: 1px solid #d9e6f2;
            border-radius: .9rem;
            background: #fff;
            padding: .9rem 1rem;
        }

        .package-metric .label {
            display: block;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6b7f95;
            margin-bottom: .35rem;
        }

        .package-metric .value {
            display: block;
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .package-metric .hint {
            display: block;
            margin-top: .25rem;
            color: #64748b;
            font-size: .82rem;
        }

        .package-metric.is-delivered {
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
            border-color: #bbf7d0;
        }

        .package-metric.is-pending {
            background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
            border-color: #fed7aa;
        }

        .progress-footnote {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            color: #64748b;
            font-size: .9rem;
        }

        .bag-progress-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem;
            margin-top: 1rem;
        }

        .bag-progress-card {
            border: 1px solid #d9e6f2;
            border-radius: 1rem;
            background: #fff;
            padding: 1rem;
        }

        .bag-progress-card .topline {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: .55rem;
        }

        .bag-progress-card .bag-name {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .bag-progress-card .bag-meta {
            color: #64748b;
            font-size: .82rem;
            margin-top: .15rem;
        }

        .bag-progress-card .bag-ratio {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
        }

        .bag-progress-card .bag-message {
            color: #334155;
            font-size: .93rem;
            margin-bottom: .55rem;
        }

        .bag-progress-card .bag-footer {
            display: flex;
            justify-content: space-between;
            gap: .8rem;
            flex-wrap: wrap;
            color: #64748b;
            font-size: .82rem;
            margin-top: .55rem;
        }

        @media (max-width: 992px) {
            .package-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .bag-progress-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .package-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
@stop

@section('content_header')
    <div class="page-shell">
        <section class="page-hero">
            <span class="page-kicker"><i class="fas fa-building"></i> Vista operativa</span>
            <h1 class="page-title">{{ $company->name }}</h1>
            <p class="page-subtitle">Desde esta vista puedes revisar lo que la empresa ha cargado en la integracion: tokens, manifiestos, sacas, paquetes y movimientos.</p>
        </section>
    </div>
@stop

@section('content')
    @php
        $manifestExplorer = $recentManifests->map(function ($manifest) {
            return [
                'id' => $manifest->id,
                'cn31_number' => $manifest->cn31_number,
                'origin_office' => $manifest->origin_office,
                'destination_office' => $manifest->destination_office,
                'dispatch_date' => $manifest->dispatch_date?->format('Y-m-d H:i'),
                'total_bags' => $manifest->total_bags,
                'total_packages' => $manifest->total_packages,
                'total_weight_kg' => (float) $manifest->total_weight_kg,
                'status' => $manifest->status,
                'delivered_at' => $manifest->meta['delivered_at'] ?? null,
                'bags' => $manifest->bags->map(function ($bag) {
                    return [
                        'id' => $bag->id,
                        'bag_number' => $bag->bag_number,
                        'status' => $bag->status,
                        'delivered_at' => $bag->meta['delivered_at'] ?? null,
                        'declared_package_count' => $bag->declared_package_count,
                        'declared_weight_kg' => (float) $bag->declared_weight_kg,
                        'loaded_package_count' => (int) ($bag->meta['loaded_package_count'] ?? 0),
                        'loaded_weight_kg' => (float) ($bag->meta['loaded_weight_kg'] ?? 0),
                        'packages' => $bag->cn33Packages->map(function ($cn33) {
                            $package = $cn33->package;

                            return [
                                'id' => $cn33->id,
                                'tracking_code' => $cn33->tracking_code,
                                'reference' => $cn33->reference,
                                'recipient_name' => $cn33->recipient_name,
                                'destination' => $cn33->destination,
                                'weight_kg' => (float) $cn33->weight_kg,
                                'status' => $cn33->status,
                                'delivered_at' => $cn33->meta['delivered_at'] ?? null,
                                'package_detail' => $package ? [
                                    'tracking_code' => $package->tracking_code,
                                    'reference' => $package->reference,
                                    'status' => $package->status,
                                    'delivered_at' => $package->meta['delivered_at'] ?? null,
                                    'sender_name' => $package->sender_name,
                                    'sender_country' => $package->sender_country,
                                    'recipient_name' => $package->recipient_name,
                                    'recipient_document' => $package->recipient_document,
                                    'recipient_phone' => $package->recipient_phone,
                                    'recipient_whatsapp' => $package->recipient_whatsapp,
                                    'recipient_address' => $package->recipient_address,
                                    'recipient_address_reference' => $package->recipient_address_reference,
                                    'recipient_city' => $package->recipient_city,
                                    'recipient_department' => $package->recipient_department,
                                    'origin_office' => $package->origin_office,
                                    'destination_office' => $package->destination_office,
                                    'description' => $package->shipment_description,
                                    'gross_weight_grams' => $package->gross_weight_grams,
                                    'weight_kg' => $package->weight_kg !== null ? (float) $package->weight_kg : null,
                                    'dimensions' => trim(collect([
                                        $package->length_cm,
                                        $package->width_cm,
                                        $package->height_cm,
                                    ])->filter(fn ($value) => $value !== null)->implode(' x ')),
                                    'value_fob_usd' => $package->value_fob_usd !== null ? (float) $package->value_fob_usd : null,
                                    'shipment_date' => $package->shipment_date?->format('Y-m-d H:i'),
                                    'registered_at' => $package->registered_at?->format('Y-m-d H:i'),
                                    'movements' => $package->movements->map(fn ($movement) => [
                                        'status' => $movement->status,
                                        'location' => $movement->location,
                                        'description' => $movement->description,
                                        'occurred_at' => $movement->occurred_at?->format('Y-m-d H:i'),
                                    ])->values(),
                                ] : null,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values();

        $bagDeliveryProgress = $recentBags->map(function ($bag) {
            $declared = (int) $bag->declared_package_count;
            $delivered = $bag->cn33Packages->where('status', 'entregado')->count();
            $pending = max($declared - $delivered, 0);
            $pct = $declared > 0 ? round(($delivered / $declared) * 100, 1) : 0.0;

            if ($declared === 0) {
                $message = 'Esta saca no tiene paquetes declarados.';
            } elseif ($delivered === 0) {
                $message = "No se entrego ningun paquete de esta saca. Faltan {$pending}.";
            } elseif ($pending === 0) {
                $message = "Todos los {$declared} paquetes de esta saca fueron entregados.";
            } else {
                $message = "Se entregaron {$delivered} de {$declared} paquetes. Faltan {$pending}.";
            }

            return [
                'bag_number' => $bag->bag_number,
                'manifest_number' => $bag->manifest?->cn31_number,
                'status' => $bag->status,
                'declared' => $declared,
                'delivered' => $delivered,
                'pending' => $pending,
                'pct' => $pct,
                'message' => $message,
            ];
        });
    @endphp

    <div class="page-shell">
        <div class="row">
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $summary['tokens'] }}" text="Tokens" icon="fas fa-key" theme="white"/>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $summary['manifests'] }}" text="CN31" icon="fas fa-file-alt" theme="white"/>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $summary['bags'] }}" text="Sacas" icon="fas fa-shopping-bag" theme="white"/>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $summary['cn33_packages'] }}" text="CN33" icon="fas fa-boxes" theme="white"/>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $summary['packages'] }}" text="Paquetes" icon="fas fa-box" theme="white"/>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $summary['movements'] }}" text="Movimientos" icon="fas fa-route" theme="white"/>
                </div>
            </div>
        </div>

        <div class="progress-panel">
            <div class="headline">
                <span class="title">Avance de entrega</span>
                <span class="value">{{ $summary['delivered_packages'] }} / {{ $summary['packages'] }} paquetes</span>
                <span class="subtitle">Seguimiento detallado de paquetes entregados frente al total recibido desde la empresa.</span>
            </div>
            <div class="progress-bar-shell">
                <div class="progress-bar-fill" style="width: {{ $summary['delivery_progress_pct'] }}%;"></div>
            </div>
            <div class="package-metrics">
                <div class="package-metric">
                    <span class="label">Total recibido</span>
                    <span class="value">{{ $summary['packages'] }}</span>
                    <span class="hint">Paquetes cargados por la empresa</span>
                </div>
                <div class="package-metric is-delivered">
                    <span class="label">Entregados</span>
                    <span class="value">{{ $summary['delivered_packages'] }}</span>
                    <span class="hint">{{ $summary['delivery_progress_pct'] }}% del total</span>
                </div>
                <div class="package-metric is-pending">
                    <span class="label">Pendientes</span>
                    <span class="value">{{ $summary['pending_delivery_packages'] }}</span>
                    <span class="hint">Aun no marcados como entregados</span>
                </div>
                <div class="package-metric">
                    <span class="label">Relacion actual</span>
                    <span class="value">{{ $summary['delivered_packages'] }} : {{ $summary['pending_delivery_packages'] }}</span>
                    <span class="hint">Entregados vs pendientes</span>
                </div>
            </div>
            <div class="progress-footnote">
                <span>{{ $summary['delivered_bags'] }} / {{ $summary['bags'] }} sacas completadas</span>
                <span>{{ $summary['delivered_manifests'] }} / {{ $summary['manifests'] }} manifiestos completados</span>
            </div>
            @if($bagDeliveryProgress->isNotEmpty())
                <div class="bag-progress-grid">
                    @foreach($bagDeliveryProgress as $bagProgress)
                        <div class="bag-progress-card">
                            <div class="topline">
                                <div>
                                    <div class="bag-name">{{ $bagProgress['bag_number'] }}</div>
                                    <div class="bag-meta">{{ $bagProgress['manifest_number'] ?? 'Sin CN31' }} | Estado: {{ $bagProgress['status'] }}</div>
                                </div>
                                <div class="bag-ratio">{{ $bagProgress['delivered'] }} / {{ $bagProgress['declared'] }}</div>
                            </div>
                            <div class="bag-message">{{ $bagProgress['message'] }}</div>
                            <div class="progress-bar-shell" style="margin-bottom: .55rem;">
                                <div class="progress-bar-fill" style="width: {{ $bagProgress['pct'] }}%;"></div>
                            </div>
                            <div class="bag-footer">
                                <span>{{ $bagProgress['pct'] }}% entregado</span>
                                <span>{{ $bagProgress['pending'] }} pendientes</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="row">
            <div class="col-md-4">
                <x-adminlte-card title="Ficha de empresa" theme="light" icon="fas fa-id-card" class="panel-card">
                    <div class="stat-pair mb-3">
                        <div class="stat-box">
                            <span class="label">Estado</span>
                            <span class="value">{{ $company->status === 'active' ? 'Activa' : 'Inactiva' }}</span>
                        </div>
                        <div class="stat-box">
                            <span class="label">Idioma</span>
                            <span class="value">{{ strtoupper($company->locale ?? 'es') }}</span>
                        </div>
                    </div>

                    <div class="section-note mb-3">
                        <strong>Slug:</strong> {{ $company->slug }}<br>
                        <strong>API Key:</strong> <code>{{ $company->api_key }}</code><br>
                        <strong>Entorno:</strong> {{ $company->environment ?? 'sandbox' }}
                    </div>

                    <div class="section-note mb-3">
                        <strong>Contacto:</strong> {{ $company->contact_name ?: 'Sin dato' }}<br>
                        <strong>Email:</strong> {{ $company->contact_email ?: 'Sin dato' }}<br>
                        <strong>Telefono:</strong> {{ $company->contact_phone ?: 'Sin dato' }}
                    </div>

                    <div class="section-note mb-3">
                        <strong>Usuario portal:</strong> {{ $company->user?->email ?? 'Sin usuario' }}<br>
                        <strong>Ultimo ingreso:</strong> {{ $company->user?->last_login_at?->format('Y-m-d H:i') ?? 'Sin ingresos' }}<br>
                        <strong>Sesiones activas:</strong> {{ $sessionCount }}
                    </div>

                    <div class="section-note">
                        <strong>Tokens activos:</strong> {{ $summary['active_tokens'] }} de {{ $summary['tokens'] }}
                    </div>
                </x-adminlte-card>

                <x-adminlte-card title="Tokens emitidos" theme="light" icon="fas fa-key" class="panel-card">
                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Uso</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($company->apiTokens as $token)
                                    <tr>
                                        <td>
                                            <strong>{{ $token->name }}</strong><br>
                                            <small class="text-muted">{{ $token->expires_at?->format('Y-m-d H:i') }}</small>
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
                                        <td>{{ $token->last_used_at?->format('Y-m-d H:i') ?? 'Sin uso' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">No hay tokens emitidos para esta empresa.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-adminlte-card>
            </div>

            <div class="col-md-8">
                <x-adminlte-card title="Ultimos manifiestos CN31" theme="light" icon="fas fa-file-alt" class="panel-card">
                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>CN31</th>
                                    <th>Ruta</th>
                                    <th>Totales</th>
                                    <th>Estado</th>
                                    <th>Explorar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentManifests as $manifest)
                                    <tr class="interactive-row" onclick="openManifestModal({{ $manifest->id }})">
                                        <td>
                                            <strong>{{ $manifest->cn31_number }}</strong><br>
                                            <small class="text-muted">{{ $manifest->dispatch_date?->format('Y-m-d H:i') }}</small>
                                            @if(($manifest->meta['delivered_at'] ?? null))
                                                <br><small class="text-success">Entregado: {{ $manifest->meta['delivered_at'] }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $manifest->origin_office }} -> {{ $manifest->destination_office }}</td>
                                        <td>{{ $manifest->total_bags }} sacas / {{ $manifest->total_packages }} paquetes</td>
                                        <td><span class="detail-chip">{{ $manifest->status }}</span></td>
                                        <td>
                                            <button type="button" class="interactive-trigger" onclick="event.stopPropagation(); openManifestModal({{ $manifest->id }})">
                                                <i class="fas fa-layer-group"></i> Ver sacas
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">Todavia no hay manifiestos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-adminlte-card>

                <div class="row">
                    <div class="col-md-6">
                        <x-adminlte-card title="Ultimas sacas" theme="light" icon="fas fa-shopping-bag" class="panel-card">
                            <div class="table-responsive">
                                <table class="table corp-table">
                                    <thead>
                                        <tr>
                                            <th>Saca</th>
                                            <th>Manifesto</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentBags as $bag)
                                            <tr>
                                                <td>
                                                    <strong>{{ $bag->bag_number }}</strong><br>
                                                    <small class="text-muted">{{ $bag->declared_package_count }} paquetes</small>
                                                    @if(($bag->meta['delivered_at'] ?? null))
                                                        <br><small class="text-success">Entregado: {{ $bag->meta['delivered_at'] }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $bag->manifest?->cn31_number ?? 'Sin manifiesto' }}</td>
                                                <td><span class="detail-chip">{{ $bag->status }}</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-muted">Todavia no hay sacas registradas.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-adminlte-card>
                    </div>

                    <div class="col-md-6">
                        <x-adminlte-card title="Ultimos items CN33" theme="light" icon="fas fa-boxes" class="panel-card">
                            <div class="table-responsive">
                                <table class="table corp-table">
                                    <thead>
                                        <tr>
                                            <th>Tracking</th>
                                            <th>Saca</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentCn33Packages as $cn33)
                                            <tr>
                                                <td>
                                                    <strong>{{ $cn33->tracking_code }}</strong><br>
                                                    <small class="text-muted">{{ $cn33->recipient_name }}</small>
                                                    @if(($cn33->meta['delivered_at'] ?? null))
                                                        <br><small class="text-success">Entregado: {{ $cn33->meta['delivered_at'] }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $cn33->bag?->bag_number ?? 'Sin saca' }}</td>
                                                <td><span class="detail-chip">{{ $cn33->status }}</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-muted">Todavia no hay items CN33 registrados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-adminlte-card>
                    </div>
                </div>

                <x-adminlte-card title="Paquetes recientes" theme="light" icon="fas fa-box" class="panel-card">
                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Tracking</th>
                                    <th>Destinatario</th>
                                    <th>Destino</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPackages as $package)
                                    <tr>
                                        <td>
                                            <strong>{{ $package->tracking_code }}</strong><br>
                                            <small class="text-muted">{{ $package->reference ?: 'Sin referencia' }}</small>
                                            @if(($package->meta['delivered_at'] ?? null))
                                                <br><small class="text-success">Entregado: {{ $package->meta['delivered_at'] }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $package->recipient_name }}</td>
                                        <td>{{ $package->destination ?: ($package->recipient_city ?: 'Sin destino') }}</td>
                                        <td><span class="detail-chip">{{ $package->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">Todavia no hay paquetes registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-adminlte-card>

                <x-adminlte-card title="Movimientos recientes" theme="light" icon="fas fa-route" class="panel-card">
                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Tracking</th>
                                    <th>Estado</th>
                                    <th>Ubicacion</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentMovements as $movement)
                                    <tr>
                                        <td>{{ $movement->package?->tracking_code ?? 'Sin paquete' }}</td>
                                        <td><span class="detail-chip">{{ $movement->status }}</span></td>
                                        <td>{{ $movement->location ?: 'Sin ubicacion' }}</td>
                                        <td>{{ $movement->occurred_at?->format('Y-m-d H:i') ?? 'Sin fecha' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">Todavia no hay movimientos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-adminlte-card>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manifestExplorerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manifestExplorerTitle">Sacas del CN31</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="manifestExplorerBody"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bagExplorerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bagExplorerTitle">Paquetes de la saca</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="bagExplorerBody"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="packageExplorerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="packageExplorerTitle">Detalle del paquete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="packageExplorerBody"></div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        const manifestExplorerData = @json($manifestExplorer);

        function escapeHtml(value) {
            if (value === null || value === undefined || value === '') {
                return 'Sin dato';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function badge(status) {
            return `<span class="detail-chip">${escapeHtml(status)}</span>`;
        }

        function findManifest(manifestId) {
            return manifestExplorerData.find((item) => item.id === manifestId);
        }

        function findBag(manifestId, bagId) {
            const manifest = findManifest(manifestId);
            return manifest ? manifest.bags.find((bag) => bag.id === bagId) : null;
        }

        function findPackage(manifestId, bagId, packageId) {
            const bag = findBag(manifestId, bagId);
            return bag ? bag.packages.find((item) => item.id === packageId) : null;
        }

        function openManifestModal(manifestId) {
            const manifest = findManifest(manifestId);

            if (!manifest) {
                return;
            }

            document.getElementById('manifestExplorerTitle').textContent = `Sacas del ${manifest.cn31_number}`;

            const body = `
                <div class="modal-panel">
                    <div class="detail-grid">
                        <div class="item"><span class="label">Ruta</span><span class="value">${escapeHtml(manifest.origin_office)} -> ${escapeHtml(manifest.destination_office)}</span></div>
                        <div class="item"><span class="label">Despacho</span><span class="value">${escapeHtml(manifest.dispatch_date)}</span></div>
                        <div class="item"><span class="label">Totales</span><span class="value">${escapeHtml(manifest.total_bags)} sacas / ${escapeHtml(manifest.total_packages)} paquetes</span></div>
                        <div class="item"><span class="label">Peso total</span><span class="value">${escapeHtml(manifest.total_weight_kg)} kg</span></div>
                        <div class="item"><span class="label">Entregado</span><span class="value">${escapeHtml(manifest.delivered_at)}</span></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table corp-table modal-table">
                        <thead>
                            <tr>
                                <th>Saca</th>
                                <th>Cantidades</th>
                                <th>Peso</th>
                                <th>Estado</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${manifest.bags.length ? manifest.bags.map((bag) => `
                                <tr>
                                    <td><strong>${escapeHtml(bag.bag_number)}</strong></td>
                                    <td>${escapeHtml(bag.loaded_package_count)} cargados / ${escapeHtml(bag.declared_package_count)} declarados</td>
                                    <td>${escapeHtml(bag.loaded_weight_kg)} kg / ${escapeHtml(bag.declared_weight_kg)} kg</td>
                                    <td>${badge(bag.status)}</td>
                                    <td>
                                        <button type="button" class="interactive-trigger" onclick="openBagModal(${manifest.id}, ${bag.id})">
                                            <i class="fas fa-boxes"></i> Ver paquetes
                                        </button>
                                    </td>
                                </tr>
                            `).join('') : '<tr><td colspan="5" class="text-muted">No hay sacas para este CN31.</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `;

            document.getElementById('manifestExplorerBody').innerHTML = body;
            $('#manifestExplorerModal').modal('show');
        }

        function openBagModal(manifestId, bagId) {
            const manifest = findManifest(manifestId);
            const bag = findBag(manifestId, bagId);

            if (!manifest || !bag) {
                return;
            }

            document.getElementById('bagExplorerTitle').textContent = `Paquetes de ${bag.bag_number}`;

            const body = `
                <div class="modal-panel">
                    <div class="detail-grid">
                        <div class="item"><span class="label">CN31</span><span class="value">${escapeHtml(manifest.cn31_number)}</span></div>
                        <div class="item"><span class="label">Estado saca</span><span class="value">${escapeHtml(bag.status)}</span></div>
                        <div class="item"><span class="label">Entregado</span><span class="value">${escapeHtml(bag.delivered_at)}</span></div>
                        <div class="item"><span class="label">Paquetes</span><span class="value">${escapeHtml(bag.loaded_package_count)} cargados / ${escapeHtml(bag.declared_package_count)} declarados</span></div>
                        <div class="item"><span class="label">Peso</span><span class="value">${escapeHtml(bag.loaded_weight_kg)} kg / ${escapeHtml(bag.declared_weight_kg)} kg</span></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table corp-table modal-table">
                        <thead>
                            <tr>
                                <th>Tracking</th>
                                <th>Destinatario</th>
                                <th>Destino</th>
                                <th>Estado</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${bag.packages.length ? bag.packages.map((pkg) => `
                                <tr>
                                    <td><strong>${escapeHtml(pkg.tracking_code)}</strong><br><small class="text-muted">${escapeHtml(pkg.reference)}</small></td>
                                    <td>${escapeHtml(pkg.recipient_name)}</td>
                                    <td>${escapeHtml(pkg.destination)}</td>
                                    <td>${badge(pkg.status)}</td>
                                    <td>
                                        <button type="button" class="interactive-trigger" onclick="openPackageModal(${manifest.id}, ${bag.id}, ${pkg.id})">
                                            <i class="fas fa-search"></i> Ver detalle
                                        </button>
                                    </td>
                                </tr>
                            `).join('') : '<tr><td colspan="5" class="text-muted">No hay paquetes para esta saca.</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `;

            document.getElementById('bagExplorerBody').innerHTML = body;
            $('#bagExplorerModal').modal('show');
        }

        function openPackageModal(manifestId, bagId, packageId) {
            const pkg = findPackage(manifestId, bagId, packageId);

            if (!pkg) {
                return;
            }

            const detail = pkg.package_detail;
            document.getElementById('packageExplorerTitle').textContent = `Detalle de ${pkg.tracking_code}`;

            if (!detail) {
                document.getElementById('packageExplorerBody').innerHTML = `
                    <div class="section-note">Este tracking existe en CN33, pero todavia no tiene detalle completo de paquete.</div>
                `;
                $('#packageExplorerModal').modal('show');
                return;
            }

            const movements = detail.movements.length
                ? detail.movements.map((movement) => `
                    <tr>
                        <td>${escapeHtml(movement.status)}</td>
                        <td>${escapeHtml(movement.location)}</td>
                        <td>${escapeHtml(movement.description)}</td>
                        <td>${escapeHtml(movement.occurred_at)}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="4" class="text-muted">No hay movimientos registrados para este paquete.</td></tr>';

            document.getElementById('packageExplorerBody').innerHTML = `
                <div class="modal-panel">
                    <div class="detail-grid">
                        <div class="item"><span class="label">Tracking</span><span class="value">${escapeHtml(detail.tracking_code)}</span></div>
                        <div class="item"><span class="label">Estado</span><span class="value">${escapeHtml(detail.status)}</span></div>
                        <div class="item"><span class="label">Entregado</span><span class="value">${escapeHtml(detail.delivered_at)}</span></div>
                        <div class="item"><span class="label">Referencia</span><span class="value">${escapeHtml(detail.reference)}</span></div>
                        <div class="item"><span class="label">Fecha envio</span><span class="value">${escapeHtml(detail.shipment_date)}</span></div>
                        <div class="item"><span class="label">Remitente</span><span class="value">${escapeHtml(detail.sender_name)} (${escapeHtml(detail.sender_country)})</span></div>
                        <div class="item"><span class="label">Destinatario</span><span class="value">${escapeHtml(detail.recipient_name)} / ${escapeHtml(detail.recipient_document)}</span></div>
                        <div class="item"><span class="label">Contacto</span><span class="value">${escapeHtml(detail.recipient_phone)} / ${escapeHtml(detail.recipient_whatsapp)}</span></div>
                        <div class="item"><span class="label">Ruta</span><span class="value">${escapeHtml(detail.origin_office)} -> ${escapeHtml(detail.destination_office)}</span></div>
                        <div class="item"><span class="label">Direccion</span><span class="value">${escapeHtml(detail.recipient_address)}</span></div>
                        <div class="item"><span class="label">Referencia direccion</span><span class="value">${escapeHtml(detail.recipient_address_reference)}</span></div>
                        <div class="item"><span class="label">Ciudad / Departamento</span><span class="value">${escapeHtml(detail.recipient_city)} / ${escapeHtml(detail.recipient_department)}</span></div>
                        <div class="item"><span class="label">Descripcion</span><span class="value">${escapeHtml(detail.description)}</span></div>
                        <div class="item"><span class="label">Peso</span><span class="value">${escapeHtml(detail.gross_weight_grams)} gr / ${escapeHtml(detail.weight_kg)} kg</span></div>
                        <div class="item"><span class="label">Dimensiones</span><span class="value">${escapeHtml(detail.dimensions)}</span></div>
                        <div class="item"><span class="label">Valor FOB</span><span class="value">USD ${escapeHtml(detail.value_fob_usd)}</span></div>
                        <div class="item"><span class="label">Registrado</span><span class="value">${escapeHtml(detail.registered_at)}</span></div>
                    </div>
                </div>
                <h6 class="mb-3">Historial de movimientos</h6>
                <div class="table-responsive">
                    <table class="table corp-table modal-table">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Ubicacion</th>
                                <th>Descripcion</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>${movements}</tbody>
                    </table>
                </div>
            `;

            $('#packageExplorerModal').modal('show');
        }
    </script>
@stop

@section('footer')
    <strong>API Integracion.</strong> Detalle operativo por empresa.
@stop

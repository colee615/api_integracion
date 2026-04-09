@extends('layouts.admin')

@section('content')
    <div class="grid grid-4" style="margin-bottom: 20px;">
        <div class="card">
            <div class="muted">Empresa</div>
            <div class="stats" style="font-size: 24px;">{{ $company->name }}</div>
        </div>
        <div class="card">
            <div class="muted">Paquetes</div>
            <div class="stats">{{ $packagesCount }}</div>
        </div>
        <div class="card">
            <div class="muted">Movimientos</div>
            <div class="stats">{{ $movementsCount }}</div>
        </div>
        <div class="card">
            <div class="muted">Intentos carteros</div>
            <div class="stats">{{ $totalDeliveryAttempts }}</div>
            <div class="muted">{{ $packagesWithDeliveryAttempts }} paquete(s) con intento</div>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top: 0;">Perfil empresarial</h2>
            <p><strong>Nombre:</strong> {{ $company->name }}</p>
            <p><strong>Slug:</strong> {{ $company->slug }}</p>
            <p><strong>Estado:</strong> {{ $company->status }}</p>
            <p><strong>Correo portal:</strong> {{ auth()->user()->email }}</p>
            <p><strong>Ultimo ingreso:</strong> {{ auth()->user()->last_login_at?->format('Y-m-d H:i') ?? 'Primer ingreso' }}</p>
            <p><strong>Tokens de integracion:</strong> {{ $company->apiTokens->count() }}</p>
        </div>

        <div class="card">
            <h2 style="margin-top: 0;">Actividad reciente</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Paquete</th>
                        <th>Estado</th>
                        <th>Fecha / hora</th>
                        <th>Ubicacion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentMovements as $movement)
                        <tr>
                            <td>{{ $movement->package->tracking_code }}</td>
                            <td>{{ $statusLabel($movement->status) }}</td>
                            <td>{{ $movement->occurred_at?->format('Y-m-d H:i') ?? 'Sin dato' }}</td>
                            <td>{{ $movement->location ?? 'Sin dato' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">Todavia no hay movimientos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2 style="margin-top: 0;">Estado de paquetes</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Tracking</th>
                    <th>Estado</th>
                    <th>Intentos carteros</th>
                    <th>Fecha / hora ultimo intento</th>
                    <th>Lugar ultimo intento</th>
                    <th>Resultado ultimo intento</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentPackages as $package)
                    <tr>
                        <td>{{ $package->tracking_code }}</td>
                        <td>{{ $statusLabel($package->status) }}</td>
                        <td>{{ $package->delivery_attempts }}</td>
                        <td>{{ $package->last_delivery_attempt_at?->format('Y-m-d H:i') ?? 'Sin dato' }}</td>
                        <td>{{ $package->latestDeliveryAttemptLocation() ?? 'Sin dato' }}</td>
                        <td>{{ $package->latestDeliveryAttemptDescription() ?? 'Sin dato' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">Todavia no hay paquetes registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

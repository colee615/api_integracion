@extends('layouts.admin')

@section('content')
    <div class="grid grid-3" style="margin-bottom: 20px;">
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
                        <th>Ubicacion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentMovements as $movement)
                        <tr>
                            <td>{{ $movement->package->tracking_code }}</td>
                            <td>{{ $movement->status }}</td>
                            <td>{{ $movement->location ?? 'Sin dato' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted">Todavia no hay movimientos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@extends('adminlte::page')

@section('title', 'Control Operativo de Empresa')

@section('css')
    @include('admin.partials.enterprise-theme')
    <style>
        .ops-shell{display:grid;gap:1rem}.ops-header,.ops-card{border:1px solid #d9e6f2;border-radius:1rem;background:#fff}.ops-header{padding:1.25rem;display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;background:linear-gradient(135deg,#f8fbfd 0%,#fff 100%)}.ops-kicker,.ops-label{font-size:.74rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-weight:800}.ops-title{margin:.35rem 0 0;font-size:2rem;font-weight:900;color:#10213f}.ops-subtitle{margin:.45rem 0 0;color:#55657d;max-width:760px}.ops-actions{display:flex;gap:.6rem;flex-wrap:wrap;align-items:start}.ops-btn,.ops-btn:hover{display:inline-flex;align-items:center;gap:.45rem;min-height:2.7rem;padding:0 1rem;border-radius:.85rem;border:1px solid #d9e6f2;background:#fff;color:#1d4f91;text-decoration:none;font-weight:700}.ops-btn-primary,.ops-btn-primary:hover{background:#10213f;border-color:#10213f;color:#fff}.ops-btn-danger,.ops-btn-danger:hover{background:#fff5f5;border-color:#fecaca;color:#b42318}.ops-form-inline{display:inline-flex}.ops-meta-grid,.ops-stat-grid,.ops-explorer,.ops-detail-grid,.ops-person-grid,.ops-mini-grid,.ops-vs-grid{display:grid;gap:.85rem}.ops-meta-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.ops-stat-grid{grid-template-columns:repeat(7,minmax(0,1fr))}.ops-vs-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.ops-explorer{grid-template-columns:1.1fr 1fr 1fr}.ops-detail-grid{grid-template-columns:1.4fr .8fr}.ops-person-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ops-mini-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ops-meta,.ops-stat,.ops-panel,.ops-vs-card{padding:1rem;border:1px solid #d9e6f2;border-radius:1rem;background:#fff}.ops-meta-value,.ops-stat-value{display:block;color:#10213f;font-weight:900}.ops-meta-value{font-size:1rem;margin-top:.2rem}.ops-stat-value{font-size:1.7rem;line-height:1;margin:.3rem 0}.ops-note{color:#64748b;font-size:.88rem;line-height:1.45}.ops-search{padding:1rem;border:1px solid #d9e6f2;border-radius:1rem;background:linear-gradient(135deg,#f8fbfd 0%,#fff 100%)}.ops-search-row{display:flex;gap:.75rem;flex-wrap:wrap;align-items:end}.ops-search-field{flex:1 1 360px}.ops-search-field label{display:block;margin-bottom:.4rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-weight:800}.ops-search-field input,.ops-select{width:100%;min-height:2.9rem;border:1px solid #d9e6f2;border-radius:.85rem;padding:0 .9rem;background:#fff;font-weight:600;color:#10213f}.ops-search-result{margin-top:.75rem;color:#64748b;font-size:.92rem}.ops-search-result.is-error{color:#b42318}.ops-panel-title{margin:0 0 .75rem;font-size:1.05rem;font-weight:800;color:#10213f}.ops-list{display:grid;gap:.75rem;max-height:32rem;overflow:auto}.ops-list-card{width:100%;text-align:left;border:1px solid #d9e6f2;border-radius:1rem;background:#fff;padding:.95rem 1rem;transition:.18s ease}.ops-list-card:hover,.ops-list-card.active{border-color:#93c5fd;box-shadow:0 12px 24px rgba(37,99,235,.08);transform:translateY(-1px)}.ops-list-top{display:flex;justify-content:space-between;gap:.75rem;align-items:start;margin-bottom:.35rem}.ops-list-title{display:block;font-weight:800;color:#10213f}.ops-list-meta{display:block;color:#64748b;font-size:.85rem;line-height:1.4}.ops-empty{border:1px dashed #cbd5e1;border-radius:1rem;padding:1rem;background:#f8fbff;color:#64748b}.ops-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:.9rem}.ops-summary{border:1px solid #d9e6f2;border-radius:1rem;background:#f8fbff;padding:.9rem 1rem}.ops-summary strong{display:block;color:#10213f;font-size:1.05rem;margin-top:.2rem}.ops-person{border:1px solid #d9e6f2;border-radius:1rem;overflow:hidden;background:#fff}.ops-person-head{padding:.8rem 1rem;background:#f8fbff;border-bottom:1px solid #e6eef7;display:flex;justify-content:space-between;align-items:center}.ops-person-title{font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:#335b8e;font-weight:800}.ops-person-body{padding:1rem;display:grid;gap:.75rem}.ops-person-name{font-size:1.15rem;font-weight:900;color:#10213f}.ops-item{border:1px solid #e2e8f0;border-radius:.85rem;padding:.85rem .95rem;background:#fff}.ops-item-value{display:block;margin-top:.2rem;color:#0f172a;font-weight:700;word-break:break-word}.ops-vs-card{background:linear-gradient(180deg,#fff 0%,#f8fbff 100%)}.ops-vs-top{display:flex;justify-content:space-between;gap:.75rem;align-items:end;margin-bottom:.55rem}.ops-vs-title{font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-weight:800}.ops-vs-ratio{font-size:1.35rem;font-weight:900;color:#10213f}.ops-vs-track{height:.9rem;border-radius:999px;background:#e5edf8;overflow:hidden;margin-bottom:.65rem}.ops-vs-fill{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#0f766e 0%,#22c55e 100%)}.ops-vs-meta{display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap;color:#64748b;font-size:.86rem}.status-pill{display:inline-flex;align-items:center;justify-content:center;padding:.38rem .8rem;border-radius:999px;font-size:.82rem;font-weight:800;border:1px solid transparent;line-height:1}.status-pill.status-entregado,.status-pill.status-conciliado,.status-pill.status-active,.status-pill.status-activa,.status-pill.status-activo{background:#ecfdf3;color:#027a48;border-color:#abefc6}.status-pill.status-en_proceso_aduana,.status-pill.status-liberado_aduana,.status-pill.status-en_ruta_entrega,.status-pill.status-recibido_centro_clasificacion,.status-pill.status-documentado_cn22,.status-pill.status-pre_alerta_recibida{background:#eff8ff;color:#175cd3;border-color:#b2ddff}.status-pill.status-pendiente_cn33,.status-pill.status-pendiente_cn22,.status-pill.status-programado{background:#fffaeb;color:#b54708;border-color:#fedf89}.status-pill.status-observado,.status-pill.status-incidencia_entrega,.status-pill.status-revocado,.status-pill.status-expirado,.status-pill.status-inactivo{background:#fef3f2;color:#b42318;border-color:#fecdca}@media (max-width:1200px){.ops-meta-grid,.ops-stat-grid,.ops-vs-grid,.ops-explorer,.ops-detail-grid,.ops-summary-grid,.ops-person-grid,.ops-mini-grid{grid-template-columns:1fr}}
    </style>
@stop

@section('content_header')
    <div></div>
@stop

@section('content')
    @php
        $pendingCn22 = max(($summary['cn33_packages'] ?? 0) - ($summary['packages'] ?? 0), 0);
        $manifestCompletionPct = ($summary['manifests'] ?? 0) > 0 ? round((($summary['delivered_manifests'] ?? 0) / $summary['manifests']) * 100, 1) : 0;
        $bagCompletionPct = ($summary['bags'] ?? 0) > 0 ? round((($summary['delivered_bags'] ?? 0) / $summary['bags']) * 100, 1) : 0;
        $packageCompletionPct = ($summary['cn33_packages'] ?? 0) > 0 ? round((($summary['delivered_packages'] ?? 0) / $summary['cn33_packages']) * 100, 1) : 0;
        $latestManifest = $recentManifests->first();
        $latestPackage = $recentPackages->first();
        $latestMovement = $recentMovements->first();
        $latestToken = $company->apiTokens->first();

        $explorerData = $recentManifests->map(function ($manifest) {
            return [
                'id' => $manifest->id,
                'cn31_number' => $manifest->cn31_number,
                'origin_office' => $manifest->origin_office,
                'destination_office' => $manifest->destination_office,
                'dispatch_date' => $manifest->dispatch_date?->format('Y-m-d H:i'),
                'total_bags' => $manifest->total_bags,
                'total_packages' => $manifest->total_packages,
                'status' => $manifest->status,
                'bags' => $manifest->bags->map(function ($bag) {
                    return [
                        'id' => $bag->id,
                        'bag_number' => $bag->bag_number,
                        'dispatch_number_bag' => $bag->dispatch_number_bag,
                        'status' => $bag->status,
                        'declared_package_count' => (int) $bag->declared_package_count,
                        'declared_weight_kg' => (float) $bag->declared_weight_kg,
                        'loaded_package_count' => (int) ($bag->meta['loaded_package_count'] ?? $bag->cn33Packages->count()),
                        'loaded_weight_kg' => (float) ($bag->meta['loaded_weight_kg'] ?? $bag->cn33Packages->sum('weight_kg')),
                        'packages' => $bag->cn33Packages->map(function ($cn33) {
                            $package = $cn33->package;
                            return [
                                'id' => $cn33->id,
                                'tracking_code' => $cn33->tracking_code,
                                'origin' => $cn33->origin,
                                'destination' => $cn33->destination,
                                'weight_kg' => (float) $cn33->weight_kg,
                                'status' => $cn33->status,
                                'package_detail' => $package ? [
                                    'tracking_code' => $package->tracking_code,
                                    'status' => $package->status,
                                    'sender_name' => $package->sender_name,
                                    'sender_country' => $package->sender_country,
                                    'sender_address' => $package->sender_address,
                                    'sender_phone' => $package->sender_phone,
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
                                    'value_fob_usd' => $package->value_fob_usd !== null ? (float) $package->value_fob_usd : null,
                                    'registered_at' => optional($package->registered_at)->format('Y-m-d H:i'),
                                    'last_movement_at' => optional($package->last_movement_at)->format('Y-m-d H:i'),
                                    'delivery_attempts' => (int) $package->delivery_attempts,
                                    'last_delivery_attempt_at' => optional($package->last_delivery_attempt_at)->format('Y-m-d H:i'),
                                    'last_delivery_attempt' => [
                                        'attempt' => (int) $package->delivery_attempts,
                                        'location' => $package->latestDeliveryAttemptLocation(),
                                        'description' => $package->latestDeliveryAttemptDescription(),
                                    ],
                                    'movements' => $package->movements->map(fn ($movement) => [
                                        'status' => $movement->status,
                                        'location' => $movement->location,
                                        'description' => $movement->description,
                                    ])->values(),
                                ] : null,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values();
    @endphp

    <div class="page-shell">
        <div class="ops-shell">
            <section class="ops-stat-grid">
                <article class="ops-stat"><span class="ops-label">CN31 recibidos</span><span class="ops-stat-value">{{ $summary['manifests'] }}</span><span class="ops-note">Marbetes reportados.</span></article>
                <article class="ops-stat"><span class="ops-label">Sacas CN33</span><span class="ops-stat-value">{{ $summary['bags'] }}</span><span class="ops-note">Sacas declaradas.</span></article>
                <article class="ops-stat"><span class="ops-label">Paquetes declarados</span><span class="ops-stat-value">{{ $summary['cn33_packages'] }}</span><span class="ops-note">Tracking dentro de sacas.</span></article>
                <article class="ops-stat"><span class="ops-label">CN22 registrados</span><span class="ops-stat-value">{{ $summary['packages'] }}</span><span class="ops-note">Paquetes documentados.</span></article>
                <article class="ops-stat"><span class="ops-label">Pendientes CN22</span><span class="ops-stat-value">{{ $pendingCn22 }}</span><span class="ops-note">Brecha documental actual.</span></article>
                <article class="ops-stat"><span class="ops-label">Entregados</span><span class="ops-stat-value">{{ $summary['delivered_packages'] }}</span><span class="ops-note">{{ number_format($summary['delivery_progress_pct'], 1) }}% del total.</span></article>
                <article class="ops-stat"><span class="ops-label">Intentos de entrega</span><span class="ops-stat-value">{{ $summary['total_delivery_attempts'] }}</span><span class="ops-note">{{ $summary['packages_with_delivery_attempts'] }} paquete(s) con intento registrado.</span></article>
            </section>

            <section class="ops-vs-grid">
                <article class="ops-vs-card">
                    <div class="ops-vs-top">
                        <span class="ops-vs-title">CN31 enviados vs cerrados</span>
                        <span class="ops-vs-ratio">{{ $summary['delivered_manifests'] }} / {{ $summary['manifests'] }}</span>
                    </div>
                    <div class="ops-vs-track"><span class="ops-vs-fill" style="width: {{ $manifestCompletionPct }}%;"></span></div>
                    <div class="ops-vs-meta">
                        <span>Nos enviaron: {{ $summary['manifests'] }}</span>
                        <span>Cerrados: {{ $summary['delivered_manifests'] }}</span>
                        <span>{{ number_format($manifestCompletionPct, 1) }}%</span>
                    </div>
                </article>

                <article class="ops-vs-card">
                    <div class="ops-vs-top">
                        <span class="ops-vs-title">Sacas enviadas vs completadas</span>
                        <span class="ops-vs-ratio">{{ $summary['delivered_bags'] }} / {{ $summary['bags'] }}</span>
                    </div>
                    <div class="ops-vs-track"><span class="ops-vs-fill" style="width: {{ $bagCompletionPct }}%;"></span></div>
                    <div class="ops-vs-meta">
                        <span>Nos enviaron: {{ $summary['bags'] }}</span>
                        <span>Completadas: {{ $summary['delivered_bags'] }}</span>
                        <span>{{ number_format($bagCompletionPct, 1) }}%</span>
                    </div>
                </article>

                <article class="ops-vs-card">
                    <div class="ops-vs-top">
                        <span class="ops-vs-title">Paquetes enviados vs entregados</span>
                        <span class="ops-vs-ratio">{{ $summary['delivered_packages'] }} / {{ $summary['cn33_packages'] }}</span>
                    </div>
                    <div class="ops-vs-track"><span class="ops-vs-fill" style="width: {{ $packageCompletionPct }}%;"></span></div>
                    <div class="ops-vs-meta">
                        <span>Nos enviaron: {{ $summary['cn33_packages'] }}</span>
                        <span>Entregados: {{ $summary['delivered_packages'] }}</span>
                        <span>{{ number_format($packageCompletionPct, 1) }}%</span>
                    </div>
                </article>
            </section>

            <section class="ops-search">
                <div class="ops-search-row">
                    <div class="ops-search-field">
                        <label for="integrationSearchInput">Busqueda global de integracion</label>
                        <input id="integrationSearchInput" type="text" placeholder="Busca un CN31, una saca CN33 o un tracking, por ejemplo CBB000005 o EN000001001BO">
                    </div>
                    <button type="button" class="ops-btn ops-btn-primary" id="integrationSearchButton"><i class="fas fa-search"></i> Buscar</button>
                </div>
                <div class="ops-search-result" id="integrationSearchResult">Busca cualquier identificador operativo y el explorador se posicionara directamente en el resultado.</div>
            </section>

            <section class="ops-explorer">
                <article class="ops-panel"><h2 class="ops-panel-title">CN31 recientes</h2><div id="manifestListHost" class="ops-list"></div></article>
                <article class="ops-panel"><h2 class="ops-panel-title">Sacas del CN31 seleccionado</h2><div id="bagListHost" class="ops-list"></div></article>
                <article class="ops-panel"><h2 class="ops-panel-title">Paquetes de la saca seleccionada</h2><div id="packageListHost" class="ops-list"></div></article>
            </section>

            <section class="ops-panel">
                <h2 class="ops-panel-title">Detalle del paquete seleccionado</h2>
                <div id="packageDetailHost" class="ops-empty">Selecciona un tracking para ver remitente, destinatario y movimientos.</div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="companySettingsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar empresa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form method="POST" action="{{ route('admin.companies.settings', $company) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="companyLocale">Idioma</label>
                            <select id="companyLocale" name="locale" class="ops-select">
                                <option value="es" @selected($company->locale === 'es')>Espanol</option>
                                <option value="en" @selected($company->locale === 'en')>English</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label for="companyStatus">Estado</label>
                            <select id="companyStatus" name="status" class="ops-select">
                                <option value="active" @selected($company->status === 'active')>Activa</option>
                                <option value="inactive" @selected($company->status === 'inactive')>Inactiva</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="integrationSearchModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="integrationSearchModalTitle">Detalle de integracion</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="integrationSearchModalBody"></div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        const explorerData = @json($explorerData);
        let selectedManifestId = explorerData[0]?.id || null;
        let selectedBagId = explorerData[0]?.bags?.[0]?.id || null;
        let selectedPackageId = explorerData[0]?.bags?.[0]?.packages?.[0]?.id || null;

        function escapeHtml(value) { if (value === null || value === undefined || value === '') return 'Sin dato'; return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
        function statusClass(status) { return `status-${String(status || 'sin_dato').toLowerCase().replace(/\s+/g, '_')}`; }
        function statusBadge(status) { return `<span class="status-pill ${statusClass(status)}">${escapeHtml(status)}</span>`; }
        function findManifest(id) { return explorerData.find(item => item.id === id) || null; }
        function findBag(manifestId, bagId) { const manifest = findManifest(manifestId); return manifest ? (manifest.bags || []).find(item => item.id === bagId) || null : null; }
        function findPackage(manifestId, bagId, packageId) { const bag = findBag(manifestId, bagId); return bag ? (bag.packages || []).find(item => item.id === packageId) || null : null; }
        function selectManifest(id) { selectedManifestId = id; const manifest = findManifest(id); selectedBagId = manifest?.bags?.[0]?.id || null; selectedPackageId = manifest?.bags?.[0]?.packages?.[0]?.id || null; renderExplorer(); }
        function selectBag(id) { selectedBagId = id; const bag = findBag(selectedManifestId, id); selectedPackageId = bag?.packages?.[0]?.id || null; renderExplorer(); }
        function selectPackage(id) { selectedPackageId = id; renderPackageDetail(); }

        function renderManifestList() {
            const host = document.getElementById('manifestListHost');
            if (!explorerData.length) { host.innerHTML = '<div class="ops-empty">Todavia no hay CN31 cargados para esta empresa.</div>'; return; }
            host.innerHTML = explorerData.map(manifest => `
                <button type="button" class="ops-list-card ${manifest.id === selectedManifestId ? 'active' : ''}" onclick="selectManifest(${manifest.id})">
                    <div class="ops-list-top"><div><span class="ops-list-title">${escapeHtml(manifest.cn31_number)}</span><span class="ops-list-meta">${escapeHtml(manifest.origin_office)} -> ${escapeHtml(manifest.destination_office)}</span></div>${statusBadge(manifest.status)}</div>
                    <span class="ops-list-meta">${escapeHtml(manifest.dispatch_date)} | ${escapeHtml(manifest.total_bags)} sacas | ${escapeHtml(manifest.total_packages)} paquetes</span>
                </button>
            `).join('');
        }

        function renderBagList() {
            const host = document.getElementById('bagListHost');
            const manifest = findManifest(selectedManifestId);
            if (!manifest || !(manifest.bags || []).length) { host.innerHTML = '<div class="ops-empty">Este CN31 aun no tiene sacas cargadas.</div>'; return; }
            host.innerHTML = manifest.bags.map(bag => `
                <button type="button" class="ops-list-card ${bag.id === selectedBagId ? 'active' : ''}" onclick="selectBag(${bag.id})">
                    <div class="ops-list-top"><div><span class="ops-list-title">${escapeHtml(bag.dispatch_number_bag || bag.bag_number)}</span><span class="ops-list-meta">${escapeHtml(bag.bag_number)}</span></div>${statusBadge(bag.status)}</div>
                    <span class="ops-list-meta">${escapeHtml(bag.loaded_package_count)} cargados / ${escapeHtml(bag.declared_package_count)} declarados</span>
                    <span class="ops-list-meta">${escapeHtml(bag.loaded_weight_kg)} kg / ${escapeHtml(bag.declared_weight_kg)} kg</span>
                </button>
            `).join('');
        }

        function renderPackageList() {
            const host = document.getElementById('packageListHost');
            const bag = findBag(selectedManifestId, selectedBagId);
            if (!bag || !(bag.packages || []).length) { host.innerHTML = '<div class="ops-empty">Esta saca aun no tiene paquetes visibles.</div>'; return; }
            host.innerHTML = bag.packages.map(pkg => `
                <button type="button" class="ops-list-card ${pkg.id === selectedPackageId ? 'active' : ''}" onclick="selectPackage(${pkg.id})">
                    <div class="ops-list-top"><div><span class="ops-list-title">${escapeHtml(pkg.tracking_code)}</span><span class="ops-list-meta">${escapeHtml(pkg.origin)} -> ${escapeHtml(pkg.destination)}</span></div>${statusBadge(pkg.package_detail?.status || pkg.status)}</div>
                    <span class="ops-list-meta">${escapeHtml(pkg.weight_kg)} kg | Intentos: ${escapeHtml(pkg.package_detail?.delivery_attempts ?? 0)}</span>
                </button>
            `).join('');
        }

        function renderPackageDetail() {
            const host = document.getElementById('packageDetailHost');
            const pkg = findPackage(selectedManifestId, selectedBagId, selectedPackageId);
            const detail = pkg?.package_detail || null;
            if (!pkg) { host.innerHTML = '<div class="ops-empty">Selecciona un tracking para revisar su detalle.</div>'; return; }
            if (!detail) {
                host.innerHTML = `<div class="ops-summary-grid"><div class="ops-summary"><span class="ops-label">Tracking</span><strong>${escapeHtml(pkg.tracking_code)}</strong></div><div class="ops-summary"><span class="ops-label">Estado CN33</span><strong>${escapeHtml(pkg.status)}</strong></div><div class="ops-summary"><span class="ops-label">Ruta</span><strong>${escapeHtml(pkg.origin)} -> ${escapeHtml(pkg.destination)}</strong></div><div class="ops-summary"><span class="ops-label">Peso</span><strong>${escapeHtml(pkg.weight_kg)} kg</strong></div></div><div class="ops-empty">Este tracking existe en CN33, pero todavia no tiene detalle CN22 cargado.</div>`;
                return;
            }
            const movements = (detail.movements || []).length ? detail.movements.map(movement => `<tr><td>${statusBadge(movement.status)}</td><td>${escapeHtml(movement.location)}</td><td>${escapeHtml(movement.description)}</td></tr>`).join('') : '<tr><td colspan="3" class="text-muted">Todavia no hay movimientos para este paquete.</td></tr>';
            host.innerHTML = `<div class="ops-summary-grid"><div class="ops-summary"><span class="ops-label">Tracking</span><strong>${escapeHtml(detail.tracking_code)}</strong></div><div class="ops-summary"><span class="ops-label">Estado</span><strong>${statusBadge(detail.status)}</strong></div><div class="ops-summary"><span class="ops-label">Ruta</span><strong>${escapeHtml(detail.origin_office)} -> ${escapeHtml(detail.destination_office)}</strong></div><div class="ops-summary"><span class="ops-label">Peso</span><strong>${escapeHtml(detail.gross_weight_grams)} gr / ${escapeHtml(detail.weight_kg)} kg</strong></div></div><div class="ops-mini-grid" style="margin-bottom:.9rem;"><div class="ops-item"><span class="ops-label">Intentos de entrega</span><span class="ops-item-value">${escapeHtml(detail.delivery_attempts)}</span></div><div class="ops-item"><span class="ops-label">Ultimo intento</span><span class="ops-item-value">${escapeHtml(detail.last_delivery_attempt_at)}</span></div><div class="ops-item"><span class="ops-label">Lugar ultimo intento</span><span class="ops-item-value">${escapeHtml(detail.last_delivery_attempt?.location)}</span></div><div class="ops-item"><span class="ops-label">Resultado ultimo intento</span><span class="ops-item-value">${escapeHtml(detail.last_delivery_attempt?.description)}</span></div></div><div class="ops-person-grid"><section class="ops-person"><div class="ops-person-head"><span class="ops-person-title">Remitente</span><i class="fas fa-paper-plane text-primary"></i></div><div class="ops-person-body"><div><div class="ops-person-name">${escapeHtml(detail.sender_name)}</div><div class="ops-note">${escapeHtml(detail.sender_country)}</div></div><div class="ops-item"><span class="ops-label">Direccion</span><span class="ops-item-value">${escapeHtml(detail.sender_address)}</span></div><div class="ops-item"><span class="ops-label">Telefono</span><span class="ops-item-value">${escapeHtml(detail.sender_phone)}</span></div></div></section><section class="ops-person"><div class="ops-person-head"><span class="ops-person-title">Destinatario</span><i class="fas fa-user text-primary"></i></div><div class="ops-person-body"><div><div class="ops-person-name">${escapeHtml(detail.recipient_name)}</div><div class="ops-note">Documento: ${escapeHtml(detail.recipient_document)}</div></div><div class="ops-mini-grid"><div class="ops-item"><span class="ops-label">Contacto</span><span class="ops-item-value">${escapeHtml(detail.recipient_phone)} / ${escapeHtml(detail.recipient_whatsapp)}</span></div><div class="ops-item"><span class="ops-label">Direccion</span><span class="ops-item-value">${escapeHtml(detail.recipient_address)}</span></div><div class="ops-item"><span class="ops-label">Referencia</span><span class="ops-item-value">${escapeHtml(detail.recipient_address_reference)}</span></div><div class="ops-item"><span class="ops-label">Ciudad / Departamento</span><span class="ops-item-value">${escapeHtml(detail.recipient_city)} / ${escapeHtml(detail.recipient_department)}</span></div></div></div></section></div><div class="ops-mini-grid" style="margin-top:.9rem;"><div class="ops-item"><span class="ops-label">Descripcion</span><span class="ops-item-value">${escapeHtml(detail.description)}</span></div><div class="ops-item"><span class="ops-label">Valor FOB</span><span class="ops-item-value">${detail.value_fob_usd !== null ? `USD ${escapeHtml(detail.value_fob_usd)}` : 'Sin dato'}</span></div><div class="ops-item"><span class="ops-label">Recibido en sistema</span><span class="ops-item-value">${escapeHtml(detail.registered_at)}</span></div><div class="ops-item"><span class="ops-label">Ultima actualizacion</span><span class="ops-item-value">${escapeHtml(detail.last_movement_at)}</span></div></div><div class="table-responsive" style="margin-top:1rem;"><table class="table corp-table mb-0"><thead><tr><th>Estado</th><th>Ubicacion</th><th>Descripcion</th></tr></thead><tbody>${movements}</tbody></table></div>`;
        }

        function renderExplorer() { renderManifestList(); renderBagList(); renderPackageList(); renderPackageDetail(); }

        function openSearchModal(title, body) {
            document.getElementById('integrationSearchModalTitle').textContent = title;
            document.getElementById('integrationSearchModalBody').innerHTML = body;
            $('#integrationSearchModal').modal('show');
        }

        function openManifestSearchModal(manifest) {
            const body = `<div class="ops-summary-grid"><div class="ops-summary"><span class="ops-label">CN31</span><strong>${escapeHtml(manifest.cn31_number)}</strong></div><div class="ops-summary"><span class="ops-label">Ruta</span><strong>${escapeHtml(manifest.origin_office)} -> ${escapeHtml(manifest.destination_office)}</strong></div><div class="ops-summary"><span class="ops-label">Despacho</span><strong>${escapeHtml(manifest.dispatch_date)}</strong></div><div class="ops-summary"><span class="ops-label">Estado</span><strong>${statusBadge(manifest.status)}</strong></div></div><div class="table-responsive"><table class="table corp-table mb-0"><thead><tr><th>Saca</th><th>Paquetes</th><th>Peso</th><th>Estado</th></tr></thead><tbody>${(manifest.bags || []).length ? manifest.bags.map(bag => `<tr><td><strong>${escapeHtml(bag.dispatch_number_bag || bag.bag_number)}</strong><br><small class="text-muted">${escapeHtml(bag.bag_number)}</small></td><td>${escapeHtml(bag.loaded_package_count)} / ${escapeHtml(bag.declared_package_count)}</td><td>${escapeHtml(bag.loaded_weight_kg)} kg / ${escapeHtml(bag.declared_weight_kg)} kg</td><td>${statusBadge(bag.status)}</td></tr>`).join('') : '<tr><td colspan="4" class="text-muted">No hay sacas para este CN31.</td></tr>'}</tbody></table></div>`;
            openSearchModal(`CN31 ${manifest.cn31_number}`, body);
        }

        function openBagSearchModal(manifest, bag) {
            const body = `<div class="ops-summary-grid"><div class="ops-summary"><span class="ops-label">CN31</span><strong>${escapeHtml(manifest.cn31_number)}</strong></div><div class="ops-summary"><span class="ops-label">Saca</span><strong>${escapeHtml(bag.dispatch_number_bag || bag.bag_number)}</strong></div><div class="ops-summary"><span class="ops-label">Estado</span><strong>${statusBadge(bag.status)}</strong></div><div class="ops-summary"><span class="ops-label">Peso</span><strong>${escapeHtml(bag.loaded_weight_kg)} kg / ${escapeHtml(bag.declared_weight_kg)} kg</strong></div></div><div class="table-responsive"><table class="table corp-table mb-0"><thead><tr><th>Tracking</th><th>Ruta</th><th>Peso</th><th>Estado</th></tr></thead><tbody>${(bag.packages || []).length ? bag.packages.map(pkg => `<tr><td><strong>${escapeHtml(pkg.tracking_code)}</strong></td><td>${escapeHtml(pkg.origin)} -> ${escapeHtml(pkg.destination)}</td><td>${escapeHtml(pkg.weight_kg)} kg</td><td>${statusBadge(pkg.package_detail?.status || pkg.status)}</td></tr>`).join('') : '<tr><td colspan="4" class="text-muted">No hay paquetes para esta saca.</td></tr>'}</tbody></table></div>`;
            openSearchModal(`Saca ${bag.dispatch_number_bag || bag.bag_number}`, body);
        }

        function openPackageSearchModal(pkg) {
            const detail = pkg.package_detail || null;
            if (!detail) {
                const body = `<div class="ops-summary-grid"><div class="ops-summary"><span class="ops-label">Tracking</span><strong>${escapeHtml(pkg.tracking_code)}</strong></div><div class="ops-summary"><span class="ops-label">Ruta</span><strong>${escapeHtml(pkg.origin)} -> ${escapeHtml(pkg.destination)}</strong></div><div class="ops-summary"><span class="ops-label">Peso</span><strong>${escapeHtml(pkg.weight_kg)} kg</strong></div><div class="ops-summary"><span class="ops-label">Estado</span><strong>${statusBadge(pkg.status)}</strong></div></div><div class="ops-empty">Este tracking existe en CN33, pero todavia no tiene detalle CN22.</div>`;
                openSearchModal(`Tracking ${pkg.tracking_code}`, body);
                return;
            }
            const body = `<div class="ops-summary-grid"><div class="ops-summary"><span class="ops-label">Tracking</span><strong>${escapeHtml(detail.tracking_code)}</strong></div><div class="ops-summary"><span class="ops-label">Estado</span><strong>${statusBadge(detail.status)}</strong></div><div class="ops-summary"><span class="ops-label">Ruta</span><strong>${escapeHtml(detail.origin_office)} -> ${escapeHtml(detail.destination_office)}</strong></div><div class="ops-summary"><span class="ops-label">Peso</span><strong>${escapeHtml(detail.gross_weight_grams)} gr / ${escapeHtml(detail.weight_kg)} kg</strong></div></div><div class="ops-mini-grid" style="margin-bottom:.9rem;"><div class="ops-item"><span class="ops-label">Intentos de entrega</span><span class="ops-item-value">${escapeHtml(detail.delivery_attempts)}</span></div><div class="ops-item"><span class="ops-label">Ultimo intento</span><span class="ops-item-value">${escapeHtml(detail.last_delivery_attempt_at)}</span></div><div class="ops-item"><span class="ops-label">Lugar ultimo intento</span><span class="ops-item-value">${escapeHtml(detail.last_delivery_attempt?.location)}</span></div><div class="ops-item"><span class="ops-label">Resultado ultimo intento</span><span class="ops-item-value">${escapeHtml(detail.last_delivery_attempt?.description)}</span></div></div><div class="ops-person-grid"><section class="ops-person"><div class="ops-person-head"><span class="ops-person-title">Remitente</span><i class="fas fa-paper-plane text-primary"></i></div><div class="ops-person-body"><div><div class="ops-person-name">${escapeHtml(detail.sender_name)}</div><div class="ops-note">${escapeHtml(detail.sender_country)}</div></div><div class="ops-item"><span class="ops-label">Direccion</span><span class="ops-item-value">${escapeHtml(detail.sender_address)}</span></div><div class="ops-item"><span class="ops-label">Telefono</span><span class="ops-item-value">${escapeHtml(detail.sender_phone)}</span></div></div></section><section class="ops-person"><div class="ops-person-head"><span class="ops-person-title">Destinatario</span><i class="fas fa-user text-primary"></i></div><div class="ops-person-body"><div><div class="ops-person-name">${escapeHtml(detail.recipient_name)}</div><div class="ops-note">Documento: ${escapeHtml(detail.recipient_document)}</div></div><div class="ops-mini-grid"><div class="ops-item"><span class="ops-label">Contacto</span><span class="ops-item-value">${escapeHtml(detail.recipient_phone)} / ${escapeHtml(detail.recipient_whatsapp)}</span></div><div class="ops-item"><span class="ops-label">Direccion</span><span class="ops-item-value">${escapeHtml(detail.recipient_address)}</span></div><div class="ops-item"><span class="ops-label">Referencia</span><span class="ops-item-value">${escapeHtml(detail.recipient_address_reference)}</span></div><div class="ops-item"><span class="ops-label">Ciudad / Departamento</span><span class="ops-item-value">${escapeHtml(detail.recipient_city)} / ${escapeHtml(detail.recipient_department)}</span></div></div></div></section></div>`;
            openSearchModal(`Tracking ${pkg.tracking_code}`, body);
        }

        function runIntegrationSearch() {
            const input = document.getElementById('integrationSearchInput');
            const result = document.getElementById('integrationSearchResult');
            const query = String(input.value || '').trim().toUpperCase();
            result.classList.remove('is-error');
            if (!query) { result.textContent = 'Escribe un CN31, una saca o un tracking para buscarlo.'; result.classList.add('is-error'); return; }
            const manifest = explorerData.find(item => String(item.cn31_number || '').toUpperCase().includes(query));
            if (manifest) { selectedManifestId = manifest.id; selectedBagId = manifest?.bags?.[0]?.id || null; selectedPackageId = manifest?.bags?.[0]?.packages?.[0]?.id || null; renderExplorer(); openManifestSearchModal(manifest); result.textContent = `CN31 ${manifest.cn31_number} encontrado.`; return; }
            for (const manifestItem of explorerData) {
                const bag = (manifestItem.bags || []).find(item => String(item.dispatch_number_bag || item.bag_number || '').toUpperCase().includes(query) || String(item.bag_number || '').toUpperCase().includes(query));
                if (bag) { selectedManifestId = manifestItem.id; selectedBagId = bag.id; selectedPackageId = bag?.packages?.[0]?.id || null; renderExplorer(); openBagSearchModal(manifestItem, bag); result.textContent = `Saca ${bag.dispatch_number_bag || bag.bag_number} encontrada dentro de ${manifestItem.cn31_number}.`; return; }
            }
            for (const manifestItem of explorerData) {
                for (const bag of manifestItem.bags || []) {
                    const pkg = (bag.packages || []).find(item => String(item.tracking_code || '').toUpperCase().includes(query));
                    if (pkg) { selectedManifestId = manifestItem.id; selectedBagId = bag.id; selectedPackageId = pkg.id; renderExplorer(); openPackageSearchModal(pkg); result.textContent = `Tracking ${pkg.tracking_code} encontrado en ${bag.dispatch_number_bag || bag.bag_number}.`; return; }
                }
            }
            result.textContent = `No encontramos ${query} dentro de la integracion de esta empresa.`; result.classList.add('is-error');
        }

        document.addEventListener('DOMContentLoaded', function () {
            renderExplorer();
            document.getElementById('integrationSearchButton')?.addEventListener('click', runIntegrationSearch);
            document.getElementById('integrationSearchInput')?.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); runIntegrationSearch(); } });
        });
    </script>
@stop

@section('footer')
    <strong>Integracion.</strong> Mesa de control interna por empresa.
@stop

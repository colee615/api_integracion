@extends('adminlte::page')

@section('title', 'Dashboard')

@section('css')
    @include('admin.partials.enterprise-theme')
    <style>
        .interactive-row {
            cursor: pointer;
        }

        .interactive-row:hover {
            background: #f3f8ff !important;
        }

        .interactive-row.is-active {
            background: #eef4fb !important;
        }

        .interactive-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            padding: .45rem .8rem;
            border: 1px solid #d8e1eb;
            border-radius: .7rem;
            background: #fff;
            color: #1d4f91;
            font-size: .8rem;
            font-weight: 700;
            white-space: nowrap;
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

        .package-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .summary-card {
            border: 1px solid #d8e1eb;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
            padding: 1rem 1.05rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
        }

        .summary-card .label {
            display: block;
            margin-bottom: .35rem;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            font-weight: 800;
        }

        .summary-card .value {
            display: block;
            color: #0f172a;
            font-weight: 800;
            font-size: 1.08rem;
            line-height: 1.25;
            word-break: break-word;
        }

        .person-cards-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .person-card {
            border: 1px solid #d8e1eb;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
            overflow: hidden;
        }

        .person-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .95rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fbfd;
        }

        .person-card-title {
            margin: 0;
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #355273;
        }

        .person-card-icon {
            color: #1d4f91;
            font-size: 1rem;
        }

        .person-card-body {
            padding: 1rem;
            display: grid;
            gap: .75rem;
        }

        .person-card.is-sender .person-card-header {
            background: linear-gradient(90deg, #eff6ff 0%, #f8fbfd 100%);
        }

        .person-card.is-recipient .person-card-header {
            background: linear-gradient(90deg, #f0fdf4 0%, #f8fbfd 100%);
        }

        .person-card-name {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }

        .person-card-subtitle {
            margin-top: .2rem;
            color: #64748b;
            font-size: .9rem;
            font-weight: 600;
        }

        .person-card-empty {
            min-height: 206px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #d8e1eb;
            border-radius: .9rem;
            background: #fff;
            color: #94a3b8;
            font-size: .9rem;
            font-weight: 600;
        }

        .person-card-field {
            padding: .8rem .9rem;
            border: 1px solid #e2e8f0;
            border-radius: .85rem;
            background: #fff;
        }

        .person-card-field .label {
            display: block;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            margin-bottom: .25rem;
        }

        .person-card-field .value {
            color: #0f172a;
            font-weight: 600;
            word-break: break-word;
        }

        .shipment-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .shipment-meta-card {
            border: 1px solid #d8e1eb;
            border-radius: .95rem;
            background: #fff;
            padding: .95rem 1rem;
        }

        .shipment-meta-card .label {
            display: block;
            margin-bottom: .3rem;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
        }

        .shipment-meta-card .value {
            display: block;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.35;
            word-break: break-word;
        }

        .modal-table td,
        .modal-table th {
            vertical-align: middle;
        }

        .calendar-year-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .calendar-year-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
        }

        .calendar-year-actions {
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .calendar-nav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.4rem;
            height: 2.4rem;
            border: 1px solid #d8e1eb;
            border-radius: .8rem;
            background: #fff;
            color: #1d4f91;
            font-weight: 800;
            text-decoration: none;
        }

        .calendar-year-chip {
            padding: .55rem .9rem;
            border-radius: .8rem;
            background: #eef4fb;
            color: #0f172a;
            font-weight: 800;
        }

        .calendar-month {
            border: 1px solid #d8e1eb;
            border-radius: 1rem;
            background: #fff;
            padding: .9rem;
        }

        .calendar-month-title {
            margin: 0 0 .75rem;
            font-size: .92rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #355273;
        }

        .calendar-months-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .calendar-day-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.4rem;
            height: 2.4rem;
            padding: 0 .55rem;
            border: 1px solid #edf2f7;
            background: #f8fbfd;
            color: #0f172a;
            font-size: .85rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: .75rem;
        }

        .calendar-day-link:hover {
            background: #eef4fb;
            border-color: #cbd9e8;
            color: #1d4f91;
        }

        .calendar-day-link.is-today {
            border-color: #93c5fd;
            color: #1d4f91;
        }

        .calendar-day-link.has-records {
            border-color: #fca5a5;
            background: #fff1f2;
            color: #b42318;
        }

        .calendar-day-link.is-selected {
            background: #d92d20;
            border-color: #d92d20;
            color: #fff;
            box-shadow: 0 10px 24px rgba(217, 45, 32, .18);
        }

        @media (max-width: 767.98px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .package-summary-grid,
            .person-cards-grid,
            .shipment-meta-grid {
                grid-template-columns: 1fr;
            }

            .calendar-months-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .calendar-months-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@stop

@section('content_header')
    <div class="page-shell">
        <section class="page-hero">
            <span class="page-kicker"><i class="fas fa-chart-line"></i> Centro de control</span>
            <h1 class="page-title">Panel operativo</h1>
        </section>
    </div>
@stop

@section('content')
    @php
        $selectedDateHuman = $selectedDate->format('d/m/Y');
        $calendarYear = $selectedDate->year;
        $activeMonths = collect($availableDates)
            ->map(fn ($date) => \Carbon\Carbon::parse($date)->month)
            ->unique()
            ->sort()
            ->values();
        $availableDateLookup = collect($availableDates)->flip();
        $calendarMonths = $activeMonths->map(function ($month) use ($calendarYear) {
            $monthDate = \Carbon\Carbon::create($calendarYear, $month, 1);
            $offset = $monthDate->dayOfWeekIso % 7;
            $daysInMonth = $monthDate->daysInMonth;

            return [
                'label' => $monthDate->translatedFormat('F'),
                'offset' => $offset,
                'days' => collect(range(1, $daysInMonth))->map(fn ($day) => \Carbon\Carbon::create($calendarYear, $month, $day)),
            ];
        });
        $manifestDashboardData = $todayManifests->map(function ($manifest) {
            return [
                'id' => $manifest->id,
                'company_name' => $manifest->company?->name,
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
                        'declared_package_count' => $bag->declared_package_count,
                        'declared_weight_kg' => (float) $bag->declared_weight_kg,
                        'loaded_package_count' => (int) ($bag->meta['loaded_package_count'] ?? 0),
                        'loaded_weight_kg' => (float) ($bag->meta['loaded_weight_kg'] ?? 0),
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
                                    'reference' => $package->reference,
                                    'status' => $package->status,
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
                                    'value_fob_usd' => $package->value_fob_usd !== null ? (float) $package->value_fob_usd : null,
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
        <x-adminlte-card title="Filtro operativo" theme="light" icon="fas fa-calendar-day" class="panel-card">
            <div class="calendar-year-nav">
                <div>
                    <h3 class="calendar-year-title">Fecha del CN31</h3>
                    <div class="text-muted">Selecciona directamente un dia del calendario anual del CN31.</div>
                </div>
                <div class="calendar-year-actions">
                    <a
                        href="{{ route('admin.dashboard', ['date' => $selectedDate->copy()->subYear()->format('Y-m-d')]) }}"
                        class="calendar-nav-link"
                        aria-label="Anio anterior">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span class="calendar-year-chip">{{ $calendarYear }}</span>
                    <a
                        href="{{ route('admin.dashboard', ['date' => $selectedDate->copy()->addYear()->format('Y-m-d')]) }}"
                        class="calendar-nav-link"
                        aria-label="Anio siguiente">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            @if ($calendarMonths->isNotEmpty())
                <div class="calendar-months-grid">
                    @foreach ($calendarMonths as $month)
                        <section class="calendar-month">
                            <h4 class="calendar-month-title">{{ $month['label'] }}</h4>
                            <div class="calendar-weekdays">
                                <span class="calendar-weekday">D</span>
                                <span class="calendar-weekday">L</span>
                                <span class="calendar-weekday">M</span>
                                <span class="calendar-weekday">M</span>
                                <span class="calendar-weekday">J</span>
                                <span class="calendar-weekday">V</span>
                                <span class="calendar-weekday">S</span>
                            </div>
                            <div class="calendar-days">
                                @for ($i = 0; $i < $month['offset']; $i++)
                                    <span class="calendar-day-empty"></span>
                                @endfor

                                @foreach ($month['days'] as $day)
                                    @php $hasRecords = $availableDateLookup->has($day->format('Y-m-d')); @endphp
                                    <a
                                        href="{{ route('admin.dashboard', ['date' => $day->format('Y-m-d')]) }}"
                                        class="calendar-day-link{{ $hasRecords ? ' has-records' : '' }}{{ $day->isSameDay($selectedDate) ? ' is-selected' : '' }}{{ $day->isToday() ? ' is-today' : '' }}">
                                        {{ $day->day }}
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @else
                <div class="section-note mb-0">
                    No hay meses con registros para {{ $calendarYear }}.
                </div>
            @endif
            <div class="section-note mt-3 mb-0">
                Solo el CN31 maneja fecha en este panel. Se muestran los meses que tienen manifiestos y cada mes aparece completo. La fecha seleccionada se pinta en rojo.
            </div>
        </x-adminlte-card>

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $manifestsTodayCount }}" text="CN31 pre-alertados" icon="fas fa-file-alt" theme="white"/>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $companiesSendingTodayCount }}" text="Empresas reportando" icon="fas fa-building" theme="white"/>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $bagsTodayCount }}" text="Sacas declaradas" icon="fas fa-shopping-bag" theme="white"/>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="metric-card">
                    <x-adminlte-small-box title="{{ $receivedTodayCount }}" text="Paquetes anunciados" icon="fas fa-box-open" theme="white"/>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <x-adminlte-card title="CN31 por fecha" theme="light" icon="fas fa-file-alt" class="panel-card">
                    <div class="section-note mb-3">
                        Fecha consultada: <strong>{{ $selectedDateHuman }}</strong>. Aqui ves los CN31 cuyo despacho fue declarado para esa fecha.
                    </div>
                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>CN31</th>
                                    <th>Empresa</th>
                                    <th>Sacas</th>
                                    <th>Paquetes</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($todayManifests as $manifest)
                                    <tr class="interactive-row js-manifest-row" data-manifest-id="{{ $manifest->id }}">
                                        <td><strong>{{ $manifest->cn31_number }}</strong></td>
                                        <td>{{ $manifest->company?->name }}</td>
                                        <td>{{ $manifest->total_bags }}</td>
                                        <td>{{ $manifest->total_packages }}</td>
                                        <td>{{ $manifest->dispatch_date?->format('Y-m-d H:i') ?? 'Sin fecha' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $manifest->status === 'conciliado' ? 'success' : 'secondary' }}">
                                                {{ $manifest->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted">No hay CN31 reportados para la fecha seleccionada.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-adminlte-card>
            </div>

            <div class="col-md-6">
                <x-adminlte-card title="Sacas del CN31 seleccionado" theme="light" icon="fas fa-shopping-bag" class="panel-card">
                    <div id="dashboard-selected-manifest-note" class="section-note mb-3">
                        Selecciona un CN31 de la tabla izquierda para ver las sacas que la empresa declaro dentro de esa pre-alerta.
                    </div>

                    <div class="table-responsive">
                        <table class="table corp-table">
                            <thead>
                                <tr>
                                    <th>Saca</th>
                                    <th>Paquetes</th>
                                    <th>Peso</th>
                                    <th>Estado</th>
                                    <th>Accion</th>
                                </tr>
                            </thead>
                            <tbody id="dashboard-bags-body">
                                <tr>
                                    <td colspan="5" class="text-muted">No hay CN31 seleccionado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </x-adminlte-card>
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
        const dashboardManifestData = @json($manifestDashboardData);

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

        function dashboardBadge(status) {
            return `<span class="detail-chip">${escapeHtml(status)}</span>`;
        }

        function findDashboardManifest(manifestId) {
            return dashboardManifestData.find((item) => item.id === manifestId);
        }

        function findDashboardBag(manifestId, bagId) {
            const manifest = findDashboardManifest(manifestId);

            return manifest ? manifest.bags.find((bag) => bag.id === bagId) : null;
        }

        function findDashboardPackage(manifestId, bagId, packageId) {
            const bag = findDashboardBag(manifestId, bagId);

            return bag ? bag.packages.find((item) => item.id === packageId) : null;
        }

        function renderDashboardBags(manifestId) {
            const manifest = findDashboardManifest(manifestId);
            const body = document.getElementById('dashboard-bags-body');
            const note = document.getElementById('dashboard-selected-manifest-note');

            document.querySelectorAll('.js-manifest-row').forEach((row) => {
                row.classList.toggle('is-active', Number(row.dataset.manifestId) === manifestId);
            });

            if (!manifest) {
                note.textContent = 'Selecciona un CN31 de la tabla izquierda para ver las sacas declaradas.';
                body.innerHTML = '<tr><td colspan="5" class="text-muted">No hay CN31 seleccionado.</td></tr>';
                return;
            }

            note.innerHTML = `<strong>${escapeHtml(manifest.cn31_number)}</strong> | ${escapeHtml(manifest.company_name)} | ${escapeHtml(manifest.total_bags)} sacas declaradas | ${escapeHtml(manifest.total_packages)} paquetes anunciados`;

            body.innerHTML = manifest.bags.length
                ? manifest.bags.map((bag) => `
                    <tr>
                        <td><strong>${escapeHtml(bag.dispatch_number_bag || bag.bag_number)}</strong><br><small class="text-muted">${escapeHtml(bag.bag_number)}</small></td>
                        <td>${escapeHtml(bag.loaded_package_count)} cargados / ${escapeHtml(bag.declared_package_count)} declarados</td>
                        <td>${escapeHtml(bag.loaded_weight_kg)} kg / ${escapeHtml(bag.declared_weight_kg)} kg</td>
                        <td>${dashboardBadge(bag.status)}</td>
                        <td>
                            <button type="button" class="interactive-trigger" onclick="openDashboardBagModal(${manifest.id}, ${bag.id})">
                                <i class="fas fa-boxes"></i> Ver paquetes
                            </button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" class="text-muted">No hay sacas para este CN31.</td></tr>';
        }

        function openDashboardBagModal(manifestId, bagId) {
            const manifest = findDashboardManifest(manifestId);
            const bag = findDashboardBag(manifestId, bagId);

            if (!manifest || !bag) {
                return;
            }

            document.getElementById('bagExplorerTitle').textContent = `Paquetes de ${bag.dispatch_number_bag || bag.bag_number}`;
            document.getElementById('bagExplorerBody').innerHTML = `
                <div class="modal-panel">
                    <div class="detail-grid">
                        <div class="item"><span class="label">CN31</span><span class="value">${escapeHtml(manifest.cn31_number)}</span></div>
                        <div class="item"><span class="label">Despacho saca</span><span class="value">${escapeHtml(bag.dispatch_number_bag || bag.bag_number)}</span></div>
                        <div class="item"><span class="label">Empresa</span><span class="value">${escapeHtml(manifest.company_name)}</span></div>
                        <div class="item"><span class="label">Estado saca</span><span class="value">${escapeHtml(bag.status)}</span></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table corp-table modal-table">
                        <thead>
                            <tr>
                                <th>Tracking</th>
                                <th>Ruta</th>
                                <th>Peso</th>
                                <th>Destino</th>
                                <th>Estado</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${bag.packages.length ? bag.packages.map((pkg) => `
                                <tr>
                                    <td><strong>${escapeHtml(pkg.tracking_code)}</strong></td>
                                    <td>${escapeHtml(pkg.origin)} -> ${escapeHtml(pkg.destination)}</td>
                                    <td>${escapeHtml(pkg.weight_kg)} kg</td>
                                    <td>${escapeHtml(pkg.destination)}</td>
                                    <td>${dashboardBadge(pkg.status)}</td>
                                    <td>
                                        <button type="button" class="interactive-trigger" onclick="openDashboardPackageModal(${manifest.id}, ${bag.id}, ${pkg.id})">
                                            <i class="fas fa-search"></i> Ver detalle
                                        </button>
                                    </td>
                                </tr>
                            `).join('') : '<tr><td colspan="6" class="text-muted">No hay paquetes para esta saca.</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `;

            $('#bagExplorerModal').modal('show');
        }

        function openDashboardPackageModal(manifestId, bagId, packageId) {
            const pkg = findDashboardPackage(manifestId, bagId, packageId);

            if (!pkg || !pkg.package_detail) {
                return;
            }

            const detail = pkg.package_detail;
            const movements = detail.movements.length
                ? detail.movements.map((movement) => `
                    <tr>
                        <td>${escapeHtml(movement.status)}</td>
                        <td>${escapeHtml(movement.location)}</td>
                        <td>${escapeHtml(movement.description)}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="3" class="text-muted">No hay movimientos registrados para este paquete.</td></tr>';

            document.getElementById('packageExplorerTitle').textContent = `Detalle de ${detail.tracking_code}`;
            document.getElementById('packageExplorerBody').innerHTML = `
                <div class="modal-panel">
                    <div class="package-summary-grid">
                        <div class="summary-card">
                            <span class="label">Tracking</span>
                            <span class="value">${escapeHtml(detail.tracking_code)}</span>
                        </div>
                        <div class="summary-card">
                            <span class="label">Estado</span>
                            <span class="value">${escapeHtml(detail.status)}</span>
                        </div>
                        <div class="summary-card">
                            <span class="label">Ruta</span>
                            <span class="value">${escapeHtml(detail.origin_office)} -> ${escapeHtml(detail.destination_office)}</span>
                        </div>
                        <div class="summary-card">
                            <span class="label">Peso</span>
                            <span class="value">${escapeHtml(detail.gross_weight_grams)} gr / ${escapeHtml(detail.weight_kg)} kg</span>
                        </div>
                    </div>
                    <div class="person-cards-grid">
                        <section class="person-card is-sender">
                            <div class="person-card-header">
                                <h6 class="person-card-title">Remitente</h6>
                                <i class="fas fa-paper-plane person-card-icon"></i>
                            </div>
                            <div class="person-card-body">
                                ${detail.sender_name || detail.sender_country ? `
                                    <div>
                                        <div class="person-card-name">${escapeHtml(detail.sender_name)}</div>
                                        <div class="person-card-subtitle">${escapeHtml(detail.sender_country)}</div>
                                    </div>
                                ` : `
                                    <div class="person-card-empty">Sin datos de remitente</div>
                                `}
                            </div>
                        </section>
                        <section class="person-card is-recipient">
                            <div class="person-card-header">
                                <h6 class="person-card-title">Destinatario</h6>
                                <i class="fas fa-user person-card-icon"></i>
                            </div>
                            <div class="person-card-body">
                                <div>
                                    <div class="person-card-name">${escapeHtml(detail.recipient_name)}</div>
                                    <div class="person-card-subtitle">Documento: ${escapeHtml(detail.recipient_document)}</div>
                                </div>
                                <div class="person-card-field">
                                    <span class="label">Contacto</span>
                                    <span class="value">${escapeHtml(detail.recipient_phone)} / ${escapeHtml(detail.recipient_whatsapp)}</span>
                                </div>
                                <div class="person-card-field">
                                    <span class="label">Direccion</span>
                                    <span class="value">${escapeHtml(detail.recipient_address)}</span>
                                </div>
                                <div class="person-card-field">
                                    <span class="label">Referencia direccion</span>
                                    <span class="value">${escapeHtml(detail.recipient_address_reference)}</span>
                                </div>
                                <div class="person-card-field">
                                    <span class="label">Ciudad / Departamento</span>
                                    <span class="value">${escapeHtml(detail.recipient_city)} / ${escapeHtml(detail.recipient_department)}</span>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div class="shipment-meta-grid">
                        <div class="shipment-meta-card">
                            <span class="label">Descripcion</span>
                            <span class="value">${escapeHtml(detail.description)}</span>
                        </div>
                        <div class="shipment-meta-card">
                            <span class="label">Valor FOB</span>
                            <span class="value">USD ${escapeHtml(detail.value_fob_usd)}</span>
                        </div>
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
                            </tr>
                        </thead>
                        <tbody>${movements}</tbody>
                    </table>
                </div>
            `;

            $('#packageExplorerModal').modal('show');
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-manifest-row').forEach((row) => {
                row.addEventListener('click', function () {
                    renderDashboardBags(Number(row.dataset.manifestId));
                });
            });

            if (dashboardManifestData.length) {
                renderDashboardBags(dashboardManifestData[0].id);
            }
        });
    </script>
@stop

@section('footer')
    <strong>Integracion.</strong> Panel operativo de pre-alertas, sacas y paquetes.
@stop

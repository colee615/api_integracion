<style>
    :root {
        --corp-navy: #15549a;
        --corp-slate: #667085;
        --corp-ink: #263238;
        --corp-border: #e1e4dc;
        --corp-soft: #f7f8f4;
        --corp-panel: #ffffff;
        --corp-accent: #ffcc33;
        --corp-gold: #ffcc33;
    }

    body:not(.login-page) .content-wrapper {
        background: #f7f8f5;
    }

    .sidebar-corporate {
        background: #fdfdfa !important;
        box-shadow: none !important;
    }

    .main-sidebar,
    .main-sidebar::before {
        box-shadow: none !important;
    }

    .main-sidebar {
        border-right: 1px solid #e4e6df;
    }

    .sidebar-brand-corporate {
        background: #ffcc33 !important;
        border-bottom: 1px solid rgba(63, 63, 55, 0.12);
    }

    .sidebar-dark .brand-link,
    .sidebar-corporate .brand-link {
        color: #263238 !important;
        font-weight: 500;
        letter-spacing: 0;
        padding: .55rem .8rem;
    }

    .sidebar-corporate .brand-image,
    .sidebar-brand-corporate .brand-image {
        border: 0;
        background: transparent;
        padding: 0;
        box-shadow: none;
    }

    .sidebar-brand-corporate.brand-link {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: .75rem;
        min-height: 64px;
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .sidebar-brand-corporate.brand-link img {
        height: 26px;
        width: auto;
        max-width: 80px;
        object-fit: contain;
        float: none !important;
        margin: 0 !important;
        position: static !important;
        left: auto !important;
        right: auto !important;
    }

    .sidebar-brand-corporate .brand-text,
    .sidebar-brand-corporate.brand-link .brand-text {
        display: flex !important;
        min-width: 0;
        margin: 0;
        white-space: normal;
    }

    .sidebar-brand-corporate .brand-copy {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
    }

    .sidebar-brand-corporate .brand-copy strong {
        font-size: 1rem;
        font-weight: 600;
    }

    .sidebar-brand-corporate .brand-copy small {
        margin-top: .18rem;
        font-size: .65rem;
        font-weight: 400;
    }

    .sidebar-corporate .nav-sidebar > .nav-item {
        margin-bottom: .18rem;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link {
        border-radius: .55rem;
        color: #46545a;
        padding: .68rem .8rem;
        font-size: .88rem;
        font-weight: 500;
        transition: background-color .16s ease, color .16s ease;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link .nav-icon {
        color: #718078;
        margin-right: .5rem;
        font-size: .95rem;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link:hover {
        background: #f0f1ec;
        color: #263238;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link.active {
        background: #ffcc33;
        color: #202b2f;
        box-shadow: none;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link.active .nav-icon {
        color: #202b2f;
    }

    .sidebar-corporate .nav-header {
        padding: 1.15rem .9rem .5rem;
        color: #8a918a;
        font-size: .66rem;
        font-weight: 600;
        letter-spacing: .16em;
    }

    .sidebar-corporate .nav-treeview {
        margin: .15rem 0 .65rem 1.15rem;
        padding-left: .45rem;
        border-left: 1px solid #dedfd8;
    }

    .sidebar-corporate .nav-treeview > .nav-item > .nav-link {
        color: #59666a;
        border-radius: .4rem;
        margin: .1rem 0;
        padding: .52rem .7rem;
        font-size: .83rem;
    }

    .sidebar-corporate .nav-treeview > .nav-item > .nav-link:hover,
    .sidebar-corporate .nav-treeview > .nav-item > .nav-link.active {
        background: #f0f1ec;
        color: #263238;
    }

    .sidebar-corporate .form-inline {
        margin: .15rem .65rem .8rem !important;
    }

    .sidebar-corporate .form-control-sidebar {
        height: 39px;
        border: 1px solid #e2e4dc;
        border-radius: .5rem 0 0 .5rem;
        background: #f7f8f4;
        color: #36454a;
        font-size: .78rem;
    }

    .sidebar-corporate .btn-sidebar {
        height: 39px;
        border: 1px solid #e2e4dc;
        border-left: 0;
        border-radius: 0 .5rem .5rem 0;
        background: #f7f8f4;
        color: #5d6a6d;
    }

    .sidebar-footer-brand {
        margin: 1.4rem 1.05rem 1rem;
        padding-top: .8rem;
        border-top: 1px solid #e4e6df;
        color: #536167;
        font-size: .74rem;
        line-height: 1.6;
    }

    .sidebar-footer-brand strong,
    .sidebar-footer-brand span {
        display: block;
    }

    .sidebar-footer-brand span {
        color: #849095;
        font-size: .68rem;
    }

    .content-header {
        padding-bottom: .35rem;
    }

    .main-header.navbar {
        min-height: 64px;
        background: #ffffff;
        border-bottom: 1px solid #e4e6df;
        box-shadow: none;
        backdrop-filter: blur(10px);
    }

    .main-header .nav-link,
    .main-header .navbar-nav .nav-link {
        color: #435259 !important;
    }

    .main-header .nav-link:hover,
    .main-header .navbar-nav .nav-link:hover {
        color: #15549a !important;
    }

    .user-header.bg-user-menu-corporate {
        background: #ffcc33 !important;
        color: #263238 !important;
    }

    .user-menu .nav-link {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
    }

    .user-menu-brandmark {
        display: inline-flex;
        width: 32px;
        height: 32px;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: .35rem;
        background: #ffcc33;
        box-shadow: 0 2px 6px rgba(35, 45, 35, .14);
    }

    .user-menu-brandmark img {
        display: block;
        width: 28px;
        height: auto;
    }

    .admin-header-context {
        align-items: center;
        gap: .65rem;
        margin-left: .65rem;
        color: #34434a;
        font-size: .72rem;
        letter-spacing: .015em;
    }

    .admin-header-context .context-brand {
        font-weight: 600;
        text-transform: uppercase;
    }

    .admin-header-context .context-divider {
        color: #a1a8a3;
    }

    .admin-header-context .context-page {
        color: #7b8588;
    }

    .page-shell {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .page-hero {
        position: relative;
        overflow: hidden;
        border-radius: .75rem;
        padding: 1.25rem 1.4rem;
        background: #ffffff;
        color: #263238;
        border: 1px solid var(--corp-border);
        box-shadow: 0 3px 12px rgba(35, 45, 35, 0.04);
    }

    .page-hero::after {
        content: "";
        position: absolute;
        inset: auto -70px -82px auto;
        width: 210px;
        height: 210px;
        background: rgba(255, 204, 51, 0.12);
        border-radius: 999px;
    }

    .page-hero-heading {
        position: relative;
        z-index: 1;
        gap: 1rem;
    }

    .page-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .3rem .7rem;
        border: 1px solid rgba(38, 50, 56, 0.12);
        border-radius: .4rem;
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #657176;
        margin-bottom: .75rem;
        background: #fffdf5;
    }

    .page-title {
        margin: 0;
        font-size: 2.1rem;
        font-weight: 700;
        letter-spacing: -.02em;
    }

    .page-subtitle {
        margin: .45rem 0 0;
        max-width: 760px;
        color: #68777c;
        font-size: 1rem;
    }

    .metric-card {
        border: 0;
        border-radius: .75rem;
        background: #ffffff;
        border: 1px solid var(--corp-border);
        box-shadow: 0 4px 14px rgba(35, 45, 35, 0.045);
        overflow: hidden;
    }

    .metric-card .small-box {
        margin-bottom: 0;
        border-radius: 1rem;
        background: transparent !important;
        color: var(--corp-ink) !important;
    }

    .metric-card .small-box .icon {
        top: 16px;
        right: 18px;
        opacity: .14;
        color: #c49a18 !important;
        font-size: 52px;
    }

    .metric-card .small-box h3 {
        font-size: 1.8rem;
        font-weight: 700;
    }

    .metric-card .small-box p {
        color: #68777c;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        font-size: .76rem;
    }

    .panel-card.card {
        border: 1px solid var(--corp-border);
        border-radius: .75rem;
        box-shadow: 0 4px 14px rgba(35, 45, 35, 0.045);
        overflow: hidden;
    }

    .panel-card .card-header {
        border-bottom: 1px solid #eceee8;
        background: #ffffff;
        padding: 1rem 1.15rem;
    }

    .panel-card .card-title {
        font-weight: 700;
        letter-spacing: -.01em;
        color: #34434a;
    }

    .panel-card .card-body {
        padding: 1.15rem;
        background: #ffffff;
    }

    .form-zone {
        background: #ffffff;
    }

    .form-zone .form-control,
    .form-zone .custom-select {
        border-color: #d8e1eb;
        border-radius: .65rem;
        min-height: 42px;
        box-shadow: none;
    }

    .form-zone .form-control:focus,
    .form-zone .custom-select:focus {
        border-color: #a8b4b7;
        box-shadow: 0 0 0 .12rem rgba(21, 84, 154, 0.12);
    }

    .table-style-submit.btn,
    .table-style-submit button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        min-height: 40px;
        padding: .65rem 1rem;
        border-radius: .65rem;
        border: 1px solid #d8e1eb !important;
        background: #f8fbfd !important;
        color: #274c77 !important;
        box-shadow: none !important;
        font-weight: 700;
    }

    .table-style-submit.btn:hover,
    .table-style-submit button:hover {
        background: #eef4fb !important;
        border-color: #c9d7e6 !important;
        color: #1f3e61 !important;
    }

    .corp-table {
        margin-bottom: 0;
    }

    .corp-table thead th {
        border-top: 0;
        border-bottom: 1px solid #e1e4dc;
        background: #f5f6f2;
        color: #667176;
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        font-weight: 700;
    }

    .corp-table tbody td {
        vertical-align: middle;
        border-color: #e8eef5;
        padding: 1rem .9rem;
    }

    .corp-table tbody tr:hover {
        background: #fafbf8;
    }

    .detail-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        padding: .45rem .75rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 700;
        background: #fff8df;
        color: #755c11;
        border: 1px solid #f0df9e;
    }

    .section-note {
        padding: .85rem 1rem;
        border-radius: .9rem;
        background: #f8f9f6;
        border: 1px solid #e3e6de;
        color: #667176;
    }

    .token-cell .input-group-text,
    .token-cell .btn {
        border-color: #d8e1eb;
    }

    .token-cell input.form-control[readonly] {
        background: #fcfdff;
        border-color: #d8e1eb;
        font-family: Consolas, monospace;
        font-size: .84rem;
    }

    .inline-actions,
    .inline-actions form {
        margin: 0;
        width: 100%;
    }

    .inline-actions .btn,
    .inline-actions button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        width: 100%;
        min-width: 160px;
        min-height: 40px;
        padding: .65rem .9rem;
        border-radius: .65rem;
        font-weight: 700;
        font-size: .86rem;
        line-height: 1.2;
        white-space: nowrap;
    }

    .company-table td {
        vertical-align: top;
    }

    .user-table td:last-child {
        width: 190px;
    }

    .company-table strong {
        color: var(--corp-ink);
    }

    .company-locale-form,
    .company-actions {
        display: flex;
        flex-direction: column;
        gap: .45rem;
        margin: 0;
    }

    .company-locale-form .form-control {
        min-width: 140px;
    }

    .company-locale-form .btn,
    .company-actions .btn,
    .company-actions form {
        width: 100%;
    }

    .company-actions {
        min-width: 170px;
    }

    .company-actions .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        width: 100%;
        min-height: 38px;
        padding: .65rem .9rem;
        border-radius: .65rem;
        font-weight: 700;
        font-size: .86rem;
        line-height: 1.2;
        white-space: nowrap;
    }

    .company-session-chip {
        min-width: 110px;
        white-space: nowrap;
    }

    .company-load-note {
        margin-top: .35rem;
        font-size: .8rem;
    }

    .action-stack {
        display: flex;
        flex-direction: column;
        gap: .45rem;
        min-width: 170px;
    }

    .action-stack > .btn,
    .action-stack > form {
        width: 100%;
        margin: 0;
    }

    .action-stack form .btn,
    .action-stack form button,
    .action-stack > .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        width: 100%;
        min-height: 40px;
        padding: .65rem .9rem;
        border-radius: .65rem;
        font-weight: 700;
        font-size: .86rem;
        line-height: 1.2;
        white-space: nowrap;
    }

    @media (max-width: 991.98px) {
        .company-actions {
            min-width: 155px;
        }
    }

    .login-page {
        background:
            radial-gradient(circle at top left, rgba(15, 118, 110, 0.18), transparent 25%),
            linear-gradient(135deg, #0f172a 0%, #132d46 48%, #1f6f78 100%);
    }

    .login-logo a {
        color: #fff !important;
        font-weight: 700;
        letter-spacing: -.02em;
    }

    .login-card-body,
    .register-card-body {
        padding: 1.75rem;
    }

    .login-box .card {
        border: 0;
        border-radius: 1.2rem;
        box-shadow: 0 24px 50px rgba(15, 23, 42, 0.28);
        overflow: hidden;
    }

    .login-box-msg {
        color: #64748b;
        margin-bottom: 1.25rem;
    }

    .corp-auth-note {
        border-radius: .9rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: .8rem .9rem;
        color: #475569;
        font-size: .9rem;
    }

    .main-footer {
        background: rgba(255, 255, 255, 0.96);
        border-top: 1px solid #e4e6df;
        color: #748084;
    }

    .preloader {
        background: rgba(255, 255, 255, .96) !important;
    }

    .preloader-brand-logo {
        object-fit: contain;
        border-radius: 0 !important;
        box-shadow: none;
    }

    .swal2-popup.corp-swal-popup {
        border-radius: 12px !important;
        border: 1px solid #e1e4dc !important;
        box-shadow: 0 16px 40px rgba(35, 45, 35, 0.12) !important;
    }

    .swal2-title.corp-swal-title {
        color: #263238 !important;
        font-size: 1.7rem !important;
        font-weight: 700 !important;
        letter-spacing: -.02em !important;
        margin-bottom: .5rem !important;
    }

    .swal2-confirm.corp-swal-confirm {
        min-width: 150px;
        min-height: 44px;
        border-radius: 7px !important;
        border: 0 !important;
        background: #ffcc33 !important;
        color: #253238 !important;
        box-shadow: none !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
    }

    .swal2-confirm.corp-swal-confirm:focus {
        box-shadow: 0 0 0 .2rem rgba(255, 204, 51, 0.24) !important;
    }

    /* Shared finishing pass: pages with their own component styles still use the same admin palette. */
    body:not(.login-page) {
        font-family: "Segoe UI", Arial, sans-serif;
        color: #34434a;
    }

    .btn-primary,
    .btn-primary:hover,
    .btn-primary:focus {
        border-color: #efbd20 !important;
        background: #ffcc33 !important;
        color: #253238 !important;
        box-shadow: none !important;
    }

    .btn-outline-primary {
        border-color: #e2bd43;
        color: #5e501e;
    }

    .btn-outline-primary:hover {
        border-color: #efbd20;
        background: #ffcc33;
        color: #253238;
    }

    .form-control,
    .custom-select {
        border-color: #dfe3db;
        border-radius: .45rem;
        box-shadow: none;
    }

    .form-control:focus,
    .custom-select:focus {
        border-color: #d6b342;
        box-shadow: 0 0 0 .12rem rgba(255, 204, 51, .2);
    }

    .ops-header,
    .ops-card,
    .ops-meta,
    .ops-stat,
    .ops-panel,
    .ops-vs-card,
    .ops-search,
    .ops-summary,
    .ops-person,
    .ops-item,
    .summary-card,
    .person-card,
    .shipment-meta-card,
    .modal-panel,
    .detail-grid .item {
        border-color: var(--corp-border) !important;
        border-radius: .7rem !important;
        background: #ffffff !important;
        box-shadow: 0 3px 12px rgba(35, 45, 35, .035) !important;
    }

    .ops-header,
    .ops-search,
    .ops-person-head,
    .person-card-header,
    .calendar-month {
        background: #ffffff !important;
    }

    .ops-title,
    .ops-panel-title,
    .ops-meta-value,
    .ops-stat-value,
    .ops-list-title,
    .ops-summary strong,
    .ops-person-name,
    .ops-item-value,
    .ops-vs-ratio,
    .summary-card .value,
    .person-card-name,
    .shipment-meta-card .value,
    .detail-grid .item .value,
    .calendar-year-title {
        color: #263238 !important;
    }

    .ops-kicker,
    .ops-label,
    .ops-subtitle,
    .ops-note,
    .ops-list-meta,
    .ops-vs-title,
    .ops-vs-meta,
    .ops-person-title,
    .summary-card .label,
    .person-card-subtitle,
    .person-card-field .label,
    .shipment-meta-card .label,
    .detail-grid .item .label,
    .calendar-month-title,
    .calendar-weekday {
        color: #737f82 !important;
    }

    .ops-btn,
    .ops-btn:hover,
    .interactive-trigger,
    .calendar-nav-link,
    .calendar-day-link {
        border-color: #e0e3db !important;
        border-radius: .45rem !important;
        background: #ffffff !important;
        color: #46545a !important;
        box-shadow: none !important;
    }

    .ops-btn-primary,
    .ops-btn-primary:hover,
    .calendar-day-link.is-selected {
        border-color: #efbd20 !important;
        background: #ffcc33 !important;
        color: #253238 !important;
        box-shadow: none !important;
    }

    .ops-list-card,
    .ops-empty,
    .person-card-empty {
        border-color: #e1e4dc !important;
        border-radius: .6rem !important;
        background: #fafbf8 !important;
        color: #68777c !important;
    }

    .ops-list-card:hover,
    .ops-list-card.active,
    .interactive-row:hover,
    .interactive-row.is-active {
        border-color: #e8c84f !important;
        background: #fff9e5 !important;
        box-shadow: none !important;
        transform: none !important;
    }

    .ops-person-head,
    .person-card-header {
        border-bottom-color: #eceee8 !important;
    }

    .ops-vs-track {
        background: #eceee8 !important;
    }

    .ops-vs-fill {
        background: #ffcc33 !important;
    }

    .calendar-year-chip,
    .calendar-day-link.has-records,
    .calendar-day-link.is-today {
        border-color: #ead684 !important;
        background: #fff8df !important;
        color: #66551d !important;
        box-shadow: none !important;
    }

    .corp-table tbody tr:hover {
        background: #fffbed;
    }

    @media (min-width: 768px) and (max-width: 1199.98px) {
        .ops-meta-grid,
        .ops-summary-grid,
        .ops-person-grid,
        .ops-mini-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .ops-stat-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        }

        .ops-vs-grid,
        .ops-detail-grid,
        .ops-explorer {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 767.98px) {
        .ops-meta-grid,
        .ops-vs-grid,
        .ops-explorer,
        .ops-detail-grid,
        .ops-summary-grid,
        .ops-person-grid,
        .ops-mini-grid {
            grid-template-columns: 1fr !important;
        }

        .ops-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .admin-header-context {
            display: none !important;
        }

        .page-hero {
            padding: 1rem;
        }

        .page-title,
        .ops-title {
            font-size: 1.55rem;
        }
    }
</style>

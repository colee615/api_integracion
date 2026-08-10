<style>
    :root {
        --corp-navy: #1f4fb8;
        --corp-slate: #5677bb;
        --corp-ink: #163f9d;
        --corp-border: #d9e4f5;
        --corp-soft: #f6f9fe;
        --corp-panel: #ffffff;
        --corp-accent: #fecc36;
        --corp-gold: #fecc36;
    }

    body:not(.login-page) .content-wrapper {
        background: #ffffff;
    }

    .sidebar-corporate {
        background: #fecc36 !important;
        box-shadow: none !important;
    }

    .main-sidebar,
    .main-sidebar::before {
        box-shadow: none !important;
    }

    .sidebar-brand-corporate {
        background: #fecc36 !important;
        border-bottom: 1px solid rgba(31, 79, 184, 0.14);
    }

    .sidebar-dark .brand-link,
    .sidebar-corporate .brand-link {
        color: #173f9f !important;
        font-weight: 700;
        letter-spacing: -.01em;
        padding: .8rem 1rem;
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
        justify-content: center;
        gap: 0;
        padding-left: .75rem;
        padding-right: .75rem;
    }

    .sidebar-brand-corporate.brand-link img {
        height: 42px;
        width: auto;
        max-width: 150px;
        object-fit: contain;
        float: none !important;
        margin: 0 !important;
        position: static !important;
        left: auto !important;
        right: auto !important;
    }

    .sidebar-brand-corporate .brand-text,
    .sidebar-brand-corporate.brand-link .brand-text {
        display: none;
    }

    .sidebar-corporate .nav-sidebar > .nav-item {
        margin-bottom: .35rem;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link {
        border-radius: .9rem;
        color: rgba(23, 63, 159, 0.92);
        padding: .9rem 1rem;
        font-weight: 700;
        transition: all .18s ease;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link .nav-icon {
        color: #2052bc;
        margin-right: .55rem;
        font-size: 1rem;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link:hover {
        background: rgba(255, 255, 255, 0.35);
        color: #143b99;
        transform: translateX(2px);
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link.active {
        background: linear-gradient(90deg, #1f4fb8 0%, #1841a0 100%);
        color: #ffffff;
        box-shadow: none;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link.active .nav-icon {
        color: #ffffff;
    }

    .sidebar-corporate .nav-treeview > .nav-item > .nav-link {
        color: rgba(255, 255, 255, 0.7);
        border-radius: .75rem;
        margin: .15rem 0;
    }

    .sidebar-corporate .nav-treeview > .nav-item > .nav-link:hover,
    .sidebar-corporate .nav-treeview > .nav-item > .nav-link.active {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
    }

    .content-header {
        padding-bottom: .35rem;
    }

    .main-header.navbar {
        background: #fecc36;
        border-bottom: 1px solid rgba(31, 79, 184, 0.14);
        box-shadow: none;
        backdrop-filter: blur(10px);
    }

    .main-header .nav-link,
    .main-header .navbar-nav .nav-link {
        color: #1f4fb8 !important;
    }

    .main-header .nav-link:hover,
    .main-header .navbar-nav .nav-link:hover {
        color: #143b99 !important;
    }

    .page-shell {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .page-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.35rem;
        padding: 1.45rem 1.6rem;
        background:
            radial-gradient(circle at top right, rgba(31, 79, 184, 0.08), transparent 22%),
            linear-gradient(135deg, #ffffff 0%, #f7faff 52%, #eef4ff 100%);
        color: #173f9f;
        border: 1px solid #d9e4f5;
        box-shadow: 0 18px 45px rgba(20, 59, 153, 0.08);
    }

    .page-hero::after {
        content: "";
        position: absolute;
        inset: auto -70px -82px auto;
        width: 210px;
        height: 210px;
        background: rgba(31, 79, 184, 0.08);
        border-radius: 999px;
    }

    .page-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .3rem .7rem;
        border: 1px solid rgba(31, 79, 184, 0.16);
        border-radius: 999px;
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #5476bc;
        margin-bottom: .75rem;
        background: #ffffff;
    }

    .page-title {
        margin: 0;
        font-size: 2.1rem;
        font-weight: 800;
        letter-spacing: -.03em;
    }

    .page-subtitle {
        margin: .45rem 0 0;
        max-width: 760px;
        color: #5677bb;
        font-size: 1rem;
    }

    .metric-card {
        border: 0;
        border-radius: 1.1rem;
        background: #ffffff;
        border: 1px solid #d9e4f5;
        box-shadow: 0 14px 30px rgba(20, 59, 153, 0.06);
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
        color: #1f57db !important;
        font-size: 52px;
    }

    .metric-card .small-box h3 {
        font-size: 1.8rem;
        font-weight: 700;
    }

    .metric-card .small-box p {
        color: #5f7fc2;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        font-size: .76rem;
    }

    .panel-card.card {
        border: 1px solid var(--corp-border);
        border-radius: 1.15rem;
        box-shadow: 0 14px 34px rgba(20, 59, 153, 0.06);
        overflow: hidden;
    }

    .panel-card .card-header {
        border-bottom: 1px solid #e2ebf8;
        background: #ffffff;
        padding: 1rem 1.15rem;
    }

    .panel-card .card-title {
        font-weight: 700;
        letter-spacing: -.01em;
        color: #173f9f;
    }

    .panel-card .card-body {
        padding: 1.15rem;
        background: #ffffff;
    }

    .form-zone {
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
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
        border-color: #9bb3cc;
        box-shadow: 0 0 0 .12rem rgba(37, 84, 124, 0.1);
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
        border-bottom: 1px solid #d8e4f5;
        background: #f7faff;
        color: #5e7ebf;
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
        background: #f8fbff;
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
        background: #edf4ff;
        color: #1f4fb8;
        border: 1px solid #d7e3f7;
    }

    .section-note {
        padding: .85rem 1rem;
        border-radius: .9rem;
        background: #f8fbff;
        border: 1px solid #d9e4f5;
        color: #5a7bbd;
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
        border-top: 1px solid #d9e4f5;
        color: #5b7bbd;
    }

    .swal2-popup.corp-swal-popup {
        border-radius: 26px !important;
        border: 1px solid #d9e4f5 !important;
        box-shadow: 0 24px 60px rgba(20, 59, 153, 0.16) !important;
    }

    .swal2-title.corp-swal-title {
        color: #173f9f !important;
        font-size: 2rem !important;
        font-weight: 800 !important;
        letter-spacing: -.03em !important;
        margin-bottom: .5rem !important;
    }

    .swal2-confirm.corp-swal-confirm {
        min-width: 170px;
        min-height: 50px;
        border-radius: 14px !important;
        border: 0 !important;
        box-shadow: 0 14px 28px rgba(31, 79, 184, 0.2) !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
    }

    .swal2-confirm.corp-swal-confirm:focus {
        box-shadow: 0 0 0 .2rem rgba(31, 79, 184, 0.14), 0 14px 28px rgba(31, 79, 184, 0.2) !important;
    }
</style>

<style>
    :root {
        --corp-navy: #0f172a;
        --corp-slate: #334155;
        --corp-ink: #1e293b;
        --corp-border: #dbe4ee;
        --corp-soft: #f4f7fb;
        --corp-panel: #ffffff;
        --corp-accent: #0f766e;
        --corp-gold: #b98900;
    }

    body:not(.login-page) .content-wrapper {
        background:
            radial-gradient(circle at top right, rgba(15, 118, 110, 0.08), transparent 20%),
            linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
    }

    .sidebar-corporate {
        background:
            linear-gradient(180deg, #112031 0%, #18324a 18%, #153851 52%, #102938 100%) !important;
    }

    .sidebar-brand-corporate {
        background:
            linear-gradient(90deg, #0b2036 0%, #143b63 60%, #0f766e 100%) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .sidebar-dark .brand-link,
    .sidebar-corporate .brand-link {
        color: #f8fbff !important;
        font-weight: 700;
        letter-spacing: -.01em;
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    .sidebar-corporate .brand-image,
    .sidebar-brand-corporate .brand-image {
        border: 2px solid rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.14);
        padding: .15rem;
    }

    .sidebar-corporate .nav-sidebar > .nav-item {
        margin-bottom: .35rem;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link {
        border-radius: .9rem;
        color: rgba(255, 255, 255, 0.82);
        padding: .9rem 1rem;
        font-weight: 600;
        transition: all .18s ease;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link .nav-icon {
        color: #8fd3ff;
        margin-right: .55rem;
        font-size: 1rem;
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
        transform: translateX(2px);
    }

    .sidebar-corporate .nav-sidebar > .nav-item > .nav-link.active {
        background: linear-gradient(90deg, #1d4ed8 0%, #0f766e 100%);
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(15, 118, 110, 0.25);
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

    .page-shell {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .page-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        padding: 1.35rem 1.5rem;
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 58%, #0f766e 100%);
        color: #fff;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
    }

    .page-hero::after {
        content: "";
        position: absolute;
        inset: auto -80px -90px auto;
        width: 220px;
        height: 220px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 999px;
    }

    .page-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .3rem .7rem;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: .75rem;
    }

    .page-title {
        margin: 0;
        font-size: 1.95rem;
        font-weight: 700;
        letter-spacing: -.02em;
    }

    .page-subtitle {
        margin: .45rem 0 0;
        max-width: 760px;
        color: rgba(255, 255, 255, 0.82);
        font-size: 1rem;
    }

    .metric-card {
        border: 0;
        border-radius: 1rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
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
        opacity: .18;
        color: var(--corp-navy) !important;
        font-size: 52px;
    }

    .metric-card .small-box h3 {
        font-size: 1.8rem;
        font-weight: 700;
    }

    .metric-card .small-box p {
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        font-size: .76rem;
    }

    .panel-card.card {
        border: 1px solid var(--corp-border);
        border-radius: 1rem;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .panel-card .card-header {
        border-bottom: 1px solid #e8eef5;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%);
        padding: 1rem 1.15rem;
    }

    .panel-card .card-title {
        font-weight: 700;
        letter-spacing: -.01em;
    }

    .panel-card .card-body {
        padding: 1.15rem;
    }

    .form-zone {
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    }

    .detail-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .35rem .65rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
        background: #edf4ff;
        color: #274c77;
    }

    .corp-table {
        margin-bottom: 0;
    }

    .corp-table thead th {
        border-top: 0;
        border-bottom: 1px solid #d8e1eb;
        background: #f8fbfd;
        color: #475569;
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        font-weight: 700;
    }

    .corp-table tbody td {
        vertical-align: middle;
        border-color: #e8eef5;
    }

    .corp-table tbody tr:hover {
        background: #fbfdff;
    }

    .stat-pair {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    .stat-box {
        border: 1px solid #e3ebf3;
        border-radius: .85rem;
        background: #f8fbfd;
        padding: .9rem 1rem;
    }

    .stat-box .label {
        display: block;
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: .35rem;
    }

    .stat-box .value {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--corp-ink);
    }

    .section-note {
        padding: .85rem 1rem;
        border-radius: .9rem;
        background: #f7fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
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

    .action-stack {
        display: flex;
        flex-direction: column;
        gap: .45rem;
        min-width: 122px;
    }

    .action-stack .btn,
    .inline-actions .btn {
        border-radius: .65rem;
        font-weight: 600;
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
        background: #fff;
        border-top: 1px solid #e2e8f0;
        color: #64748b;
    }
</style>

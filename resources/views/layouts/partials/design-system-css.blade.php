/* ==========================================================================
   MYBATCHOY / EUSTACE BATCHOY UNIFIED DESIGN SYSTEM
   Tokens:
     bg: #111111 | sidebar: #0A0A0A | surface: #1B1B1B | input: #202020
     border: #333333 | primary: #E53935 | secondary: #FFC107
     text: #FFFFFF | muted: #BDBDBD | success: #4CAF50
   ========================================================================== */

:root {
    color-scheme: dark;
    --input-bg: #202020;
    --surface-hover: #242424;
    --success: #4CAF50;
    --warning: #FFC107;
    --danger: #E53935;
}

body {
    background-color: #111111 !important;
    color: #FFFFFF;
}

/* ---------- Typography Hierarchy ---------- */
.eb-page-header {
    background: linear-gradient(135deg, #1B1B1B 0%, #141414 100%);
    border: 1px solid #333333;
    border-radius: 14px;
    padding: 1.25rem 1.4rem;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
}

.eb-page-header::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #E53935 0%, #FFC107 100%);
}

.eb-page-header-top {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.eb-breadcrumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.45rem;
    font-size: 0.76rem;
    color: #BDBDBD;
}

.eb-section-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: rgba(229, 57, 53, 0.16);
    color: #FFFFFF;
    border: 1px solid rgba(229, 57, 53, 0.45);
    border-radius: 6px;
    padding: 0.18rem 0.55rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.eb-breadcrumb-sep {
    color: #666666;
}

.eb-page-title {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 800;
    color: #FFFFFF;
    letter-spacing: -0.02em;
    line-height: 1.25;
}

.eb-page-subtitle {
    margin: 0.3rem 0 0;
    color: #BDBDBD;
    font-size: 0.88rem;
    line-height: 1.45;
    max-width: 72ch;
}

.eb-header-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.55rem;
}

.eb-header-filters {
    margin-top: 1rem;
    padding-top: 0.95rem;
    border-top: 1px solid #2A2A2A;
}

/* ---------- Buttons ---------- */
.btn {
    border-radius: 9px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.16s ease;
}

.btn-lg {
    font-size: 0.94rem;
    border-radius: 10px;
}

.btn-sm {
    font-size: 0.78rem;
    border-radius: 8px;
}

.btn-primary {
    background-color: #E53935 !important;
    border-color: #E53935 !important;
    color: #FFFFFF !important;
    font-weight: 700;
}

.btn-primary:hover,
.btn-primary:focus {
    background-color: #C62828 !important;
    border-color: #C62828 !important;
    color: #FFFFFF !important;
    box-shadow: 0 4px 14px rgba(229, 57, 53, 0.32);
}

.btn-secondary {
    background-color: #262626 !important;
    border-color: #3A3A3A !important;
    color: #FFFFFF !important;
}

.btn-secondary:hover,
.btn-secondary:focus {
    background-color: #333333 !important;
    border-color: #4A4A4A !important;
    color: #FFFFFF !important;
}

.btn-warning {
    background-color: #FFC107 !important;
    border-color: #FFC107 !important;
    color: #111111 !important;
    font-weight: 700;
}

.btn-warning:hover,
.btn-warning:focus {
    background-color: #FFB300 !important;
    border-color: #FFB300 !important;
    color: #111111 !important;
}

.btn-success {
    background-color: #2E7D32 !important;
    border-color: #4CAF50 !important;
    color: #FFFFFF !important;
    font-weight: 700;
}

.btn-success:hover,
.btn-success:focus {
    background-color: #1B5E20 !important;
    border-color: #4CAF50 !important;
    color: #FFFFFF !important;
}

.btn-danger {
    background-color: #C62828 !important;
    border-color: #E53935 !important;
    color: #FFFFFF !important;
    font-weight: 700;
}

.btn-danger:hover,
.btn-danger:focus {
    background-color: #B71C1C !important;
    border-color: #E53935 !important;
    color: #FFFFFF !important;
}

.btn-outline-light {
    background-color: #141414 !important;
    border-color: #333333 !important;
    color: #FFFFFF !important;
}

.btn-outline-light:hover,
.btn-outline-light:focus {
    background-color: #242424 !important;
    border-color: #555555 !important;
    color: #FFFFFF !important;
}

.btn-outline-primary {
    background-color: rgba(229, 57, 53, 0.08) !important;
    border-color: rgba(229, 57, 53, 0.5) !important;
    color: #FF8A80 !important;
}

.btn-outline-primary:hover,
.btn-outline-primary:focus {
    background-color: #E53935 !important;
    border-color: #E53935 !important;
    color: #FFFFFF !important;
}

.btn-outline-warning {
    background-color: rgba(255, 193, 7, 0.08) !important;
    border-color: rgba(255, 193, 7, 0.5) !important;
    color: #FFC107 !important;
}

.btn-outline-warning:hover,
.btn-outline-warning:focus {
    background-color: #FFC107 !important;
    border-color: #FFC107 !important;
    color: #111111 !important;
}

.btn-outline-danger {
    background-color: rgba(229, 57, 53, 0.08) !important;
    border-color: rgba(229, 57, 53, 0.5) !important;
    color: #FF8A80 !important;
}

.btn-outline-danger:hover,
.btn-outline-danger:focus {
    background-color: #E53935 !important;
    border-color: #E53935 !important;
    color: #FFFFFF !important;
}

.btn-outline-success {
    background-color: rgba(76, 175, 80, 0.1) !important;
    border-color: rgba(76, 175, 80, 0.5) !important;
    color: #81C784 !important;
}

.btn-outline-success:hover,
.btn-outline-success:focus {
    background-color: #2E7D32 !important;
    border-color: #4CAF50 !important;
    color: #FFFFFF !important;
}

/* ---------- Form Controls & Filters ---------- */
.form-label {
    color: #E5E5E5;
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 0.38rem;
}

.form-control,
.form-select {
    background-color: #202020 !important;
    border: 1px solid #333333 !important;
    color: #FFFFFF !important;
    border-radius: 9px;
    font-size: 0.88rem;
}

.form-control::placeholder {
    color: #888888 !important;
}

.form-control:focus,
.form-select:focus {
    background-color: #242424 !important;
    border-color: #FFC107 !important;
    color: #FFFFFF !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.18) !important;
}

.form-control[readonly],
.form-control:disabled,
.form-select:disabled {
    background-color: #161616 !important;
    color: #BDBDBD !important;
    border-color: #2A2A2A !important;
    opacity: 0.85;
}

.form-check-input {
    background-color: #202020;
    border-color: #4A4A4A;
}

.form-check-input:checked {
    background-color: #E53935;
    border-color: #E53935;
}

.form-check-input:focus {
    border-color: #FFC107;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.2);
}

.input-group-text {
    background-color: #161616 !important;
    border-color: #333333 !important;
    color: #BDBDBD !important;
}

.form-text {
    color: #BDBDBD !important;
    font-size: 0.78rem;
}

.eb-filter-card {
    background: #1B1B1B;
    border: 1px solid #333333;
    border-radius: 12px;
    padding: 1rem 1.15rem;
    margin-bottom: 1.25rem;
}

/* ---------- Tabs & Navigation Pills ---------- */
.eb-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin-bottom: 1.25rem;
    padding: 0.4rem;
    background: #161616;
    border: 1px solid #333333;
    border-radius: 12px;
}

.eb-tab-link {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.5rem 0.9rem;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #BDBDBD;
    text-decoration: none;
    border: 1px solid transparent;
    transition: all 0.15s ease;
}

.eb-tab-link:hover {
    color: #FFFFFF;
    background: #222222;
}

.eb-tab-link.active {
    background: #E53935;
    color: #FFFFFF;
    border-color: #E53935;
    font-weight: 700;
}

/* ---------- Unified Table Card & Tables ---------- */
.eb-table-card,
.eb-panel-card {
    background: #1B1B1B;
    border: 1px solid #333333;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.22);
}

.eb-panel-header,
.eb-table-header {
    padding: 0.9rem 1.15rem;
    background: #161616;
    border-bottom: 1px solid #2A2A2A;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
}

.eb-panel-title,
.eb-table-title {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: #FFFFFF;
    margin: 0;
}

.eb-panel-body {
    padding: 1.1rem 1.15rem;
}

.eb-panel-action,
.eb-view-all-link {
    color: #E53935;
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: none;
    transition: color 0.15s ease;
}

.eb-panel-action:hover,
.eb-view-all-link:hover {
    color: #FFC107;
}

.eb-table {
    --bs-table-bg: transparent;
    --bs-table-color: #FFFFFF;
    --bs-table-hover-bg: #222222;
    --bs-table-hover-color: #FFFFFF;
    --bs-table-border-color: #2A2A2A;
    margin-bottom: 0;
}

.eb-table thead th {
    background-color: #141414 !important;
    color: #BDBDBD !important;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.75rem 0.9rem;
    border-bottom: 1px solid #333333 !important;
    white-space: nowrap;
}

.eb-table tbody td {
    padding: 0.8rem 0.9rem;
    border-bottom: 1px solid #262626 !important;
    color: #FFFFFF;
    font-size: 0.86rem;
    vertical-align: middle;
}

.eb-table tbody tr:last-child td {
    border-bottom: 0 !important;
}

.eb-table-footer {
    padding: 0.8rem 1.15rem;
    background: #161616;
    border-top: 1px solid #2A2A2A;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

/* ---------- Status Badges & Transaction Pills ---------- */
.eb-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.24rem 0.62rem;
    border-radius: 6px;
    font-size: 0.73rem;
    font-weight: 700;
    line-height: 1.25;
    white-space: nowrap;
}

.eb-badge-success {
    background: rgba(76, 175, 80, 0.16);
    color: #81C784;
    border: 1px solid rgba(76, 175, 80, 0.45);
}

.eb-badge-warning {
    background: rgba(255, 193, 7, 0.16);
    color: #FFC107;
    border: 1px solid rgba(255, 193, 7, 0.45);
}

.eb-badge-danger {
    background: rgba(229, 57, 53, 0.18);
    color: #FF8A80;
    border: 1px solid rgba(229, 57, 53, 0.5);
}

.eb-badge-neutral {
    background: #242424;
    color: #BDBDBD;
    border: 1px solid #3A3A3A;
}

/* Brand-strict Spoilage Pill (Zero purple) */
.eb-tx-pill.tx-spoil {
    background: #7F1D1D !important;
    color: #FFCDD2 !important;
    border: 1px solid #E53935;
}

/* ---------- Alerts & Feedback ---------- */
.eb-alert {
    border-radius: 11px;
    padding: 0.9rem 1.1rem;
    margin-bottom: 1.1rem;
    border: 1px solid #333333;
    background: #1B1B1B;
    color: #FFFFFF;
}

.eb-alert-success {
    background: rgba(76, 175, 80, 0.12);
    border-color: rgba(76, 175, 80, 0.5);
    color: #C8E6C9;
}

.eb-alert-warning {
    background: rgba(255, 193, 7, 0.12);
    border-color: rgba(255, 193, 7, 0.5);
    color: #FFE082;
}

.eb-alert-danger {
    background: rgba(229, 57, 53, 0.14);
    border-color: rgba(229, 57, 53, 0.55);
    color: #FFCDD2;
}

.eb-alert-info {
    background: #1F1F1F;
    border-color: #444444;
    color: #E0E0E0;
}

/* ---------- Empty State ---------- */
.eb-empty-state {
    text-align: center;
    padding: 2.75rem 1.25rem;
}

.eb-empty-icon {
    width: 52px;
    height: 52px;
    margin: 0 auto 0.85rem;
    border-radius: 50%;
    background: #242424;
    border: 1px solid #333333;
    color: #FFC107;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.eb-empty-title {
    font-size: 1rem;
    font-weight: 700;
    color: #FFFFFF;
    margin-bottom: 0.35rem;
}

.eb-empty-desc {
    font-size: 0.84rem;
    color: #BDBDBD;
    max-width: 44ch;
    margin: 0 auto 1rem;
}

/* ---------- Modals & Dropdowns ---------- */
.modal-content {
    background-color: #1B1B1B !important;
    border: 1px solid #333333 !important;
    border-radius: 14px !important;
    color: #FFFFFF !important;
}

.modal-header,
.modal-footer {
    border-color: #2C2C2C !important;
}

.dropdown-menu {
    background-color: #1B1B1B !important;
    border: 1px solid #333333 !important;
    border-radius: 10px !important;
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.55);
}

.dropdown-item {
    color: #FFFFFF !important;
    font-size: 0.85rem;
}

.dropdown-item:hover,
.dropdown-item:focus {
    background-color: #242424 !important;
    color: #FFC107 !important;
}

/* ---------- Pagination (Bootstrap 5 Dark Override) ---------- */
.pagination {
    --bs-pagination-bg: #141414;
    --bs-pagination-border-color: #333333;
    --bs-pagination-color: #BDBDBD;
    --bs-pagination-hover-bg: #242424;
    --bs-pagination-hover-color: #FFFFFF;
    --bs-pagination-hover-border-color: #4A4A4A;
    --bs-pagination-focus-bg: #242424;
    --bs-pagination-focus-color: #FFC107;
    --bs-pagination-focus-box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.2);
    --bs-pagination-active-bg: #E53935;
    --bs-pagination-active-border-color: #E53935;
    --bs-pagination-active-color: #FFFFFF;
    --bs-pagination-disabled-bg: #121212;
    --bs-pagination-disabled-border-color: #262626;
    --bs-pagination-disabled-color: #555555;
    margin-bottom: 0;
    gap: 0.25rem;
}

.pagination .page-link {
    border-radius: 7px !important;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.35rem 0.68rem;
}

/* ---------- Dashboard Class Compatibility ---------- */
.eb-hero-title {
    margin: 0;
    font-size: 1.55rem;
    font-weight: 800;
    color: #FFFFFF;
    letter-spacing: -0.02em;
    line-height: 1.25;
}

.eb-hero-subtitle {
    margin: 0.3rem 0 0;
    color: #BDBDBD;
    font-size: 0.9rem;
}

.eb-kpi-meta {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.74rem;
    font-weight: 700;
    margin-top: 0.25rem;
}

.eb-kpi-meta.meta-up {
    color: #4CAF50;
}

.eb-kpi-meta.meta-warn {
    color: #FFC107;
}

.eb-kpi-meta.meta-danger {
    color: #E53935;
}

.donut-num {
    font-size: 1.55rem;
    font-weight: 800;
    color: #FFFFFF;
    line-height: 1;
}

.donut-lbl {
    font-size: 0.76rem;
    color: #BDBDBD;
    margin-top: 2px;
}

.eb-legend-list li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.45rem 0;
    border-bottom: 1px solid #262626;
}

.eb-legend-list li:last-child {
    border-bottom: 0;
}

.eb-legend-label {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    color: #BDBDBD;
    font-size: 0.86rem;
}

.eb-legend-val {
    font-weight: 800;
    color: #FFFFFF;
    font-size: 0.9rem;
}

.eb-purchase-total-box .total-amount {
    font-size: 1.45rem;
    font-weight: 800;
    color: #FFFFFF;
    margin: 0.15rem 0;
}

.eb-sys-check {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #2E7D32;
    color: #FFFFFF;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.95rem;
    margin: 0 auto;
}

.eb-sys-clock {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #242424;
    border: 1px solid #333333;
    color: #FFC107;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    margin: 0 auto;
}

.eb-sys-label {
    font-size: 0.73rem;
    color: #FFFFFF;
    font-weight: 600;
    line-height: 1.3;
    margin-top: 0.4rem;
}

.apk-title {
    font-size: 0.82rem;
    font-weight: 800;
    color: #FFC107;
    letter-spacing: 0.02em;
}

.apk-sub {
    font-size: 1rem;
    font-weight: 800;
    color: #FFFFFF;
    line-height: 1.15;
}

.apk-desc {
    font-size: 0.72rem;
    color: #BDBDBD;
    margin: 0.3rem 0 0.6rem;
}

.btn-apk {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: #FFC107;
    color: #111111;
    font-weight: 800;
    font-size: 0.74rem;
    padding: 0.35rem 0.65rem;
    border-radius: 7px;
    text-decoration: none;
    transition: background-color 0.15s ease;
}

.btn-apk:hover {
    background: #FFB300;
    color: #111111;
}

.eb-apk-phone {
    width: 64px;
    flex-shrink: 0;
    background: #0A0A0A;
    border: 2px solid #333333;
    border-radius: 12px;
    padding: 0.45rem 0.35rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.eb-apk-phone img {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    object-fit: cover;
}

.eb-quick-btn.qb-primary {
    background: #E53935;
    border-color: #E53935;
    color: #FFFFFF;
}

.eb-quick-btn.qb-primary:hover {
    background: #C62828;
    border-color: #C62828;
}

/* ---------- Auth & Error Shared Pages ---------- */
.eb-auth-card {
    background: #1B1B1B;
    border: 1px solid #333333;
    border-radius: 16px;
    box-shadow: 0 22px 54px rgba(0, 0, 0, 0.55);
    overflow: hidden;
    position: relative;
}

.eb-auth-card::before {
    content: "";
    display: block;
    height: 4px;
    background: linear-gradient(90deg, #E53935 0%, #FFC107 100%);
}

.eb-auth-header {
    padding: 1.5rem 1.75rem 1rem;
    border-bottom: 1px solid #2A2A2A;
    text-align: center;
}

.eb-auth-logo {
    width: 58px;
    height: 58px;
    border-radius: 12px;
    border: 2px solid #E53935;
    object-fit: cover;
    margin-bottom: 0.65rem;
}

.eb-auth-body {
    padding: 1.45rem 1.75rem 1.6rem;
}

.eb-auth-footer {
    padding: 0.9rem 1.75rem;
    background: #141414;
    border-top: 1px solid #2A2A2A;
    font-size: 0.78rem;
    color: #BDBDBD;
}

.eb-password-wrap {
    position: relative;
}

.eb-password-wrap .form-control {
    padding-right: 4.5rem;
}

.eb-password-toggle {
    position: absolute;
    right: 0.45rem;
    top: 50%;
    transform: translateY(-50%);
    background: #141414;
    border: 1px solid #333333;
    color: #BDBDBD;
    border-radius: 6px;
    padding: 0.22rem 0.55rem;
    font-size: 0.74rem;
    font-weight: 600;
    cursor: pointer;
}

.eb-password-toggle:hover {
    color: #FFC107;
    border-color: #FFC107;
}

/* ---------- Responsive Consistency ---------- */
@media (max-width: 767.98px) {
    .eb-page-header {
        padding: 1rem;
        border-radius: 12px;
    }

    .eb-page-header-top {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }

    .eb-header-actions {
        width: 100%;
    }

    .eb-header-actions .btn {
        flex: 1 1 auto;
        min-height: 40px;
        justify-content: center;
    }

    .eb-page-title {
        font-size: 1.22rem;
    }

    .eb-page-subtitle {
        font-size: 0.82rem;
    }

    .eb-hero-title {
        font-size: 1.25rem;
    }

    .eb-hero-subtitle {
        font-size: 0.8rem;
    }

    .eb-filter-card {
        padding: 0.85rem;
        border-radius: 12px;
    }

    .form-control,
    .form-select,
    .btn {
        min-height: 42px;
    }

    .btn-sm {
        min-height: 36px;
        padding: 0.4rem 0.72rem;
    }

    .eb-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 0.35rem;
    }

    .eb-tabs::-webkit-scrollbar {
        display: none;
    }

    .eb-tab-link {
        flex-shrink: 0;
        white-space: nowrap;
        min-height: 38px;
    }

    /* Stacked Mobile Card Tables (Zero horizontal overflow on phones) */
    .eb-responsive-table {
        display: block;
        width: 100%;
    }

    .eb-responsive-table thead {
        display: none;
    }

    .eb-responsive-table tbody {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        padding: 0.75rem;
    }

    .eb-responsive-table tbody tr {
        display: block;
        background: #141414;
        border: 1px solid #2D2D2D;
        border-radius: 12px;
        padding: 0.75rem 0.85rem;
    }

    .eb-responsive-table tbody tr:hover {
        background: #181818;
    }

    .eb-responsive-table tbody td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.36rem 0 !important;
        border-bottom: 1px solid #242424 !important;
        text-align: right !important;
        white-space: normal !important;
        max-width: none !important;
        font-size: 0.83rem;
    }

    .eb-responsive-table tbody td::before {
        content: attr(data-label);
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #9E9E9E;
        text-align: left;
        flex-shrink: 0;
    }

    .eb-responsive-table tbody td.eb-cell-primary {
        display: block;
        text-align: left !important;
        padding-top: 0.1rem !important;
        padding-bottom: 0.5rem !important;
        margin-bottom: 0.1rem;
    }

    .eb-responsive-table tbody td.eb-cell-primary::before {
        display: none;
    }

    .eb-responsive-table tbody td.eb-cell-actions {
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 0.45rem;
        padding-top: 0.55rem !important;
        border-bottom: 0 !important;
    }

    .eb-responsive-table tbody td.eb-cell-actions::before {
        display: none;
    }

    .eb-responsive-table tbody tr td:last-child {
        border-bottom: 0 !important;
    }

    .eb-table-footer {
        justify-content: center;
        text-align: center;
        padding: 0.75rem 0.9rem;
    }

    .eb-table thead th,
    .eb-table tbody td {
        padding: 0.65rem 0.6rem;
        font-size: 0.8rem;
    }

    .eb-auth-header,
    .eb-auth-body,
    .eb-auth-footer {
        padding-left: 1.15rem;
        padding-right: 1.15rem;
    }
}


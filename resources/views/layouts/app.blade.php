<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'MyBatchoy') }} - @yield('title', 'Inventory System')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        // Immediately restore collapsed sidebar state to prevent layout flash
        (function() {
            try {
                if (localStorage.getItem('mybatchoy_sidebar_collapsed') === 'true' && window.innerWidth >= 992) {
                    document.documentElement.classList.add('sidebar-is-collapsed');
                }
            } catch(e) {}
        })();
    </script>
    <style>
        :root {
            --bg: #111111;              /* near-black main background */
            --bg-soft: #161616;
            --sidebar: #0A0A0A;         /* deep black */
            --surface: #1B1B1B;         /* charcoal */
            --surface-2: #222222;
            --border: #333333;          /* subtle dark gray */
            --primary: #E53935;         /* strong red primary accent */
            --primary-soft: #EF5350;    /* secondary red */
            --secondary: #FFC107;       /* warm yellow secondary accent */
            --text: #FFFFFF;            /* white (essential text & key figures) */
            --muted: #BDBDBD;           /* light gray secondary text */
            --success: #4CAF50;         /* green for successful operations */
            --warning: #FFC107;         /* yellow for warnings */
            --danger: #E53935;          /* red for danger/out-of-stock */
            --shadow: rgba(0, 0, 0, 0.7);
            --sidebar-expanded-width: 260px;
            --sidebar-collapsed-width: 76px;
            --sidebar-transition-duration: 250ms;
            --sidebar-timing: cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        a { text-decoration: none; }

        .muted { color: var(--muted) !important; }
        .accent { color: var(--primary) !important; }
        .text-accent-yellow { color: var(--secondary) !important; }
        .text-accent-red { color: var(--primary) !important; }
        .text-danger { color: var(--danger) !important; }
        .text-warning { color: var(--warning) !important; }
        .text-success { color: var(--success) !important; }
        .border-border { border-color: var(--border) !important; }
        .bg-surface { background: var(--surface) !important; }
        .bg-surface-2 { background: var(--surface-2) !important; }

        .brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.02em;
        }
        .brand-mark {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(229, 57, 53, 0.12);
            border: 1px solid rgba(229, 57, 53, 0.35);
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .public-body {
            background: #111111;
            color: var(--text);
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(229, 57, 53, 0.25);
            transition: all 0.2s ease;
        }
        .btn-primary:hover,
        .btn-primary:focus {
            background: #c62828;
            border-color: #c62828;
            color: #FFFFFF;
            box-shadow: 0 6px 18px rgba(229, 57, 53, 0.38);
            transform: translateY(-1px);
        }
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--border);
            background: transparent;
            transition: all 0.2s ease;
        }
        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background: rgba(229, 57, 53, 0.12);
            border-color: var(--primary);
            color: #FFFFFF;
            transform: translateY(-1px);
        }
        .btn-outline-warning {
            color: var(--warning);
            border-color: var(--border);
            background: transparent;
            transition: all 0.2s ease;
        }
        .btn-outline-warning:hover,
        .btn-outline-warning:focus {
            background: rgba(255, 193, 7, 0.12);
            border-color: var(--warning);
            color: #FFFFFF;
            transform: translateY(-1px);
        }

        .card {
            background: var(--surface);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.45);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        .card-hover-subtle:hover {
            border-color: rgba(229, 57, 53, 0.4);
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.6);
            transform: translateY(-2px);
        }

        .table,
        .form-control,
        .form-select,
        .form-check-input {
            background: var(--surface-2);
            border-color: var(--border);
            color: var(--text);
        }
        .table {
            --bs-table-color: var(--text);
            --bs-table-bg: var(--surface);
            --bs-table-border-color: var(--border);
            --bs-table-striped-color: var(--text);
            --bs-table-striped-bg: rgba(255, 255, 255, 0.02);
            --bs-table-hover-color: var(--text);
            --bs-table-hover-bg: rgba(229, 57, 53, 0.06);
        }
        .table thead th {
            background: #151515;
            color: var(--muted);
            border-bottom-color: var(--border);
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .table td,
        .table th {
            border-color: var(--border);
            color: var(--text);
            vertical-align: middle;
        }
        .form-control:focus,
        .form-select:focus {
            background-color: var(--surface-2);
            border-color: var(--primary);
            color: var(--text);
            box-shadow: 0 0 0 0.2rem rgba(229, 57, 53, 0.22);
        }
        .form-select option { background: var(--surface); color: var(--text); }
        .alert-success { background: rgba(76, 175, 80, 0.12); border-color: rgba(76, 175, 80, 0.35); color: #81C784; }
        .alert-danger { background: rgba(229, 57, 53, 0.12); border-color: rgba(229, 57, 53, 0.35); color: #EF9A9A; }
        .alert-warning { background: rgba(255, 193, 7, 0.12); border-color: rgba(255, 193, 7, 0.35); color: #FFD54F; }
        .alert-info { background: rgba(255, 193, 7, 0.1); border-color: rgba(255, 193, 7, 0.3); color: #FFD54F; }

        .badge-primary-subtle { background: rgba(229, 57, 53, 0.15); color: #EF5350; border: 1px solid rgba(229, 57, 53, 0.35); font-weight: 500; }
        .badge-success-subtle { background: rgba(76, 175, 80, 0.15); color: #81C784; border: 1px solid rgba(76, 175, 80, 0.35); font-weight: 500; }
        .badge-warning-subtle { background: rgba(255, 193, 7, 0.15); color: #FFD54F; border: 1px solid rgba(255, 193, 7, 0.35); font-weight: 500; }
        .badge-danger-subtle { background: rgba(229, 57, 53, 0.18); color: #EF9A9A; border: 1px solid rgba(229, 57, 53, 0.4); font-weight: 500; }
        .badge-secondary-subtle { background: rgba(255, 255, 255, 0.08); color: #BDBDBD; border: 1px solid var(--border); font-weight: 500; }

        /* SKELETON SHIMMER SYSTEM - Charcoal Dark */
        .skeleton {
            background: #1B1B1B;
            position: relative;
            overflow: hidden;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }
        .skeleton-shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            transform: translateX(-100%);
            background-image: linear-gradient(
                90deg,
                rgba(27, 27, 27, 0) 0%,
                rgba(255, 255, 255, 0.03) 20%,
                rgba(255, 255, 255, 0.07) 50%,
                rgba(255, 255, 255, 0.03) 80%,
                rgba(27, 27, 27, 0) 100%
            );
            animation: skeleton-shimmer 1.8s infinite;
        }
        @keyframes skeleton-shimmer {
            100% {
                transform: translateX(100%);
            }
        }
        .skeleton-wrapper {
            transition: opacity 220ms ease;
        }
        .skeleton-loaded {
            display: none !important;
        }

        /* PAGE ENTRANCE TRANSITION */
        .page-fade-in {
            animation: pageFadeIn 280ms cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes pageFadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* REDUCED MOTION SUPPORT */
        @media (prefers-reduced-motion: reduce) {
            .skeleton-shimmer::after {
                animation: none !important;
                background: none !important;
            }
            .page-fade-in {
                animation: none !important;
            }
            * {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }

        /* APP SHELL & ANIMATED SIDEBAR */
        .app-shell {
            display: flex;
            min-height: 100vh;
            background: var(--bg);
            position: relative;
        }

        .sidebar {
            width: var(--sidebar-expanded-width);
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            padding: 1rem 0.85rem 0.85rem;
            position: sticky;
            top: 0;
            height: 100vh;
            max-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: width var(--sidebar-transition-duration) var(--sidebar-timing);
            flex-shrink: 0;
            overflow: hidden;
            z-index: 100;
        }

        html.sidebar-is-collapsed .sidebar,
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        /* Sidebar Header */
        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 0.75rem;
            flex-shrink: 0;
            position: relative;
        }
        .brand-meta {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0;
        }
        .brand-text {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text);
            white-space: nowrap;
        }
        .brand-subtitle {
            font-size: 0.65rem;
            color: var(--secondary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            white-space: nowrap;
        }
        .sidebar-toggle {
            margin-left: auto;
            border: 1px solid var(--border);
            background: #181818;
            color: var(--muted);
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .sidebar-toggle:hover,
        .sidebar-toggle:focus-visible {
            background: rgba(229, 57, 53, 0.16);
            border-color: var(--primary);
            color: #FFFFFF;
            outline: none;
        }

        /* User badge */
        .sidebar-user {
            background: #141414;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.65rem 0.8rem;
            margin-bottom: 0.75rem;
            flex-shrink: 0;
            overflow: hidden;
        }
        .sidebar-user-meta {
            font-size: 0.7rem;
            color: #777777;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.15rem;
            white-space: nowrap;
        }
        .sidebar-user-name {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--text);
            white-space: nowrap;
        }

        /* Navigation List */
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 2px;
            padding-bottom: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(229, 57, 53, 0.25) transparent;
        }
        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(229, 57, 53, 0.3);
            border-radius: 4px;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(229, 57, 53, 0.55);
        }

        .nav-group {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .nav-group-label {
            font-size: 0.66rem;
            color: #777777;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            padding: 0.35rem 0.5rem 0.15rem;
            white-space: nowrap;
            overflow: hidden;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.62rem 0.75rem;
            border-radius: 10px;
            color: var(--muted);
            transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease;
            border: 1px solid transparent;
            font-weight: 500;
            font-size: 0.88rem;
            white-space: nowrap;
            position: relative;
            cursor: pointer;
        }
        .nav-link:hover,
        .nav-link:focus-visible {
            background: rgba(255, 255, 255, 0.05);
            border-color: #333333;
            color: #FFFFFF;
            outline: none;
        }
        /* Active nav item: red indicator, red border */
        .nav-link.active {
            background: rgba(229, 57, 53, 0.14);
            border-color: var(--primary);
            border-left: 3px solid var(--primary);
            color: #FFFFFF;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(229, 57, 53, 0.18);
        }
        .nav-link .nav-icon {
            width: 20px;
            text-align: center;
            color: var(--muted);
            font-weight: 700;
            flex-shrink: 0;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.18s ease;
        }
        .nav-link:hover .nav-icon {
            color: #FFFFFF;
        }
        /* Selected icon: yellow accent used sparingly */
        .nav-link.active .nav-icon {
            color: var(--secondary);
        }
        .nav-link.logout-btn:hover,
        .nav-link.logout-btn:focus-visible {
            background: rgba(229, 57, 53, 0.15);
            border-color: rgba(229, 57, 53, 0.4);
            color: #EF5350;
        }

        /* Fading elements during collapse */
        .brand-text,
        .brand-subtitle,
        .sidebar-user-meta,
        .sidebar-user-name,
        .nav-group-label,
        .nav-label {
            transition: opacity 160ms ease, transform 160ms ease, max-width var(--sidebar-transition-duration) var(--sidebar-timing);
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            display: inline-block;
        }

        html.sidebar-is-collapsed .brand-text,
        html.sidebar-is-collapsed .brand-subtitle,
        html.sidebar-is-collapsed .sidebar-user-meta,
        html.sidebar-is-collapsed .sidebar-user-name,
        html.sidebar-is-collapsed .nav-group-label,
        html.sidebar-is-collapsed .nav-label,
        .sidebar.collapsed .brand-text,
        .sidebar.collapsed .brand-subtitle,
        .sidebar.collapsed .sidebar-user-meta,
        .sidebar.collapsed .sidebar-user-name,
        .sidebar.collapsed .nav-group-label,
        .sidebar.collapsed .nav-label {
            opacity: 0;
            max-width: 0;
            transform: translateX(-8px);
            pointer-events: none;
        }

        /* Collapsed mode tooltips */
        html.sidebar-is-collapsed .sidebar-header,
        .sidebar.collapsed .sidebar-header {
            justify-content: center;
        }
        html.sidebar-is-collapsed .sidebar-toggle,
        .sidebar.collapsed .sidebar-toggle {
            margin-left: 0;
        }
        html.sidebar-is-collapsed .brand-mark,
        .sidebar.collapsed .brand-mark {
            display: none;
        }
        html.sidebar-is-collapsed .sidebar-user,
        .sidebar.collapsed .sidebar-user {
            padding: 0.5rem 0.2rem;
            text-align: center;
        }

        html.sidebar-is-collapsed .nav-link,
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        html.sidebar-is-collapsed .nav-link .nav-icon,
        .sidebar.collapsed .nav-link .nav-icon {
            margin-right: 0;
        }

        /* Tooltip balloon on hover/focus in collapsed mode */
        html.sidebar-is-collapsed .nav-link::after,
        .sidebar.collapsed .nav-link::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%) scale(0.92);
            background: #1B1B1B;
            color: #FFFFFF;
            font-size: 0.78rem;
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8);
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 160ms ease, transform 160ms ease;
            z-index: 1050;
        }
        html.sidebar-is-collapsed .nav-link:hover::after,
        html.sidebar-is-collapsed .nav-link:focus-visible::after,
        .sidebar.collapsed .nav-link:hover::after,
        .sidebar.collapsed .nav-link:focus-visible::after {
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }

        /* MAIN PANEL */
        .main-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left var(--sidebar-transition-duration) var(--sidebar-timing);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1.15rem 1.75rem;
            border-bottom: 1px solid var(--border);
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .page-title {
            font-size: 1.55rem;
            font-weight: 700;
            margin: 0;
            color: #FFFFFF;
        }
        .topbar-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--muted);
        }
        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #1A1A1A;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            color: var(--text);
            font-size: 0.85rem;
        }
        .content-area {
            padding: 1.75rem;
            flex: 1;
        }
        .content-shell {
            max-width: 1500px;
            margin: 0 auto;
        }

        /* MOBILE SLIDE-OUT DRAWER (< 992px) */
        @media (max-width: 991.98px) {
            .app-shell {
                display: block;
            }
            .sidebar {
                position: fixed !important;
                top: 0;
                left: 0;
                bottom: 0;
                height: 100vh !important;
                width: 270px !important;
                z-index: 1045 !important;
                transform: translateX(-100%);
                transition: transform 250ms var(--sidebar-timing) !important;
                box-shadow: 0 0 35px rgba(0, 0, 0, 0.8);
            }
            .sidebar.mobile-open {
                transform: translateX(0) !important;
            }
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.8);
                backdrop-filter: blur(4px);
                z-index: 1040;
                opacity: 0;
                pointer-events: none;
                transition: opacity 250ms ease;
            }
            .sidebar-backdrop.active {
                opacity: 1;
                pointer-events: auto;
            }
            .topbar, .content-area { padding: 1rem; }
        }
    </style>
</head>
<body class="@auth management-body @else public-body @endauth">
    @auth
        @php
            $currentUser = Auth::user();
            $userRole = $currentUser?->role?->name;
        @endphp
        <div class="app-shell">
            <!-- Mobile Backdrop -->
            <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeMobileDrawer()"></div>

            <aside class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <div class="brand-mark">MB</div>
                    <div class="brand-meta">
                        <div class="brand-text">MyBatchoy</div>
                        <div class="brand-subtitle">Inventory System</div>
                    </div>
                    <button class="sidebar-toggle" id="sidebarToggleBtn" type="button" aria-label="Toggle sidebar navigation" aria-controls="sidebar-navigation" aria-expanded="true" onclick="toggleSidebar()">
                        ☰
                    </button>
                </div>

                <div class="sidebar-user">
                    <div class="sidebar-user-meta">{{ $userRole === 'owner' ? 'Owner / Admin' : 'Staff Member' }}</div>
                    <div class="sidebar-user-name">{{ $currentUser->name }}</div>
                </div>

                <nav class="sidebar-nav" id="sidebar-navigation">
                    @if($userRole === 'owner')
                        <!-- 1. OVERVIEW -->
                        <div class="nav-group">
                            <div class="nav-group-label">Overview</div>
                            <a class="nav-link {{ request()->routeIs('dashboard','owner.dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard" data-tooltip="Dashboard">
                                <span class="nav-icon">◈</span><span class="nav-label">Dashboard</span>
                            </a>
                        </div>

                        <!-- 2. INVENTORY -->
                        <div class="nav-group">
                            <div class="nav-group-label">Inventory</div>
                            <a class="nav-link {{ request()->routeIs('owner.inventory.*') ? 'active' : '' }}" href="{{ route('owner.inventory.index') }}" title="Inventory" data-tooltip="Inventory">
                                <span class="nav-icon">▣</span><span class="nav-label">Inventory</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.transactions.*') ? 'active' : '' }}" href="{{ route('owner.transactions.index') }}" title="Stock Transactions" data-tooltip="Stock Transactions">
                                <span class="nav-icon">⇄</span><span class="nav-label">Stock Transactions</span>
                            </a>
                        </div>

                        <!-- 3. PURCHASING -->
                        <div class="nav-group">
                            <div class="nav-group-label">Purchasing</div>
                            <a class="nav-link {{ request()->routeIs('owner.suppliers.*') ? 'active' : '' }}" href="{{ route('owner.suppliers.index') }}" title="Suppliers" data-tooltip="Suppliers">
                                <span class="nav-icon">◎</span><span class="nav-label">Suppliers</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.purchases.*') ? 'active' : '' }}" href="{{ route('owner.purchases.index') }}" title="Purchases" data-tooltip="Purchases">
                                <span class="nav-icon">◫</span><span class="nav-label">Purchases</span>
                            </a>
                        </div>

                        <!-- 4. ANALYTICS -->
                        <div class="nav-group">
                            <div class="nav-group-label">Analytics</div>
                            <a class="nav-link {{ request()->routeIs('owner.reports.*') ? 'active' : '' }}" href="{{ route('owner.reports.index') }}" title="Reports" data-tooltip="Reports">
                                <span class="nav-icon">▤</span><span class="nav-label">Reports</span>
                            </a>
                        </div>

                        <!-- 5. ADMINISTRATION -->
                        <div class="nav-group">
                            <div class="nav-group-label">Administration</div>
                            <a class="nav-link {{ request()->routeIs('owner.staff.*') ? 'active' : '' }}" href="{{ route('owner.staff.index') }}" title="Staff Management" data-tooltip="Staff Management">
                                <span class="nav-icon">◎</span><span class="nav-label">Staff Management</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.audit.index') ? 'active' : '' }}" href="{{ route('owner.audit.index') }}" title="Audit Logs" data-tooltip="Audit Logs">
                                <span class="nav-icon">◌</span><span class="nav-label">Audit Logs</span>
                            </a>
                        </div>

                        <!-- 6. ACCOUNT -->
                        <div class="nav-group">
                            <div class="nav-group-label">Account</div>
                            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}" title="Profile" data-tooltip="Profile">
                                <span class="nav-icon">◍</span><span class="nav-label">Profile</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.settings.*') ? 'active' : '' }}" href="{{ route('owner.settings.index') }}" title="Settings" data-tooltip="Settings">
                                <span class="nav-icon">⚙</span><span class="nav-label">Settings</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button class="nav-link logout-btn w-100 text-start" type="submit" title="Logout" data-tooltip="Logout">
                                    <span class="nav-icon" style="color: #f87171;">⎋</span>
                                    <span class="nav-label">Logout</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <!-- STAFF SECTIONS -->
                        <!-- 1. OVERVIEW -->
                        <div class="nav-group">
                            <div class="nav-group-label">Overview</div>
                            <a class="nav-link {{ request()->routeIs('dashboard','staff.dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard" data-tooltip="Dashboard">
                                <span class="nav-icon">◈</span><span class="nav-label">Dashboard</span>
                            </a>
                        </div>

                        <!-- 2. INVENTORY -->
                        <div class="nav-group">
                            <div class="nav-group-label">Inventory</div>
                            <a class="nav-link {{ request()->routeIs('staff.inventory.index') ? 'active' : '' }}" href="{{ route('staff.inventory.index') }}" title="Inventory" data-tooltip="Inventory">
                                <span class="nav-icon">▣</span><span class="nav-label">Inventory</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('staff.transactions.*') ? 'active' : '' }}" href="{{ route('staff.transactions.index') }}" title="Stock Transactions" data-tooltip="Stock Transactions">
                                <span class="nav-icon">⇄</span><span class="nav-label">Stock Transactions</span>
                            </a>
                        </div>

                        <!-- 3. ACCOUNT -->
                        <div class="nav-group">
                            <div class="nav-group-label">Account</div>
                            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}" title="Profile" data-tooltip="Profile">
                                <span class="nav-icon">◍</span><span class="nav-label">Profile</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button class="nav-link logout-btn w-100 text-start" type="submit" title="Logout" data-tooltip="Logout">
                                    <span class="nav-icon" style="color: #f87171;">⎋</span>
                                    <span class="nav-label">Logout</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </nav>
            </aside>

            <div class="main-panel">
                <header class="topbar">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-primary d-lg-none" type="button" aria-label="Open navigation menu" onclick="toggleMobileDrawer()">
                            ☰
                        </button>
                        <div>
                            @yield('title', 'Dashboard')
                        </div>
                    </div>
                    <div class="topbar-meta">
                        <span class="user-pill">
                            <span class="brand-mark" style="width:24px;height:24px;font-size:0.65rem;">{{ strtoupper(substr($currentUser->name, 0, 1)) }}</span>
                            {{ $currentUser->name }}
                        </span>
                    </div>
                </header>

                <main class="content-area">
                    <div class="content-shell page-fade-in">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

    @else
        <div class="min-vh-100 d-flex flex-column justify-content-between" style="background: #111111;">
            <header class="py-3 px-4 border-bottom" style="border-color: var(--border); background: #0A0A0A;">
                <div class="container d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="brand-mark">MB</div>
                        <div>
                            <span class="brand-text">MyBatchoy</span>
                            <span class="badge badge-primary-subtle ms-2" style="font-size: 0.7rem;">Inventory System</span>
                        </div>
                    </div>
                    <span class="muted small d-none d-sm-inline">Internal Operations Portal</span>
                </div>
            </header>

            <main class="flex-grow-1 d-flex align-items-center py-4">
                @yield('content')
            </main>

            <footer class="py-3 text-center border-top" style="border-color: var(--border); background: #0A0A0A; color: var(--muted); font-size: 0.8rem;">
                <div class="container">
                    <span>© {{ date('Y') }} MyBatchoy — Internal Inventory Management System. Authorized personnel only.</span>
                </div>
            </footer>
        </div>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Desktop Collapse and Mobile Drawer Controller
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggleBtn');
            const isMobile = window.matchMedia('(max-width: 991.98px)').matches;

            if (isMobile) {
                toggleMobileDrawer();
                return;
            }

            const isCollapsed = sidebar.classList.toggle('collapsed');
            document.documentElement.classList.toggle('sidebar-is-collapsed', isCollapsed);
            if (toggle) {
                toggle.setAttribute('aria-expanded', !isCollapsed);
            }

            try {
                localStorage.setItem('mybatchoy_sidebar_collapsed', isCollapsed ? 'true' : 'false');
            } catch (e) {}
        }

        function toggleMobileDrawer() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!sidebar || !backdrop) return;

            const isOpen = sidebar.classList.toggle('mobile-open');
            backdrop.classList.toggle('active', isOpen);
            document.body.style.overflow = isOpen ? 'hidden' : '';
        }

        function closeMobileDrawer() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.remove('mobile-open');
            if (backdrop) backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close drawer on Escape key press
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMobileDrawer();
            }
        });

        // Auto close drawer when navigating on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 992) {
                            closeMobileDrawer();
                        }
                    });
                });
            }

            // Smooth dashboard counter animations
            animateCounters();
        });

        // Smooth Counter Animation Function (respects prefers-reduced-motion)
        function animateCounters() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return; // Let static server-rendered numbers display instantly
            }

            const counterElements = document.querySelectorAll('[data-counter]');
            counterElements.forEach(el => {
                const target = parseFloat(el.getAttribute('data-counter'));
                if (isNaN(target)) return;

                const prefix = el.getAttribute('data-prefix') || '';
                const decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
                const duration = 650; // milliseconds
                const startTime = performance.now();

                function update(now) {
                    const elapsed = now - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    // Smooth ease-out quad curve
                    const easeOut = 1 - (1 - progress) * (1 - progress);
                    const current = target * easeOut;

                    if (decimals > 0) {
                        el.textContent = prefix + current.toLocaleString(undefined, {
                            minimumFractionDigits: decimals,
                            maximumFractionDigits: decimals
                        });
                    } else {
                        el.textContent = prefix + Math.round(current).toLocaleString();
                    }

                    if (progress < 1) {
                        requestAnimationFrame(update);
                    } else {
                        // Ensure final exact value
                        if (decimals > 0) {
                            el.textContent = prefix + target.toLocaleString(undefined, {
                                minimumFractionDigits: decimals,
                                maximumFractionDigits: decimals
                            });
                        } else {
                            el.textContent = prefix + Math.round(target).toLocaleString();
                        }
                    }
                }

                requestAnimationFrame(update);
            });
        }
    </script>
</body>
</html>

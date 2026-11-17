<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'MyBatchoy') }} - @yield('title', 'Inventory System')</title>
    @if (app()->environment('production'))
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Poppins:ital,wght@0,400;0,500;0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">
    @else
        <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    @endif
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
            --input-bg: #202020;        /* standard dark input surface */
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
            font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: clip;
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

        /* PAGE ENTRANCE TRANSITION (avoid transform/forwards so nested modals keep root stacking context) */
        .page-fade-in {
            animation: pageFadeIn 220ms cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes pageFadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
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

        /* APP SHELL & ANIMATED SIDEBAR (TARGET DESIGN REFERENCE) */
        .app-shell {
            display: flex;
            min-height: 100vh;
            background: var(--bg);
            position: relative;
        }

        .sidebar {
            width: var(--sidebar-expanded-width);
            background:
                radial-gradient(circle at 0% 100%, rgba(229, 57, 53, 0.22) 0%, rgba(255, 193, 7, 0.08) 22%, transparent 48%),
                #0A0A0A;
            border-right: 1px solid #262626;
            padding: clamp(0.45rem, 1vh, 0.85rem) 0.75rem clamp(0.45rem, 0.9vh, 0.75rem);
            position: sticky;
            top: 0;
            height: 100vh;
            height: 100dvh;
            max-height: 100dvh;
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

        /* Sidebar Brand Lockup (Eustace Batchoy Unli Sabaw) */
        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.1rem 0.25rem clamp(0.35rem, 0.9vh, 0.75rem);
            border-bottom: 1px solid #222222;
            margin-bottom: clamp(0.25rem, 0.65vh, 0.5rem);
            flex-shrink: 0;
            position: relative;
        }
        .brand-mascot-wrap {
            width: clamp(34px, 5vh, 44px);
            height: clamp(34px, 5vh, 44px);
            border-radius: 50%;
            border: 2px solid #FFC107;
            box-shadow: 0 0 12px rgba(229, 57, 53, 0.45);
            overflow: hidden;
            flex-shrink: 0;
            background: #1B1B1B;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .brand-mascot-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .brand-meta {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0;
            line-height: 1.05;
        }
        .brand-script {
            font-family: 'Kaushan Script', 'Segoe Script', 'Brush Script MT', cursive;
            font-size: clamp(0.95rem, 1.6vh, 1.12rem);
            color: #FFFFFF;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        .brand-text {
            font-size: clamp(0.98rem, 1.7vh, 1.15rem);
            font-weight: 800;
            color: #FFC107;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .brand-subtitle {
            display: inline-block;
            align-self: flex-start;
            background: #E53935;
            color: #FFFFFF;
            font-size: 0.58rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 2px;
            white-space: nowrap;
        }
        .sidebar-toggle {
            border: 1px solid #2E2E2E;
            background: #171717;
            color: #FFFFFF;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .sidebar-toggle:hover,
        .sidebar-toggle:focus-visible {
            background: rgba(229, 57, 53, 0.18);
            border-color: var(--primary);
            color: #FFFFFF;
            outline: none;
        }

        /* Compact Navigation List */
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: clamp(0.18rem, 0.9vh, 0.58rem);
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 2px;
            padding-bottom: 0.25rem;
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

        .nav-group {
            display: flex;
            flex-direction: column;
            gap: clamp(0.08rem, 0.35vh, 0.22rem);
        }
        .nav-group-label {
            font-size: clamp(0.6rem, 0.98vh, 0.68rem);
            line-height: 1.15;
            color: #888888;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            font-weight: 700;
            padding: clamp(0.14rem, 0.4vh, 0.35rem) 0.68rem 0.08rem;
            white-space: nowrap;
            overflow: hidden;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: clamp(0.28rem, 0.85vh, 0.62rem) 0.68rem;
            min-height: clamp(30px, 5.1vh, 46px);
            border-radius: 10px;
            background: transparent;
            color: #E2E2E2;
            transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
            border: 1px solid transparent;
            font-weight: 600;
            font-size: clamp(0.88rem, 1.75vh, 1.02rem);
            line-height: 1.2;
            white-space: nowrap;
            position: relative;
            cursor: pointer;
        }
        .nav-link:hover,
        .nav-link:focus-visible {
            background: rgba(255, 255, 255, 0.06);
            border-color: #2E2E2E;
            color: #FFFFFF;
            outline: none;
        }
        /* Active nav item: solid red pill matching IMAGE 1 */
        .nav-link.active {
            background: linear-gradient(90deg, #E53935 0%, #B71C1C 100%);
            border-color: rgba(255, 255, 255, 0.14);
            color: #FFFFFF;
            font-weight: 700;
            box-shadow: 0 6px 16px rgba(229, 57, 53, 0.35);
        }
        .nav-link .nav-icon {
            width: 20px;
            height: 20px;
            text-align: center;
            color: #BDBDBD;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.18s ease, transform 0.18s ease;
        }
        .nav-link:hover .nav-icon {
            color: #FFFFFF;
            transform: scale(1.05);
        }
        .nav-link.active .nav-icon {
            color: #FFFFFF !important;
        }
        .nav-link.logout-btn {
            margin-top: 0.15rem;
            border-top: 1px solid #1F1F1F;
            border-radius: 10px;
        }
        .nav-link.logout-btn:hover,
        .nav-link.logout-btn:focus-visible {
            background: rgba(229, 57, 53, 0.15);
            border-color: rgba(229, 57, 53, 0.4);
            color: #FFC107;
        }

        /* Fading elements during collapse */
        .brand-meta,
        .nav-group-label,
        .nav-label {
            transition: opacity 160ms ease, transform 160ms ease, max-width var(--sidebar-transition-duration) var(--sidebar-timing);
            max-width: 210px;
            white-space: nowrap;
            overflow: hidden;
        }

        html.sidebar-is-collapsed .brand-meta,
        html.sidebar-is-collapsed .nav-group-label,
        html.sidebar-is-collapsed .nav-label,
        .sidebar.collapsed .brand-meta,
        .sidebar.collapsed .nav-group-label,
        .sidebar.collapsed .nav-label {
            opacity: 0;
            max-width: 0;
            transform: translateX(-8px);
            pointer-events: none;
        }
        html.sidebar-is-collapsed .nav-group-label,
        .sidebar.collapsed .nav-group-label {
            height: 0;
            padding: 0;
            margin: 0;
        }

        /* Collapsed mode layout */
        html.sidebar-is-collapsed .sidebar-header,
        .sidebar.collapsed .sidebar-header {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        html.sidebar-is-collapsed .brand-mascot-wrap,
        .sidebar.collapsed .brand-mascot-wrap {
            width: clamp(32px, 4.5vh, 40px);
            height: clamp(32px, 4.5vh, 40px);
        }

        html.sidebar-is-collapsed .nav-link,
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
            gap: 0;
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

        /* MAIN PANEL & TOPBAR (TARGET DESIGN REFERENCE) */
        .main-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: #111111;
            transition: margin-left var(--sidebar-transition-duration) var(--sidebar-timing);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.7rem 1.5rem;
            min-height: 60px;
            border-bottom: 1px solid #262626;
            background: #0D0D0D;
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .topbar-title-text {
            font-size: 0.95rem;
            font-weight: 600;
            color: #BDBDBD;
            letter-spacing: 0.01em;
        }
        .page-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0;
            color: #FFFFFF;
        }
        .topbar-meta {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            color: var(--muted);
        }
        .topbar-bell-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #181818;
            border: 1px solid #2E2E2E;
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .topbar-bell-btn:hover,
        .topbar-bell-btn[aria-expanded="true"] {
            border-color: var(--primary);
            background: rgba(229, 57, 53, 0.14);
            color: #FFFFFF;
        }
        .topbar-bell-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #E53935;
            color: #FFFFFF;
            font-size: 0.65rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #0D0D0D;
        }
        .eb-notif-wrap {
            position: relative;
        }
        .eb-notif-dropdown {
            width: min(390px, calc(100vw - 1.5rem));
            padding: 0;
            margin-top: 0.5rem !important;
            background: #141414;
            border: 1px solid #2E2E2E;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.72);
            color: #FFFFFF;
            overflow: hidden;
            z-index: 1060;
        }
        .eb-notif-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            background: #1A1A1A;
            border-bottom: 1px solid #2A2A2A;
        }
        .eb-notif-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #FFFFFF;
            line-height: 1.2;
        }
        .eb-notif-subtitle {
            font-size: 0.75rem;
            color: #A8A8A8;
            margin-top: 0.12rem;
        }
        .eb-notif-count-chip {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
            background: rgba(76, 175, 80, 0.14);
            color: #81C784;
            border: 1px solid rgba(76, 175, 80, 0.35);
            white-space: nowrap;
        }
        .eb-notif-count-chip.has-alerts {
            background: rgba(229, 57, 53, 0.16);
            color: #FF8A80;
            border-color: rgba(229, 57, 53, 0.45);
        }
        .eb-notif-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            padding: 0.6rem 0.9rem;
            background: #121212;
            border-bottom: 1px solid #242424;
        }
        .eb-notif-pill {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.18rem 0.5rem;
            border-radius: 999px;
            border: 1px solid transparent;
        }
        .eb-notif-pill.pill-danger {
            background: rgba(229, 57, 53, 0.16);
            color: #FF8A80;
            border-color: rgba(229, 57, 53, 0.4);
        }
        .eb-notif-pill.pill-warning {
            background: rgba(255, 193, 7, 0.14);
            color: #FFD54F;
            border-color: rgba(255, 193, 7, 0.38);
        }
        .eb-notif-pill.pill-info {
            background: rgba(66, 165, 245, 0.14);
            color: #90CAF9;
            border-color: rgba(66, 165, 245, 0.35);
        }
        .eb-notif-pill.pill-spoilage {
            background: rgba(255, 112, 67, 0.16);
            color: #FFAB91;
            border-color: rgba(255, 112, 67, 0.4);
        }
        .eb-notif-list {
            max-height: 360px;
            overflow-y: auto;
            padding: 0.55rem;
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }
        .eb-notif-item {
            display: block;
            text-decoration: none;
            background: #191919;
            border: 1px solid #292929;
            border-left-width: 3px;
            border-radius: 10px;
            padding: 0.65rem 0.75rem;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
        }
        .eb-notif-item:hover {
            background: #222222;
            border-color: #3D3D3D;
            transform: translateY(-1px);
        }
        .eb-notif-item.sev-danger { border-left-color: #E53935; }
        .eb-notif-item.sev-warning { border-left-color: #FFC107; }
        .eb-notif-item.sev-info { border-left-color: #42A5F5; }
        .eb-notif-item.sev-spoilage { border-left-color: #FF7043; }
        .eb-notif-item-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.28rem;
        }
        .eb-notif-badge {
            font-size: 0.66rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.14rem 0.45rem;
            border-radius: 6px;
            background: #262626;
            color: #F5F5F5;
        }
        .eb-notif-item.sev-danger .eb-notif-badge { background: rgba(229, 57, 53, 0.22); color: #FF8A80; }
        .eb-notif-item.sev-warning .eb-notif-badge { background: rgba(255, 193, 7, 0.2); color: #FFD54F; }
        .eb-notif-item.sev-info .eb-notif-badge { background: rgba(66, 165, 245, 0.2); color: #90CAF9; }
        .eb-notif-item.sev-spoilage .eb-notif-badge { background: rgba(255, 112, 67, 0.2); color: #FFAB91; }
        .eb-notif-time {
            font-size: 0.7rem;
            color: #9A9A9A;
        }
        .eb-notif-item-title {
            font-size: 0.86rem;
            font-weight: 700;
            color: #FFFFFF;
            line-height: 1.3;
        }
        .eb-notif-item-msg {
            font-size: 0.78rem;
            color: #C2C2C2;
            margin-top: 0.16rem;
            line-height: 1.38;
        }
        .eb-notif-item-cta {
            font-size: 0.72rem;
            font-weight: 700;
            color: #FFC107;
            margin-top: 0.35rem;
        }
        .eb-notif-empty {
            text-align: center;
            padding: 1.5rem 1rem;
        }
        .eb-notif-empty-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #FFFFFF;
        }
        .eb-notif-empty-sub {
            font-size: 0.78rem;
            color: #9E9E9E;
            margin-top: 0.25rem;
        }
        .eb-notif-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.7rem 0.95rem;
            background: #1A1A1A;
            border-top: 1px solid #2A2A2A;
        }
        .eb-notif-footer-link {
            font-size: 0.76rem;
            font-weight: 700;
            color: #FFC107;
            text-decoration: none;
        }
        .eb-notif-footer-link:hover {
            color: #FFFFFF;
            text-decoration: underline;
        }
        @media (max-width: 575.98px) {
            .eb-notif-dropdown {
                position: fixed !important;
                top: 62px !important;
                left: 0.75rem !important;
                right: 0.75rem !important;
                width: auto !important;
                transform: none !important;
            }
        }
        .user-profile-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            background: #171717;
            border: 1px solid #2E2E2E;
            border-radius: 12px;
            padding: 0.32rem 0.75rem 0.32rem 0.4rem;
            color: var(--text);
            cursor: pointer;
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        .user-profile-trigger:hover {
            border-color: #444444;
            background: #1F1F1F;
        }
        .user-avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2B2B2B 0%, #1A1A1A 100%);
            border: 1.5px solid #FFC107;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .user-profile-info {
            display: flex;
            flex-direction: column;
            text-align: left;
            line-height: 1.15;
        }
        .user-profile-name {
            font-size: 0.84rem;
            font-weight: 700;
            color: #FFFFFF;
        }
        .user-profile-role {
            font-size: 0.7rem;
            color: #BDBDBD;
            font-weight: 500;
        }
        .content-area {
            padding: 1.35rem 1.5rem 1.5rem;
            flex: 1;
        }
        .content-shell {
            max-width: 1480px;
            margin: 0 auto;
        }

        /* TARGET REFERENCE DASHBOARD DESIGN SYSTEM */
        .eb-hero-banner {
            position: relative;
            background: #141414;
            border: 1px solid #2E2E2E;
            border-radius: 16px;
            overflow: hidden;
            min-height: 128px;
            margin-bottom: 1.15rem;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.55);
            display: flex;
            align-items: center;
        }
        .eb-hero-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(115deg, transparent 42%, rgba(229, 57, 53, 0.32) 48%, rgba(229, 57, 53, 0.08) 56%, transparent 62%),
                linear-gradient(115deg, transparent 52%, rgba(255, 193, 7, 0.28) 57%, transparent 65%);
            pointer-events: none;
            z-index: 1;
        }
        .eb-hero-art {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 44%;
            max-width: 460px;
            z-index: 1;
            pointer-events: none;
        }
        .eb-hero-art img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            opacity: 0.92;
        }
        .eb-hero-art::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, #141414 0%, rgba(20, 20, 20, 0.86) 24%, rgba(20, 20, 20, 0.15) 65%, rgba(20, 20, 20, 0.25) 100%);
        }
        .eb-hero-content {
            position: relative;
            z-index: 2;
            padding: 1.25rem 1.5rem;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .eb-hero-greeting {
            font-size: 1.65rem;
            font-weight: 800;
            color: #FFFFFF;
            margin: 0 0 0.2rem;
            letter-spacing: -0.01em;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.65);
        }
        .eb-hero-sub {
            color: #D0D0D0;
            font-size: 0.9rem;
            margin: 0;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.75);
        }
        .eb-hero-controls {
            background: rgba(17, 17, 17, 0.88);
            backdrop-filter: blur(8px);
            border: 1px solid #333333;
            border-radius: 12px;
            padding: 0.45rem 0.65rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        /* KPI Cards (Circular Badge + Big Figure + Sparkline Wave) */
        .eb-kpi-card {
            position: relative;
            background: #1B1B1B;
            border: 1px solid #2E2E2E;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            height: 100%;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.42);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .eb-kpi-card:hover {
            transform: translateY(-2px);
            border-color: #444444;
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.6);
        }
        .eb-kpi-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .eb-kpi-circle.kpi-red {
            background: linear-gradient(135deg, #EF5350 0%, #C62828 100%);
            box-shadow: 0 6px 16px rgba(229, 57, 53, 0.38);
            color: #FFFFFF;
        }
        .eb-kpi-circle.kpi-yellow {
            background: linear-gradient(135deg, #FFCA28 0%, #F57F17 100%);
            box-shadow: 0 6px 16px rgba(255, 193, 7, 0.34);
            color: #111111;
        }
        .eb-kpi-circle.kpi-green {
            background: linear-gradient(135deg, #66BB6A 0%, #2E7D32 100%);
            box-shadow: 0 6px 16px rgba(76, 175, 80, 0.34);
            color: #FFFFFF;
        }
        .eb-kpi-body {
            flex: 1;
            min-width: 0;
            z-index: 2;
        }
        .eb-kpi-label {
            font-size: 0.76rem;
            font-weight: 600;
            color: #BDBDBD;
            margin-bottom: 0.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .eb-kpi-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #FFFFFF;
            line-height: 1.12;
            letter-spacing: -0.02em;
        }
        .eb-kpi-trend {
            font-size: 0.72rem;
            font-weight: 600;
            margin-top: 0.2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .eb-kpi-spark {
            width: 68px;
            height: 36px;
            flex-shrink: 0;
            opacity: 0.92;
        }

        /* Dashboard Section Cards */
        .eb-panel-card {
            background: #1B1B1B;
            border: 1px solid #2E2E2E;
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.45);
        }
        .eb-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .eb-panel-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 700;
            color: #FFFFFF;
            margin: 0;
        }
        .eb-view-all-link {
            font-size: 0.8rem;
            font-weight: 600;
            color: #E53935;
            transition: color 0.15s ease;
        }
        .eb-view-all-link:hover {
            color: #FFC107;
        }

        /* Donut Chart with Center Overlay */
        .eb-donut-stage {
            position: relative;
            width: 175px;
            height: 175px;
            margin: 0 auto;
            flex-shrink: 0;
        }
        .eb-donut-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            text-align: center;
        }
        .eb-donut-center-num {
            font-size: 1.85rem;
            font-weight: 800;
            color: #FFFFFF;
            line-height: 1.05;
        }
        .eb-donut-center-lbl {
            font-size: 0.75rem;
            color: #BDBDBD;
            font-weight: 500;
        }
        .eb-legend-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            flex: 1;
            min-width: 155px;
        }
        .eb-legend-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #E0E0E0;
            padding: 0.35rem 0.55rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.02);
        }
        .eb-legend-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        /* Solid Transaction Type Pill Badges (Matching IMAGE 1) */
        .eb-tx-pill {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            font-size: 0.73rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            text-align: center;
            min-width: 84px;
        }
        .eb-tx-pill.tx-in {
            background: #2E7D32;
            color: #FFFFFF;
        }
        .eb-tx-pill.tx-out {
            background: #C62828;
            color: #FFFFFF;
        }
        .eb-tx-pill.tx-adj {
            background: #FFC107;
            color: #111111;
        }
        .eb-tx-pill.tx-spoil {
            background: #7F1D1D;
            color: #FFCDD2;
            border: 1px solid #E53935;
        }

        /* Purchase Summary Split Layout */
        .eb-purchase-total-box {
            border-left: 3px solid #FFC107;
            padding-left: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* System Status & APK Promo Card */
        .eb-sys-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            text-align: center;
        }
        .eb-sys-item {
            padding: 0.6rem 0.35rem;
            border-right: 1px solid #2A2A2A;
        }
        .eb-sys-item:last-child {
            border-right: none;
        }
        .eb-apk-card {
            background: radial-gradient(circle at 85% 50%, rgba(229, 57, 53, 0.24) 0%, #1B1B1B 70%);
            border: 1px solid #2E2E2E;
            border-radius: 14px;
            padding: 1rem 1.15rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            height: 100%;
        }
        .eb-apk-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: linear-gradient(90deg, #FFC107 0%, #FF9800 100%);
            color: #111111 !important;
            font-weight: 800;
            font-size: 0.76rem;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
            transition: transform 0.18s ease;
        }
        .eb-apk-btn:hover {
            transform: translateY(-1px);
            color: #000000 !important;
        }

        /* Uniform Quick Action Pills */
        .eb-quick-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 38px;
            padding: 0.42rem 0.9rem;
            border-radius: 9px;
            font-size: 0.82rem;
            font-weight: 600;
            border: 1px solid #333333;
            background: #222222;
            color: #FFFFFF;
            transition: all 0.18s ease;
        }
        .eb-quick-btn:hover {
            border-color: var(--primary);
            background: rgba(229, 57, 53, 0.14);
            color: #FFFFFF;
            transform: translateY(-1px);
        }
        .eb-quick-btn.eb-quick-primary {
            background: linear-gradient(90deg, #E53935 0%, #C62828 100%);
            border-color: #EF5350;
            box-shadow: 0 4px 12px rgba(229, 57, 53, 0.28);
        }
        .eb-quick-btn.eb-quick-primary:hover {
            background: #D32F2F;
        }

        /* App Footer Matching IMAGE 1 */
        .eb-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1.5rem;
            border-top: 1px solid #222222;
            background: #0A0A0A;
            font-size: 0.78rem;
            color: #9E9E9E;
        }
        .eb-footer-tagline {
            font-family: 'Kaushan Script', 'Segoe Script', 'Brush Script MT', cursive;
            font-size: 1.05rem;
            color: #FFC107;
            letter-spacing: 0.02em;
        }

        /* Mobile Header Brand Lockup + Fixed Bottom Nav */
        .eb-mobile-brand {
            align-items: center;
            gap: 0.55rem;
            text-decoration: none;
            min-width: 0;
        }
        .eb-mobile-brand .brand-mascot-wrap {
            width: 38px;
            height: 38px;
        }
        .eb-mobile-brand-meta {
            display: flex;
            flex-direction: column;
            line-height: 1;
            min-width: 0;
        }
        .eb-mobile-brand-top {
            display: flex;
            align-items: baseline;
            gap: 0.25rem;
            white-space: nowrap;
        }
        .eb-mobile-brand-meta .brand-script {
            font-size: 0.95rem;
        }
        .eb-mobile-brand-meta .brand-text {
            font-size: 0.98rem;
        }
        .eb-mobile-brand-meta .brand-subtitle {
            margin-top: 2px;
            font-size: 0.52rem;
            padding: 1px 5px;
        }
        .sidebar-close-btn {
            margin-left: auto;
            border: 1px solid #2E2E2E;
            background: #171717;
            color: #FFFFFF;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }
        .sidebar-close-btn:hover {
            border-color: var(--primary);
            background: rgba(229, 57, 53, 0.16);
        }

        /* Mobile Recent Transactions List & Yellow Title Accent */
        .eb-panel-title-accent {
            width: 3px;
            height: 18px;
            border-radius: 2px;
            background: #FFC107;
            display: inline-block;
            flex-shrink: 0;
        }
        .eb-mobile-tx-list {
            display: flex;
            flex-direction: column;
        }
        .eb-mobile-tx-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.7rem;
            padding: 0.75rem 0.95rem;
            border-bottom: 1px solid #262626;
        }
        .eb-mobile-tx-row:last-child {
            border-bottom: 0;
        }
        .eb-mobile-tx-left {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-width: 0;
            flex: 1;
        }
        .eb-mobile-tx-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #FFFFFF;
        }
        .eb-mobile-tx-icon.tx-in { background: #2E7D32; }
        .eb-mobile-tx-icon.tx-out { background: #C62828; }
        .eb-mobile-tx-icon.tx-adj { background: #FFC107; color: #111111; }
        .eb-mobile-tx-icon.tx-spoil { background: #B71C1C; }
        .eb-mobile-tx-text {
            min-width: 0;
        }
        .eb-mobile-tx-type {
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .eb-mobile-tx-item {
            font-size: 0.82rem;
            font-weight: 600;
            color: #FFFFFF;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .eb-mobile-tx-right {
            text-align: right;
            flex-shrink: 0;
        }
        .eb-mobile-tx-qty {
            font-size: 0.84rem;
            font-weight: 800;
            line-height: 1.2;
        }
        .eb-mobile-tx-date {
            font-size: 0.68rem;
            color: #9E9E9E;
            margin-top: 2px;
        }

        /* Mobile Bottom Navigation Bar (< 768px) */
        .eb-mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background: #0A0A0A;
            border-top: 1px solid #262626;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            padding: 0.38rem 0.25rem calc(0.42rem + env(safe-area-inset-bottom, 0px));
            box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.72);
        }
        .eb-bottom-nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.18rem;
            padding: 0.25rem 0.2rem;
            color: #9E9E9E;
            font-size: 0.69rem;
            font-weight: 600;
            text-decoration: none;
            position: relative;
            transition: color 0.16s ease;
        }
        .eb-bottom-nav-link:hover {
            color: #FFFFFF;
        }
        .eb-bottom-nav-link.active {
            color: #E53935;
            font-weight: 700;
        }
        .eb-bottom-nav-link.active::before {
            content: '';
            position: absolute;
            top: -0.38rem;
            left: 24%;
            right: 24%;
            height: 2.5px;
            border-radius: 0 0 3px 3px;
            background: #E53935;
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
                height: 100vh;
                height: 100dvh !important;
                max-height: 100dvh;
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
            .topbar {
                background: #0A0A0A;
                padding: 0.55rem 0.9rem;
                min-height: 58px;
            }
            .content-area { padding: 0.85rem 0.9rem 1.25rem; }
            .eb-hero-art { width: 48%; max-width: 240px; opacity: 0.88; }
            .eb-hero-greeting { font-size: 1.35rem; }
            .eb-kpi-spark { display: none; }
        }

        /* MOBILE PHONE REFERENCE LAYOUT (< 768px) */
        @media (max-width: 767.98px) {
            .main-panel {
                padding-bottom: calc(68px + env(safe-area-inset-bottom, 0px));
            }
            .eb-hero-banner {
                min-height: 118px;
                margin-bottom: 0.9rem;
            }
            .eb-hero-banner::before {
                background:
                    linear-gradient(122deg, transparent 42%, rgba(229, 57, 53, 0.42) 50%, rgba(229, 57, 53, 0.14) 60%, transparent 66%),
                    linear-gradient(122deg, transparent 54%, rgba(255, 193, 7, 0.35) 60%, transparent 69%);
            }
            .eb-hero-art {
                width: 46%;
                max-width: 175px;
                opacity: 0.95;
            }
            .eb-hero-art::after {
                background: linear-gradient(90deg, #141414 0%, rgba(20, 20, 20, 0.72) 22%, rgba(20, 20, 20, 0.08) 70%, transparent 100%);
            }
            .eb-hero-content {
                padding: 0.95rem 1rem;
                gap: 0.65rem;
            }
            .eb-hero-text {
                max-width: 60%;
            }
            .eb-hero-controls {
                width: 100%;
                padding: 0.45rem 0.55rem;
                gap: 0.45rem;
            }
            .eb-hero-controls .form-select,
            .eb-hero-controls .form-control {
                min-width: 0 !important;
                width: 100% !important;
            }
            .eb-kpi-card {
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 0.95rem 0.6rem;
                gap: 0.42rem;
                border-radius: 14px;
            }
            .eb-kpi-circle {
                width: 42px;
                height: 42px;
            }
            .eb-kpi-body {
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .eb-kpi-label {
                order: 2;
                font-size: 0.78rem;
                color: #BDBDBD;
                white-space: normal;
                margin-bottom: 0;
            }
            .eb-kpi-value {
                order: 1;
                font-size: clamp(1.15rem, 5.2vw, 1.55rem);
                line-height: 1.1;
                margin-bottom: 0.1rem;
                overflow-wrap: anywhere;
            }
            .eb-kpi-meta {
                order: 3;
                justify-content: center;
                font-size: 0.69rem;
                margin-top: 0.12rem;
            }
            .eb-kpi-card.eb-kpi-wide-mobile {
                flex-direction: row;
                justify-content: flex-start;
                text-align: left;
                padding: 0.85rem 1rem;
                gap: 0.85rem;
            }
            .eb-kpi-card.eb-kpi-wide-mobile .eb-kpi-body {
                align-items: flex-start;
            }
            .eb-kpi-card.eb-kpi-wide-mobile .eb-kpi-value {
                order: 2;
                font-size: 1.4rem;
            }
            .eb-kpi-card.eb-kpi-wide-mobile .eb-kpi-label {
                order: 1;
            }
            .eb-panel-header,
            .eb-table-header {
                padding: 0.8rem 0.95rem;
                margin-bottom: 0;
            }
            .eb-panel-body {
                padding: 0.9rem;
            }
            .eb-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
                padding: 0.8rem 0.95rem 0.95rem;
            }
        }

        /* USABILITY & ACCESSIBILITY ENHANCEMENTS */
        .nav-subhint {
            display: block;
            font-size: 0.72rem;
            color: var(--muted);
            font-weight: 400;
            line-height: 1.15;
            margin-top: 1px;
        }
        .sidebar.collapsed .nav-subhint,
        html.sidebar-is-collapsed .nav-subhint {
            display: none;
        }
        .table-light, .table thead th {
            background-color: #141414 !important;
            color: #FFFFFF !important;
            border-color: var(--border) !important;
        }
        .form-label {
            font-weight: 600;
            color: #FFFFFF;
            margin-bottom: 0.35rem;
        }
        .field-help {
            display: block;
            color: var(--muted);
            font-size: 0.85rem;
            margin-top: 0.3rem;
            line-height: 1.4;
        }
        .required-star {
            color: #E53935;
            font-weight: 700;
        }
        .btn:focus-visible,
        .form-control:focus-visible,
        .form-select:focus-visible,
        .nav-link:focus-visible {
            outline: 2px solid #FFC107 !important;
            outline-offset: 2px;
        }
        .page-guide-subtitle {
            color: var(--muted);
            font-size: 0.98rem;
            margin-top: 0.35rem;
            margin-bottom: 0;
        }

        @include('layouts.partials.design-system-css')
    </style>
</head>
<body class="@auth management-body @else public-body @endauth">
    @auth
        @php
            $currentUser = Auth::user();
            $userRole = $currentUser
                ? ($currentUser->relationLoaded('role')
                    ? $currentUser->role?->name
                    : \App\Models\Role::cachedNameById($currentUser->role_id))
                : null;
            $routePrefix = match ($userRole) {
                'system_admin' => 'sysadmin',
                'owner' => 'owner',
                'supplier' => 'supplier',
                default => 'staff',
            };
            $userRoleLabel = match ($userRole) {
                'system_admin' => 'System Admin (Dev)',
                'owner' => 'Owner / Admin',
                'supplier' => 'Supplier Portal',
                default => 'Staff Member',
            };
            $notifSummary = $currentUser
                ? \App\Services\StockNotificationService::forUser($currentUser, $userRole, 15)
                : ['total_count' => 0, 'counts' => ['out_of_stock' => 0, 'low_stock' => 0, 'reorder' => 0, 'spoilage' => 0], 'notifications' => []];
            $notifTotalCount = (int) ($notifSummary['total_count'] ?? 0);
            $notifCounts = $notifSummary['counts'] ?? ['out_of_stock' => 0, 'low_stock' => 0, 'reorder' => 0, 'spoilage' => 0];
            $notifItems = $notifSummary['notifications'] ?? [];
            $inventoryAlertFilter = (($notifCounts['out_of_stock'] ?? 0) > 0 && ($notifCounts['low_stock'] ?? 0) === 0)
                ? 'out_of_stock'
                : 'low_stock';
        @endphp
        <div class="app-shell">
            <!-- Mobile Backdrop -->
            <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeMobileDrawer()"></div>

            <aside class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <div class="brand-mascot-wrap">
                        <img src="{{ asset('images/eustace-mascot.jpg') }}" alt="Eustace Batchoy Logo">
                    </div>
                    <div class="brand-meta">
                        <span class="brand-script">Eustace</span>
                        <span class="brand-text">BATCHOY <span class="visually-hidden">MyBatchoy</span></span>
                        <span class="brand-subtitle">UNLI SABAW</span>
                    </div>
                    <button type="button" class="sidebar-close-btn d-inline-flex d-lg-none" onclick="closeMobileDrawer()" aria-label="Close navigation menu">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                        </svg>
                    </button>
                </div>

                <nav class="sidebar-nav" id="sidebar-navigation">
                    @if($userRole === 'system_admin')
                        <div class="nav-group">
                            <div class="nav-group-label">TECHNICAL OVERVIEW</div>
                            <a class="nav-link {{ request()->routeIs('dashboard','sysadmin.dashboard') ? 'active' : '' }}" href="{{ route('sysadmin.dashboard') }}" title="Dashboard" data-tooltip="Dashboard">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Technical Dashboard</span>
                            </a>
                        </div>

                        <div class="nav-group">
                            <div class="nav-group-label">SYSTEM OPERATIONS</div>
                            <a class="nav-link {{ request()->routeIs('sysadmin.health') ? 'active' : '' }}" href="{{ route('sysadmin.health') }}" title="Application Health" data-tooltip="App Health">
                                <span class="nav-icon" style="color: #4CAF50;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.354 5.354-4 4a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7 9.293l3.646-3.647a.5.5 0 0 1 .708.708z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Application Health</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('sysadmin.security*') ? 'active' : '' }}" href="{{ route('sysadmin.security') }}" title="Security Monitoring" data-tooltip="Security Monitoring">
                                <span class="nav-icon" style="color: #FFC107;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Security Monitoring</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('sysadmin.backup*') ? 'active' : '' }}" href="{{ route('sysadmin.backup') }}" title="Backups" data-tooltip="Backups">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Backups</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('sysadmin.config*') ? 'active' : '' }}" href="{{ route('sysadmin.config') }}" title="Technical Configuration" data-tooltip="Technical Configuration">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872l-.1-.34zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Technical Config</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('sysadmin.accounts*') ? 'active' : '' }}" href="{{ route('sysadmin.accounts.index') }}" title="System Admin Accounts" data-tooltip="Admin Accounts">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Admin Accounts</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('sysadmin.access*') ? 'active' : '' }}" href="{{ route('sysadmin.access.index') }}" title="Business Access Requests" data-tooltip="Access Requests">
                                <span class="nav-icon" style="color: #E53935;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Access Requests</span>
                            </a>
                        </div>

                        <div class="nav-group">
                            <div class="nav-group-label">ACCOUNT</div>
                            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}" title="Profile" data-tooltip="Profile">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Profile</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button class="nav-link logout-btn w-100 text-start" type="submit" title="Logout" data-tooltip="Logout">
                                    <span class="nav-icon" style="color: #FFC107;">
                                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                                            <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                                        </svg>
                                    </span>
                                    <span class="nav-label">Logout</span>
                                </button>
                            </form>
                        </div>
                    @elseif($userRole === 'owner')
                        <!-- 1. OVERVIEW -->
                        <div class="nav-group">
                            <div class="nav-group-label">OVERVIEW</div>
                            <a class="nav-link {{ request()->routeIs('dashboard','owner.dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard" data-tooltip="Dashboard">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </div>

                        <!-- 2. INVENTORY -->
                        <div class="nav-group">
                            <div class="nav-group-label">INVENTORY</div>
                            <a class="nav-link {{ request()->routeIs('owner.inventory.*') ? 'active' : '' }}" href="{{ route('owner.inventory.index') }}" title="Inventory" data-tooltip="Inventory">
                                <span class="nav-icon" style="color: #FFC107;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Inventory</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.transactions.*') ? 'active' : '' }}" href="{{ route('owner.transactions.index') }}" title="Stock Transactions" data-tooltip="Stock Transactions">
                                <span class="nav-icon" style="color: #4CAF50;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Stock Transactions</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.adjustments.*') ? 'active' : '' }}" href="{{ route('owner.adjustments.index') }}" title="Stock Adjustments & Counts" data-tooltip="Stock Adjustments">
                                <span class="nav-icon" style="color: #FFC107;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                                        <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Stock Adjustments</span>
                            </a>
                        </div>

                        <!-- 3. PURCHASING -->
                        <div class="nav-group">
                            <div class="nav-group-label">PURCHASING</div>
                            <a class="nav-link {{ request()->routeIs('owner.suppliers.*') ? 'active' : '' }}" href="{{ route('owner.suppliers.index') }}" title="Suppliers" data-tooltip="Suppliers">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Suppliers</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.supplier-accounts.*') ? 'active' : '' }}" href="{{ route('owner.supplier-accounts.index') }}" title="Supplier Accounts" data-tooltip="Supplier Accounts">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm9.5-5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1H15V3.5a.5.5 0 0 1 .5-.5z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Supplier Accounts</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.purchases.*') ? 'active' : '' }}" href="{{ route('owner.purchases.index') }}" title="Purchases" data-tooltip="Purchases">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Purchases</span>
                            </a>
                        </div>

                        <!-- 4. ANALYTICS -->
                        <div class="nav-group">
                            <div class="nav-group-label">ANALYTICS</div>
                            <a class="nav-link {{ request()->routeIs('owner.reports.*') ? 'active' : '' }}" href="{{ route('owner.reports.index') }}" title="Reports" data-tooltip="Reports">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm-5 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Reports</span>
                            </a>
                        </div>

                        <!-- 5. ADMINISTRATION -->
                        <div class="nav-group">
                            <div class="nav-group-label">ADMINISTRATION</div>
                            <a class="nav-link {{ request()->routeIs('owner.staff.*') ? 'active' : '' }}" href="{{ route('owner.staff.index') }}" title="Staff Management" data-tooltip="Staff Management">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Staff Management</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.audit.index') ? 'active' : '' }}" href="{{ route('owner.audit.index') }}" title="Audit Logs" data-tooltip="Audit Logs">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/>
                                        <path d="M5 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 5 8zm0-2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0 5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-1-5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zM4 8a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm0 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Audit Logs</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.access.*') ? 'active' : '' }}" href="{{ route('owner.access.index') }}" title="System Admin Access Requests" data-tooltip="Access Requests">
                                <span class="nav-icon" style="color: #E53935;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Access Requests</span>
                            </a>
                        </div>

                        <!-- 6. ACCOUNT -->
                        <div class="nav-group">
                            <div class="nav-group-label">ACCOUNT</div>
                            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}" title="Profile" data-tooltip="Profile">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Profile</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('owner.settings.*') ? 'active' : '' }}" href="{{ route('owner.settings.index') }}" title="Settings" data-tooltip="Settings">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872l-.1-.34zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Settings</span>
                            </a>
                            <button class="nav-link d-lg-none w-100 text-start" type="button" onclick="closeMobileDrawer(); openHelpGuideModal();">
                                <span class="nav-icon" style="color: #FFC107;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Quick Help Guide</span>
                            </button>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button class="nav-link logout-btn w-100 text-start" type="submit" title="Logout" data-tooltip="Logout">
                                    <span class="nav-icon" style="color: #FFC107;">
                                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                                            <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                                        </svg>
                                    </span>
                                    <span class="nav-label">Logout</span>
                                </button>
                            </form>
                        </div>
                    @elseif($userRole === 'supplier')
                        <div class="nav-group">
                            <div class="nav-group-label">SUPPLIER PORTAL</div>
                            <a class="nav-link {{ request()->routeIs('dashboard','supplier.dashboard') ? 'active' : '' }}" href="{{ route('supplier.dashboard') }}" title="Dashboard" data-tooltip="Dashboard">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Supplier Dashboard</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('supplier.orders.*') ? 'active' : '' }}" href="{{ route('supplier.orders.index') }}" title="Purchase Orders" data-tooltip="Purchase Orders">
                                <span class="nav-icon" style="color: #FFC107;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Purchase Orders</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('supplier.profile*') ? 'active' : '' }}" href="{{ route('supplier.profile') }}" title="Supplier Profile" data-tooltip="Supplier Profile">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Supplier Profile</span>
                            </a>
                        </div>

                        <div class="nav-group">
                            <div class="nav-group-label">ACCOUNT</div>
                            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}" title="Account Security" data-tooltip="Account Security">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">My Account</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button class="nav-link logout-btn w-100 text-start" type="submit" title="Logout" data-tooltip="Logout">
                                    <span class="nav-icon" style="color: #FFC107;">
                                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                                            <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                                        </svg>
                                    </span>
                                    <span class="nav-label">Logout</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <!-- STAFF SECTIONS -->
                        <!-- 1. OVERVIEW -->
                        <div class="nav-group">
                            <div class="nav-group-label">OVERVIEW</div>
                            <a class="nav-link {{ request()->routeIs('dashboard','staff.dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard" data-tooltip="Dashboard">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </div>

                        <!-- 2. INVENTORY -->
                        <div class="nav-group">
                            <div class="nav-group-label">INVENTORY</div>
                            <a class="nav-link {{ request()->routeIs('staff.inventory.index') ? 'active' : '' }}" href="{{ route('staff.inventory.index') }}" title="Inventory" data-tooltip="Inventory">
                                <span class="nav-icon" style="color: #FFC107;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Inventory</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('staff.transactions.*') ? 'active' : '' }}" href="{{ route('staff.transactions.index') }}" title="Stock Transactions" data-tooltip="Stock Transactions">
                                <span class="nav-icon" style="color: #4CAF50;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Stock Transactions</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('staff.adjustments.*') ? 'active' : '' }}" href="{{ route('staff.adjustments.index') }}" title="Count & Adjust Requests" data-tooltip="Count Requests">
                                <span class="nav-icon" style="color: #FFC107;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                                        <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Count &amp; Adjust Requests</span>
                            </a>
                        </div>

                        <!-- 3. ACCOUNT -->
                        <div class="nav-group">
                            <div class="nav-group-label">ACCOUNT</div>
                            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}" title="Profile" data-tooltip="Profile">
                                <span class="nav-icon">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Profile</span>
                            </a>
                            <button class="nav-link d-lg-none w-100 text-start" type="button" onclick="closeMobileDrawer(); openHelpGuideModal();">
                                <span class="nav-icon" style="color: #FFC107;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
                                    </svg>
                                </span>
                                <span class="nav-label">Quick Help Guide</span>
                            </button>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button class="nav-link logout-btn w-100 text-start" type="submit" title="Logout" data-tooltip="Logout">
                                    <span class="nav-icon" style="color: #FFC107;">
                                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                                            <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                                        </svg>
                                    </span>
                                    <span class="nav-label">Logout</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </nav>
            </aside>

            <div class="main-panel">
                <header class="topbar">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <button class="sidebar-toggle d-none d-lg-inline-flex" id="sidebarToggleBtn" type="button" aria-label="Toggle sidebar navigation" aria-controls="sidebar-navigation" aria-expanded="true" onclick="toggleSidebar()">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                        </button>
                        <a href="{{ route($routePrefix . '.dashboard') }}" class="eb-mobile-brand d-flex d-lg-none" aria-label="MyBatchoy Home">
                            <span class="brand-mascot-wrap">
                                <img src="{{ asset('images/eustace-mascot.jpg') }}" alt="Eustace Batchoy">
                            </span>
                            <span class="eb-mobile-brand-meta">
                                <span class="eb-mobile-brand-top">
                                    <span class="brand-script">Eustace</span>
                                    <span class="brand-text">BATCHOY</span>
                                </span>
                                <span class="brand-subtitle">UNLI SABAW</span>
                            </span>
                        </a>
                        <div class="topbar-title-text d-none d-lg-block">
                            @yield('title', 'Dashboard')
                        </div>
                    </div>
                    <div class="topbar-meta">
                        <button type="button" class="btn btn-sm btn-outline-warning d-none d-lg-inline-flex align-items-center gap-1" id="openHelpGuideBtn" onclick="openHelpGuideModal()" title="Open step-by-step instructions">
                            <span aria-hidden="true">?</span>
                            <span class="d-none d-md-inline">Quick Help</span>
                        </button>

                        <div class="dropdown eb-notif-wrap">
                            <button
                                type="button"
                                class="topbar-bell-btn"
                                id="notificationsBellBtn"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Open stock notifications"
                                title="{{ $notifTotalCount > 0 ? $notifTotalCount . ' stock notification(s)' : 'No stock problems right now' }}"
                            >
                                <svg width="17" height="17" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zm.995-14.901a1 1 0 1 0-1.99 0A5.002 5.002 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901z"/>
                                </svg>
                                @if($notifTotalCount > 0)
                                    <span class="topbar-bell-badge" id="notificationsBellBadge">{{ $notifTotalCount > 99 ? '99+' : $notifTotalCount }}</span>
                                @endif
                            </button>

                            <div class="dropdown-menu dropdown-menu-end eb-notif-dropdown" id="eb-notif-dropdown" aria-labelledby="notificationsBellBtn">
                                <div class="eb-notif-header">
                                    <div>
                                        <div class="eb-notif-title">Notifications</div>
                                        <div class="eb-notif-subtitle">
                                            @if($notifTotalCount > 0)
                                                {{ $notifTotalCount }} {{ \Illuminate\Support\Str::plural('alert', $notifTotalCount) }} need attention
                                            @elseif($userRole === 'system_admin')
                                                No active access grants
                                            @elseif($userRole === 'supplier')
                                                No pending purchase orders
                                            @else
                                                No active stock issues
                                            @endif
                                        </div>
                                    </div>
                                    <span class="eb-notif-count-chip {{ $notifTotalCount > 0 ? 'has-alerts' : '' }}">
                                        {{ $notifTotalCount > 0 ? $notifTotalCount . ' Active' : 'All Good' }}
                                    </span>
                                </div>

                                @if($notifTotalCount > 0 && in_array($userRole, ['owner', 'staff'], true))
                                    <div class="eb-notif-pills">
                                        @if(($notifCounts['out_of_stock'] ?? 0) > 0)
                                            <span class="eb-notif-pill pill-danger">{{ $notifCounts['out_of_stock'] }} Out of Stock</span>
                                        @endif
                                        @if(($notifCounts['low_stock'] ?? 0) > 0)
                                            <span class="eb-notif-pill pill-warning">{{ $notifCounts['low_stock'] }} Low Stock</span>
                                        @endif
                                        @if(($notifCounts['reorder'] ?? 0) > 0)
                                            <span class="eb-notif-pill pill-info">{{ $notifCounts['reorder'] }} Reorder</span>
                                        @endif
                                        @if(($notifCounts['spoilage'] ?? 0) > 0)
                                            <span class="eb-notif-pill pill-spoilage">{{ $notifCounts['spoilage'] }} Spoilage</span>
                                        @endif
                                    </div>
                                @endif

                                <div class="eb-notif-list">
                                    @forelse($notifItems as $notif)
                                        @php
                                            if ($userRole === 'system_admin' || $userRole === 'supplier') {
                                                $notifUrl = !empty($notif['route']) ? route($notif['route']) : route($routePrefix . '.dashboard');
                                                $notifBadge = $userRole === 'system_admin' ? 'ACCESS' : 'ORDER';
                                                $notifCta = $userRole === 'system_admin' ? 'Open access portal →' : 'View purchase order →';
                                            } else {
                                                $notifUrl = ($notif['action_type'] ?? '') === 'spoilage_history'
                                                    ? route($routePrefix . '.transactions.index', ['type' => 'spoilage'])
                                                    : route($routePrefix . '.transactions.stockIn', ['item' => $notif['item_id']]);
                                                $notifBadge = $notif['badge'] ?? 'ALERT';
                                                $notifCta = ($notif['action_type'] ?? '') === 'spoilage_history' ? 'View spoilage history →' : 'Tap to add stock →';
                                            }
                                            $severityClass = match ($notif['severity'] ?? 'info') {
                                                'danger' => 'sev-danger',
                                                'warning' => 'sev-warning',
                                                'spoilage' => 'sev-spoilage',
                                                default => 'sev-info',
                                            };
                                        @endphp
                                        <a href="{{ $notifUrl }}" class="eb-notif-item {{ $severityClass }}">
                                            <div class="eb-notif-item-top">
                                                <span class="eb-notif-badge">{{ $notifBadge }}</span>
                                                @if(!empty($notif['time_ago']))
                                                    <span class="eb-notif-time">{{ $notif['time_ago'] }}</span>
                                                @endif
                                            </div>
                                            <div class="eb-notif-item-title">{{ $notif['title'] }}</div>
                                            <div class="eb-notif-item-msg">{{ $notif['message'] }}</div>
                                            <div class="eb-notif-item-cta">
                                                {{ $notifCta }}
                                            </div>
                                        </a>
                                    @empty
                                        <div class="eb-notif-empty">
                                            <div class="eb-notif-empty-title">{{ $userRole === 'system_admin' ? 'No temporary business grants active' : ($userRole === 'supplier' ? 'All orders up to date' : 'All stock levels look good') }}</div>
                                            <div class="eb-notif-empty-sub">{{ $userRole === 'system_admin' ? 'Technical admin operations run isolated from confidential business records.' : ($userRole === 'supplier' ? 'No unconfirmed purchase orders waiting right now.' : 'No out-of-stock, low-stock, reorder warnings, or recent spoilage found.') }}</div>
                                        </div>
                                    @endforelse
                                </div>

                                <div class="eb-notif-footer">
                                    @if($userRole === 'system_admin')
                                        <a href="{{ route('sysadmin.access.index') }}" class="eb-notif-footer-link">
                                            Access Requests
                                        </a>
                                        <a href="{{ route('sysadmin.security') }}" class="eb-notif-footer-link">
                                            Security Logs
                                        </a>
                                    @elseif($userRole === 'supplier')
                                        <a href="{{ route('supplier.orders.index') }}" class="eb-notif-footer-link">
                                            Purchase Orders
                                        </a>
                                        <a href="{{ route('supplier.profile') }}" class="eb-notif-footer-link">
                                            Supplier Profile
                                        </a>
                                    @else
                                        <a href="{{ route($routePrefix . '.inventory.index', ['status' => $inventoryAlertFilter]) }}" class="eb-notif-footer-link">
                                            View All Stock Problems
                                        </a>
                                        <a href="{{ route($routePrefix . '.transactions.index', ['type' => 'spoilage']) }}" class="eb-notif-footer-link">
                                            Spoilage History
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <button class="sidebar-toggle d-inline-flex d-lg-none" id="mobileDrawerToggleBtn" type="button" aria-label="Open menu navigation" aria-controls="sidebar" onclick="toggleMobileDrawer()">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                        </button>

                        <div class="dropdown d-none d-lg-block">
                            <button class="user-profile-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="user-avatar-circle">{{ strtoupper(substr($currentUser->name, 0, 1)) }}</span>
                                <span class="user-profile-info d-none d-sm-flex">
                                    <span class="user-profile-name">{{ $currentUser->name }}</span>
                                    <span class="user-profile-role">{{ $userRoleLabel }}</span>
                                </span>
                                <svg width="12" height="12" fill="#BDBDBD" viewBox="0 0 16 16" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg" style="background: #1B1B1B; border: 1px solid #333333; min-width: 200px;">
                                <li class="px-3 py-2 border-bottom border-border">
                                    <div class="fw-bold text-white small">{{ $currentUser->name }}</div>
                                    <div class="muted" style="font-size: 0.72rem;">{{ $userRoleLabel }}</div>
                                </li>
                                <li><a class="dropdown-item py-2 small text-white" href="{{ route('profile.show') }}">My Profile</a></li>
                                <li><button class="dropdown-item py-2 small text-warning" type="button" onclick="openHelpGuideModal()">Quick Help Guide</button></li>
                                <li><hr class="dropdown-divider border-border"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item py-2 small text-danger fw-semibold">Sign Out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </header>

                <main class="content-area">
                    <div class="content-shell page-fade-in">
                        @if (session('error'))
                            <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
                                <span aria-hidden="true" class="fs-5">&#9888;</span>
                                <div class="flex-grow-1">
                                    <strong class="d-block">Action Could Not Be Completed</strong>
                                    <span>{{ session('error') }}</span>
                                </div>
                            </div>
                        @endif

                        @include('components.first-time-guide')
                        @yield('content')
                    </div>
                </main>

                <footer class="eb-footer">
                    <div>© {{ date('Y') }} Eustace Batchoy. All rights reserved. | MyBatchoy v1.0</div>
                    <div class="eb-footer-tagline">Quality Food, Better Management.</div>
                </footer>
            </div>

            <!-- Mobile Bottom Navigation Bar (< 768px, Matches Reference Phone UI) -->
            <nav class="eb-mobile-bottom-nav d-md-none" aria-label="Mobile quick navigation">
                @if ($userRole === 'system_admin')
                    <a href="{{ route('sysadmin.dashboard') }}" class="eb-bottom-nav-link {{ request()->routeIs('dashboard', 'sysadmin.dashboard') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                        </svg>
                        <span>Home</span>
                    </a>
                    <a href="{{ route('sysadmin.health') }}" class="eb-bottom-nav-link {{ request()->routeIs('sysadmin.health') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.354 5.354-4 4a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7 9.293l3.646-3.647a.5.5 0 0 1 .708.708z"/>
                        </svg>
                        <span>Health</span>
                    </a>
                    <a href="{{ route('sysadmin.security') }}" class="eb-bottom-nav-link {{ request()->routeIs('sysadmin.security*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524z"/>
                        </svg>
                        <span>Security</span>
                    </a>
                    <a href="{{ route('sysadmin.access.index') }}" class="eb-bottom-nav-link {{ request()->routeIs('sysadmin.access*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                        </svg>
                        <span>Access</span>
                    </a>
                @elseif ($userRole === 'supplier')
                    <a href="{{ route('supplier.dashboard') }}" class="eb-bottom-nav-link {{ request()->routeIs('dashboard', 'supplier.dashboard') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                        </svg>
                        <span>Home</span>
                    </a>
                    <a href="{{ route('supplier.orders.index') }}" class="eb-bottom-nav-link {{ request()->routeIs('supplier.orders.*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                        </svg>
                        <span>Orders</span>
                    </a>
                    <a href="{{ route('supplier.profile') }}" class="eb-bottom-nav-link {{ request()->routeIs('supplier.profile*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                        </svg>
                        <span>Supplier</span>
                    </a>
                    <a href="{{ route('profile.show') }}" class="eb-bottom-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                        </svg>
                        <span>Account</span>
                    </a>
                @elseif ($userRole === 'owner')
                    <a href="{{ route($routePrefix . '.dashboard') }}" class="eb-bottom-nav-link {{ request()->routeIs('dashboard', 'owner.dashboard', 'staff.dashboard') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                        </svg>
                        <span>Home</span>
                    </a>
                    <a href="{{ route($routePrefix . '.inventory.index') }}" class="eb-bottom-nav-link {{ request()->routeIs($routePrefix . '.inventory.*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/>
                        </svg>
                        <span>Inventory</span>
                    </a>
                    <a href="{{ route('owner.reports.index') }}" class="eb-bottom-nav-link {{ request()->routeIs('owner.reports.*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm-5 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3z"/>
                        </svg>
                        <span>Reports</span>
                    </a>
                    <a href="{{ route('owner.settings.index') }}" class="eb-bottom-nav-link {{ request()->routeIs('owner.settings.*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872l-.1-.34zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
                        </svg>
                        <span>Settings</span>
                    </a>
                @else
                    <a href="{{ route($routePrefix . '.dashboard') }}" class="eb-bottom-nav-link {{ request()->routeIs('dashboard', 'owner.dashboard', 'staff.dashboard') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                        </svg>
                        <span>Home</span>
                    </a>
                    <a href="{{ route($routePrefix . '.inventory.index') }}" class="eb-bottom-nav-link {{ request()->routeIs($routePrefix . '.inventory.*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/>
                        </svg>
                        <span>Inventory</span>
                    </a>
                    <a href="{{ route('staff.transactions.index') }}" class="eb-bottom-nav-link {{ request()->routeIs('staff.transactions.*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z"/>
                        </svg>
                        <span>Transactions</span>
                    </a>
                    <a href="{{ route('profile.show') }}" class="eb-bottom-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <svg width="19" height="19" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                        </svg>
                        <span>Profile</span>
                    </a>
                @endif
            </nav>
        </div>

    @else
        <div class="min-vh-100 d-flex flex-column justify-content-between" style="background: #111111;">
            <header class="py-2 py-md-3 px-3 px-md-4 border-bottom" style="border-color: #242424 !important; background: #0A0A0A;">
                <div class="container d-flex align-items-center justify-content-between">
                    <a href="{{ route('login') }}" class="eb-mobile-brand d-flex text-decoration-none">
                        <span class="brand-mascot-wrap" style="width: 38px; height: 38px;">
                            <img src="{{ asset('images/eustace-mascot.jpg') }}" alt="Eustace Batchoy">
                        </span>
                        <span class="eb-mobile-brand-meta">
                            <span class="eb-mobile-brand-top">
                                <span class="brand-script">Eustace</span>
                                <span class="brand-text">BATCHOY <span class="visually-hidden">MyBatchoy</span></span>
                            </span>
                            <span class="brand-subtitle">UNLI SABAW</span>
                        </span>
                    </a>
                    <span class="small d-none d-sm-inline" style="color: #BDBDBD;">Eustace Batchoy &bull; Internal Operations Portal</span>
                </div>
            </header>

            <main class="flex-grow-1 d-flex align-items-center py-4">
                @yield('content')
            </main>

            <footer class="py-3 text-center border-top" style="border-color: #242424 !important; background: #0A0A0A; color: #BDBDBD; font-size: 0.8rem;">
                <div class="container">
                    <span>&copy; {{ date('Y') }} Eustace Batchoy &mdash; MyBatchoy v1.0. Authorized personnel only.</span>
                </div>
            </footer>
        </div>
    @endauth

    @if (app()->environment('production'))
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @else
        <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @endif
    <script src="{{ asset('vendor/qrcode/qrcode.min.js') }}"></script>
    <script>
        (function renderOfflineQrCodes() {
            function buildQr() {
                if (typeof window.qrcode !== 'function') return;
                if (window.qrcode.stringToBytesFuncs && window.qrcode.stringToBytesFuncs['UTF-8']) {
                    window.qrcode.stringToBytes = window.qrcode.stringToBytesFuncs['UTF-8'];
                }
                document.querySelectorAll('.eb-offline-qr[data-qr-text]').forEach(function (el) {
                    var value = el.getAttribute('data-qr-text') || '';
                    if (!value) return;
                    try {
                        var qr = window.qrcode(0, 'M');
                        qr.addData(value);
                        qr.make();
                        el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 1, scalable: true, alt: el.getAttribute('aria-label') || 'QR Code' });
                        var svg = el.querySelector('svg');
                        if (svg) {
                            svg.style.width = '100%';
                            svg.style.height = '100%';
                            svg.style.display = 'block';
                        }
                    } catch (e) {}
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', buildQr);
            } else {
                buildQr();
            }
        })();
    </script>
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

        let skeletonSafetyTimer = null;

        function resetPageSkeletons() {
            if (skeletonSafetyTimer) {
                clearTimeout(skeletonSafetyTimer);
                skeletonSafetyTimer = null;
            }
            document.querySelectorAll('[id$="SkeletonWrapper"]').forEach(function(el) {
                el.classList.add('d-none');
            });
            document.querySelectorAll('[id$="LiveContent"]').forEach(function(el) {
                el.classList.remove('d-none');
                el.style.opacity = '';
                el.style.pointerEvents = '';
            });
        }

        window.activatePageSkeleton = function(skeletonId, liveId) {
            const skeleton = document.getElementById(skeletonId);
            const live = document.getElementById(liveId);
            if (!skeleton || !live) {
                return;
            }
            live.style.opacity = '';
            live.style.pointerEvents = '';
            live.classList.add('d-none');
            skeleton.classList.remove('d-none');

            if (skeletonSafetyTimer) {
                clearTimeout(skeletonSafetyTimer);
            }
            skeletonSafetyTimer = setTimeout(resetPageSkeletons, 8000);
        };

        window.addEventListener('pageshow', resetPageSkeletons);

        // Auto close drawer when navigating on mobile
        document.addEventListener('DOMContentLoaded', function() {
            resetPageSkeletons();
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

        // First-time guide persistence & modal helper
        (function initFirstTimeGuide() {
            const GUIDE_KEY = 'mybatchoy_guide_dismissed_v1';

            function ensureRootModal(modalEl) {
                if (modalEl && modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }
            }

            function portalShellModals() {
                document.querySelectorAll('.app-shell .modal').forEach(ensureRootModal);
            }

            portalShellModals();

            document.addEventListener('DOMContentLoaded', function () {
                portalShellModals();

                const banner = document.getElementById('firstTimeGuideBanner');
                if (banner) {
                    try {
                        if (localStorage.getItem(GUIDE_KEY) !== '1') {
                            banner.classList.remove('d-none');
                        }
                    } catch (e) {
                        banner.classList.remove('d-none');
                    }
                }

                // Global double-submit protection & optional friendly confirmation dialog
                document.querySelectorAll('form[data-safe-submit]').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (form.dataset.submitting === 'true') {
                            event.preventDefault();
                            return;
                        }
                        if (!form.reportValidity()) {
                            return;
                        }
                        const confirmMessage = form.dataset.confirmMessage || form.dataset.confirm;
                        if (confirmMessage && form.dataset.confirmed !== 'true') {
                            event.preventDefault();
                            const modalEl = document.getElementById('globalConfirmModal');
                            if (modalEl && typeof bootstrap !== 'undefined') {
                                ensureRootModal(modalEl);
                                document.getElementById('globalConfirmTitle').textContent = form.dataset.confirmTitle || 'Please Confirm';
                                document.getElementById('globalConfirmBody').textContent = confirmMessage;
                                const proceedBtn = document.getElementById('globalConfirmProceedBtn');
                                proceedBtn.textContent = form.dataset.confirmButton || 'Yes, Confirm';
                                proceedBtn.className = 'btn ' + (form.dataset.confirmVariant || 'btn-primary');
                                proceedBtn.onclick = function () {
                                    form.dataset.confirmed = 'true';
                                    bootstrap.Modal.getInstance(modalEl)?.hide();
                                    form.requestSubmit ? form.requestSubmit() : form.submit();
                                };
                                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                                return;
                            }
                            if (!window.confirm(confirmMessage)) {
                                return;
                            }
                        }
                        form.dataset.submitting = 'true';
                        const submitBtn = form.querySelector('button[type="submit"]:not([data-skip-loading])');
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            const label = submitBtn.dataset.loadingLabel || 'Saving, please wait...';
                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + label;
                        }
                    });
                });
            });

            window.dismissFirstTimeGuide = function (save) {
                const banner = document.getElementById('firstTimeGuideBanner');
                if (banner) banner.classList.add('d-none');
                if (save) {
                    try { localStorage.setItem(GUIDE_KEY, '1'); } catch (e) {}
                }
            };

            window.openHelpGuideModal = function () {
                const modalEl = document.getElementById('helpGuideModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    ensureRootModal(modalEl);
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            };
        })();
    </script>

    <!-- Global Accessible Confirmation Modal -->
    <div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-labelledby="globalConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #1B1B1B; border: 1px solid var(--border); color: var(--text);">
                <div class="modal-header border-border">
                    <h5 class="modal-title fw-bold" id="globalConfirmTitle">Please Confirm</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="globalConfirmBody" style="font-size: 1.02rem; line-height: 1.55;"></div>
                <div class="modal-footer border-border">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Go Back</button>
                    <button type="button" class="btn btn-primary" id="globalConfirmProceedBtn">Yes, Confirm</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

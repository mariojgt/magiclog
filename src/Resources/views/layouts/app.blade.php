<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Request Logger') }}</title>
    <!-- Custom Logger Styles -->
    @push('styles')
    <style>
        /* Reset and Base Styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            /* Core Colors - Reduced and simplified */
            --primary: #9333ea;
            --primary-light: #a855f7;
            --primary-dark: #7e22ce;
            --secondary: #22d3ee;

            /* Background Colors */
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --bg-card-hover: #334155;

            /* Text Colors - Simplified to 3 main options */
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;

            /* Border Colors - Reduced to 2 options */
            --border: #334155;
            --border-light: #475569;

            /* Status Colors */
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #eab308;
            --info: #3b82f6;

            /* HTTP Method Colors */
            --get: #10b981;
            --post: #3b82f6;
            --put: #f59e0b;
            --delete: #ef4444;

            /* Spacing System */
            --space-xs: 0.25rem;  /* 4px */
            --space-sm: 0.5rem;   /* 8px */
            --space-md: 1rem;     /* 16px */
            --space-lg: 1.5rem;   /* 24px */
            --space-xl: 2rem;     /* 32px */

            /* Border Radius */
            --radius-sm: 0.375rem;   /* 6px */
            --radius-md: 0.5rem;     /* 8px */
            --radius-lg: 0.75rem;    /* 12px */

            /* Shadows */
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                    0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                        0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: var(--text-primary);
            background-color: var(--bg-dark);
            background-image:
                radial-gradient(circle at 25% 25%, rgba(147, 51, 234, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(34, 211, 238, 0.1) 0%, transparent 50%);
            min-height: 100vh;
        }

        /* Typography */
        h1, h2, h3, h4, h5 {
            margin-bottom: var(--space-sm);
            font-weight: 600;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }

        h1 {
            font-size: 2.25rem;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: var(--space-lg);
        }

        h2 { font-size: 1.8rem; }
        h5 { font-size: 1.25rem; }

        /* Text utility classes */
        .text-muted { color: var(--text-muted); }
        .text-white { color: var(--text-primary); }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-gradient {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .text-success { color: var(--success); }
        .text-danger { color: var(--danger); }
        .text-warning { color: var(--warning); }


        /* Layout System */
        .container {
            width: 100%;
            padding-right: var(--space-md);
            padding-left: var(--space-md);
            margin-right: auto;
            margin-left: auto;
            max-width: 1280px;
        }

        .container-fluid {
            width: 100%;
            padding-right: var(--space-md);
            padding-left: var(--space-md);
        }

        /* Simplified Grid System */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: calc(var(--space-lg) / -2);
            margin-left: calc(var(--space-lg) / -2);
        }

        /* Column classes with common padding */
        [class^="col-"] {
            padding: 0 calc(var(--space-lg) / 2);
        }

        .col-12 { flex: 0 0 100%; max-width: 100%; }
        .col-md-2 { flex: 0 0 16.666667%; max-width: 16.666667%; }
        .col-md-3 { flex: 0 0 25%; max-width: 25%; }
        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; }

        /* Flex utilities */
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-center { justify-content: center; }
        .align-items-center { align-items: center; }

        /* Spacing utilities - Using the spacing system variables */
        .py-4 { padding-top: var(--space-lg); padding-bottom: var(--space-lg); }
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: var(--space-xs); }
        .mb-2 { margin-bottom: var(--space-sm); }
        .mb-4 { margin-bottom: var(--space-lg); }
        .mr-1 { margin-right: var(--space-xs); }
        .mr-2 { margin-right: var(--space-sm); }
        .mr-3 { margin-right: var(--space-md); }
        .p-2 { padding: var(--space-sm); }
        .p-3 { padding: var(--space-md); }
        .p-4 { padding: var(--space-lg); }
        .mt-3 { margin-top: var(--space-md); }
        .mt-4 { margin-top: var(--space-lg); }

        /* Background utilities */
        .bg-primary { background-color: var(--primary); }
        .bg-success { background-color: var(--success); }
        .bg-warning { background-color: var(--warning); }
        .bg-danger { background-color: var(--danger); }

        /* Shape utilities */
        .rounded-circle { border-radius: 50%; }

        /* Media Queries - More concise and focused */
        @media (max-width: 768px) {
            /* Handle mobile columns */
            .col-md-2, .col-md-3, .col-md-4, .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: var(--space-md);
            }

            /* Stack flex elements on mobile */
            .d-flex {
                flex-direction: column;
            }

            /* Give some extra space to card icons on mobile */
            .card-icon {
                margin-top: var(--space-md);
            }
        }

        /* Card Component */
        .card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: var(--space-lg);
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
            border-color: var(--border-light);
        }

        .card-body {
            flex: 1 1 auto;
            padding: var(--space-lg);
        }

        .card-title {
            margin-bottom: 0.75rem;
            font-weight: 500;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-md) var(--space-lg);
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
        }

        /* Stats Card */
        .stats-card .card-body {
            padding: var(--space-md);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
        }

        .stats-card h2 {
            font-size: 2rem;
            font-weight: 700;
        }

        .card-icon {
            width: 3.5rem;
            height: 3.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.5rem;
            box-shadow: var(--shadow);
        }

        /* Button Component */
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: var(--space-sm) var(--space-md);
            font-size: 1rem;
            line-height: 1.5;
            border-radius: var(--radius-md);
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        /* Button ripple effect */
        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: scale(0);
            opacity: 1;
            transition: transform 0.5s, opacity 0.3s;
        }

        .btn:active::after {
            transform: scale(3);
            opacity: 0;
        }

        /* Button sizes */
        .btn-sm {
            padding: var(--space-xs) var(--space-sm);
            font-size: 0.875rem;
        }

        /* Button variants */
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 6px rgba(147, 51, 234, 0.25);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            box-shadow: 0 4px 12px rgba(147, 51, 234, 0.4);
        }

        .btn-secondary {
            background-color: var(--bg-card-hover);
            color: var(--text-primary);
            border-color: var(--border);
        }

        .btn-secondary:hover {
            background-color: var(--border);
            border-color: var(--border-light);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
            border-color: var(--danger);
            box-shadow: 0 4px 6px rgba(239, 68, 68, 0.25);
        }

        .btn-danger:hover {
            background-color: #b91c1c;
            border-color: #b91c1c;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .btn-info {
            background-color: var(--secondary);
            color: var(--bg-dark);
            border-color: var(--secondary);
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(34, 211, 238, 0.25);
        }

        .btn-info:hover {
            background-color: var(--secondary-light);
            border-color: var(--secondary-light);
            box-shadow: 0 4px 12px rgba(34, 211, 238, 0.4);
        }

        /* Badge Component */
        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            border-radius: var(--radius-sm);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* HTTP Method badges */
        .badge-get {
            background-color: rgba(16, 185, 129, 0.2);
            color: var(--get);
            border: 1px solid var(--get);
        }

        .badge-post {
            background-color: rgba(59, 130, 246, 0.2);
            color: var(--post);
            border: 1px solid var(--post);
        }

        .badge-put {
            background-color: rgba(245, 158, 11, 0.2);
            color: var(--put);
            border: 1px solid var(--put);
        }

        .badge-delete {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--delete);
            border: 1px solid var(--delete);
        }

        /* Status badges */
        .badge-success {
            background-color: rgba(34, 197, 94, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .badge-danger {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .badge-warning {
            background-color: rgba(245, 158, 11, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .badge-primary {
            background-color: var(--primary);
            color: white;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: var(--space-md);
        }

        .form-control {
            display: block;
            width: 100%;
            height: calc(1.5em + var(--space-sm) + 2px);
            padding: var(--space-sm) var(--space-md);
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--text-primary);
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            transition: all 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(147, 51, 234, 0.25);
        }

        /* Select with custom dropdown arrow */
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24'%3E%3Cpath fill='none' d='M0 0h24v24H0z'/%3E%3Cpath d='M12 13.172l4.95-4.95 1.414 1.414L12 16 5.636 9.636 7.05 8.222z' fill='rgba(203,213,225,1)'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
        }

        /* Range slider styling */
        .form-control-range {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: var(--bg-card-hover);
            outline: none;
        }

        .form-control-range::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .form-control-range::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .form-control-range::-webkit-slider-thumb:hover {
            background: var(--primary-light);
            box-shadow: 0 0 10px rgba(147, 51, 234, 0.5);
        }

        .form-control-range::-moz-range-thumb:hover {
            background: var(--primary-light);
            box-shadow: 0 0 10px rgba(147, 51, 234, 0.5);
        }

        /* Table Styles */
        .table-container {
            border-radius: var(--radius-lg);
            overflow: hidden;
            background-color: var(--bg-card);
        }

        .table {
            width: 100%;
            margin-bottom: 0;
            color: var(--text-primary);
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: var(--space-md) var(--space-lg);
            vertical-align: middle;
            border-top: 1px solid var(--border);
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid var(--border);
            background-color: rgba(255, 255, 255, 0.05);
            font-weight: 600;
            letter-spacing: 0.025em;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .table thead tr {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .table-hover tbody tr {
            transition: background-color 0.15s ease-in-out;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Table row states */
        .error-row {
            background-color: rgba(239, 68, 68, 0.05);
        }

        /* Truncate text in table */
        .truncate-text {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Pagination for tables */
        .custom-pagination {
            display: flex;
            list-style: none;
            padding: 0;
            gap: 8px;
        }

        .custom-pagination li {
            display: inline-block;
        }

        .custom-pagination li a,
        .custom-pagination li span {
            display: block;
            padding: 8px 12px;
            color: var(--text-primary);
            background-color: var(--bg-card);
            border: 1px solid var(--primary);
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease-in-out;
        }

        .custom-pagination li a:hover {
            background-color: var(--primary);
            color: white;
        }

        .custom-pagination li.active span {
            background-color: var(--primary);
            color: white;
            border-color: white;
        }

        .custom-pagination li.disabled span {
            color: var(--text-muted);
            background-color: var(--bg-card-hover);
            border-color: var(--border);
            cursor: not-allowed;
        }

        /* Data Visualizations */
        .distribution-bars {
            margin-top: var(--space-md);
        }

        .distribution-item {
            margin-bottom: var(--space-md);
        }

        .progress {
            height: 0.75rem;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.5s ease;
        }

        /* Icon System */
        .icon {
            display: inline-block;
            width: 1.5rem;
            height: 1.5rem;
            stroke-width: 0;
            stroke: currentColor;
            fill: currentColor;
            vertical-align: -0.125em;
            font-style: normal;
            position: relative;
        }

        /* SVG icons - consolidated with a common pattern */
        .icon::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-size: contain;
            background-repeat: no-repeat;
        }

        .icon-arrow-left::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='19' y1='12' x2='5' y2='12'%3E%3C/line%3E%3Cpolyline points='12 19 5 12 12 5'%3E%3C/polyline%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        .icon-chart-line::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 3v18h18'/%3E%3Cpath d='M18 9l-6 6-3-3-6 6'/%3E%3Cpath d='M18 9h4v4'/%3E%3C/svg%3E");
        }

        .icon-calendar-day::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3Ccircle cx='12' cy='16' r='2'/%3E%3C/svg%3E");
        }

        .icon-stopwatch::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cpolyline points='12 6 12 12 16 14'/%3E%3C/svg%3E");
        }

        .icon-alert-circle::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cline x1='12' y1='8' x2='12' y2='12'/%3E%3Cline x1='12' y1='16' x2='12.01' y2='16'/%3E%3C/svg%3E");
        }

        .icon-download::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/%3E%3Cpolyline points='7 10 12 15 17 10'/%3E%3Cline x1='12' y1='15' x2='12' y2='3'/%3E%3C/svg%3E");
        }

        .icon-trash::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 6h18'/%3E%3Cpath d='M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2'/%3E%3Cline x1='10' y1='11' x2='10' y2='17'/%3E%3Cline x1='14' y1='11' x2='14' y2='17'/%3E%3C/svg%3E");
        }

        /* Futuristic glow effects */
        .glow {
            box-shadow: 0 0 15px rgba(147, 51, 234, 0.5);
        }

        .glow-cyan {
            box-shadow: 0 0 15px rgba(34, 211, 238, 0.5);
        }

        .glow-text {
            text-shadow: 0 0 10px rgba(147, 51, 234, 0.7);
        }

        /* Spinner and loaders */
        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: var(--space-sm);
        }

        /* Animation keyframes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInUp {
            from {
                transform: translate3d(0, 20px, 0);
                opacity: 0;
            }
            to {
                transform: translate3d(0, 0, 0);
                opacity: 1;
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Animation utility classes */
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        .slide-in-up {
            animation: slideInUp 0.5s ease forwards;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* Code formatting for log details */
        pre {
            margin: 0;
            font-family: 'Fira Code', 'SFMono-Regular', Consolas, monospace;
            font-size: 0.875rem;
            line-height: 1.6;
            tab-size: 2;
            hyphens: none;
            border-radius: var(--radius-sm);
            padding: var(--space-md);
            background-color: #1a1b26;
            color: #c0caf5;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* Syntax highlighting */
        pre .string { color: #9ece6a; }
        pre .number { color: #ff9e64; }
        pre .boolean { color: #bb9af7; }
        pre .null { color: #f7768e; }
        pre .key { color: #7aa2f7; }

        /* Request details display */
        .method-path {
            display: flex;
            align-items: center;
            margin-top: var(--space-md);
            margin-bottom: var(--space-lg);
            padding: var(--space-sm) var(--space-md);
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            overflow-x: auto;
            white-space: nowrap;
            font-family: 'Fira Code', monospace;
        }

        .method-path .badge {
            margin-right: var(--space-md);
            font-size: 0.8rem;
            padding: 0.45em 0.75em;
        }

        .method-path .url-path {
            font-weight: 500;
        }

        /* Status indicator */
        .status-indicator {
            display: flex;
            align-items: center;
            margin-bottom: var(--space-lg);
        }

        .status-indicator .status-code {
            font-weight: 600;
            margin-right: var(--space-sm);
        }

        .status-indicator .status-text {
            color: var(--text-muted);
        }

        .status-indicator .response-time {
            margin-left: auto;
            background-color: rgba(255, 255, 255, 0.05);
            padding: 0.35em 0.65em;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Request details grid */
        .request-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-lg);
        }

        .request-detail-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: var(--space-md);
        }

        .request-detail-card h5 {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: var(--space-sm);
        }

        .request-detail-card p {
            font-size: 1.125rem;
            margin: 0;
            color: var(--text-primary);
        }

        /* Modal Component
        *
        * A complete modal system with backdrop, animations, and responsive design.
        * This includes all needed styles for modal dialogs, headers, footers, and content.
        */

        /* Modal backdrop - the dark overlay behind modals */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .modal-backdrop.show {
            opacity: 1;
            pointer-events: auto;
        }

        /* Modal container */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            outline: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0.9);
            transition: opacity 0.3s ease, transform 0.3s ease;
            pointer-events: none;
        }

        .modal.show {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
        }

        /* Modal dialog - controls width and positioning */
        .modal-dialog {
            position: relative;
            width: auto;
            margin: var(--space-sm);
            pointer-events: none;
            max-width: 500px;
            transition: transform 0.3s ease-out;
        }

        /* Modal size variations */
        .modal-dialog.modal-sm {
            max-width: 300px;
        }

        .modal-dialog.modal-lg {
            max-width: 800px;
            width: 90%;
        }

        .modal-dialog.modal-xl {
            max-width: 1140px;
            width: 95%;
        }

        /* Modal content - the visible part with background */
        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            pointer-events: auto;
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            outline: 0;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        /* Modal header with title and close button */
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-md) var(--space-lg);
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
        }

        .modal-title {
            margin-bottom: 0;
            line-height: 1.5;
            font-weight: 600;
            color: var(--primary-light);
        }

        /* Modal body - main content area with scrolling */
        .modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: var(--space-lg);
            max-height: 80vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--bg-card);
        }

        /* Custom scrollbar for modal body */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: var(--bg-card);
        }

        .modal-body::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 20px;
            border: 2px solid var(--bg-card);
        }

        /* Modal footer with action buttons */
        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: var(--space-md) var(--space-lg);
            border-top: 1px solid var(--border);
            gap: var(--space-sm);
        }

        /* Close button for modals */
        .close {
            float: right;
            font-size: 1.5rem;
            font-weight: 600;
            line-height: 1;
            color: var(--text-muted);
            text-shadow: none;
            opacity: 0.75;
            background: none;
            border: 0;
            cursor: pointer;
            padding: 0;
            margin-left: var(--space-md);
            transition: all 0.15s ease;
        }

        .close:hover {
            color: var(--secondary);
            opacity: 1;
        }

        /* Tabs component inside modals */
        .tabs-container {
            border-bottom: 1px solid var(--border);
            margin-bottom: var(--space-md);
        }

        .tab-buttons {
            display: flex;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: var(--space-sm) var(--space-md);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.15s ease;
            font-weight: 500;
        }

        .tab-button.active {
            border-bottom-color: var(--primary);
            color: var(--primary-light);
        }

        .tab-button:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .tab-content {
            padding: var(--space-md) 0;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            .modal-dialog.modal-lg,
            .modal-dialog.modal-xl {
                width: calc(100% - 1rem);
            }

            .modal-body {
                max-height: 70vh;
                padding: var(--space-md);
            }

            .modal-header,
            .modal-footer {
                padding: var(--space-sm) var(--space-md);
            }

            .tab-buttons {
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Improved Dropdown Styles */
        .dropdown {
            position: relative;
        }

        .dropdown-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 1000;
            display: none;
            min-width: 150px;
            padding: var(--space-sm) 0;
            margin: var(--space-xs) 0 0;
            font-size: 1rem;
            color: var(--text-primary);
            text-align: left;
            list-style: none;
            background-color: var(--bg-card);
            background-clip: padding-box;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow);
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-menu .dropdown-item {
            display: block;
            width: 100%;
            padding: var(--space-sm) var(--space-md);
            clear: both;
            font-weight: 400;
            color: var(--text-primary);
            text-align: inherit;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            transition: background-color 0.2s ease;
        }

        .dropdown-menu .dropdown-item:hover {
            background-color: var(--bg-card-hover);
            color: var(--text-primary);
        }

        /* Action Buttons Container */
        .actions {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .actions .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
                align-items: stretch;
            }

            .actions > * {
                margin-bottom: var(--space-sm);
                width: 100%;
            }

            .dropdown-menu {
                position: static;
                margin-top: 0;
                transform: none;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div id="app">
        <main class="py-4">
            @yield('content')
        </main>
    </div>

    <!-- Minimal JS Dependencies -->
    <script>
        // Shared modal instances that can be used across scripts
        window.modalInstances = {};

        // Improved Dropdown Implementation
        class SimpleDropdown {
            constructor(element) {
                this.element = typeof element === 'string' ? document.querySelector(element) : element;
                if (!this.element) return;

                this.menu = this.element.nextElementSibling;
                if (!this.menu || !this.menu.classList.contains('dropdown-menu')) return;

                this.init();
            }

            init() {
                // Toggle dropdown on click
                this.element.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggle();
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!this.element.contains(e.target) && !this.menu.contains(e.target)) {
                        this.hide();
                    }
                });
            }

            toggle() {
                if (this.menu.classList.contains('show')) {
                    this.hide();
                } else {
                    this.show();
                }
            }

            show() {
                // Close any other open dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(openMenu => {
                    if (openMenu !== this.menu) {
                        openMenu.classList.remove('show');
                    }
                });

                this.menu.classList.add('show');
            }

            hide() {
                this.menu.classList.remove('show');
            }
        }
        // Simple Modal Implementation
        class SimpleModal {
            constructor(element) {
                this.element = typeof element === 'string' ? document.querySelector(element) : element;
                if (!this.element) return;

                this.id = this.element.id;
                this.init();

                // Store in global instances
                window.modalInstances[this.id] = this;
            }

            init() {
                const closeButtons = this.element.querySelectorAll('[data-dismiss="modal"]');
                closeButtons.forEach(button => {
                    button.addEventListener('click', () => this.hide());
                });

                // Close when clicking outside modal content
                this.element.addEventListener('click', (event) => {
                    if (event.target === this.element) {
                        this.hide();
                    }
                });

                // Set up trigger elements that open this modal
                const triggers = document.querySelectorAll(`[data-target="#${this.id}"], [data-toggle="modal"][href="#${this.id}"]`);
                triggers.forEach(trigger => {
                    trigger.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.show();
                    });
                });
            }

            show() {
                document.body.classList.add('modal-open');
                this.element.style.display = 'flex';

                // Small delay to allow display change before adding show class (for animations)
                setTimeout(() => {
                    this.element.classList.add('show');
                }, 10);

                // Set up the modal backdrop if it doesn't exist
                let backdrop = document.querySelector('.modal-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.classList.add('modal-backdrop');
                    document.body.appendChild(backdrop);
                }

                // Small delay for backdrop too
                setTimeout(() => {
                    backdrop.classList.add('show');
                }, 10);

                // Add click handler to backdrop
                backdrop.addEventListener('click', () => {
                    this.hide();
                });
            }

            hide() {
                this.element.classList.remove('show');
                document.body.classList.remove('modal-open');

                // Hide the backdrop
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.classList.remove('show');

                    // Delay removal to allow for animation
                    setTimeout(() => {
                        backdrop.remove();
                    }, 300);
                }

                // Delay hiding the modal to allow for animation
                setTimeout(() => {
                    this.element.style.display = 'none';
                }, 300);
            }
        }

        // Simple datepicker replacement
        class SimpleDatepicker {
            constructor(element, options = {}) {
                this.element = typeof element === 'string' ? document.querySelector(element) : element;
                if (!this.element) return;

                this.options = options;
                this.init();
            }

            init() {
                this.element.type = 'date';
                if (this.options.maxDate === 'today') {
                    const today = new Date().toISOString().split('T')[0];
                    this.element.max = today;
                }

                // For range, we'll just use two inputs
                if (this.options.mode === 'range') {
                    // Create container
                    const container = document.createElement('div');
                    container.style.display = 'flex';
                    container.style.gap = '10px';

                    // Create start date input
                    const startDate = document.createElement('input');
                    startDate.type = 'date';
                    startDate.className = this.element.className;
                    startDate.placeholder = 'Start date';
                    if (this.options.maxDate === 'today') {
                        startDate.max = this.element.max;
                    }

                    // Create end date input
                    const endDate = document.createElement('input');
                    endDate.type = 'date';
                    endDate.className = this.element.className;
                    endDate.placeholder = 'End date';
                    if (this.options.maxDate === 'today') {
                        endDate.max = this.element.max;
                    }

                    // Try to set values from existing input if it's a range
                    if (this.element.value) {
                        const rangeParts = this.element.value.split(' to ');
                        if (rangeParts.length === 2) {
                            startDate.value = rangeParts[0];
                            endDate.value = rangeParts[1];
                        }
                    }

                    // Replace original input with container
                    this.element.parentNode.replaceChild(container, this.element);
                    container.appendChild(startDate);
                    container.appendChild(endDate);

                    // Set up change handlers to update hidden field
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = this.element.name;
                    if (this.element.value) {
                        hiddenInput.value = this.element.value;
                    }
                    container.appendChild(hiddenInput);

                    const updateHidden = () => {
                        if (startDate.value && endDate.value) {
                            hiddenInput.value = `${startDate.value} to ${endDate.value}`;
                        } else if (startDate.value) {
                            hiddenInput.value = startDate.value;
                        } else {
                            hiddenInput.value = '';
                        }
                    };

                    startDate.addEventListener('change', updateHidden);
                    endDate.addEventListener('change', updateHidden);
                }
            }
        }

        // Show alert notification helper
        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());

            // Create new alert
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = message;
            document.body.appendChild(alert);

            // Show the alert
            setTimeout(() => {
                alert.classList.add('show');
            }, 10);

            // Hide after 5 seconds
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        }

        // Helper functions for JSON formatting
        function formatJSON(json) {
            if (typeof json === 'string') {
                try {
                    json = JSON.parse(json);
                } catch (e) {
                    // If it's not valid JSON, just return the string
                    return json;
                }
            }

            // Format the JSON with syntax highlighting
            const formatted = JSON.stringify(json, null, 2);

            // Apply syntax highlighting
            return syntaxHighlight(formatted);
        }

        function syntaxHighlight(json) {
            // Add syntax highlighting classes
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = 'number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'key';
                    } else {
                        cls = 'string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'boolean';
                } else if (/null/.test(match)) {
                    cls = 'null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        }

        // Initialize components when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Create global modalInstances object if it doesn't exist
            window.modalInstances = window.modalInstances || {};

            // Initialize all modals
            document.querySelectorAll('.modal').forEach(modal => {
                new SimpleModal(modal);
            });

            // Initialize all dropdowns
            document.querySelectorAll('.dropdown-toggle').forEach(dropdown => {
                new SimpleDropdown(dropdown);
            });

            // Initialize date pickers
            document.querySelectorAll('.daterange-picker').forEach(element => {
                // Your date picker initialization code here
                // ...
            });

            // Register purge logs functionality
            const confirmPurgeBtn = document.getElementById('confirm-purge-logs');
            if (confirmPurgeBtn) {
                confirmPurgeBtn.addEventListener('click', function() {
                    const days = document.getElementById('days-to-keep').value;
                    purgeOldLogs(days);
                });
            }

            // Days to keep slider
            const daysToKeepSlider = document.getElementById('days-to-keep');
            const daysValue = document.getElementById('days-value');
            if (daysToKeepSlider && daysValue) {
                daysToKeepSlider.addEventListener('input', function() {
                    daysValue.textContent = this.value + ' days';
                });
            }

            // View log details buttons
            document.querySelectorAll('.view-log-details').forEach(button => {
                button.addEventListener('click', function() {
                    const logId = this.getAttribute('data-log-id');
                    if (logId) fetchLogDetails(logId);
                });
            });

            // Reset form button
            const resetButton = document.querySelector('button[type="reset"]');
            if (resetButton) {
                resetButton.addEventListener('click', function() {
                    setTimeout(() => {
                        window.location.href = window.location.pathname;
                    }, 100);
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>

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
            --primary: #9333ea;         /* Purple 600 */
            --primary-dark: #7e22ce;    /* Purple 700 */
            --primary-light: #a855f7;   /* Purple 500 */
            --secondary: #22d3ee;       /* Cyan 400 */
            --secondary-light: #67e8f9; /* Cyan 300 */

            --bg-dark: #0f172a;         /* Slate 900 */
            --bg-card: #1e293b;         /* Slate 800 */
            --bg-card-hover: #334155;   /* Slate 700 */

            --text-primary: #f8fafc;    /* Slate 50 */
            --text-secondary: #cbd5e1;  /* Slate 300 */
            --text-muted: #94a3b8;      /* Slate 400 */

            --border: #334155;          /* Slate 700 */
            --border-light: #475569;    /* Slate 600 */

            --success: #22c55e;         /* Green 500 */
            --danger: #ef4444;          /* Red 500 */
            --warning: #eab308;         /* Yellow 500 */
            --info: #3b82f6;            /* Blue 500 */

            --get: #10b981;             /* Emerald 500 */
            --post: #3b82f6;            /* Blue 500 */
            --put: #f59e0b;             /* Amber 500 */
            --delete: #ef4444;          /* Red 500 */

            --grid-gap: 1.5rem;
            --card-radius: 0.75rem;
            --btn-radius: 0.5rem;
            --badge-radius: 0.375rem;

            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            margin-bottom: 0.5rem;
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
            margin-bottom: 1.5rem;
        }

        h2 { font-size: 1.8rem; }
        h5 { font-size: 1.25rem; }

        .text-muted { color: var(--text-muted); }
        .text-white { color: var(--text-primary); }
        .text-center { text-align: center; }
        .text-gradient {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* Layout */
        .container {
            width: 100%;
            padding-right: 1rem;
            padding-left: 1rem;
            margin-right: auto;
            margin-left: auto;
            max-width: 1280px;
        }

        .container-fluid {
            width: 100%;
            padding-right: 1rem;
            padding-left: 1rem;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: calc(var(--grid-gap) / -2);
            margin-left: calc(var(--grid-gap) / -2);
        }

        .col-12 { flex: 0 0 100%; max-width: 100%; padding: 0 calc(var(--grid-gap) / 2); }
        .col-md-3 { flex: 0 0 25%; max-width: 25%; padding: 0 calc(var(--grid-gap) / 2); }
        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; padding: 0 calc(var(--grid-gap) / 2); }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; padding: 0 calc(var(--grid-gap) / 2); }
        .col-md-2 { flex: 0 0 16.666667%; max-width: 16.666667%; padding: 0 calc(var(--grid-gap) / 2); }

        /* Margin & Padding */
        .py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
        .mb-0 { margin-bottom: 0; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mr-2 { margin-right: 0.5rem; }
        .mr-1 { margin-right: 0.25rem; }
        .mr-3 { margin-right: 1rem; }
        .p-3 { padding: 1rem; }
        .p-4 { padding: 1.5rem; }
        .p-6 { padding: 2rem; }
        .mt-4 { margin-top: 1.5rem; }

        /* Cards */
        .card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: var(--bg-card);
            background-clip: border-box;
            border: 1px solid var(--border);
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
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
            padding: 1.5rem;
        }

        .card-title {
            margin-bottom: 0.75rem;
            font-weight: 500;
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

        .stats-card .card-body {
            padding: 1.25rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
        }

        .stats-card h2 {
            font-size: 2rem;
            font-weight: 700;
        }

        /* Form Elements */
        .form-control {
            display: block;
            width: 100%;
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.5rem 1rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--text-primary);
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--btn-radius);
            transition: all 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(147, 51, 234, 0.25);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24'%3E%3Cpath fill='none' d='M0 0h24v24H0z'/%3E%3Cpath d='M12 13.172l4.95-4.95 1.414 1.414L12 16 5.636 9.636 7.05 8.222z' fill='rgba(203,213,225,1)'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
        }

        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: var(--btn-radius);
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

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

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: var(--btn-radius);
        }

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

        /* Tables */
        .table-container {
            border-radius: var(--card-radius);
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
            padding: 1rem 1.5rem;
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

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            border-radius: var(--badge-radius);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

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

        .badge-primary {
            background-color: var(--primary);
            color: white;
        }

        .badge-warning {
            background-color: rgba(245, 158, 11, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        /* Utility Classes */
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-center { justify-content: center; }
        .align-items-center { align-items: center; }

        .bg-primary { background-color: var(--primary); }
        .bg-success { background-color: var(--success); }
        .bg-warning { background-color: var(--warning); }
        .bg-danger { background-color: var(--danger); }

        .rounded-circle { border-radius: 50%; }

        /* Modal */
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

        .modal-dialog {
            position: relative;
            width: auto;
            margin: 0.5rem;
            pointer-events: none;
            max-width: 500px;
            transition: transform 0.3s ease-out;
        }

        .modal-lg {
            max-width: 900px;
            width: 90%;
        }

        .modal-xl {
            max-width: 1140px;
            width: 95%;
        }

        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            pointer-events: auto;
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--card-radius);
            outline: 0;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
        }

        .modal-title {
            margin-bottom: 0;
            line-height: 1.5;
            font-weight: 600;
            color: var(--primary-light);
        }

        .modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: 1.5rem;
            max-height: 80vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--bg-card);
        }

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

        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--border);
            gap: 0.5rem;
        }

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
            margin-left: 1rem;
            transition: all 0.15s ease;
        }

        .close:hover {
            color: var(--secondary);
            opacity: 1;
        }

        /* JSON Viewer */
        .json-viewer {
            padding: 0;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        .json-viewer .json-sections {
            display: flex;
            border-bottom: 1px solid var(--border);
        }

        .json-viewer .json-section {
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.15s ease;
        }

        .json-viewer .json-section.active {
            border-bottom-color: var(--primary);
            color: var(--primary-light);
        }

        .json-viewer .json-section:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .json-viewer .json-content {
            padding: 1.5rem;
        }

        .json-viewer .json-headers,
        .json-viewer .json-body,
        .json-viewer .json-response {
            display: none;
        }

        .json-viewer .json-headers.active,
        .json-viewer .json-body.active,
        .json-viewer .json-response.active {
            display: block;
        }

        /* Code formatting */
        pre {
            margin: 0;
            font-family: 'Fira Code', 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 0.875rem;
            line-height: 1.6;
            tab-size: 2;
            hyphens: none;
            border-radius: 0.375rem;
            padding: 1.25rem;
            background-color: #1a1b26;
            color: #c0caf5;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        pre .string { color: #9ece6a; }
        pre .number { color: #ff9e64; }
        pre .boolean { color: #bb9af7; }
        pre .null { color: #f7768e; }
        pre .key { color: #7aa2f7; }

        /* Method path display */
        .method-path {
            display: flex;
            align-items: center;
            margin-top: 1rem;
            margin-bottom: 1.5rem;
            padding: 0.75rem 1.25rem;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: var(--badge-radius);
            border: 1px solid var(--border);
            overflow-x: auto;
            white-space: nowrap;
            font-family: 'Fira Code', monospace;
        }

        .method-path .badge {
            margin-right: 1rem;
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
            margin-bottom: 1.5rem;
        }

        .status-indicator .status-code {
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .status-indicator .status-text {
            color: var(--text-muted);
        }

        .status-indicator .response-time {
            margin-left: auto;
            background-color: rgba(255, 255, 255, 0.05);
            padding: 0.35em 0.65em;
            border-radius: var(--badge-radius);
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Request details grid */
        .request-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .request-detail-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: var(--card-radius);
            border: 1px solid var(--border);
            padding: 1.25rem;
        }

        .request-detail-card h5 {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .request-detail-card p {
            font-size: 1.125rem;
            margin: 0;
            color: var(--text-primary);
        }

        /* Tabs */
        .tabs-container {
            border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }

        .tab-buttons {
            display: flex;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 0.75rem 1.25rem;
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
            padding: 1rem 0;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* Alert */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 0.75rem 1.25rem;
            border-radius: 0.25rem;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            max-width: 350px;
        }

        .alert.show {
            opacity: 1;
            transform: translateY(0);
        }

        .alert-success {
            color: #d1fae5;
            background-color: rgba(16, 185, 129, 0.9);
            border: 1px solid #10b981;
        }

        .alert-danger {
            color: #fee2e2;
            background-color: rgba(239, 68, 68, 0.9);
            border: 1px solid #ef4444;
        }

        /* Icons */
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

        .icon-chart-line::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 3v18h18'/%3E%3Cpath d='M18 9l-6 6-3-3-6 6'/%3E%3Cpath d='M18 9h4v4'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        .icon-calendar-day::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3Ccircle cx='12' cy='16' r='2'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        .icon-stopwatch::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cpolyline points='12 6 12 12 16 14'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        .icon-alert-circle::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cline x1='12' y1='8' x2='12' y2='12'/%3E%3Cline x1='12' y1='16' x2='12.01' y2='16'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        .icon-download::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/%3E%3Cpolyline points='7 10 12 15 17 10'/%3E%3Cline x1='12' y1='15' x2='12' y2='3'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        .icon-trash::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 6h18'/%3E%3Cpath d='M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2'/%3E%3Cline x1='10' y1='11' x2='10' y2='17'/%3E%3Cline x1='14' y1='11' x2='14' y2='17'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
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

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        .slide-in-up {
            animation: slideInUp 0.5s ease forwards;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* Dropdown */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle::after {
            display: inline-block;
            margin-left: 0.255em;
            vertical-align: 0.255em;
            content: "";
            border-top: 0.3em solid;
            border-right: 0.3em solid transparent;
            border-bottom: 0;
            border-left: 0.3em solid transparent;
        }

        .dropdown-menu {
            position: absolute;
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--card-radius);
            padding: 0.5rem 0;
            margin: 0.125rem 0 0;
            font-size: 1rem;
            text-align: left;
            min-width: 10rem;
            z-index: 1000;
            box-shadow: var(--shadow);
            display: none;
            right: 0;
        }

        .dropdown-menu-right {
            right: 0;
            left: auto;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.25rem 1.5rem;
            clear: both;
            font-weight: 400;
            color: var(--text-primary);
            text-align: inherit;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            text-decoration: none;
        }

        .dropdown-item:hover, .dropdown-item:focus {
            color: var(--text-primary);
            text-decoration: none;
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 1rem;
        }

        /* Spinner */
        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 768px) {
            .col-md-3, .col-md-4, .col-md-6, .col-md-2 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 1rem;
            }

            .d-flex {
                flex-direction: column;
            }

            .card-icon {
                margin-top: 1rem;
            }

            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            .modal-lg, .modal-xl {
                width: calc(100% - 1rem);
            }

            .table th,
            .table td {
                padding: 0.75rem;
            }

            .json-viewer .json-sections {
                flex-wrap: wrap;
            }

            .request-details-grid {
                grid-template-columns: 1fr;
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

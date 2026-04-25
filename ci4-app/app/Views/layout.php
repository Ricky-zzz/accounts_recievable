<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'AR Admin') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #FAF9F6;
            --header-bg: #F5F5DC;
            --surface: #ffffff;
            --surface-soft: #f6f7f9;
            --ink: #151515;
            --muted: #5c6168;
            --line: #d5dae1;
            --line-strong: #a8b0bc;
        }

        body {
            font-family: 'IBM Plex Sans', 'Segoe UI', sans-serif;
            background-color: var(--bg);
            color: var(--ink);
        }

        .site-header {
            background: var(--header-bg);
            border-bottom: 1px solid var(--line);
        }

        .brand-lockup {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            color: inherit;
            text-decoration: none;
        }

        .brand-mark {
            width: 2.75rem;
            height: 2.75rem;
            object-fit: cover;
            border-radius: 0.75rem;
            border: 1px solid var(--line);
            background: var(--surface);
        }

        .brand-copy {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .brand-title {
            font-size: 1rem;
            font-weight: 600;
        }

        .brand-subtitle {
            font-size: 0.72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;

            border: 1px solid var(--line-strong);
            background: var(--surface);

            padding: 0.35rem 0.7rem;
            font-weight: 500;
            font-size: 0.85rem;

            border-radius: 0.25rem;

            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .btn:hover {
            background: var(--surface-soft);
        }

        .btn-secondary {
            border-color: var(--line);
            font-weight: 500;
            color: var(--muted);
        }

        .btn-link {
            color: var(--muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .btn-link:hover {
            color: var(--ink);
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .input {
            width: 100%;
            border: 1px solid var(--line);
            background: var(--surface);

            padding: 0.35rem 0.7rem;
            /* match button */
            font-size: 0.85rem;

            border-radius: 0.25rem;
            /* match button sharpness */

            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .input:focus {
            outline: none;
            border-color: var(--ink);
            box-shadow: 0 0 0 1px var(--ink);
        }

        .btn:focus {
            outline: 2px solid var(--ink);
            outline-offset: 2px;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.875rem;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 0;
            overflow: hidden;
        }

        .table th,
        .table td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }

        .table thead th {
            background: var(--surface-soft);
            border-bottom: 1px solid var(--line);
            font-weight: 600;
            text-align: left;
            white-space: nowrap;
        }

        .table tbody tr+tr td {
            border-top: 1px solid var(--line);
        }

        .table tbody tr:hover td {
            background: #f9fafb;
        }

        .muted {
            color: var(--muted);
        }

        .nav-link {
            color: var(--ink);
            /* make all links black */
            text-decoration: none;
            padding: 0.35rem 0.6rem;
            border-radius: 0.55rem;
            font-weight: 400;
            /* normal */
            transition: background 0.15s ease, color 0.15s ease;
        }

        .nav-link-active {
            font-weight: 600;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--line);
        }

        .nav-link:hover {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.7);
        }

        .nav-link:focus-visible {
            outline: 2px solid var(--ink);
            outline-offset: 1px;
        }

        .nav-group {
            position: relative;
        }

        .nav-group summary {
            cursor: pointer;
            list-style: none;
        }

        .nav-group summary::-webkit-details-marker {
            display: none;
        }

        .nav-menu {
            position: absolute;
            top: calc(100% + 0.35rem);
            right: 0;
            z-index: 50;
            min-width: 11rem;
            border: 1px solid var(--line);
            border-radius: 0.55rem;
            background: var(--surface);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
            padding: 0.35rem;
        }

        .nav-menu .nav-link {
            display: block;
            width: 100%;
        }

        .account-summary {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .account-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border: 1px solid var(--line-strong);
            border-radius: 9999px;
            background: var(--surface);
            color: var(--ink);
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .account-name {
            max-width: 9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tab-link {
            display: inline-flex;
            align-items: center;
            border-bottom: 2px solid transparent;
            padding: 0.7rem 1rem;
            color: var(--muted);
            font-weight: 500;
            transition: color 0.15s ease, border-color 0.15s ease;
        }

        .tab-link:hover {
            color: var(--ink);
        }

        .tab-link-active {
            border-bottom-color: var(--ink);
            color: var(--ink);
            font-weight: 600;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--line);
            background: var(--surface-soft);
            color: var(--ink);
            border-radius: 9999px;
            padding: 0.2rem 0.55rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .field-error {
            margin-top: 0.3rem;
            display: block;
            font-size: 0.75rem;
            color: #343a40;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(12, 14, 18, 0.4);
            padding: 1rem;
        }

        .modal-panel {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 0;
            background: var(--surface);
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
        }

        .flash-message {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            border: 1px solid;
            border-left: 4px solid;
            border-radius: 0.75rem;
            padding: 0.7rem 0.9rem;
            font-size: 0.875rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.16);
            pointer-events: auto;
        }

        .flash-message-success {
            border-color: #86efac;
            border-left-color: #16a34a;
            background: #f0fdf4;
            color: #14532d;
        }

        .flash-message-error {
            border-color: #fca5a5;
            border-left-color: #dc2626;
            background: #fef2f2;
            color: #7f1d1d;
        }

        .flash-stack {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 70;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: min(92vw, 24rem);
            pointer-events: none;
        }

        .flash-close {
            border: 0;
            border-radius: 0.45rem;
            background: transparent;
            color: inherit;
            font-size: 1rem;
            line-height: 1;
            padding: 0.15rem 0.4rem;
            cursor: pointer;
        }

        .flash-close:hover {
            background: rgba(0, 0, 0, 0.08);
        }

        .flash-text {
            flex: 1;
        }

        .pagination {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
            margin: 1rem 0 0;
            padding: 0;
            list-style: none;
        }

        .pagination li a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            height: 2rem;
            padding: 0 0.6rem;
            border: 1px solid var(--line);
            border-radius: 0.45rem;
            background: var(--surface);
            color: var(--ink);
            text-decoration: none;
            font-size: 0.825rem;
            font-weight: 500;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .pagination li a:hover {
            background: var(--surface-soft);
            border-color: var(--line-strong);
        }

        .pagination li.active a {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
            pointer-events: none;
        }

        .pagination li a:focus-visible {
            outline: 2px solid var(--ink);
            outline-offset: 1px;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="antialiased">
    <?php
    helper('permissions');

    $successMessage = session()->getFlashdata('success');
    $errorMessage = session()->getFlashdata('error');

    $currentPath = trim((string) uri_string(), '/');
    if (str_starts_with($currentPath, 'index.php/')) {
        $currentPath = substr($currentPath, 10);
    }

    $isNavActive = static function (string $segment) use ($currentPath): bool {
        return $currentPath === $segment || str_starts_with($currentPath, $segment . '/');
    };

    $navAttributes = static function (string $segment) use ($isNavActive): string {
        if (! $isNavActive($segment)) {
            return 'class="nav-link"';
        }

        return 'class="nav-link nav-link-active" aria-current="page"';
    };

    $reportsActive = $isNavActive('deliveries')
        || $isNavActive('payments')
        || $isNavActive('reports/credits')
        || $isNavActive('reports/overdue');

    $accountName = trim((string) (session()->get('name') ?: session()->get('username') ?: 'Account'));
    $accountInitial = strtoupper(substr($accountName, 0, 1)) ?: 'A';
    $logoUrl = base_url('logo.png');
    ?>

    <?php if ($successMessage || $errorMessage): ?>
        <div class="flash-stack">
            <?php if ($successMessage): ?>
                <div x-data="{ open: true }" x-show="open" x-transition.opacity.duration.200ms class="flash-message flash-message-success" x-cloak>
                    <div class="flash-text">
                        <?= esc($successMessage) ?>
                    </div>
                    <button class="flash-close" type="button" aria-label="Dismiss success message" @click="open = false">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div x-data="{ open: true }" x-show="open" x-transition.opacity.duration.200ms class="flash-message flash-message-error" x-cloak>
                    <div class="flash-text">
                        <?= esc($errorMessage) ?>
                    </div>
                    <button class="flash-close" type="button" aria-label="Dismiss error message" @click="open = false">&times;</button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="min-h-screen">
        <header class="site-header">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <div>
                    <a href="<?= base_url('/') ?>" class="brand-lockup">
                        <img class="brand-mark" src="<?= esc($logoUrl) ?>" alt="SRC Enterprises logo">
                        <span class="brand-copy">
                            <span class="brand-title">Accounts Recievable</span>
                            <span class="brand-subtitle">SRC Enterprises Inc</span>
                        </span>
                    </a>
                </div>
                <?php if (session()->get('user_id')): ?>
                    <nav class="flex flex-wrap items-center gap-1 text-sm text-black">
                        <a href="<?= base_url('clients') ?>" <?= $navAttributes('clients') ?>>Clients</a>
                        <a href="<?= base_url('products') ?>" <?= $navAttributes('products') ?>>Products</a>
                        <?php if (can_access('banks.view')): ?>
                            <a href="<?= base_url('banks') ?>" <?= $navAttributes('banks') ?>>Banks</a>
                        <?php endif; ?>
                        <?php if (can_access('cashiers.view')): ?>
                            <a href="<?= base_url('cashiers') ?>" <?= $navAttributes('cashiers') ?>>Cashiers</a>
                        <?php endif; ?>
                        <details class="nav-group">
                            <summary class="<?= $reportsActive ? 'nav-link nav-link-active' : 'nav-link' ?>" <?= $reportsActive ? 'aria-current="page"' : '' ?>>Reports</summary>
                            <div class="nav-menu">
                                <a href="<?= base_url('deliveries') ?>" <?= $navAttributes('deliveries') ?>>Deliveries</a>
                                <a href="<?= base_url('payments') ?>" <?= $navAttributes('payments') ?>>Collections</a>
                                <a href="<?= base_url('reports/credits') ?>" <?= $navAttributes('reports/credits') ?>>Credits</a>
                                <a href="<?= base_url('reports/overdue') ?>" <?= $navAttributes('reports/overdue') ?>>Overdue</a>
                            </div>
                        </details>
                        <a href="<?= base_url('boa') ?>" <?= $navAttributes('boa') ?>>BOA</a>
                        <details class="nav-group">
                            <summary class="nav-link account-summary">
                                <span class="account-icon" aria-hidden="true"><?= esc($accountInitial) ?></span>
                                <span class="account-name"><?= esc($accountName) ?></span>
                            </summary>
                            <div class="nav-menu">
                                <a href="<?= base_url('logout') ?>" class="nav-link">Logout</a>
                            </div>
                        </details>
                    </nav>
                <?php endif; ?>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-6 py-6">
            <?= $this->renderSection('content') ?>
        </main>
    </div>
</body>

</html>

<!doctype html>
<?php
/**
 * @var string|null $title
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Payables Admin') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <script defer src="<?= base_url('assets/js/alpine.min.js') ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --header-bg: #e6f0ed;
            --surface: #fff;
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
            gap: .8rem;
            color: inherit;
            text-decoration: none;
        }

        .brand-mark {
            width: 2.75rem;
            height: 2.75rem;
            object-fit: cover;
            border-radius: .75rem;
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
            font-size: .72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: .75rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .04);
        }

        .filter-card {
            background: var(--surface);
            box-shadow: 0 8px 20px rgba(15, 23, 42, .03);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .25rem;
            border: 1px solid var(--line-strong);
            background: var(--surface);
            color: var(--ink);
            padding: .35rem .7rem;
            font-weight: 600;
            font-size: .85rem;
            border-radius: .25rem;
            transition: background .15s ease, border-color .15s ease, color .15s ease;
        }

        .btn:hover {
            background: var(--surface-soft);
        }

        .btn:disabled {
            cursor: not-allowed;
            opacity: .5;
        }

        .btn-secondary {
            border-color: var(--line);
            font-weight: 500;
            color: #3f444a;
        }

        .btn-strong {
            color: var(--ink);
            font-size: .92rem;
            font-weight: 900;
        }

        .total-highlight {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .btn-link {
            color: var(--muted);
            text-decoration: none;
            transition: color .15s ease;
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
            padding: .35rem .7rem;
            font-size: .85rem;
            border-radius: .25rem;
        }

        .input:focus,
        .btn:focus {
            outline: 2px solid var(--ink);
            outline-offset: 2px;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .875rem;
            background: var(--surface);
            border: 1px solid var(--line);
            overflow: hidden;
        }

        .modal-split {
            display: grid;
            gap: 1.5rem;
        }

        .modal-split > div {
            min-width: 0;
            overflow-x: auto;
        }

        @media (min-width: 1024px) {
            .modal-split {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            }
        }

        .table th,
        .table td {
            padding: .75rem 1rem;
            vertical-align: middle;
        }

        .table thead th {
            background: var(--surface-soft);
            border-bottom: 1px solid var(--line);
            font-weight: 600;
            text-align: left;
            white-space: nowrap;
        }

        .table thead th.text-right {
            text-align: right;
        }

        .table thead th.text-center {
            text-align: center;
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
            text-decoration: none;
            padding: .35rem .6rem;
            border-radius: .55rem;
            font-weight: 400;
            transition: background .15s ease, color .15s ease;
        }

        .nav-link-active {
            font-weight: 600;
            background: rgba(255, 255, 255, .94);
            border: 1px solid var(--line);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, .7);
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
            top: calc(100% + .35rem);
            right: 0;
            z-index: 50;
            min-width: 12rem;
            border: 1px solid var(--line);
            border-radius: .55rem;
            background: var(--surface);
            box-shadow: 0 16px 32px rgba(15, 23, 42, .12);
            padding: .35rem;
        }

        .nav-menu .nav-link {
            display: block;
            width: 100%;
        }

        .account-summary {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
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
            font-size: .72rem;
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
            padding: .7rem 1rem;
            color: var(--muted);
            font-weight: 500;
        }

        .tab-link-active {
            border-bottom-color: var(--ink);
            color: var(--ink);
            font-weight: 600;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(12, 14, 18, .4);
            padding: 1rem;
        }

        .modal-panel {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 0;
            background: var(--surface);
            box-shadow: 0 24px 48px rgba(15, 23, 42, .18);
        }

        .flash-stack {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 70;
            display: flex;
            flex-direction: column;
            gap: .75rem;
            width: min(92vw, 24rem);
            pointer-events: none;
        }

        .flash-message {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            border: 1px solid;
            border-left: 4px solid;
            border-radius: .75rem;
            padding: .7rem .9rem;
            font-size: .875rem;
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

        .flash-close {
            border: 0;
            border-radius: .45rem;
            background: transparent;
            color: inherit;
            font-size: 1rem;
            line-height: 1;
            padding: .15rem .4rem;
            cursor: pointer;
        }

        .pagination {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .4rem;
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
            padding: 0 .6rem;
            border: 1px solid var(--line);
            border-radius: .45rem;
            background: var(--surface);
            color: var(--ink);
            text-decoration: none;
            font-size: .825rem;
            font-weight: 500;
        }

        .pagination li.active a {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
            pointer-events: none;
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
    $isNavActive = static fn(string $segment): bool => $currentPath === $segment || str_starts_with($currentPath, $segment . '/');
    $navAttributes = static fn(string $segment): string => $isNavActive($segment) ? 'class="nav-link nav-link-active" aria-current="page"' : 'class="nav-link"';
    $reportsActive = $isNavActive('supplier-orders') || $isNavActive('purchase-orders') || $isNavActive('payables') || $isNavActive('payable-reports');
    $accountName = trim((string) (session()->get('name') ?: session()->get('username') ?: 'Account'));
    $accountInitial = strtoupper(substr($accountName, 0, 1)) ?: 'A';
    ?>

    <div id="flash-stack" class="flash-stack">
        <?php if ($successMessage): ?>
            <div x-data="{ open: true }" x-show="open" x-transition.opacity.duration.200ms class="flash-message flash-message-success" x-cloak>
                <div class="flash-text"><?= esc($successMessage) ?></div>
                <button class="flash-close" type="button" aria-label="Dismiss success message" @click="open = false">&times;</button>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div x-data="{ open: true }" x-show="open" x-transition.opacity.duration.200ms class="flash-message flash-message-error" x-cloak>
                <div class="flash-text"><?= esc($errorMessage) ?></div>
                <button class="flash-close" type="button" aria-label="Dismiss error message" @click="open = false">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <div class="min-h-screen">
        <header class="site-header">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <a href="<?= base_url('payables-dashboard') ?>" class="brand-lockup">
                    <img class="brand-mark" src="<?= esc(base_url('logo.png')) ?>" alt="SRC Enterprises logo">
                    <span class="brand-copy">
                        <span class="brand-title">Accounts Payable</span>
                        <span class="brand-subtitle">SRC Enterprises Inc</span>
                    </span>
                </a>
                <?php if (session()->get('user_id')): ?>
                    <nav class="flex flex-wrap items-center gap-1 text-sm text-black">
                        <a href="<?= base_url('/') ?>">Recievables</a>
                        <?php if (can_access('suppliers.view')): ?>
                            <a href="<?= base_url('suppliers') ?>" <?= $navAttributes('suppliers') ?>>Suppliers</a>
                        <?php endif; ?>
                        <?php if (can_access('products.view')): ?>
                            <a href="<?= base_url('payables/products') ?>" <?= $navAttributes('payables/products') ?>>Products</a>
                        <?php endif; ?>
                        <?php if (can_access('banks.view')): ?>
                            <a href="<?= base_url('payables/banks') ?>" <?= $navAttributes('payables/banks') ?>>Banks</a>
                        <?php endif; ?>
                        <details class="nav-group">
                            <summary class="<?= $reportsActive ? 'nav-link nav-link-active' : 'nav-link' ?>" <?= $reportsActive ? 'aria-current="page"' : '' ?>>Reports</summary>
                            <div class="nav-menu">
                                <?php if (can_access('purchase_orders.view')): ?>
                                    <a href="<?= base_url('supplier-orders') ?>" <?= $navAttributes('supplier-orders') ?>>Purchase Orders</a>
                                    <a href="<?= base_url('purchase-orders') ?>" <?= $navAttributes('purchase-orders') ?>>Pickups</a>
                                <?php endif; ?>
                                <?php if (can_access('payables.view')): ?>
                                    <a href="<?= base_url('payables') ?>" <?= $navAttributes('payables') ?>>CV / Payments</a>
                                <?php endif; ?>
                                <?php if (can_access('payable_reports.credits.view')): ?>
                                    <a href="<?= base_url('payable-reports/credits') ?>" <?= $navAttributes('payable-reports/credits') ?>>Credits</a>
                                <?php endif; ?>
                                <?php if (can_access('payable_reports.overdue.view')): ?>
                                    <a href="<?= base_url('payable-reports/overdue') ?>" <?= $navAttributes('payable-reports/overdue') ?>>Overdue</a>
                                <?php endif; ?>
                                <?php if (can_access('payable_reports.voided.view')): ?>
                                    <a href="<?= base_url('payable-reports/voided') ?>" <?= $navAttributes('payable-reports/voided') ?>>Voided Pickups</a>
                                    <a href="<?= base_url('payable-reports/voided-pos') ?>" <?= $navAttributes('payable-reports/voided-pos') ?>>Voided POs</a>
                                <?php endif; ?>
                            </div>
                        </details>
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
    <script>
        window.showToast = function(message, type = 'error') {
            const stack = document.getElementById('flash-stack');
            if (!stack || !message) return;
            const toast = document.createElement('div');
            const variant = type === 'success' ? 'success' : 'error';
            toast.className = `flash-message flash-message-${variant}`;
            const text = document.createElement('div');
            text.className = 'flash-text';
            text.textContent = message;
            const close = document.createElement('button');
            close.className = 'flash-close';
            close.type = 'button';
            close.setAttribute('aria-label', `Dismiss ${variant} message`);
            close.innerHTML = '&times;';
            close.addEventListener('click', () => toast.remove());
            toast.append(text, close);
            stack.appendChild(toast);
            window.setTimeout(() => toast.remove(), 5000);
        };
    </script>
</body>

</html>

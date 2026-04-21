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
            --bg: #f7f7f7;
            --surface: #ffffff;
            --ink: #111111;
            --muted: #555555;
            --line: #d1d5db;
            --line-strong: #111111;
        }

        body {
            font-family: 'IBM Plex Sans', 'Segoe UI', sans-serif;
            background-color: var(--bg);
            background-image: linear-gradient(#eeeeee 1px, transparent 1px),
                linear-gradient(90deg, #eeeeee 1px, transparent 1px);
            background-size: 48px 48px;
            color: var(--ink);
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.04);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            border: 1px solid var(--ink);
            background: var(--surface);
            padding: 0.5rem 1rem;
            font-weight: 600;
        }

        .btn-secondary {
            border-color: var(--line);
            font-weight: 500;
        }

        .btn-link {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .input {
            width: 100%;
            border: 1px solid var(--line);
            background: var(--surface);
            padding: 0.5rem 0.75rem;
        }

        .input:focus,
        .btn:focus {
            outline: 2px solid var(--line-strong);
            outline-offset: 2px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .table thead tr {
            border-bottom: 1px solid var(--line-strong);
        }

        .table tbody tr {
            border-bottom: 1px solid var(--line);
        }

        .table th {
            text-align: left;
            font-weight: 600;
            padding: 0.65rem 0;
        }

        .table td {
            padding: 0.55rem 0;
        }

        .muted {
            color: var(--muted);
        }

        .nav-link {
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="antialiased">
    <div class="min-h-screen">
        <header class="border-b border-gray-300">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <div class="text-lg font-semibold">AR Admin</div>
                <?php if (session()->get('user_id')): ?>
                    <nav class="flex items-center gap-4 text-sm">
                        <a href="<?= base_url('clients') ?>" class="nav-link">Clients</a>
                        <a href="<?= base_url('products') ?>" class="nav-link">Products</a>
                        <a href="<?= base_url('banks') ?>" class="nav-link">Banks</a>
                        <a href="<?= base_url('cashiers') ?>" class="nav-link">Cashiers</a>
                        <a href="<?= base_url('deliveries') ?>" class="nav-link">Deliveries</a>
                        <a href="<?= base_url('payments') ?>" class="nav-link">Payments</a>
                        <a href="<?= base_url('boa') ?>" class="nav-link">BOA</a>
                        <a href="<?= base_url('excess') ?>" class="nav-link">Excess</a>
                        <a href="<?= base_url('logout') ?>" class="nav-link">Logout</a>
                    </nav>
                <?php endif; ?>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-6 py-6">
            <?php if ($message = session()->getFlashdata('success')): ?>
                <div class="card mb-4 px-4 py-2 text-sm">
                    <?= esc($message) ?>
                </div>
            <?php endif; ?>
            <?php if ($message = session()->getFlashdata('error')): ?>
                <div class="card mb-4 px-4 py-2 text-sm">
                    <?= esc($message) ?>
                </div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>
</body>

</html>
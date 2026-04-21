<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<h1 class="text-xl font-semibold">Void Delivery</h1>
<p class="mt-1 text-sm muted">This action cannot be undone.</p>

<form class="mt-6 max-w-xl space-y-4" method="post" action="<?= base_url('deliveries/' . $delivery['id'] . '/void') ?>">
    <?= csrf_field() ?>
    <div class="card p-4 text-sm">
        <div class="flex justify-between">
            <span>DR#</span>
            <span><?= esc($delivery['dr_no']) ?></span>
        </div>
        <div class="mt-2 flex justify-between">
            <span>Date</span>
            <span><?= esc($delivery['date']) ?></span>
        </div>
        <div class="mt-2 flex justify-between">
            <span>Total</span>
            <span><?= esc(number_format((float) $delivery['total_amount'], 2)) ?></span>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium" for="void_reason">Reason</label>
        <textarea class="input mt-1" id="void_reason" name="void_reason" rows="3" required><?= esc(old('void_reason')) ?></textarea>
    </div>
    <div class="flex gap-3">
        <button class="btn" type="submit">Void Delivery</button>
        <a class="btn btn-secondary" href="<?= base_url('deliveries') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
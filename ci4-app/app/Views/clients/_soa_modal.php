<?php
$defaultSoaStart = $defaultSoaStart ?? date('Y-m-01');
$defaultSoaEnd = $defaultSoaEnd ?? date('Y-m-t');
?>

<div class="modal-backdrop" x-show="openSoa" x-cloak @click.self="closeSoaModal()">
    <div class="modal-panel max-w-md p-6" @click.stop>
        <div class="flex items-start justify-between gap-4">
            <h2 class="text-lg font-semibold" x-text="`SOA for ${soaClientName}`"></h2>
            <button class="btn btn-secondary" type="button" @click="closeSoaModal()">Close</button>
        </div>

        <div class="mt-4 space-y-3 text-sm">
            <div>
                <label class="block text-sm font-medium" for="soa_start">Billing From</label>
                <input class="input mt-1" id="soa_start" type="date" x-model="soaStart">
            </div>
            <div>
                <label class="block text-sm font-medium" for="soa_end">Billing To</label>
                <input class="input mt-1" id="soa_end" type="date" x-model="soaEnd" @change="recomputeSoaDueDate()">
            </div>
            <div>
                <label class="block text-sm font-medium" for="soa_due">Due Date</label>
                <input class="input mt-1" id="soa_due" type="date" x-model="soaDueDate">
            </div>
        </div>

        <div class="mt-5 flex gap-3">
            <button class="btn btn-strong" type="button" @click="openSoaPrint()">Print</button>
            <button class="btn btn-secondary" type="button" @click="closeSoaModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
    function soaModalState() {
        const baseUrl = '<?= rtrim(base_url(), '/') ?>';
        const defaultSoaStart = '<?= esc((string) $defaultSoaStart, 'js') ?>';
        const defaultSoaEnd = '<?= esc((string) $defaultSoaEnd, 'js') ?>';

        return {
            openSoa: false,
            soaClientId: null,
            soaClientName: '',
            soaClientTerm: '',
            soaStart: defaultSoaStart,
            soaEnd: defaultSoaEnd,
            soaDueDate: '',
            openSoaModal(id, name, paymentTerm = '') {
                this.soaClientId = id;
                this.soaClientName = name || '';
                this.soaClientTerm = paymentTerm === null || paymentTerm === undefined ? '' : String(paymentTerm);
                this.soaStart = defaultSoaStart;
                this.soaEnd = defaultSoaEnd;
                this.recomputeSoaDueDate();
                this.openSoa = true;
            },
            closeSoaModal() {
                this.openSoa = false;
            },
            recomputeSoaDueDate() {
                if (!this.soaEnd) {
                    this.soaDueDate = '';
                    return;
                }

                const term = parseInt(this.soaClientTerm || '0', 10);
                const days = Number.isNaN(term) ? 0 : term;
                const date = new Date(`${this.soaEnd}T00:00:00`);

                if (Number.isNaN(date.getTime())) {
                    this.soaDueDate = '';
                    return;
                }

                date.setDate(date.getDate() + days);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                this.soaDueDate = `${year}-${month}-${day}`;
            },
            openSoaPrint() {
                if (!this.soaClientId) {
                    return;
                }

                const params = new URLSearchParams({
                    start: this.soaStart || '',
                    end: this.soaEnd || '',
                    due_date: this.soaDueDate || '',
                });
                window.open(`${baseUrl}/clients/${this.soaClientId}/soa?${params.toString()}`, '_blank');
                this.openSoa = false;
            },
        };
    }
</script>

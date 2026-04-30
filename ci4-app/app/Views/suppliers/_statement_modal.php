<?php
$defaultSupplierStatementStart = $defaultSupplierStatementStart ?? date('Y-m-01');
$defaultSupplierStatementEnd = $defaultSupplierStatementEnd ?? date('Y-m-t');
?>

<div class="modal-backdrop" x-show="openSupplierStatement" x-cloak @click.self="closeSupplierStatementModal()">
    <div class="modal-panel max-w-md p-6" @click.stop>
        <div class="flex items-start justify-between gap-4">
            <h2 class="text-lg font-semibold" x-text="`Payables for ${supplierStatementName}`"></h2>
            <button class="btn btn-secondary" type="button" @click="closeSupplierStatementModal()">Close</button>
        </div>

        <div class="mt-4 space-y-3 text-sm">
            <div>
                <label class="block text-sm font-medium" for="supplier_statement_start">Billing From</label>
                <input class="input mt-1" id="supplier_statement_start" type="date" x-model="supplierStatementStart">
            </div>
            <div>
                <label class="block text-sm font-medium" for="supplier_statement_end">Billing To</label>
                <input class="input mt-1" id="supplier_statement_end" type="date" x-model="supplierStatementEnd" @change="recomputeSupplierStatementDueDate()">
            </div>
            <div>
                <label class="block text-sm font-medium" for="supplier_statement_due">Due Date</label>
                <input class="input mt-1" id="supplier_statement_due" type="date" x-model="supplierStatementDueDate">
            </div>
        </div>

        <div class="mt-5 flex gap-3">
            <button class="btn btn-strong" type="button" @click="openSupplierStatementPrint()">Print</button>
            <button class="btn btn-secondary" type="button" @click="closeSupplierStatementModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
    function supplierStatementModalState() {
        const baseUrl = '<?= rtrim(base_url(), '/') ?>';
        const defaultSupplierStatementStart = '<?= esc((string) $defaultSupplierStatementStart, 'js') ?>';
        const defaultSupplierStatementEnd = '<?= esc((string) $defaultSupplierStatementEnd, 'js') ?>';

        return {
            openSupplierStatement: false,
            supplierStatementId: null,
            supplierStatementName: '',
            supplierStatementTerm: '',
            supplierStatementStart: defaultSupplierStatementStart,
            supplierStatementEnd: defaultSupplierStatementEnd,
            supplierStatementDueDate: '',
            openSupplierStatementModal(id, name, paymentTerm = '') {
                this.supplierStatementId = id;
                this.supplierStatementName = name || '';
                this.supplierStatementTerm = paymentTerm === null || paymentTerm === undefined ? '' : String(paymentTerm);
                this.supplierStatementStart = defaultSupplierStatementStart;
                this.supplierStatementEnd = defaultSupplierStatementEnd;
                this.recomputeSupplierStatementDueDate();
                this.openSupplierStatement = true;
            },
            closeSupplierStatementModal() {
                this.openSupplierStatement = false;
            },
            recomputeSupplierStatementDueDate() {
                if (!this.supplierStatementEnd) {
                    this.supplierStatementDueDate = '';
                    return;
                }

                const term = parseInt(this.supplierStatementTerm || '0', 10);
                const days = Number.isNaN(term) ? 0 : term;
                const date = new Date(`${this.supplierStatementEnd}T00:00:00`);

                if (Number.isNaN(date.getTime())) {
                    this.supplierStatementDueDate = '';
                    return;
                }

                date.setDate(date.getDate() + days);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                this.supplierStatementDueDate = `${year}-${month}-${day}`;
            },
            openSupplierStatementPrint() {
                if (!this.supplierStatementId) {
                    return;
                }

                const params = new URLSearchParams({
                    start: this.supplierStatementStart || '',
                    end: this.supplierStatementEnd || '',
                    due_date: this.supplierStatementDueDate || '',
                });
                window.open(`${baseUrl}/suppliers/${this.supplierStatementId}/payables?${params.toString()}`, '_blank');
                this.openSupplierStatement = false;
            },
        };
    }
</script>

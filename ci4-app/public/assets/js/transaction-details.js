(function () {
    function createState() {
        return {
            open: false,
            selectedId: null,
            fallbackLabel: '',
            loading: false,
            error: '',
            cache: {},
        };
    }

    function normalizeKey(id) {
        return id === null || id === undefined ? '' : String(id);
    }

    function toNumber(value) {
        const number = parseFloat(value);
        return Number.isFinite(number) ? number : 0;
    }

    window.transactionDetailsState = function transactionDetailsState(options = {}) {
        const endpoints = options.endpoints || {};

        return {
            transactionDetailEndpoints: endpoints,
            transactionDetailStates: {
                delivery: createState(),
                payment: createState(),
                purchaseOrder: createState(),
                payable: createState(),
                supplierOrder: createState(),
            },

            transactionDetailState(type) {
                if (!this.transactionDetailStates[type]) {
                    this.transactionDetailStates[type] = createState();
                }

                return this.transactionDetailStates[type];
            },

            transactionDetailOpen(type) {
                return this.transactionDetailState(type).open;
            },

            async openDetail(type, id, fallbackLabel = '') {
                const state = this.transactionDetailState(type);
                const key = normalizeKey(id);

                state.open = true;
                state.selectedId = key;
                state.fallbackLabel = fallbackLabel || '';
                state.error = '';

                if (!key || state.cache[key]) {
                    return;
                }

                const endpoint = this.transactionDetailEndpoints[type];
                if (!endpoint) {
                    state.error = 'Detail endpoint is not configured.';
                    return;
                }

                state.loading = true;

                try {
                    const response = await fetch(endpoint.replace(/\/$/, '') + '/' + encodeURIComponent(key), {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Unable to load details.');
                    }

                    state.cache[key] = data;
                } catch (error) {
                    state.error = error.message || 'Unable to load details.';
                } finally {
                    state.loading = false;
                }
            },

            closeDetail(type) {
                const state = this.transactionDetailState(type);
                state.open = false;
                state.selectedId = null;
                state.error = '';
            },

            transactionDetail(type) {
                const state = this.transactionDetailState(type);
                return state.cache[normalizeKey(state.selectedId)] || null;
            },

            detail(type) {
                return this.transactionDetail(type);
            },

            transactionDetailRecord(type, key) {
                const detail = this.transactionDetail(type);
                return detail && detail[key] ? detail[key] : null;
            },

            transactionDetailRows(type, key) {
                const detail = this.transactionDetail(type);
                return detail && Array.isArray(detail[key]) ? detail[key] : [];
            },

            rows(type, key) {
                return this.transactionDetailRows(type, key);
            },

            transactionDetailLoading(type) {
                return this.transactionDetailState(type).loading;
            },

            detailLoading(type) {
                return this.transactionDetailLoading(type);
            },

            transactionDetailError(type) {
                return this.transactionDetailState(type).error;
            },

            detailError(type) {
                return this.transactionDetailError(type);
            },

            transactionDetailFallback(type) {
                return this.transactionDetailState(type).fallbackLabel || '';
            },

            transactionDetailSum(type, key, field, alternateField = '') {
                return this.transactionDetailRows(type, key).reduce((sum, item) => {
                    const primary = toNumber(item[field]);
                    if (primary !== 0 || !alternateField) {
                        return sum + primary;
                    }

                    return sum + toNumber(item[alternateField]);
                }, 0);
            },

            paymentOtherAccountRows() {
                return this.transactionDetailRows('payment', 'other_accounts').filter((item) => {
                    return toNumber(item.dr) > 0 && String(item.account_title || '').trim() !== '';
                });
            },

            paymentArOtherRow() {
                return this.transactionDetailRows('payment', 'other_accounts').find((item) => toNumber(item.ar_others) > 0) || null;
            },

            formatAmount(value) {
                return toNumber(value).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },

            formatQty(value) {
                return toNumber(value).toLocaleString(undefined, {
                    minimumFractionDigits: 5,
                    maximumFractionDigits: 5,
                });
            },
        };
    };
})();

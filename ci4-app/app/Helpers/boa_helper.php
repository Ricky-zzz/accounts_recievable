<?php

if (! function_exists('boa_column_from_bank_name')) {
    function boa_column_from_bank_name(string $bankName): string
    {
        $normalized = strtoupper(trim($bankName));
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized);
        $normalized = trim($normalized, '_');

        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^[0-9]/', $normalized)) {
            $normalized = 'B_' . $normalized;
        }

        $reserved = [
            'ID',
            'DATE',
            'PAYOR',
            'REFERENCE',
            'PAYMENT_ID',
            'AR_TRADE',
            'AR_OTHERS',
            'ACCOUNT_TITLE',
            'DR',
            'CR',
            'NOTE',
            'DESCRIPTION',
            'CREATED_AT',
            'UPDATED_AT',
        ];

        if (in_array($normalized, $reserved, true)) {
            return '';
        }

        return $normalized;
    }
}

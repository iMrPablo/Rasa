<?php
/**
 * Helper Functions
 */

function jsonResponse(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function createId(): string {
    return function_exists('random_bytes') ? bin2hex(random_bytes(8)) : uniqid('', true);
}

function generateInvoiceId(): string {
    $suffix = function_exists('random_bytes') ? strtoupper(bin2hex(random_bytes(2))) : strtoupper(substr(uniqid('', true), -4));
    return 'RSA-ID-' . $suffix;
}

function generateInvoiceNumber(array $sales): string {
    $maxNum = 0;
    foreach ($sales as $sale) {
        if (isset($sale['invoice_number']) && preg_match('/^RSA-(\d+)$/', $sale['invoice_number'], $matches)) {
            $num = (int)$matches[1];
            if ($num > $maxNum) $maxNum = $num;
        }
    }
    return 'RSA-' . str_pad($maxNum + 1, 8, '0', STR_PAD_LEFT);
}

function escapeHtml(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

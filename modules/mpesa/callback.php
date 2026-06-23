<?php
/**
 * M-Pesa STK Push Callback
 * Safaricom sends the payment result here after user enters PIN.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Log raw input for debugging
$raw = file_get_contents('php://input');
file_put_contents(__DIR__ . '/mpesa-callback.log', date('Y-m-d H:i:s') . ' ' . $raw . "\n", FILE_APPEND);

$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    exit;
}

$body = $data['Body'] ?? [];
$stkCallback = $body['stkCallback'] ?? [];
$resultCode = $stkCallback['ResultCode'] ?? 1;
$resultDesc = $stkCallback['ResultDesc'] ?? '';

// Failed transaction
if ($resultCode != 0) {
    // Optionally mark the payment as failed - for now just log
    http_response_code(200);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Received']);
    exit;
}

$callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
$meta = [];
foreach ($callbackMetadata as $item) {
    $meta[$item['Name']] = $item['Value'] ?? '';
}

$transId = $meta['TransID'] ?? '';
$amount = $meta['Amount'] ?? 0;
$phone = $meta['PhoneNumber'] ?? '';
$invoice_no = $stkCallback['BillRefNumber'] ?? $meta['AccountReference'] ?? '';

if (!$invoice_no || !$transId) {
    http_response_code(200);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Missing reference']);
    exit;
}

// Find transaction by invoice
$txn = db_get_row("SELECT * FROM transactions WHERE invoice_no = ?", [$invoice_no]);
if (!$txn) {
    http_response_code(200);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Transaction not found']);
    exit;
}

// Update with verified M-Pesa payment
$new_paid = $txn['paid_amount'] + $amount;
$new_due = $txn['total_amount'] - $new_paid;
$new_status = $new_due <= 0 ? 'paid' : 'partial';

db_query(
    "UPDATE transactions SET
     transaction_id = CONCAT(IFNULL(transaction_id,''), ' / MPESA:', ?),
     paid_amount = ?,
     due_amount = ?,
     payment_status = ?,
     verified_by = 0,
     verified_at = NOW()
     WHERE invoice_no = ?",
    [$transId, $new_paid, $new_due, $new_status, $invoice_no]
);

http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);

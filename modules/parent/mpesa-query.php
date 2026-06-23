<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('parent');

header('Content-Type: application/json');

$checkoutRequestId = trim($_GET['checkout_id'] ?? '');
$invoice_no = sanitize($_GET['invoice_no'] ?? '');
$child_id = (int)($_GET['child_id'] ?? 0);
$amount = (float)($_GET['amount'] ?? 0);

if (!$checkoutRequestId || !$invoice_no || !$child_id || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Missing parameters']);
    exit;
}

$txn = db_get_row(
    "SELECT t.* FROM transactions t
     JOIN student_parents sp ON t.student_id = sp.student_id
     WHERE t.invoice_no = ? AND sp.parent_user_id = ?",
    [$invoice_no, get_user_id()]
);
if (!$txn) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'error' => 'Transaction not found']);
    exit;
}

if ($txn['verified_at']) {
    echo json_encode(['status' => 'completed', 'resultCode' => 0, 'resultDesc' => 'Payment already confirmed']);
    exit;
}

$shortcode = '174379';
$passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$consumerKey = 'uKwnFefxyoXfwvFPvV8GUG9SXe8VgHll8W470FGDVi0G1GEQ';
$consumerSecret = 'GcewAhPGeAjiBnjYiMoFz2p4aF3lb03j8VII3NuVqbq7ptQnRJGRUW6mgCGraDBB';

$timestamp = date('YmdHis');
$password = base64_encode($shortcode . $passkey . $timestamp);

$ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$tokenResp = curl_exec($ch);
$tokenHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tokenResp === false || $tokenHttp !== 200) {
    $err = curl_error($ch) ?: null;
    echo json_encode([
        'status' => 'error',
        'error' => 'Failed to obtain access token',
        'http_code' => $tokenHttp,
        'token_response' => $tokenResp,
        'curl_error' => $err
    ]);
    exit;
}

$tokenData = json_decode($tokenResp, true);
$access_token = $tokenData['access_token'] ?? null;
if (!$access_token) {
    echo json_encode(['status' => 'error', 'error' => 'No access token received']);
    exit;
}

$queryPayload = [
    'BusinessShortCode' => $shortcode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'CheckoutRequestID' => $checkoutRequestId
];

$ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch) ?: null;
curl_close($ch);

if ($resp === false || $http !== 200) {
    echo json_encode([
        'status' => 'error',
        'error' => 'Query request failed',
        'http_code' => $http,
        'response' => $resp,
        'curl_error' => $curlErr,
        'payload' => $queryPayload
    ]);
    exit;
}

$queryResult = json_decode($resp, true);

// Check if the query itself was accepted
$queryResponseCode = $queryResult['ResponseCode'] ?? null;
if ($queryResponseCode !== '0' && $queryResponseCode !== 0) {
    echo json_encode([
        'status' => 'pending',
        'resultCode' => -1,
        'resultDesc' => $queryResult['ResponseDescription'] ?? 'Query pending'
    ]);
    exit;
}

$resultCode = (int)($queryResult['ResultCode'] ?? -1);
$resultDesc = $queryResult['ResultDesc'] ?? '';

if ($resultCode === 0) {
    $actualAmount = $amount;
    $receiptNumber = '';

    if (isset($queryResult['ResultParameters']['ResultParameter']) && is_array($queryResult['ResultParameters']['ResultParameter'])) {
        foreach ($queryResult['ResultParameters']['ResultParameter'] as $param) {
            if (($param['Key'] ?? '') === 'TransactionAmount') $actualAmount = (float)($param['Value'] ?? $amount);
            if (($param['Key'] ?? '') === 'TransactionReceipt') $receiptNumber = $param['Value'] ?? '';
            if (($param['Key'] ?? '') === 'ReceiptNumber') $receiptNumber = $param['Value'] ?? '';
        }
    }

    $new_paid = $txn['paid_amount'] + $actualAmount;
    $new_due = $txn['total_amount'] - $new_paid;
    $new_status = $new_due <= 0 ? 'paid' : 'partial';

    db_query(
        "UPDATE transactions SET
         transaction_id = CONCAT(IFNULL(transaction_id,''), ' / MPESA:', ?),
         paid_amount = ?,
         due_amount = ?,
         payment_status = ?,
         payment_date = CURDATE(),
         verified_by = 0,
         verified_at = NOW()
         WHERE invoice_no = ? AND verified_at IS NULL",
        [$receiptNumber, $new_paid, $new_due, $new_status, $invoice_no]
    );

    echo json_encode([
        'status' => 'completed',
        'resultCode' => 0,
        'resultDesc' => 'Payment confirmed',
        'receipt' => $receiptNumber,
        'amount' => $actualAmount
    ]);
    exit;
}

$terminalCodes = [1032, 1037, 1031, 1025, 2001];
$isTerminal = in_array($resultCode, $terminalCodes, true);
$isCancelled = $resultCode === 1037;

echo json_encode([
    'status' => $isTerminal ? ($isCancelled ? 'cancelled' : 'failed') : 'pending',
    'resultCode' => $resultCode,
    'resultDesc' => $resultDesc
]);

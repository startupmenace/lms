<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('parent');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$invoice_no = sanitize($_POST['invoice_no'] ?? '');
$child_id = (int)($_POST['child_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$phone = trim($_POST['phone'] ?? '');

if (!$invoice_no || !$child_id || $amount <= 0 || !$phone) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Verify transaction belongs to this parent's child
$txn = db_get_row(
    "SELECT t.* FROM transactions t
     JOIN student_parents sp ON t.student_id = sp.student_id
     WHERE t.invoice_no = ? AND sp.parent_user_id = ?",
    [$invoice_no, get_user_id()]
);
if (!$txn) {
    http_response_code(404);
    echo json_encode(['error' => 'Transaction not found']);
    exit;
}

// Normalize phone
$phone = preg_replace('/\D+/', '', $phone);
if (strpos($phone, '0') === 0) $phone = '254' . substr($phone, 1);
elseif (strpos($phone, '7') === 0) $phone = '254' . $phone;

if (strlen($phone) !== 12) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid phone number. Use format: 0712345678']);
    exit;
}

// M-Pesa credentials
$shortcode = '174379';
$passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$consumerKey = 'uKwnFefxyoXfwvFPvV8GUG9SXe8VgHll8W470FGDVi0G1GEQ';
$consumerSecret = 'GcewAhPGeAjiBnjYiMoFz2p4aF3lb03j8VII3NuVqbq7ptQnRJGRUW6mgCGraDBB';
$callbackUrl = (defined('BASE_URL') ? BASE_URL : 'https://ziada.co.ke') . '/modules/mpesa/callback.php';

$timestamp = date('YmdHis');
$password = base64_encode($shortcode . $passkey . $timestamp);

// Get access token
$ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$tokenResp = curl_exec($ch);
$token_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tokenResp === false || $token_http !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to obtain access token']);
    exit;
}

$tokenData = json_decode($tokenResp, true);
$access_token = $tokenData['access_token'] ?? null;
if (!$access_token) {
    http_response_code(502);
    echo json_encode(['error' => 'No access token received']);
    exit;
}

$payload = [
    'BusinessShortCode' => $shortcode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => (int)$amount,
    'PartyA' => $phone,
    'PartyB' => $shortcode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callbackUrl,
    'AccountReference' => $invoice_no,
    'TransactionDesc' => 'Fee payment - ' . $invoice_no
];

$ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'STK push request failed']);
    exit;
}

$result = json_decode($resp, true);

if (($result['ResponseCode'] ?? '') !== '0') {
    http_response_code(502);
    echo json_encode(['error' => $result['errorMessage'] ?? 'STK push failed', 'details' => $result]);
    exit;
}

// Store the M-Pesa request ID for callback matching
$merchantRequestId = $result['MerchantRequestID'];
$checkoutRequestId = $result['CheckoutRequestID'];

// Store STK push reference — actual payment confirmation comes via callback
db_query(
    "UPDATE transactions SET
     payment_method = 'mpesa_stk',
     transaction_id = ?
     WHERE invoice_no = ? AND student_id = ?",
    ['STK-' . $merchantRequestId, $invoice_no, $child_id]
);

echo json_encode([
    'success' => true,
    'message' => 'STK push sent. Check your phone to complete payment.',
    'MerchantRequestID' => $merchantRequestId,
    'CheckoutRequestID' => $checkoutRequestId,
    'invoice_no' => $invoice_no,
    'child_id' => $child_id,
    'amount' => $amount,
    'phone' => $phone
]);

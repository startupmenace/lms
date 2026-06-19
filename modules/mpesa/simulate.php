
<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['error' => 'Invalid request method']);
  exit;
}

$phone_number = trim($_POST['phone_number'] ?? '');
$amount = trim($_POST['amount'] ?? '');

// basic validation
if (empty($phone_number) || empty($amount) || !is_numeric($amount)) {
  echo json_encode(['error' => 'Phone number and numeric amount are required']);
  exit;
}

// normalize phone to international format (2547XXXXXXXX)
$phone_number = preg_replace('/\D+/', '', $phone_number);
if (strpos($phone_number, '0') === 0) {
  $phone_number = '254' . substr($phone_number, 1);
} elseif (strpos($phone_number, '7') === 0) {
  $phone_number = '254' . $phone_number;
}

// NOTE: Hardcoded credentials (provided directly). Consider using environment variables instead for security.
$shortcode = '174379';
$passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$consumerKey = 'uKwnFefxyoXfwvFPvV8GUG9SXe8VgHll8W470FGDVi0G1GEQ';
$consumerSecret = 'GcewAhPGeAjiBnjYiMoFz2p4aF3lb03j8VII3NuVqbq7ptQnRJGRUW6mgCGraDBB';
$callbackUrl = getenv('MPESA_CALLBACK_URL') ?: (defined('BASE_URL') ? BASE_URL . '/modules/mpesa/callback.php' : '');

if (empty($callbackUrl)) {
  echo json_encode([
    'error' => 'Missing MPESA callback URL',
    'hint' => 'Set MPESA_CALLBACK_URL or ensure BASE_URL is defined'
  ]);
  exit;
}

$timestamp = date('YmdHis');
$password = base64_encode($shortcode . $passkey . $timestamp);

$ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // set true in production
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$tokenResp = curl_exec($ch);
$token_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$token_curl_err = curl_error($ch);
curl_close($ch);

if ($tokenResp === false || $token_http !== 200) {
  echo json_encode([
    'error' => 'Failed to obtain access token',
    'details' => $tokenResp,
    'http_code' => $token_http,
    'curl_error' => $token_curl_err
  ]);
  exit;
}

$tokenData = json_decode($tokenResp, true);
$access_token = $tokenData['access_token'] ?? null;
if (!$access_token) {
  echo json_encode(['error' => 'No access token in response', 'details' => $tokenData]);
  exit;
}

$payload = [
  'BusinessShortCode' => $shortcode,
  'Password' => $password,
  'Timestamp' => $timestamp,
  'TransactionType' => 'CustomerPayBillOnline',
  'Amount' => (int)$amount,
  'PartyA' => $phone_number,
  'PartyB' => $shortcode,
  'PhoneNumber' => $phone_number,
  'CallBackURL' => $callbackUrl,
  'AccountReference' => 'Ziada',
  'TransactionDesc' => 'Fee payment'
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
$curlErr = curl_error($ch);
curl_close($ch);

if ($resp === false) {
  echo json_encode(['error' => 'STK push request failed', 'details' => $curlErr]);
  exit;
}

// forward the raw response (usually JSON)
header('Content-Type: application/json');
http_response_code($http ?: 200);
echo $resp;
?>

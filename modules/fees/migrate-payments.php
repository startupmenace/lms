<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$conn = db_connect();
$messages = [];

$conn->query("
CREATE TABLE IF NOT EXISTS payment_gateway_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_name VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    config_data TEXT NOT NULL,
    instructions TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$messages[] = "Table `payment_gateway_config` ready.";

$defaults = [
    ['mpesa', 'M-Pesa', '{"paybill":"247247","till":"","account_format":"Student Name - Invoice No"}', 'Send via M-Pesa Paybill 247247. Use name + invoice as account number.'],
    ['bank_transfer', 'Bank Transfer', '{"bank_name":"Equity Bank","account_name":"Ziada LMS","account_number":"1234567890","branch":"Nairobi"}', 'Transfer to our Equity Bank account. Upload receipt below.']
];

foreach ($defaults as $d) {
    $exists = db_get_row("SELECT id FROM payment_gateway_config WHERE gateway_name = ?", [$d[0]]);
    if (!$exists) {
        db_insert("INSERT INTO payment_gateway_config (gateway_name, label, config_data, instructions) VALUES (?,?,?,?)", $d);
        $messages[] = "Gateway '{$d[0]}' inserted.";
    } else {
        $messages[] = "Gateway '{$d[0]}' already exists. Skipped.";
    }
}

$cols = $conn->query("SHOW COLUMNS FROM transactions LIKE 'payment_proof'");
if ($cols->num_rows === 0) {
    $conn->query("ALTER TABLE transactions ADD COLUMN payment_proof VARCHAR(255) DEFAULT NULL AFTER transaction_id");
    $conn->query("ALTER TABLE transactions ADD COLUMN verified_by INT DEFAULT NULL AFTER payment_proof");
    $conn->query("ALTER TABLE transactions ADD COLUMN verified_at DATETIME DEFAULT NULL AFTER verified_by");
    $conn->query("ALTER TABLE transactions ADD COLUMN payment_note TEXT DEFAULT NULL AFTER verified_at");
    $messages[] = "Columns added to `transactions`.";
} else {
    $messages[] = "Columns already exist on `transactions`. Skipped.";
}

$conn->close();

$page_title = 'Migration';
include __DIR__ . '/../../includes/header.php';
?>
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-8">
        <h2 class="text-xl font-bold text-teal-600 mb-4">Payment System Migration</h2>
        <div class="space-y-2">
            <?php foreach ($messages as $m): ?>
            <div class="flex items-center gap-2 text-sm text-gray-700">
                <i class="fas fa-check-circle text-green-500"></i> <?= sanitize($m) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="index.php?tab=gateways" class="mt-6 inline-block bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
            <i class="fas fa-arrow-left mr-2"></i>Go to Fee Management
        </a>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>

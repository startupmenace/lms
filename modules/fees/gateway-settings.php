<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('fees');

$page_title = 'Payment Gateway Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gateway'])) {
    $gateway_name = sanitize($_POST['gateway_name']);
    $label = sanitize($_POST['label']);
    $instructions = sanitize($_POST['instructions']);

    $fields = [];
    foreach ($_POST as $key => $val) {
        if (strpos($key, 'cfg_') === 0) {
            $fields[str_replace('cfg_', '', $key)] = sanitize($val);
        }
    }
    $config_data = json_encode($fields);

    $existing = db_get_row("SELECT id FROM payment_gateway_config WHERE gateway_name = ?", [$gateway_name]);
    if ($existing) {
        db_query("UPDATE payment_gateway_config SET label=?, config_data=?, instructions=? WHERE gateway_name=?", [$label, $config_data, $instructions, $gateway_name]);
    } else {
        db_insert("INSERT INTO payment_gateway_config (gateway_name, label, config_data, instructions) VALUES (?,?,?,?)", [$gateway_name, $label, $config_data, $instructions]);
    }
    set_flash('success', 'Gateway settings saved.');
    redirect('gateway-settings.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_gateway'])) {
    $id = (int)$_POST['gateway_id'];
    $gateway = db_get_row("SELECT id, is_active FROM payment_gateway_config WHERE id = ?", [$id]);
    if ($gateway) {
        $new = $gateway['is_active'] ? 0 : 1;
        db_query("UPDATE payment_gateway_config SET is_active=? WHERE id=?", [$new, $id]);
        set_flash('success', 'Gateway status updated.');
    }
    redirect('gateway-settings.php');
}

$gateways = db_get_all("SELECT * FROM payment_gateway_config ORDER BY gateway_name");

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <?php if (has_flash('success')): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <i class="fas fa-check-circle"></i> <?= get_flash('success') ?>
    </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-8">
        <!-- M-Pesa Settings -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-mobile-alt text-xl text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">M-Pesa</h3>
                        <?php
                        $mpesa = db_get_row("SELECT * FROM payment_gateway_config WHERE gateway_name = 'mpesa'");
                        $mpesa_config = $mpesa ? json_decode($mpesa['config_data'], true) : [];
                        ?>
                        <span class="text-xs <?= $mpesa && $mpesa['is_active'] ? 'text-green-600' : 'text-gray-400' ?>">
                            <i class="fas fa-circle mr-1 text-[8px]"></i><?= $mpesa && $mpesa['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                </div>
                <?php if ($mpesa): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="gateway_id" value="<?= $mpesa['id'] ?>">
                    <button type="submit" name="toggle_gateway" class="text-sm <?= $mpesa['is_active'] ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' ?> transition">
                        <i class="fas <?= $mpesa['is_active'] ? 'fa-pause-circle' : 'fa-play-circle' ?>"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="gateway_name" value="mpesa">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Label</label>
                    <input type="text" name="label" value="<?= sanitize($mpesa['label'] ?? 'M-Pesa') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Paybill Number</label>
                    <input type="text" name="cfg_paybill" value="<?= sanitize($mpesa_config['paybill'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., 247247">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Till Number (optional)</label>
                    <input type="text" name="cfg_till" value="<?= sanitize($mpesa_config['till'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., 123456">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Account Number Format (instructions for student)</label>
                    <input type="text" name="cfg_account_format" value="<?= sanitize($mpesa_config['account_format'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., Student Name - Invoice">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Payment Instructions</label>
                    <textarea name="instructions" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Instructions shown to students..."><?= sanitize($mpesa['instructions'] ?? '') ?></textarea>
                </div>
                <button type="submit" name="save_gateway" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition"><i class="fas fa-save mr-2"></i>Save M-Pesa Settings</button>
            </form>
        </div>

        <!-- Bank Transfer Settings -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-university text-xl text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Bank Transfer</h3>
                        <?php
                        $bank = db_get_row("SELECT * FROM payment_gateway_config WHERE gateway_name = 'bank_transfer'");
                        $bank_config = $bank ? json_decode($bank['config_data'], true) : [];
                        ?>
                        <span class="text-xs <?= $bank && $bank['is_active'] ? 'text-green-600' : 'text-gray-400' ?>">
                            <i class="fas fa-circle mr-1 text-[8px]"></i><?= $bank && $bank['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                </div>
                <?php if ($bank): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="gateway_id" value="<?= $bank['id'] ?>">
                    <button type="submit" name="toggle_gateway" class="text-sm <?= $bank['is_active'] ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' ?> transition">
                        <i class="fas <?= $bank['is_active'] ? 'fa-pause-circle' : 'fa-play-circle' ?>"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="gateway_name" value="bank_transfer">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Label</label>
                    <input type="text" name="label" value="<?= sanitize($bank['label'] ?? 'Bank Transfer') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Bank Name</label>
                    <input type="text" name="cfg_bank_name" value="<?= sanitize($bank_config['bank_name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., Equity Bank">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Account Name</label>
                    <input type="text" name="cfg_account_name" value="<?= sanitize($bank_config['account_name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., Jewel House School">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Account Number</label>
                    <input type="text" name="cfg_account_number" value="<?= sanitize($bank_config['account_number'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., 1234567890">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
                    <input type="text" name="cfg_branch" value="<?= sanitize($bank_config['branch'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., Nairobi">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Payment Instructions</label>
                    <textarea name="instructions" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Instructions shown to students..."><?= sanitize($bank['instructions'] ?? '') ?></textarea>
                </div>
                <button type="submit" name="save_gateway" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition"><i class="fas fa-save mr-2"></i>Save Bank Settings</button>
            </form>
        </div>
    </div>

    <div class="mt-8 bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700">
        <i class="fas fa-info-circle mr-2"></i>
        <strong>Note:</strong> This is manual payment verification. Students will see these payment options, make the transfer, and submit the transaction details. You verify and confirm payments from the <a href="index.php?tab=verification" class="underline font-medium">Verification</a> tab.
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

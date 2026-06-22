<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('fees');

$page_title = 'Fee Management';
$tab = $_GET['tab'] ?? 'structures';

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200">
            <a href="?tab=structures" class="px-6 py-3 text-sm font-medium <?= $tab == 'structures' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-layer-group mr-2"></i>Fee Structure
            </a>
            <a href="?tab=reports" class="px-6 py-3 text-sm font-medium <?= $tab == 'reports' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-file-alt mr-2"></i>Reports
            </a>
            <a href="?tab=collection" class="px-6 py-3 text-sm font-medium <?= $tab == 'collection' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-hand-holding-usd mr-2"></i>Fee Collection
            </a>
            <a href="?tab=transactions" class="px-6 py-3 text-sm font-medium <?= $tab == 'transactions' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-exchange-alt mr-2"></i>Transactions
            </a>
            <a href="?tab=reminders" class="px-6 py-3 text-sm font-medium <?= $tab == 'reminders' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-bell mr-2"></i>Reminders
            </a>
            <a href="?tab=gateways" class="px-6 py-3 text-sm font-medium <?= $tab == 'gateways' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-cog mr-2"></i>Gateways
            </a>
            <a href="?tab=verification" class="px-6 py-3 text-sm font-medium <?= $tab == 'verification' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-check-double mr-2"></i>Verification
            </a>
        </div>
    </div>

    <?php if ($tab == 'structures'): ?>
    <?php
    $structures = db_get_all("SELECT fs.*, GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as class_names,
        (SELECT COUNT(*) FROM fee_types WHERE fee_structure_id = fs.id) as type_count
        FROM fee_structures fs
        LEFT JOIN fee_structure_classes fsc ON fs.id = fsc.fee_structure_id
        LEFT JOIN classes c ON fsc.class_id = c.id
        GROUP BY fs.id ORDER BY fs.created_at DESC");
    ?>
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500"><?= count($structures) ?> fee structures</p>
            <a href="create-structure.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
                <i class="fas fa-plus"></i> Create Fee Structure
            </a>
            <a href="generate-bills.php" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 transition flex items-center gap-2">
                <i class="fas fa-file-invoice"></i> Generate Bills
            </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($structures)): ?>
        <div class="col-span-full bg-white rounded-xl border border-gray-200 p-12 text-center">
            <i class="fas fa-file-invoice text-5xl text-gray-300 mb-4 block"></i>
            <p class="text-gray-500">No fee structures created yet.</p>
        </div>
        <?php else: ?>
        <?php foreach ($structures as $fs): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-semibold text-gray-900"><?= sanitize($fs['name']) ?></h3>
            <div class="text-sm text-gray-500 mt-2 space-y-1">
                <div><span class="font-medium">Prefix:</span> <?= sanitize($fs['prefix']) ?> | <span class="font-medium">Receipt:</span> #<?= $fs['receipt_start'] ?></div>
                <div><span class="font-medium">Frequency:</span> <?= ucfirst(str_replace('_', ' ', $fs['frequency'])) ?><?= $fs['due_day'] && $fs['frequency'] === 'monthly' ? ' (Due on ' . ordinal($fs['due_day']) . ')' : '' ?></div>
                <div><span class="font-medium">Classes:</span> <?= sanitize($fs['class_names'] ?? 'N/A') ?></div>
                <?php if ($fs['term_config']): $tc = json_decode($fs['term_config'], true); ?>
                <div class="mt-1 flex gap-1 flex-wrap">
                    <?php foreach ($tc as $t => $c): ?>
                    <span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded"><?= sanitize($t) ?>: <?= sanitize($c['prefix'] ?? '') ?><?= !empty($c['due_date']) ? ' (due ' . format_date($c['due_date']) . ')' : '' ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div><span class="font-medium">Fee Types:</span> <?= $fs['type_count'] ?></div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 flex gap-2">
                <a href="edit-structure.php?id=<?= $fs['id'] ?>" class="text-teal-600 hover:text-teal-800 text-sm"><i class="fas fa-edit"></i> Edit</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php elseif ($tab == 'reports'): ?>
    <?php
    $report_class = (int)($_GET['report_class'] ?? 0);
    $report_term = $_GET['report_term'] ?? '';
    $report_session = $_GET['report_session'] ?? date('Y') . '-' . (date('Y') + 1);
    $classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");

    $report_data = [];
    if ($report_class) {
        $where = "s.class_id = ? AND s.is_active = 1";
        $params = [$report_class];
        if ($report_term) { $where .= " AND t.term = ?"; $params[] = $report_term; }
        if ($report_session) { $where .= " AND t.session_year = ?"; $params[] = $report_session; }

        $report_data = db_get_all("SELECT s.id as student_id, s.parent_name, s.enrollment_id, c.name as class_name,
            COALESCE(t.id,0) as tx_id, t.invoice_no, t.term, t.session_year,
            COALESCE(t.total_amount,0) as billed, COALESCE(t.discount,0) as discount,
            COALESCE(t.paid_amount,0) as paid, t.payment_status
            FROM students s
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN transactions t ON t.student_id = s.id
            WHERE $where
            ORDER BY s.parent_name, t.term", $params);

        // Group by student
        $grouped = [];
        foreach ($report_data as $r) {
            $sid = $r['student_id'];
            if (!isset($grouped[$sid])) {
                $grouped[$sid] = ['name' => $r['parent_name'], 'enrollment' => $r['enrollment_id'], 'class' => $r['class_name'], 'invoices' => [], 'totals' => ['billed'=>0,'discount'=>0,'paid'=>0]];
            }
            $grouped[$sid]['invoices'][] = $r;
            $grouped[$sid]['totals']['billed'] += $r['billed'];
            $grouped[$sid]['totals']['discount'] += $r['discount'];
            $grouped[$sid]['totals']['paid'] += $r['paid'];
        }
    }
    ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
            <input type="hidden" name="tab" value="reports">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                <select name="report_class" required class="border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none" onchange="this.form.submit()">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $report_class == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Term</label>
                <select name="report_term" class="border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">All Terms</option>
                    <option value="Term 1" <?= $report_term == 'Term 1' ? 'selected' : '' ?>>Term 1</option>
                    <option value="Term 2" <?= $report_term == 'Term 2' ? 'selected' : '' ?>>Term 2</option>
                    <option value="Term 3" <?= $report_term == 'Term 3' ? 'selected' : '' ?>>Term 3</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Session</label>
                <input type="text" name="report_session" value="<?= sanitize($report_session) ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none w-28">
            </div>
            <div>
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-xs font-medium hover:bg-teal-700 transition"><i class="fas fa-search mr-1"></i> Generate Report</button>
            </div>
        </form>

        <?php if ($report_class): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Student</th>
                        <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Enrollment</th>
                        <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Class</th>
                        <th class="text-right py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Billed</th>
                        <th class="text-right py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Discount</th>
                        <th class="text-right py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Applicable</th>
                        <th class="text-right py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Paid</th>
                        <th class="text-right py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Balance</th>
                        <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Term</th>
                        <th class="text-left py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Invoice</th>
                        <th class="text-center py-2 px-2 font-bold text-gray-700 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grouped)): ?>
                    <tr><td colspan="11" class="py-10 text-center text-gray-400">No data for the selected filters.</td></tr>
                    <?php else: ?>
                    <?php foreach ($grouped as $sid => $g): 
                        $applicable = $g['totals']['billed'] - $g['totals']['discount'];
                        $balance = $applicable - $g['totals']['paid'];
                    ?>
                    <tr class="border-b border-gray-100 bg-gray-50/50">
                        <td class="py-2 px-2 font-semibold text-gray-900"><?= sanitize($g['name']) ?></td>
                        <td class="py-2 px-2 text-gray-600"><?= sanitize($g['enrollment']) ?></td>
                        <td class="py-2 px-2 text-gray-600"><?= sanitize($g['class']) ?></td>
                        <td class="py-2 px-2 text-right font-semibold"><?= format_currency($g['totals']['billed']) ?></td>
                        <td class="py-2 px-2 text-right text-orange-600"><?= format_currency($g['totals']['discount']) ?></td>
                        <td class="py-2 px-2 text-right font-semibold"><?= format_currency($applicable) ?></td>
                        <td class="py-2 px-2 text-right text-green-600 font-semibold"><?= format_currency($g['totals']['paid']) ?></td>
                        <td class="py-2 px-2 text-right font-bold <?= $balance > 0 ? 'text-red-600' : 'text-green-600' ?>"><?= format_currency($balance) ?></td>
                        <td colspan="3"></td>
                    </tr>
                    <?php foreach ($g['invoices'] as $inv): 
                        $inv_applicable = $inv['billed'] - $inv['discount'];
                        $inv_balance = $inv_applicable - $inv['paid'];
                    ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td colspan="3"></td>
                        <td class="py-1.5 px-2 text-right text-gray-700"><?= format_currency($inv['billed']) ?></td>
                        <td class="py-1.5 px-2 text-right text-orange-600"><?= format_currency($inv['discount']) ?></td>
                        <td class="py-1.5 px-2 text-right text-gray-700"><?= format_currency($inv_applicable) ?></td>
                        <td class="py-1.5 px-2 text-right text-green-600"><?= format_currency($inv['paid']) ?></td>
                        <td class="py-1.5 px-2 text-right <?= $inv_balance > 0 ? 'text-red-600 font-medium' : 'text-green-600' ?>"><?= format_currency($inv_balance) ?></td>
                        <td class="py-1.5 px-2 text-center text-gray-600"><?= sanitize($inv['term'] ?? '') ?></td>
                        <td class="py-1.5 px-2 text-gray-600">#<?= sanitize($inv['invoice_no'] ?? '') ?></td>
                        <td class="py-1.5 px-2 text-center">
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full <?= $inv['payment_status'] == 'paid' ? 'bg-green-100 text-green-700' : ($inv['payment_status'] == 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
                                <?= ucfirst($inv['payment_status'] ?? 'N/A') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif ($tab == 'collection'): ?>
    <?php
    $classes_list = db_get_all("SELECT c.*, 
        (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count,
        (SELECT COALESCE(SUM(total_amount), 0) FROM transactions t JOIN students s ON t.student_id = s.id WHERE s.class_id = c.id) as total_billed,
        (SELECT COALESCE(SUM(discount), 0) FROM transactions t JOIN students s ON t.student_id = s.id WHERE s.class_id = c.id) as total_discount,
        (SELECT COALESCE(SUM(paid_amount), 0) FROM transactions t JOIN students s ON t.student_id = s.id WHERE s.class_id = c.id) as total_paid,
        (SELECT COALESCE(SUM(total_amount - discount - paid_amount), 0) FROM transactions t JOIN students s ON t.student_id = s.id WHERE s.class_id = c.id) as total_due
        FROM classes c ORDER BY c.name");
    ?>
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Fee collection overview by class</p>
        <a href="generate-bills.php" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 transition flex items-center gap-2">
            <i class="fas fa-file-invoice"></i> Generate Bills
        </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($classes_list)): ?>
        <div class="col-span-full bg-white rounded-xl border border-gray-200 p-12 text-center">
            <i class="fas fa-school text-5xl text-gray-300 mb-4 block"></i>
            <p class="text-gray-500">No fee collection data available.</p>
        </div>
        <?php else: ?>
        <?php foreach ($classes_list as $c): 
            $applicable = $c['total_billed'] - $c['total_discount'];
            $pct = $applicable > 0 ? round($c['total_paid'] / $applicable * 100) : 0;
            $bar_color = $pct >= 100 ? 'bg-green-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-red-500');
            $label_color = $pct >= 100 ? 'text-green-600' : ($pct >= 50 ? 'text-amber-600' : 'text-red-600');
        ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900"><?= sanitize($c['name']) ?></h3>
                <span class="text-xs bg-teal-100 text-teal-700 font-semibold px-2.5 py-1 rounded-full"><?= $c['student_count'] ?> students</span>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total Billed</span>
                    <span class="font-semibold text-gray-900"><?= format_currency($c['total_billed']) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Discounts</span>
                    <span class="font-semibold text-orange-600"><?= format_currency($c['total_discount']) ?></span>
                </div>
                <div class="flex justify-between text-sm pt-1 border-t border-dashed border-gray-200">
                    <span class="font-medium text-gray-700">Total Applicable</span>
                    <span class="font-bold text-gray-900"><?= format_currency($applicable) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total Paid</span>
                    <span class="font-semibold text-green-600"><?= format_currency($c['total_paid']) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total Due</span>
                    <span class="font-semibold text-red-600"><?= format_currency($c['total_due']) ?></span>
                </div>
                <div class="pt-2">
                    <div class="flex items-center justify-between text-sm mb-1.5">
                        <span class="text-gray-500 font-medium">Collection Rate</span>
                        <span class="font-bold <?= $label_color ?>"><?= $pct ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div class="h-full rounded-full transition-all <?= $bar_color ?>" style="width: <?= min($pct, 100) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php elseif ($tab == 'transactions'): ?>
    <?php
    $transactions = db_get_all("SELECT t.*, s.parent_name, s.enrollment_id, c.name as class_name
        FROM transactions t
        LEFT JOIN students s ON t.student_id = s.id
        LEFT JOIN classes c ON s.class_id = c.id
        ORDER BY t.created_at DESC LIMIT 30");

    $tx_summary = db_get_row("SELECT
        COALESCE(SUM(total_amount),0) as billed,
        COALESCE(SUM(discount),0) as discount,
        COALESCE(SUM(total_amount - discount),0) as applicable,
        COALESCE(SUM(paid_amount),0) as paid,
        COALESCE(SUM(total_amount - discount - paid_amount),0) as due
        FROM transactions") ?? ['billed'=>0,'discount'=>0,'applicable'=>0,'paid'=>0,'due'=>0];
    $tx_pct = $tx_summary['applicable'] > 0 ? round($tx_summary['paid'] / $tx_summary['applicable'] * 100) : 0;
    $tx_bar = $tx_pct >= 100 ? 'bg-green-500' : ($tx_pct >= 50 ? 'bg-amber-500' : 'bg-red-500');
    $tx_label = $tx_pct >= 100 ? 'text-green-600' : ($tx_pct >= 50 ? 'text-amber-600' : 'text-red-600');
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Billed</p><p class="text-xl font-bold text-gray-900 mt-1"><?= format_currency($tx_summary['billed']) ?></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Discounts</p><p class="text-xl font-bold text-orange-600 mt-1"><?= format_currency($tx_summary['discount']) ?></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Applicable</p><p class="text-xl font-bold text-gray-900 mt-1"><?= format_currency($tx_summary['applicable']) ?></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Collected</p><p class="text-xl font-bold text-green-600 mt-1"><?= format_currency($tx_summary['paid']) ?></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Due</p><p class="text-xl font-bold text-red-600 mt-1"><?= format_currency($tx_summary['due']) ?></p></div>
    </div>
    <?php if ($tx_summary['applicable'] > 0): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="flex items-center justify-between mb-1.5"><span class="text-sm font-medium text-gray-700">Overall Collection Progress</span><span class="text-sm font-bold <?= $tx_label ?>"><?= $tx_pct ?>%</span></div>
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden"><div class="h-full rounded-full <?= $tx_bar ?>" style="width: <?= min($tx_pct, 100) ?>%"></div></div>
    </div>
    <?php endif; ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Invoice</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Student</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Class</th>
                    <th class="text-right py-3 px-4 font-medium text-gray-500">Billed</th>
                    <th class="text-right py-3 px-4 font-medium text-gray-500">Disc.</th>
                    <th class="text-right py-3 px-4 font-medium text-gray-500">Applicable</th>
                    <th class="text-right py-3 px-4 font-medium text-gray-500">Paid</th>
                    <th class="text-right py-3 px-4 font-medium text-gray-500">Due</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Progress</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Status</th>
                    <th class="text-center py-3 px-4 font-medium text-gray-500">Due Date</th>
                    <th class="text-left py-3 px-4 font-medium text-gray-500">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr><td colspan="12" class="py-12 text-center text-gray-400">No transactions found.</td></tr>
                <?php else: ?>
                <?php foreach ($transactions as $t): 
                    $applicable = $t['total_amount'] - ($t['discount'] ?? 0);
                    $pct = $applicable > 0 ? round($t['paid_amount'] / $applicable * 100) : 0;
                    $bar_color = $pct >= 100 ? 'bg-green-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-red-500');
                    $is_overdue = $t['due_date'] && $t['due_date'] < date('Y-m-d') && $t['payment_status'] !== 'paid';
                ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50 <?= $is_overdue ? 'bg-red-50/50' : '' ?>">
                    <td class="py-3 px-4 font-medium text-gray-900">#<?= sanitize($t['invoice_no']) ?></td>
                    <td class="py-3 px-4"><?= sanitize($t['parent_name'] ?? 'N/A') ?></td>
                    <td class="py-3 px-4"><?= sanitize($t['class_name'] ?? 'N/A') ?></td>
                    <td class="py-3 px-4 text-right"><?= format_currency($t['total_amount']) ?></td>
                    <td class="py-3 px-4 text-right text-orange-600 font-medium"><?= format_currency($t['discount'] ?? 0) ?></td>
                    <td class="py-3 px-4 text-right font-medium text-gray-900"><?= format_currency($applicable) ?></td>
                    <td class="py-3 px-4 text-right text-green-600 font-medium"><?= format_currency($t['paid_amount']) ?></td>
                    <td class="py-3 px-4 text-right text-red-600 font-medium"><?= format_currency($applicable - $t['paid_amount']) ?></td>
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2 min-w-[90px]">
                            <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-[60px]">
                                <div class="h-full rounded-full <?= $bar_color ?>" style="width: <?= min($pct, 100) ?>%"></div>
                            </div>
                            <span class="text-xs font-bold <?= $pct >= 100 ? 'text-green-600' : ($pct >= 50 ? 'text-amber-600' : 'text-red-600') ?>"><?= $pct ?>%</span>
                        </div>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="text-xs font-semibold px-2 py-1 rounded-full <?= $t['payment_status'] == 'paid' ? 'bg-green-100 text-green-700' : ($t['payment_status'] == 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
                            <?= ucfirst($t['payment_status']) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($t['due_date']): ?>
                            <span class="text-xs font-medium <?= $is_overdue ? 'text-red-600 font-bold' : 'text-gray-600' ?>">
                                <?= format_date($t['due_date']) ?>
                                <?php if ($is_overdue): ?><i class="fas fa-exclamation-circle ml-0.5"></i><?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4"><?= $t['payment_date'] ? format_date($t['payment_date']) : format_date($t['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($tab == 'reminders'): ?>
    <?php
    $due_payments = db_get_all("SELECT t.*, s.parent_name, s.parent_phone, s.enrollment_id, c.name as class_name
        FROM transactions t
        LEFT JOIN students s ON t.student_id = s.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE t.payment_status IN ('pending','partial') AND t.due_amount > 0
        ORDER BY COALESCE(t.due_date, t.created_at) ASC LIMIT 20");
    ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <?php if (empty($due_payments)): ?>
        <div class="text-center py-8 text-gray-400">
            <i class="fas fa-check-circle text-4xl text-green-400 mb-3 block"></i>
            <p>No pending payments. All fees are up to date!</p>
        </div>
        <?php else: ?>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Reminders</h3>
        <div class="space-y-4">
            <?php foreach ($due_payments as $d): 
                $is_overdue = $d['due_date'] && $d['due_date'] < date('Y-m-d');
            ?>
            <div class="flex items-center justify-between p-4 rounded-lg border <?= $is_overdue ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200' ?>">
                <div>
                    <p class="font-medium text-gray-900"><?= sanitize($d['parent_name']) ?></p>
                    <p class="text-sm text-gray-500"><?= sanitize($d['enrollment_id']) ?> | <?= sanitize($d['class_name']) ?> | Due: <?= format_currency($d['due_amount']) ?></p>
                    <?php if ($d['due_date']): ?>
                    <p class="text-xs <?= $is_overdue ? 'text-red-600 font-bold' : 'text-gray-500' ?> mt-0.5">
                        <i class="fas fa-calendar mr-0.5"></i> Due: <?= format_date($d['due_date']) ?>
                        <?php if ($is_overdue): ?><span class="ml-1">(Overdue)</span><?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="flex gap-2">
                    <a href="#" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-600 transition">
                        <i class="fas fa-paper-plane mr-1"></i> Send Reminder
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif ($tab == 'gateways'): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
        <i class="fas fa-cog text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500 mb-4">Configure M-Pesa and Bank Transfer payment gateways.</p>
        <a href="gateway-settings.php" class="bg-teal-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition inline-flex items-center gap-2">
            <i class="fas fa-external-link-alt"></i> Open Gateway Settings
        </a>
    </div>

    <?php elseif ($tab == 'verification'): ?>
    <?php
    $pending_v = db_get_all("SELECT t.*, s.parent_name, s.enrollment_id, c.name as class_name
        FROM transactions t 
        LEFT JOIN students s ON t.student_id = s.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE t.payment_method IS NOT NULL AND t.verified_by IS NULL
        ORDER BY t.created_at DESC");
    ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Pending Verifications</h3>
            <a href="verify-payment.php" class="text-sm text-teal-600 hover:underline">Full Verification Page <i class="fas fa-arrow-right ml-1"></i></a>
        </div>
        <?php if (empty($pending_v)): ?>
        <div class="p-12 text-center text-gray-400">
            <i class="fas fa-check-circle text-4xl text-green-400 mb-3 block"></i>
            <p>All payments verified!</p>
        </div>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200 bg-gray-50/50">
                <th class="text-left py-3 px-4 font-medium text-gray-500">Invoice</th>
                <th class="text-left py-3 px-4 font-medium text-gray-500">Student</th>
                <th class="text-right py-3 px-4 font-medium text-gray-500">Amount</th>
                <th class="text-left py-3 px-4 font-medium text-gray-500">Method</th>
                <th class="text-left py-3 px-4 font-medium text-gray-500">Txn ID</th>
                <th class="text-center py-3 px-4 font-medium text-gray-500">Action</th>
            </tr></thead>
            <tbody>
                <?php foreach ($pending_v as $p): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium">#<?= sanitize($p['invoice_no']) ?></td>
                    <td class="py-3 px-4"><?= sanitize($p['parent_name'] ?? 'N/A') ?></td>
                    <td class="py-3 px-4 text-right font-medium text-green-600"><?= format_currency($p['paid_amount']) ?></td>
                    <td class="py-3 px-4">
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $p['payment_method'] == 'mpesa' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
                            <?= ucfirst(str_replace('_', ' ', $p['payment_method'])) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-xs text-gray-600"><?= sanitize($p['transaction_id'] ?? '—') ?></td>
                    <td class="py-3 px-4 text-center">
                        <a href="verify-payment.php" class="text-teal-600 hover:underline text-sm">Verify</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

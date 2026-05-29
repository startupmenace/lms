<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Leave Management';
$tab = $_GET['tab'] ?? 'my-leave';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'apply') {
        $from = $_POST['from_date'];
        $to = $_POST['to_date'];
        $leave_type_id = (int)$_POST['leave_type_id'];
        $total_days = floor((strtotime($to) - strtotime($from)) / 86400) + 1;

        if ($total_days < 1) { set_flash('error', 'Invalid date range'); redirect('?tab=apply'); }

        // Check balance
        $lt = db_get_row("SELECT * FROM leave_types WHERE id=?", [$leave_type_id]);
        $used = db_get_row("SELECT COALESCE(SUM(total_days),0) as used FROM leave_applications WHERE user_id=? AND leave_type_id=? AND status IN ('approved','pending') AND YEAR(from_date)=YEAR(?)", [get_user_id(), $leave_type_id, $from])['used'];
        $remaining = $lt['max_days_per_year'] - $used;

        if ($total_days > $remaining) {
            set_flash('error', "Insufficient balance. You have $remaining days remaining for {$lt['name']}.");
            redirect('?tab=apply');
        }

        db_insert("INSERT INTO leave_applications (user_id,leave_type_id,reason,from_date,to_date,total_days) VALUES (?,?,?,?,?,?)", [
            get_user_id(), $leave_type_id, $_POST['reason'], $from, $to, $total_days
        ]);
        set_flash('success', 'Leave application submitted');
        redirect('?tab=my-leave');
    }

    if ($action === 'cancel') {
        $id = (int)$_POST['id'];
        $app = db_get_row("SELECT * FROM leave_applications WHERE id=? AND user_id=? AND status='pending'", [$id, get_user_id()]);
        if ($app) {
            db_query("UPDATE leave_applications SET status='cancelled' WHERE id=?", [$id]);
            set_flash('success', 'Leave cancelled');
        }
        redirect('?tab=my-leave');
    }

    if ($action === 'review') {
        $id = (int)$_POST['id'];
        $status = $_POST['status'];
        db_query("UPDATE leave_applications SET status=?, reviewed_by=?, review_notes=?, reviewed_at=NOW() WHERE id=?", [
            $status, get_user_id(), $_POST['review_notes'] ?? null, $id
        ]);
        set_flash('success', 'Leave ' . $status);
        redirect('?tab=all');
    }

    if ($action === 'add_type') {
        db_insert("INSERT INTO leave_types (name,description,max_days_per_year,color) VALUES (?,?,?,?)", [
            $_POST['name'], $_POST['description'] ?? null, (int)$_POST['max_days'], $_POST['color'] ?? '#0d9488'
        ]);
        set_flash('success', 'Leave type added');
        redirect('?tab=types');
    }
    if ($action === 'edit_type') {
        db_query("UPDATE leave_types SET name=?, description=?, max_days_per_year=?, color=?, is_active=? WHERE id=?", [
            $_POST['name'], $_POST['description'] ?? null, (int)$_POST['max_days'], $_POST['color'] ?? '#0d9488',
            (int)$_POST['is_active'], (int)$_POST['id']
        ]);
        set_flash('success', 'Leave type updated');
        redirect('?tab=types');
    }
    if ($action === 'delete_type') {
        db_query("DELETE FROM leave_types WHERE id=?", [(int)$_POST['id']]);
        set_flash('success', 'Leave type deleted');
        redirect('?tab=types');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                <i class="fas fa-calendar-alt text-teal-600 mr-2"></i> Leave Management
            </h1>
            <p class="text-gray-500 text-sm mt-1">Apply for leave and manage staff leave requests</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="?tab=my-leave" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='my-leave'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-list mr-2"></i>My Leave
            </a>
            <a href="?tab=apply" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='apply'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-paper-plane mr-2"></i>Apply
            </a>
            <?php if (has_role('admin')): ?>
            <a href="?tab=all" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='all'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-users mr-2"></i>All Applications
            </a>
            <a href="?tab=types" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='types'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-tags mr-2"></i>Leave Types
            </a>
            <?php endif; ?>
        </div>
        <div class="p-5">
            <?php
            $tab_file = __DIR__ . '/_' . $tab . '.php';
            if (file_exists($tab_file)) include $tab_file;
            else echo '<p class="text-gray-500 text-center py-8">Tab not found.</p>';
            ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

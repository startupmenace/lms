<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('timetable');

$page_title = 'Timetable';
$tab = $_GET['tab'] ?? 'view';

$day_names = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$day_short = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Periods ---
    if ($action === 'add_period') {
        db_insert("INSERT INTO timetable_periods (name,start_time,end_time,sort_order) VALUES (?,?,?,?)", [
            $_POST['name'], $_POST['start_time'], $_POST['end_time'], (int)($_POST['sort_order'] ?? 0)
        ]);
        set_flash('success', 'Period added');
        redirect('?tab=periods');
    }
    if ($action === 'edit_period') {
        db_query("UPDATE timetable_periods SET name=?,start_time=?,end_time=?,sort_order=?,is_active=? WHERE id=?", [
            $_POST['name'], $_POST['start_time'], $_POST['end_time'], (int)($_POST['sort_order'] ?? 0),
            (int)$_POST['is_active'], (int)$_POST['id']
        ]);
        set_flash('success', 'Period updated');
        redirect('?tab=periods');
    }
    if ($action === 'delete_period') {
        db_query("DELETE FROM timetable_periods WHERE id=?", [(int)$_POST['id']]);
        set_flash('success', 'Period deleted');
        redirect('?tab=periods');
    }

    // --- Entries ---
    if ($action === 'add_entry') {
        $existing = db_get_row("SELECT id FROM timetable_entries WHERE day_of_week=? AND period_id=? AND class_id=? AND academic_year=?", [
            (int)$_POST['day_of_week'], (int)$_POST['period_id'], (int)$_POST['class_id'], $_POST['academic_year'] ?? date('Y')
        ]);
        $subject_id = (int)($_POST['subject_id'] ?? 0) ?: null;
        if ($existing) {
            db_query("UPDATE timetable_entries SET subject_id=?, teacher_id=?, room=?, is_active=? WHERE id=?", [
                $subject_id, (int)($_POST['teacher_id'] ?? 0) ?: null, $_POST['room'] ?? null,
                (int)$_POST['is_active'], $existing['id']
            ]);
            set_flash('success', 'Timetable entry updated');
        } else {
            db_insert("INSERT INTO timetable_entries (day_of_week,period_id,class_id,subject_id,teacher_id,room,academic_year,is_active,created_by) VALUES (?,?,?,?,?,?,?,?,?)", [
                (int)$_POST['day_of_week'], (int)$_POST['period_id'], (int)$_POST['class_id'],
                $subject_id, (int)($_POST['teacher_id'] ?? 0) ?: null, $_POST['room'] ?? null,
                $_POST['academic_year'] ?? date('Y'), (int)$_POST['is_active'], get_user_id()
            ]);
            set_flash('success', 'Timetable entry added');
        }
        redirect('?tab=entries');
    }
    if ($action === 'edit_entry') {
        db_query("UPDATE timetable_entries SET day_of_week=?,period_id=?,class_id=?,subject_id=?,teacher_id=?,room=?,is_active=? WHERE id=?", [
            (int)$_POST['day_of_week'], (int)$_POST['period_id'], (int)$_POST['class_id'],
            (int)($_POST['subject_id'] ?? 0) ?: null, (int)($_POST['teacher_id'] ?? 0) ?: null,
            $_POST['room'] ?? null, (int)$_POST['is_active'], (int)$_POST['id']
        ]);
        set_flash('success', 'Entry updated');
        redirect('?tab=entries');
    }
    if ($action === 'delete_entry') {
        db_query("DELETE FROM timetable_entries WHERE id=?", [(int)$_POST['id']]);
        set_flash('success', 'Entry deleted');
        redirect('?tab=entries');
    }

    // --- Disputes ---
    if ($action === 'raise_dispute') {
        $existing = db_get_row("SELECT id FROM timetable_disputes WHERE entry_id=? AND teacher_id=? AND status='pending'", [(int)$_POST['entry_id'], get_user_id()]);
        if ($existing) {
            set_flash('error', 'You already have a pending dispute for this entry');
        } else {
            db_insert("INSERT INTO timetable_disputes (entry_id,teacher_id,reason) VALUES (?,?,?)", [
                (int)$_POST['entry_id'], get_user_id(), $_POST['reason']
            ]);
            set_flash('success', 'Dispute raised. Admin will review it.');
        }
        redirect('?tab=disputes');
    }
    if ($action === 'resolve_dispute') {
        db_query("UPDATE timetable_disputes SET status=?, resolved_by=?, resolution_notes=?, resolved_at=NOW() WHERE id=?", [
            $_POST['status'], get_user_id(), $_POST['resolution_notes'] ?? null, (int)$_POST['id']
        ]);
        set_flash('success', 'Dispute ' . $_POST['status']);
        redirect('?tab=disputes');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                <i class="fas fa-table text-teal-600 mr-2"></i> Timetable
            </h1>
            <p class="text-gray-500 text-sm mt-1">School timetable — periods, classes, and teacher assignments</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="?tab=view" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='view'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-calendar-week mr-2"></i>Timetable
            </a>
            <?php if (has_role('admin')): ?>
            <a href="?tab=entries" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='entries'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-edit mr-2"></i>Edit Entries
            </a>
            <a href="?tab=periods" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='periods'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-clock mr-2"></i>Periods
            </a>
            <?php endif; ?>
            <a href="?tab=disputes" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='disputes'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-exclamation-triangle mr-2"></i>Disputes
            </a>
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

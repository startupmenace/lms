<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('classes');

$page_title = 'Classes';
$action = $_GET['action'] ?? '';
$class_id = (int)($_GET['id'] ?? 0);
$tab = $_GET['tab'] ?? 'overview';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_post = $_POST['action'] ?? '';

    // ── Assign teacher ──
    if ($action_post === 'assign_teacher') {
        $cid = (int)$_POST['class_id'];
        $tid = (int)$_POST['teacher_id'];
        $sid = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;
        $role = $_POST['role'] ?? 'teacher';

        $params = [$cid, $tid];
        if ($sid) {
            $params[] = $sid;
            $exists = db_get_row("SELECT id FROM class_teachers WHERE class_id=? AND teacher_id=? AND subject_id=?", $params);
        } else {
            $exists = db_get_row("SELECT id FROM class_teachers WHERE class_id=? AND teacher_id=? AND subject_id IS NULL", $params);
        }
        if (!$exists) {
            db_insert("INSERT INTO class_teachers (class_id, teacher_id, subject_id, role) VALUES (?,?,?,?)", [$cid, $tid, $sid, $role]);
            set_flash('success', 'Teacher assigned.');
        } else {
            set_flash('error', 'This teacher is already assigned.');
        }
        redirect("?id=$cid&tab=teachers");
    }

    // ── Remove teacher ──
    if ($action_post === 'remove_teacher') {
        db_query("DELETE FROM class_teachers WHERE id=?", [(int)$_POST['id']]);
        set_flash('success', 'Teacher removed.');
        redirect("?id=" . (int)$_POST['class_id'] . "&tab=teachers");
    }

    // ── Set class teacher ──
    if ($action_post === 'set_class_teacher') {
        $cid = (int)$_POST['class_id'];
        $tid = (int)$_POST['teacher_id'];
        db_query("UPDATE class_teachers SET role='teacher' WHERE class_id=? AND role='class_teacher'", [$cid]);
        $ct = db_get_row("SELECT id FROM class_teachers WHERE class_id=? AND teacher_id=?", [$cid, $tid]);
        if ($ct) {
            db_query("UPDATE class_teachers SET role='class_teacher' WHERE id=?", [$ct['id']]);
        } else {
            db_insert("INSERT INTO class_teachers (class_id, teacher_id, role) VALUES (?,?,'class_teacher')", [$cid, $tid]);
        }
        set_flash('success', 'Class teacher updated.');
        redirect("?id=$cid&tab=teachers");
    }

    // ── Assign subjects to class ──
    if ($action_post === 'save_subjects') {
        $cid = (int)$_POST['class_id'];
        db_query("DELETE FROM class_subjects WHERE class_id=?", [$cid]);
        $subjects = $_POST['subjects'] ?? [];
        foreach ($subjects as $sid) {
            db_insert("INSERT INTO class_subjects (class_id, subject_id) VALUES (?,?)", [$cid, (int)$sid]);
        }
        set_flash('success', 'Subjects updated.');
        redirect("?id=$cid&tab=subjects");
    }

    // ── Create homework ──
    if ($action_post === 'create_homework') {
        $cid = (int)$_POST['class_id'];
        db_insert("INSERT INTO homework (class_id, subject_id, teacher_id, title, description, due_date, submission_type) VALUES (?,?,?,?,?,?,?)", [
            $cid,
            !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null,
            get_user_id(),
            sanitize($_POST['title']),
            sanitize($_POST['description'] ?? ''),
            $_POST['due_date'] ?: null,
            $_POST['submission_type'] ?? 'digital'
        ]);
        set_flash('success', 'Homework created.');
        redirect("?id=$cid&tab=homework");
    }

    // ── Submit homework (student side) ──
    if ($action_post === 'submit_homework') {
        $hid = (int)$_POST['homework_id'];
        $student_id = (int)$_POST['student_id'];
        $text = sanitize($_POST['submission_text'] ?? '');
        $exists = db_get_row("SELECT id FROM homework_submissions WHERE homework_id=? AND student_id=?", [$hid, $student_id]);
        if ($exists) {
            db_query("UPDATE homework_submissions SET submission_text=? WHERE id=?", [$text, $exists['id']]);
        } else {
            db_insert("INSERT INTO homework_submissions (homework_id, student_id, submission_text) VALUES (?,?,?)", [$hid, $student_id, $text]);
        }
        set_flash('success', 'Homework submitted.');
        redirect("?id=" . (int)$_POST['class_id'] . "&tab=homework");
    }

    // ── Add resource link ──
    if ($action_post === 'add_resource') {
        $cid = (int)$_POST['class_id'];
        db_insert("INSERT INTO class_resources (class_id, title, description, url, resource_type, uploaded_by) VALUES (?,?,?,?,?,?)", [
            $cid, sanitize($_POST['title']), sanitize($_POST['description'] ?? ''),
            sanitize($_POST['url']), $_POST['resource_type'] ?? 'link', get_user_id()
        ]);
        set_flash('success', 'Resource added.');
        redirect("?id=$cid&tab=resources");
    }

    // ── Delete resource ──
    if ($action_post === 'delete_resource') {
        $cid = (int)$_POST['class_id'];
        db_query("DELETE FROM class_resources WHERE id=?", [(int)$_POST['id']]);
        set_flash('success', 'Resource deleted.');
        redirect("?id=$cid&tab=resources");
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <?php if ($class_id && $action !== 'list'): ?>
        <?php include __DIR__ . '/_view.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/_list.php'; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

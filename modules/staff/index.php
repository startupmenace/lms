<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin', 'teacher');

$page_title = 'Staff Manager';
$tab = $_GET['tab'] ?? 'details';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_account') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? 'password';
        $role = $_POST['role'] ?? 'teacher';
        $phone = trim($_POST['phone'] ?? '');

        $existing = db_get_row("SELECT id FROM users WHERE email=?", [$email]);
        if ($existing) {
            set_flash('error', 'A user with this email already exists');
            redirect('?tab=accounts');
        }

        $user_id = db_insert("INSERT INTO users (username, email, password, full_name, phone, role, is_active) VALUES (?,?,?,?,?,?,1)", [
            strtolower(str_replace(' ', '.', $full_name)), $email, password_hash($password, PASSWORD_DEFAULT), $full_name, $phone, $role
        ]);
        if ($user_id) {
            set_flash('success', 'Staff account created for ' . $full_name . ' (default password: ' . $password . ')');
        } else {
            set_flash('error', 'Failed to create account');
        }
        redirect('?tab=accounts');
    }

    if ($action === 'edit_account') {
        $id = (int)$_POST['id'];
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'teacher';
        $phone = trim($_POST['phone'] ?? '');
        $is_active = (int)$_POST['is_active'];

        $existing = db_get_row("SELECT id FROM users WHERE email=? AND id!=?", [$email, $id]);
        if ($existing) {
            set_flash('error', 'Another user already has this email');
            redirect('?tab=accounts');
        }

        db_query("UPDATE users SET full_name=?, email=?, phone=?, role=?, is_active=? WHERE id=?", [$full_name, $email, $phone, $role, $is_active, $id]);

        $password = $_POST['password'] ?? '';
        if (!empty($password)) {
            db_query("UPDATE users SET password=? WHERE id=?", [password_hash($password, PASSWORD_DEFAULT), $id]);
            set_flash('success', 'Account updated and password reset');
        } else {
            set_flash('success', 'Account updated');
        }
        redirect('?tab=accounts');
    }

    if ($action === 'delete_account') {
        $id = (int)$_POST['id'];
        if ($id === get_user_id()) {
            set_flash('error', 'You cannot delete your own account');
        } else {
            db_query("UPDATE users SET is_active=0 WHERE id=? AND role!='admin'", [$id]);
            set_flash('success', 'Account deactivated');
        }
        redirect('?tab=accounts');
    }

    if ($action === 'save_details') {
        $user_id = (int)$_POST['user_id'];

        // Update user phone
        if (!empty($_POST['phone'])) {
            db_query("UPDATE users SET phone=? WHERE id=?", [$_POST['phone'], $user_id]);
        }

        // Upsert staff_details
        $existing = db_get_row("SELECT id FROM staff_details WHERE user_id=?", [$user_id]);
        $details = [
            'employee_id' => $_POST['employee_id'] ?? null,
            'date_of_birth' => $_POST['date_of_birth'] ?: null,
            'gender' => $_POST['gender'] ?: null,
            'address' => $_POST['address'] ?: null,
            'qualification' => $_POST['qualification'] ?: null,
            'date_of_joining' => $_POST['date_of_joining'] ?: null,
            'kra_pin' => strtoupper($_POST['kra_pin'] ?? '') ?: null,
            'bank_name' => $_POST['bank_name'] ?: null,
            'bank_account' => $_POST['bank_account'] ?: null,
            'bank_branch' => $_POST['bank_branch'] ?: null,
            'sha_number' => $_POST['sha_number'] ?: null,
            'nssf_number' => $_POST['nssf_number'] ?: null,
            'tsc_number' => $_POST['tsc_number'] ?: null,
        ];

        if ($existing) {
            $sql = "UPDATE staff_details SET employee_id=?, date_of_birth=?, gender=?, address=?, qualification=?, date_of_joining=?, kra_pin=?, bank_name=?, bank_account=?, bank_branch=?, sha_number=?, nssf_number=?, tsc_number=? WHERE user_id=?";
            db_query($sql, array_merge(array_values($details), [$user_id]));
        } else {
            $sql = "INSERT INTO staff_details (user_id, employee_id, date_of_birth, gender, address, qualification, date_of_joining, kra_pin, bank_name, bank_account, bank_branch, sha_number, nssf_number, tsc_number) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            db_insert($sql, array_merge([$user_id], array_values($details)));
        }

        // Next of kin
        if (isset($_POST['kin_name'])) {
            // Delete removed kin
            $keep_ids = [];
            foreach (($_POST['kin_id'] ?? []) as $i => $kid) {
                $kid = (int)$kid;
                if ($kid && !empty($_POST['kin_name'][$i])) $keep_ids[] = $kid;
            }
            if ($keep_ids) {
                db_query("DELETE FROM staff_next_of_kin WHERE user_id=? AND id NOT IN (" . implode(',', $keep_ids) . ")", [$user_id]);
            } else {
                db_query("DELETE FROM staff_next_of_kin WHERE user_id=?", [$user_id]);
            }

            foreach (($_POST['kin_name'] ?? []) as $i => $name) {
                if (empty(trim($name))) continue;
                $kid = (int)($_POST['kin_id'][$i] ?? 0);
                $data = [
                    trim($name),
                    trim($_POST['kin_relation'][$i] ?? ''),
                    trim($_POST['kin_phone'][$i] ?? ''),
                    trim($_POST['kin_email'][$i] ?? ''),
                    (int)($_POST['kin_primary'][$i] ?? 0),
                ];
                if ($kid) {
                    db_query("UPDATE staff_next_of_kin SET name=?, relationship=?, phone=?, email=?, is_primary=? WHERE id=? AND user_id=?", array_merge($data, [$kid, $user_id]));
                } else {
                    db_insert("INSERT INTO staff_next_of_kin (user_id, name, relationship, phone, email, is_primary) VALUES (?,?,?,?,?,?)", array_merge([$user_id], $data));
                }
            }
        }

        // Beneficiaries
        if (isset($_POST['ben_name'])) {
            $keep_ids = [];
            foreach (($_POST['ben_id'] ?? []) as $i => $bid) {
                $bid = (int)$bid;
                if ($bid && !empty($_POST['ben_name'][$i])) $keep_ids[] = $bid;
            }
            if ($keep_ids) {
                db_query("DELETE FROM staff_beneficiaries WHERE user_id=? AND id NOT IN (" . implode(',', $keep_ids) . ")", [$user_id]);
            } else {
                db_query("DELETE FROM staff_beneficiaries WHERE user_id=?", [$user_id]);
            }

            foreach (($_POST['ben_name'] ?? []) as $i => $name) {
                if (empty(trim($name))) continue;
                $bid = (int)($_POST['ben_id'][$i] ?? 0);
                $data = [
                    trim($name),
                    trim($_POST['ben_relation'][$i] ?? ''),
                    trim($_POST['ben_phone'][$i] ?? ''),
                    (float)($_POST['ben_percentage'][$i] ?? 0),
                ];
                if ($bid) {
                    db_query("UPDATE staff_beneficiaries SET name=?, relationship=?, phone=?, percentage=? WHERE id=? AND user_id=?", array_merge($data, [$bid, $user_id]));
                } else {
                    db_insert("INSERT INTO staff_beneficiaries (user_id, name, relationship, phone, percentage) VALUES (?,?,?,?,?)", array_merge([$user_id], $data));
                }
            }
        }

        set_flash('success', 'Staff details saved');
        redirect('?tab=details&staff_id=' . $user_id);
    }

    if ($action === 'save_attendance') {
        $date = $_POST['date'];
        $user_ids = $_POST['user_id'] ?? [];

        foreach ($user_ids as $uid) {
            $uid = (int)$uid;
            $status = $_POST['status'][$uid] ?? 'present';
            $check_in = $_POST['check_in'][$uid] ?? null;
            $check_out = $_POST['check_out'][$uid] ?? null;
            $remarks = $_POST['remarks'][$uid] ?? null;

            $existing = db_get_row("SELECT id FROM staff_attendance WHERE user_id=? AND date=?", [$uid, $date]);
            if ($existing) {
                db_query("UPDATE staff_attendance SET status=?, check_in=?, check_out=?, remarks=?, marked_by=? WHERE id=?", [$status, $check_in ?: null, $check_out ?: null, $remarks ?: null, get_user_id(), $existing['id']]);
            } else {
                db_insert("INSERT INTO staff_attendance (user_id, date, status, check_in, check_out, remarks, marked_by) VALUES (?,?,?,?,?,?,?)", [$uid, $date, $status, $check_in ?: null, $check_out ?: null, $remarks ?: null, get_user_id()]);
            }
        }
        set_flash('success', 'Attendance saved for ' . date('d M Y', strtotime($date)));
        redirect('?tab=attendance&att_tab=mark&date=' . $date);
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                <i class="fas fa-users-cog text-teal-600 mr-2"></i> Staff Manager
            </h1>
            <p class="text-gray-500 text-sm mt-1">Manage teacher profiles, statutory details, next of kin, beneficiaries, and attendance</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="?tab=details" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='details'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-id-card mr-2"></i>Staff Details
            </a>
            <a href="?tab=attendance" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='attendance'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-clipboard-check mr-2"></i>Attendance
            </a>
            <?php if (has_role('admin')): ?>
            <a href="?tab=accounts" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab=='accounts'?'text-teal-600 border-b-2 border-teal-600':'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-user-plus mr-2"></i>Accounts
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

<?php
require_once __DIR__ . "/../../includes/session.php";
require_once __DIR__ . "/../../includes/functions.php";
require_login();

$page_title = "My Profile";
$user_id = get_user_id();
$user = db_get_row("SELECT * FROM users WHERE id=?", [$user_id]);
$role = $user["role"];

$staff = null; $student = null;
if ($role === "admin" || $role === "teacher") {
    $staff = db_get_row("SELECT * FROM staff_details WHERE user_id=?", [$user_id]);
    $kin = db_get_all("SELECT * FROM staff_next_of_kin WHERE user_id=?", [$user_id]);
    $beneficiaries = db_get_all("SELECT * FROM staff_beneficiaries WHERE user_id=? ORDER BY percentage DESC", [$user_id]);
} elseif ($role === "student") {
    $student = db_get_row("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id=c.id WHERE s.user_id=?", [$user_id]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "update_profile") {
        $full_name = trim($_POST["full_name"] ?? $user["full_name"]);
        $email = trim($_POST["email"] ?? $user["email"]);
        $phone = trim($_POST["phone"] ?? "");
        db_query("UPDATE users SET full_name=?, email=?, phone=? WHERE id=?", [$full_name, $email, $phone, $user_id]);
        $_SESSION["user_name"] = $full_name;

        $current_pass = $_POST["current_password"] ?? "";
        $new_pass = $_POST["new_password"] ?? "";
        $confirm_pass = $_POST["confirm_password"] ?? "";
        if (!empty($current_pass) && !empty($new_pass)) {
            if (password_verify($current_pass, $user["password"])) {
                if ($new_pass === $confirm_pass) {
                    db_query("UPDATE users SET password=? WHERE id=?", [password_hash($new_pass, PASSWORD_DEFAULT), $user_id]);
                    set_flash("success", "Profile updated and password changed");
                } else { set_flash("error", "New passwords do not match"); redirect("index.php"); }
            } else { set_flash("error", "Current password is incorrect"); redirect("index.php"); }
        } else { set_flash("success", "Profile updated"); }
        redirect("index.php");
    }

    if ($action === "save_staff_details" && ($role === "admin" || $role === "teacher")) {
        $details = [
            "employee_id" => $_POST["employee_id"] ?: null,
            "date_of_birth" => $_POST["date_of_birth"] ?: null,
            "gender" => $_POST["gender"] ?: null,
            "address" => $_POST["address"] ?: null,
            "qualification" => $_POST["qualification"] ?: null,
            "date_of_joining" => $_POST["date_of_joining"] ?: null,
            "kra_pin" => strtoupper($_POST["kra_pin"] ?? "") ?: null,
            "bank_name" => $_POST["bank_name"] ?: null,
            "bank_account" => $_POST["bank_account"] ?: null,
            "bank_branch" => $_POST["bank_branch"] ?: null,
            "sha_number" => $_POST["sha_number"] ?: null,
            "nssf_number" => $_POST["nssf_number"] ?: null,
            "tsc_number" => $_POST["tsc_number"] ?: null,
        ];
        if (!empty($_POST["phone"])) db_query("UPDATE users SET phone=? WHERE id=?", [$_POST["phone"], $user_id]);

        if ($staff) {
            $sql = "UPDATE staff_details SET employee_id=?, date_of_birth=?, gender=?, address=?, qualification=?, date_of_joining=?, kra_pin=?, bank_name=?, bank_account=?, bank_branch=?, sha_number=?, nssf_number=?, tsc_number=? WHERE user_id=?";
            db_query($sql, array_merge(array_values($details), [$user_id]));
        } else {
            $sql = "INSERT INTO staff_details (user_id, employee_id, date_of_birth, gender, address, qualification, date_of_joining, kra_pin, bank_name, bank_account, bank_branch, sha_number, nssf_number, tsc_number) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            db_insert($sql, array_merge([$user_id], array_values($details)));
        }

        db_query("DELETE FROM staff_next_of_kin WHERE user_id=?", [$user_id]);
        foreach (($_POST["kin_name"] ?? []) as $i => $name) {
            if (empty(trim($name ?? ""))) continue;
            db_insert("INSERT INTO staff_next_of_kin (user_id, name, relationship, phone, email, is_primary) VALUES (?,?,?,?,?,?)", [
                $user_id, trim($name), trim($_POST["kin_relation"][$i] ?? ""), trim($_POST["kin_phone"][$i] ?? ""),
                trim($_POST["kin_email"][$i] ?? ""), (int)($_POST["kin_primary"][$i] ?? 0)
            ]);
        }

        db_query("DELETE FROM staff_beneficiaries WHERE user_id=?", [$user_id]);
        foreach (($_POST["ben_name"] ?? []) as $i => $name) {
            if (empty(trim($name ?? ""))) continue;
            db_insert("INSERT INTO staff_beneficiaries (user_id, name, relationship, phone, percentage) VALUES (?,?,?,?,?)", [
                $user_id, trim($name), trim($_POST["ben_relation"][$i] ?? ""), trim($_POST["ben_phone"][$i] ?? ""),
                (float)($_POST["ben_percentage"][$i] ?? 0)
            ]);
        }
        set_flash("success", "Staff details saved");
        redirect("index.php");
    }
}
$user = db_get_row("SELECT * FROM users WHERE id=?", [$user_id]);
$header_file = ($role === "student") ? __DIR__ . "/../../includes/student-header.php" : __DIR__ . "/../../includes/header.php";
$footer_file = ($role === "student") ? __DIR__ . "/../../includes/student-footer.php" : __DIR__ . "/../../includes/footer.php";
include $header_file;
?>
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
            <i class="fas fa-user-circle text-teal-600 mr-2"></i> My Profile
        </h1>
        <p class="text-gray-500 text-sm mt-1">Manage your personal information, preferences, and account settings</p>
    </div>

    <!-- Profile Header -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-teal-500 to-teal-700 px-6 py-8">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-2xl font-bold text-white ring-4 ring-white/30">
                    <?= get_avatar($user["full_name"]) ?>
                </div>
                <div class="text-white">
                    <h2 class="text-xl font-bold"><?= sanitize($user["full_name"] ?? "User") ?></h2>
                    <p class="text-teal-100 text-sm capitalize"><?= $role ?></p>
                    <p class="text-teal-200 text-xs mt-0.5"><?= sanitize($user["email"] ?? "") ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Personal Info -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200">
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <i class="fas fa-user-edit text-teal-600"></i> Edit Personal Information
            </h3>
        </div>
        <form method="POST" class="p-5">
            <input type="hidden" name="action" value="update_profile">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" value="<?= sanitize($user["full_name"]) ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="<?= sanitize($user["email"]) ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="<?= sanitize($user["phone"] ?? "") ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>

                <div class="sm:col-span-2 border-t border-gray-200 pt-4 mt-2">
                    <h4 class="text-xs font-bold text-gray-700 mb-3"><i class="fas fa-lock text-gray-400 mr-1"></i> Change Password (leave blank to keep current)</h4>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">New Password</label>
                    <input type="password" name="new_password" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" name="confirm_password" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-teal-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <?php if ($role === "admin" || $role === "teacher"): ?>
    <!-- Staff Details -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200">
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <i class="fas fa-briefcase text-teal-600"></i> Employment & Statutory Details
            </h3>
        </div>
        <form method="POST" class="p-5">
            <input type="hidden" name="action" value="save_staff_details">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Employee ID</label>
                    <input type="text" name="employee_id" value="<?= sanitize($staff["employee_id"] ?? "") ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="<?= $staff["date_of_birth"] ?? "" ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Gender</label>
                    <select name="gender" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">-- Select --</option>
                        <option value="male" <?= ($staff["gender"]??"")=="male"?"selected":"" ?>>Male</option>
                        <option value="female" <?= ($staff["gender"]??"")=="female"?"selected":"" ?>>Female</option>
                        <option value="other" <?= ($staff["gender"]??"")=="other"?"selected":"" ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Qualification</label>
                    <input type="text" name="qualification" value="<?= sanitize($staff["qualification"] ?? "") ?>" placeholder="e.g. B.Ed, M.Ed" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Date of Joining</label>
                    <input type="date" name="date_of_joining" value="<?= $staff["date_of_joining"] ?? "" ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="<?= sanitize($user["phone"] ?? "") ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Address</label>
                    <textarea name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"><?= sanitize($staff["address"] ?? "") ?></textarea>
                </div>

                <div class="sm:col-span-2 lg:col-span-3 border-t border-gray-200 pt-4 mt-2">
                    <h4 class="text-xs font-bold text-gray-700 mb-3"><i class="fas fa-file-invoice text-gray-400 mr-1"></i> Statutory & Financial</h4>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">KRA PIN</label>
                    <input type="text" name="kra_pin" value="<?= sanitize($staff["kra_pin"] ?? "") ?>" maxlength="11" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">NSSF Number</label>
                    <input type="text" name="nssf_number" value="<?= sanitize($staff["nssf_number"] ?? "") ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">SHA Number</label>
                    <input type="text" name="sha_number" value="<?= sanitize($staff["sha_number"] ?? "") ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">TSC Number</label>
                    <input type="text" name="tsc_number" value="<?= sanitize($staff["tsc_number"] ?? "") ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Bank Name</label>
                    <input type="text" name="bank_name" value="<?= sanitize($staff["bank_name"] ?? "") ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Account Number</label>
                    <input type="text" name="bank_account" value="<?= sanitize($staff["bank_account"] ?? "") ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Bank Branch</label>
                    <input type="text" name="bank_branch" value="<?= sanitize($staff["bank_branch"] ?? "") ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>

            <!-- Next of Kin -->
            <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-gray-700"><i class="fas fa-users text-gray-400 mr-1"></i> Next of Kin</h4>
                    <button type="button" onclick="addKin()" class="text-teal-600 hover:text-teal-800 text-xs font-medium"><i class="fas fa-plus mr-1"></i> Add</button>
                </div>
                <div id="kin-container" class="space-y-2">
                    <?php foreach ($kin ?? [] as $k): ?>
                    <div class="kin-entry grid grid-cols-1 sm:grid-cols-5 gap-2 p-2 bg-gray-50 rounded-lg">
                        <input type="text" name="kin_name[]" value="<?= sanitize($k["name"]) ?>" placeholder="Name" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="text" name="kin_relation[]" value="<?= sanitize($k["relationship"]) ?>" placeholder="Relationship" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="text" name="kin_phone[]" value="<?= sanitize($k["phone"]) ?>" placeholder="Phone" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="email" name="kin_email[]" value="<?= sanitize($k["email"]) ?>" placeholder="Email" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <div class="flex items-center gap-1">
                            <label class="flex items-center gap-1 text-[10px] text-gray-500 cursor-pointer">
                                <input type="checkbox" name="kin_primary[]" value="1" <?= $k["is_primary"]?"checked":"" ?> class="rounded border-gray-300 text-teal-600"> Primary
                            </label>
                            <button type="button" onclick="this.closest('.kin-entry').remove()" class="text-red-500 hover:text-red-700 ml-auto"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="kin-template" class="hidden kin-entry grid grid-cols-1 sm:grid-cols-5 gap-2 p-2 bg-gray-50 rounded-lg">
                    <input type="text" name="kin_name[]" placeholder="Name" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <input type="text" name="kin_relation[]" placeholder="Relationship" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <input type="text" name="kin_phone[]" placeholder="Phone" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <input type="email" name="kin_email[]" placeholder="Email" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <div class="flex items-center gap-1">
                        <label class="flex items-center gap-1 text-[10px] text-gray-500 cursor-pointer">
                            <input type="checkbox" name="kin_primary[]" value="1" class="rounded border-gray-300 text-teal-600"> Primary
                        </label>
                        <button type="button" onclick="this.closest('.kin-entry').remove()" class="text-red-500 hover:text-red-700 ml-auto"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            </div>

            <!-- Beneficiaries -->
            <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-gray-700"><i class="fas fa-gift text-gray-400 mr-1"></i> Beneficiaries</h4>
                    <button type="button" onclick="addBeneficiary()" class="text-teal-600 hover:text-teal-800 text-xs font-medium"><i class="fas fa-plus mr-1"></i> Add</button>
                </div>
                <div id="beneficiary-container" class="space-y-2">
                    <div class="grid grid-cols-12 gap-2 mb-1 text-[10px] font-bold text-gray-500 px-2">
                        <div class="col-span-4">Name</div>
                        <div class="col-span-3">Relationship</div>
                        <div class="col-span-2">Phone</div>
                        <div class="col-span-2">%</div>
                        <div class="col-span-1"></div>
                    </div>
                    <?php foreach ($beneficiaries ?? [] as $b): ?>
                    <div class="beneficiary-entry grid grid-cols-12 gap-2 p-2 bg-gray-50 rounded-lg items-center">
                        <input type="text" name="ben_name[]" value="<?= sanitize($b["name"]) ?>" placeholder="Name" class="col-span-4 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="text" name="ben_relation[]" value="<?= sanitize($b["relationship"]) ?>" placeholder="Relationship" class="col-span-3 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="text" name="ben_phone[]" value="<?= sanitize($b["phone"]) ?>" placeholder="Phone" class="col-span-2 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="number" name="ben_percentage[]" value="<?= $b["percentage"] ?>" placeholder="%" step="0.01" min="0" max="100" class="col-span-2 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <button type="button" onclick="this.closest('.beneficiary-entry').remove()" class="col-span-1 text-red-500 hover:text-red-700 text-xs"><i class="fas fa-times"></i></button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="beneficiary-template" class="hidden beneficiary-entry grid grid-cols-12 gap-2 p-2 bg-gray-50 rounded-lg items-center">
                    <input type="text" name="ben_name[]" placeholder="Name" class="col-span-4 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <input type="text" name="ben_relation[]" placeholder="Relationship" class="col-span-3 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <input type="text" name="ben_phone[]" placeholder="Phone" class="col-span-2 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <input type="number" name="ben_percentage[]" placeholder="%" step="0.01" min="0" max="100" class="col-span-2 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    <button type="button" onclick="this.closest('.beneficiary-entry').remove()" class="col-span-1 text-red-500 hover:text-red-700 text-xs"><i class="fas fa-times"></i></button>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="bg-teal-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-save mr-1"></i> Save Staff Details
                </button>
            </div>
        </form>
    </div>
    <?php elseif ($role === "student" && $student): ?>
    <!-- Student Info -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200">
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <i class="fas fa-graduation-cap text-teal-600"></i> Student Information
            </h3>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <span class="text-[10px] text-gray-400 uppercase tracking-wider">Enrollment ID</span>
                <p class="text-gray-900 font-medium text-sm mt-0.5"><?= sanitize($student["enrollment_id"] ?? "N/A") ?></p>
            </div>
            <div>
                <span class="text-[10px] text-gray-400 uppercase tracking-wider">Class</span>
                <p class="text-gray-900 font-medium text-sm mt-0.5"><?= sanitize($student["class_name"] ?? "N/A") ?></p>
            </div>
            <div>
                <span class="text-[10px] text-gray-400 uppercase tracking-wider">Admission Date</span>
                <p class="text-gray-900 font-medium text-sm mt-0.5"><?= $student["admission_date"] ? format_date($student["admission_date"]) : "N/A" ?></p>
            </div>
            <div>
                <span class="text-[10px] text-gray-400 uppercase tracking-wider">Date of Birth</span>
                <p class="text-gray-900 font-medium text-sm mt-0.5"><?= $student["date_of_birth"] ? format_date($student["date_of_birth"]) : "N/A" ?></p>
            </div>
            <div>
                <span class="text-[10px] text-gray-400 uppercase tracking-wider">Gender</span>
                <p class="text-gray-900 font-medium text-sm mt-0.5"><?= ucfirst($student["gender"] ?? "N/A") ?></p>
            </div>
            <div>
                <span class="text-[10px] text-gray-400 uppercase tracking-wider">Blood Group</span>
                <p class="text-gray-900 font-medium text-sm mt-0.5"><?= sanitize($student["blood_group"] ?? "N/A") ?></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h4 class="font-bold text-gray-900 text-xs mb-3"><i class="fas fa-users text-teal-600 mr-1"></i> Parent Information</h4>
            <div class="space-y-2 text-sm">
                <div><span class="text-gray-500 text-xs">Name:</span> <span class="text-gray-900 font-medium"><?= sanitize($student["parent_name"] ?? "N/A") ?></span></div>
                <div><span class="text-gray-500 text-xs">Phone:</span> <span class="text-gray-900 font-medium"><?= sanitize($student["parent_phone"] ?? "N/A") ?></span></div>
                <div><span class="text-gray-500 text-xs">Email:</span> <span class="text-gray-900 font-medium"><?= sanitize($student["parent_email"] ?? "N/A") ?></span></div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h4 class="font-bold text-gray-900 text-xs mb-3"><i class="fas fa-shield-alt text-teal-600 mr-1"></i> Guardian Information</h4>
            <div class="space-y-2 text-sm">
                <div><span class="text-gray-500 text-xs">Name:</span> <span class="text-gray-900 font-medium"><?= sanitize($student["guardian_name"] ?? "N/A") ?></span></div>
                <div><span class="text-gray-500 text-xs">Phone:</span> <span class="text-gray-900 font-medium"><?= sanitize($student["guardian_phone"] ?? "N/A") ?></span></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function addKin() {
    var tmpl = document.getElementById("kin-template");
    if (!tmpl) return;
    var clone = tmpl.cloneNode(true);
    clone.id = "";
    clone.classList.remove("hidden");
    document.getElementById("kin-container").appendChild(clone);
}
function addBeneficiary() {
    var tmpl = document.getElementById("beneficiary-template");
    if (!tmpl) return;
    var clone = tmpl.cloneNode(true);
    clone.id = "";
    clone.classList.remove("hidden");
    document.getElementById("beneficiary-container").appendChild(clone);
}
</script>

<?php include $footer_file; ?>
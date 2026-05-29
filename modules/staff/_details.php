<?php
$teachers = db_get_all("SELECT u.*, sd.id as sid, sd.employee_id, sd.kra_pin, sd.bank_name, sd.bank_account, sd.bank_branch, sd.sha_number, sd.nssf_number, sd.tsc_number, sd.date_of_birth, sd.gender, sd.address, sd.qualification, sd.date_of_joining
    FROM users u
    LEFT JOIN staff_details sd ON u.id=sd.user_id
    WHERE u.role IN ('admin','teacher') AND u.is_active=1
    ORDER BY u.full_name");

$edit_user = null;
$edit_id = (int)($_GET['staff_id'] ?? 0);
if ($edit_id) {
    $edit_user = db_get_row("SELECT u.*, sd.* FROM users u LEFT JOIN staff_details sd ON u.id=sd.user_id WHERE u.id=?", [$edit_id]);
}

$kin = [];
$beneficiaries = [];
if ($edit_id) {
    $kin = db_get_all("SELECT * FROM staff_next_of_kin WHERE user_id=? ORDER BY is_primary DESC", [$edit_id]);
    $beneficiaries = db_get_all("SELECT * FROM staff_beneficiaries WHERE user_id=? ORDER BY percentage DESC", [$edit_id]);
}
?>
<div class="flex items-center justify-between mb-4">
    <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
        <i class="fas fa-id-card text-teal-600"></i> Staff Details
        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium"><?= count($teachers) ?></span>
    </h3>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Teacher List -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-3 bg-gray-50 border-b border-gray-200">
                <input type="text" id="staff-search" placeholder="Search staff..." class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                <?php foreach ($teachers as $t): ?>
                <a href="?tab=details&staff_id=<?= $t['id'] ?>" class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 transition <?= $edit_id==$t['id']?'bg-teal-50 border-l-2 border-l-teal-500':'' ?>">
                    <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-xs font-bold flex-shrink-0"><?= get_avatar($t['full_name']) ?></div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-semibold text-gray-900 truncate"><?= sanitize($t['full_name']) ?></div>
                        <div class="text-[10px] text-gray-500 truncate"><?= sanitize($t['employee_id'] ?: 'No ID') ?> · <?= ucfirst($t['role']) ?></div>
                    </div>
                    <?php if (!$t['sid']): ?>
                    <span class="text-[9px] text-amber-600 font-medium flex-shrink-0"><i class="fas fa-exclamation-circle"></i></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Staff Detail Form -->
    <div class="lg:col-span-2">
        <?php if (!$edit_user): ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fas fa-user-plus text-4xl mb-3 block text-gray-300"></i>
            <p class="text-sm">Select a staff member from the list to view or edit their details.</p>
        </div>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="action" value="save_details">
            <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">

            <!-- Personal & Employment -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h4 class="font-bold text-gray-900 text-sm"><i class="fas fa-user text-teal-600 mr-1.5"></i> Personal & Employment</h4>
                </div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Full Name</label>
                        <input type="text" value="<?= sanitize($edit_user['full_name']) ?>" disabled class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Employee ID</label>
                        <input type="text" name="employee_id" value="<?= sanitize($edit_user['employee_id'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Email</label>
                        <input type="text" value="<?= sanitize($edit_user['email'] ?? '') ?>" disabled class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" value="<?= sanitize($edit_user['phone'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?= $edit_user['date_of_birth'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Gender</label>
                        <select name="gender" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="">— Select —</option>
                            <option value="male" <?= ($edit_user['gender']??'')=='male'?'selected':'' ?>>Male</option>
                            <option value="female" <?= ($edit_user['gender']??'')=='female'?'selected':'' ?>>Female</option>
                            <option value="other" <?= ($edit_user['gender']??'')=='other'?'selected':'' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Qualification</label>
                        <input type="text" name="qualification" value="<?= sanitize($edit_user['qualification'] ?? '') ?>" placeholder="e.g. B.Ed, M.Ed" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Date of Joining</label>
                        <input type="date" name="date_of_joining" value="<?= $edit_user['date_of_joining'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Address</label>
                        <textarea name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none"><?= sanitize($edit_user['address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- KRA, Bank, SHA, NSSF, TSC -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h4 class="font-bold text-gray-900 text-sm"><i class="fas fa-file-invoice text-teal-600 mr-1.5"></i> Statutory & Financial</h4>
                </div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">KRA PIN</label>
                        <input type="text" name="kra_pin" value="<?= sanitize($edit_user['kra_pin'] ?? '') ?>" placeholder="P000xxxxxx" maxlength="11" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">NSSF Number</label>
                        <input type="text" name="nssf_number" value="<?= sanitize($edit_user['nssf_number'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">SHA Number</label>
                        <input type="text" name="sha_number" value="<?= sanitize($edit_user['sha_number'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">TSC Number</label>
                        <input type="text" name="tsc_number" value="<?= sanitize($edit_user['tsc_number'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" value="<?= sanitize($edit_user['bank_name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Account Number</label>
                        <input type="text" name="bank_account" value="<?= sanitize($edit_user['bank_account'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Bank Branch</label>
                        <input type="text" name="bank_branch" value="<?= sanitize($edit_user['bank_branch'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                </div>
            </div>

            <!-- Next of Kin -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <h4 class="font-bold text-gray-900 text-sm"><i class="fas fa-users text-teal-600 mr-1.5"></i> Next of Kin</h4>
                    <button type="button" onclick="addKin()" class="text-teal-600 hover:text-teal-800 text-xs font-medium"><i class="fas fa-plus mr-1"></i> Add</button>
                </div>
                <div class="p-4" id="kin-container">
                    <?php foreach ($kin as $k): ?>
                    <div class="kin-entry grid grid-cols-1 sm:grid-cols-5 gap-2 mb-2 p-2 bg-gray-50 rounded-lg">
                        <input type="hidden" name="kin_id[]" value="<?= $k['id'] ?>">
                        <input type="text" name="kin_name[]" value="<?= sanitize($k['name']) ?>" placeholder="Name" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="text" name="kin_relation[]" value="<?= sanitize($k['relationship']) ?>" placeholder="Relationship" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="text" name="kin_phone[]" value="<?= sanitize($k['phone']) ?>" placeholder="Phone" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="email" name="kin_email[]" value="<?= sanitize($k['email']) ?>" placeholder="Email" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <div class="flex items-center gap-1">
                            <label class="flex items-center gap-1 text-[10px] text-gray-500 cursor-pointer">
                                <input type="checkbox" name="kin_primary[]" value="1" <?= $k['is_primary']?'checked':'' ?> class="rounded border-gray-300 text-teal-600"> Primary
                            </label>
                            <button type="button" onclick="this.closest('.kin-entry').remove()" class="text-red-500 hover:text-red-700 ml-auto"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div id="kin-template" class="hidden kin-entry grid grid-cols-1 sm:grid-cols-5 gap-2 mb-2 p-2 bg-gray-50 rounded-lg">
                        <input type="hidden" name="kin_id[]" value="0">
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
            </div>

            <!-- Beneficiaries -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <h4 class="font-bold text-gray-900 text-sm"><i class="fas fa-gift text-teal-600 mr-1.5"></i> Beneficiaries</h4>
                    <button type="button" onclick="addBeneficiary()" class="text-teal-600 hover:text-teal-800 text-xs font-medium"><i class="fas fa-plus mr-1"></i> Add</button>
                </div>
                <div class="p-4" id="beneficiary-container">
                    <div class="grid grid-cols-12 gap-2 mb-1 text-[10px] font-bold text-gray-500 px-2">
                        <div class="col-span-4">Name</div>
                        <div class="col-span-3">Relationship</div>
                        <div class="col-span-2">Phone</div>
                        <div class="col-span-2">% Allocation</div>
                        <div class="col-span-1"></div>
                    </div>
                    <?php foreach ($beneficiaries as $b): ?>
                    <div class="beneficiary-entry grid grid-cols-12 gap-2 mb-2 p-2 bg-gray-50 rounded-lg items-center">
                        <input type="hidden" name="ben_id[]" value="<?= $b['id'] ?>">
                        <input type="text" name="ben_name[]" value="<?= sanitize($b['name']) ?>" placeholder="Name" class="col-span-4 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="text" name="ben_relation[]" value="<?= sanitize($b['relationship']) ?>" placeholder="Relationship" class="col-span-3 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="text" name="ben_phone[]" value="<?= sanitize($b['phone']) ?>" placeholder="Phone" class="col-span-2 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="number" name="ben_percentage[]" value="<?= $b['percentage'] ?>" placeholder="%" step="0.01" min="0" max="100" class="col-span-2 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <button type="button" onclick="this.closest('.beneficiary-entry').remove()" class="col-span-1 text-red-500 hover:text-red-700 text-xs"><i class="fas fa-times"></i></button>
                    </div>
                    <?php endforeach; ?>
                    <div id="beneficiary-template" class="hidden beneficiary-entry grid grid-cols-12 gap-2 mb-2 p-2 bg-gray-50 rounded-lg items-center">
                        <input type="hidden" name="ben_id[]" value="0">
                        <input type="text" name="ben_name[]" placeholder="Name" class="col-span-4 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="text" name="ben_relation[]" placeholder="Relationship" class="col-span-3 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="text" name="ben_phone[]" placeholder="Phone" class="col-span-2 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <input type="number" name="ben_percentage[]" placeholder="%" step="0.01" min="0" max="100" class="col-span-2 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
                        <button type="button" onclick="this.closest('.beneficiary-entry').remove()" class="col-span-1 text-red-500 hover:text-red-700 text-xs"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="bg-teal-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-save mr-1"></i> Save All Details
                </button>
                <a href="?tab=details" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function addKin() {
    const tmpl = document.getElementById('kin-template');
    const clone = tmpl.cloneNode(true);
    clone.id = '';
    clone.classList.remove('hidden');
    document.getElementById('kin-container').appendChild(clone);
}
function addBeneficiary() {
    const tmpl = document.getElementById('beneficiary-template');
    const clone = tmpl.cloneNode(true);
    clone.id = '';
    clone.classList.remove('hidden');
    document.getElementById('beneficiary-container').appendChild(clone);
}
document.getElementById('staff-search')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('[href*="staff_id="]').forEach(a => {
        a.style.display = a.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

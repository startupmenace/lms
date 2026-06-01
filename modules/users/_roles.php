<?php
$all_roles = db_get_all("SELECT * FROM roles ORDER BY is_system DESC, name");
$edit_role_id = (int)($_GET['edit_role'] ?? 0);

$all_modules = [
    'dashboard' => 'Dashboard',
    'students' => 'Students',
    'classes' => 'Classes',
    'homework' => 'Homework',
    'attendance' => 'Attendance',
    'exams' => 'Exam Planner',
    'tests' => 'Tests',
    'evaluation' => 'Evaluation',
    'transport' => 'Transport',
    'staff' => 'Staff Manager',
    'timetable' => 'Timetable',
    'leave' => 'Leave Management',
    'fees' => 'Fee Management',
    'noticeboard' => 'Notice Board',
    'holidays' => 'Calendar',
    'diary' => 'Diary',
    'live-class' => 'Live Class',
    'chat' => 'Chat',
    'users' => 'User Management',
    'hr' => 'HR Module',
];
?>
<div class="flex flex-col lg:flex-row gap-6">
    <!-- Role List -->
    <div class="w-full lg:w-64 flex-shrink-0">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-gray-900 text-sm">Roles</h3>
            <button type="button" onclick="document.getElementById('new-role-form').classList.toggle('hidden')" class="text-teal-600 hover:text-teal-800 text-xs font-medium"><i class="fas fa-plus mr-1"></i> New</button>
        </div>

        <form id="new-role-form" method="POST" class="hidden bg-gray-50 rounded-xl border border-gray-200 p-3 mb-3 space-y-2">
            <input type="hidden" name="action" value="create_role">
            <input type="text" name="name" placeholder="Role name" required class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            <input type="text" name="description" placeholder="Description (optional)" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            <button type="submit" class="bg-teal-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition w-full">Create</button>
        </form>

        <div class="space-y-1">
            <?php foreach ($all_roles as $r): ?>
            <div class="flex items-center justify-between px-3 py-2 rounded-lg text-xs <?= $edit_role_id===$r['id']?'bg-teal-50 border border-teal-200':'hover:bg-gray-50 border border-transparent' ?>">
                <div>
                    <a href="?tab=roles&edit_role=<?= $r['id'] ?>" class="font-medium text-gray-900 hover:text-teal-700"><?= ucfirst(sanitize($r['name'])) ?></a>
                    <p class="text-[10px] text-gray-400"><?= sanitize($r['description'] ?? '') ?></p>
                </div>
                <div class="flex items-center gap-1">
                    <?php if ($r['is_system']): ?>
                    <span class="text-[9px] text-gray-400 italic">system</span>
                    <?php else: ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete role <?= sanitize($r['name']) ?>?')">
                        <input type="hidden" name="action" value="delete_role">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Permissions Grid -->
    <div class="flex-1">
        <?php
        $selected_role = null;
        if ($edit_role_id) {
            $selected_role = db_get_row("SELECT * FROM roles WHERE id=?", [$edit_role_id]);
        }
        if (!$selected_role):
        ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fas fa-hand-pointer text-4xl mb-3 block text-gray-300"></i>
            <p class="text-sm">Select a role to manage its permissions</p>
        </div>
        <?php else:
            $current_perms = db_get_all("SELECT module FROM role_permissions WHERE role_id=? AND can_view=1", [$selected_role['id']]);
            $current_modules = array_column($current_perms, 'module');
        ?>
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 text-sm">
                <i class="fas fa-lock-open text-teal-600 mr-1"></i>
                Permissions: <span class="text-teal-700"><?= ucfirst(sanitize($selected_role['name'])) ?></span>
            </h3>
            <button type="button" onclick="selectAll(true)" class="text-xs text-teal-600 hover:text-teal-800 mr-2">Select All</button>
            <button type="button" onclick="selectAll(false)" class="text-xs text-gray-500 hover:text-gray-700">Clear All</button>
        </div>

        <form method="POST" id="perms-form">
            <input type="hidden" name="action" value="save_permissions">
            <input type="hidden" name="role_id" value="<?= $selected_role['id'] ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                <?php foreach ($all_modules as $key => $label): ?>
                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 hover:border-teal-200 hover:bg-teal-50/30 cursor-pointer transition">
                    <input type="checkbox" name="modules[]" value="<?= $key ?>"
                           <?= in_array($key, $current_modules) ? 'checked' : '' ?>
                           class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    <span class="text-xs font-medium text-gray-700"><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="mt-4">
                <button type="submit" class="bg-teal-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                    <i class="fas fa-save mr-1"></i> Save Permissions
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function selectAll(checked) {
    document.querySelectorAll('#perms-form input[name="modules[]"]').forEach(cb => cb.checked = checked);
}
</script>

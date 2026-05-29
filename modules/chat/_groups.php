<?php
$all_groups = db_get_all("SELECT cg.*, u.full_name as creator_name,
    (SELECT COUNT(*) FROM chat_group_members WHERE group_id=cg.id) as member_count,
    (SELECT COUNT(*) FROM chat_messages WHERE group_id=cg.id) as msg_count
    FROM chat_groups cg
    LEFT JOIN users u ON cg.created_by=u.id
    ORDER BY cg.type, cg.name");
$all_users = db_get_all("SELECT id, full_name, role FROM users WHERE is_active=1 ORDER BY full_name");
$all_classes = db_get_all("SELECT id, name FROM classes WHERE is_active=1 ORDER BY name");
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-900"><i class="fas fa-layer-group text-teal-600 mr-2"></i>Groups & Clubs</h2>
        <p class="text-sm text-gray-500">Create and manage chat groups, clubs, and members</p>
    </div>
    <?php if (has_role('admin')): ?>
    <button onclick="openModal('create-group-modal')" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">
        <i class="fas fa-plus mr-1"></i> New Group
    </button>
    <?php endif; ?>
</div>

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 text-gray-500 text-xs uppercase">
                <th class="text-left py-3 px-3 font-semibold">Group</th>
                <th class="text-left py-3 px-3 font-semibold">Type</th>
                <th class="text-center py-3 px-3 font-semibold">Members</th>
                <th class="text-center py-3 px-3 font-semibold">Messages</th>
                <th class="text-left py-3 px-3 font-semibold">Created By</th>
                <th class="text-right py-3 px-3 font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_groups as $g): ?>
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-3 font-medium text-gray-900">
                    <span class="inline-flex items-center gap-2">
                        <?php if ($g['type'] == 'general'): ?>
                            <span class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700 text-xs"><i class="fas fa-bullhorn"></i></span>
                        <?php elseif ($g['type'] == 'class'): ?>
                            <span class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center text-blue-700 text-xs"><i class="fas fa-chalkboard"></i></span>
                        <?php else: ?>
                            <span class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center text-purple-700 text-xs"><i class="fas fa-futbol"></i></span>
                        <?php endif; ?>
                        <?= sanitize($g['name']) ?>
                    </span>
                </td>
                <td class="py-3 px-3">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full 
                        <?= $g['type']=='general'?'bg-amber-100 text-amber-800':($g['type']=='class'?'bg-blue-100 text-blue-800':'bg-purple-100 text-purple-800') ?>">
                        <?= ucfirst($g['type']) ?>
                    </span>
                </td>
                <td class="py-3 px-3 text-center text-gray-600"><?= $g['member_count'] ?></td>
                <td class="py-3 px-3 text-center text-gray-600"><?= $g['msg_count'] ?></td>
                <td class="py-3 px-3 text-gray-600"><?= sanitize($g['creator_name'] ?? '—') ?></td>
                <td class="py-3 px-3 text-right">
                    <button onclick="openModal('manage-<?= $g['id'] ?>')" class="text-teal-600 hover:text-teal-800 text-xs font-medium mr-2"><i class="fas fa-users mr-1"></i> Members</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($all_groups)): ?>
            <tr><td colspan="6" class="py-8 text-center text-gray-400">No groups yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (has_role('admin')): ?>
<!-- Create Group Modal -->
<div id="create-group-modal" class="fixed inset-0 bg-black/40 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target===this)closeModal('create-group-modal')">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-900">Create New Group</h3>
            <button onclick="closeModal('create-group-modal')" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create_group">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Group Name</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="club">Club</option>
                    <option value="class">Class Group</option>
                    <option value="general">General (Announcements)</option>
                </select>
            </div>
            <div id="class-select-wrap" style="display:none">
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">— Select —</option>
                    <?php foreach ($all_classes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Add Members</label>
                <select name="members[]" multiple class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" size="6">
                    <?php foreach ($all_users as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= sanitize($u['full_name']) ?> (<?= $u['role'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl+click to select multiple</p>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('create-group-modal')" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">Cancel</button>
                <button type="submit" class="bg-teal-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition">Create Group</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Member Management Modals -->
<?php foreach ($all_groups as $g):
$members = db_get_all("SELECT cgm.*, u.full_name, u.role FROM chat_group_members cgm JOIN users u ON cgm.user_id=u.id WHERE cgm.group_id=? ORDER BY u.full_name", [$g['id']]);
$available_users = array_filter($all_users, function($u) use ($members) {
    foreach ($members as $m) if ($m['user_id'] == $u['id']) return false;
    return true;
});
?>
<div id="manage-<?= $g['id'] ?>" class="fixed inset-0 bg-black/40 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target===this)closeModal('manage-<?= $g['id'] ?>')">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-900"><?= sanitize($g['name']) ?></h3>
            <button onclick="closeModal('manage-<?= $g['id'] ?>')" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-500 mb-4"><?= $g['member_count'] ?> member(s)</p>
        <div class="space-y-2 mb-4 max-h-60 overflow-y-auto">
            <?php foreach ($members as $m): ?>
            <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-teal-100 flex items-center justify-center text-xs font-bold text-teal-700"><?= get_avatar($m['full_name']) ?></div>
                    <div>
                        <span class="text-sm font-medium text-gray-900"><?= sanitize($m['full_name']) ?></span>
                        <span class="text-xs text-gray-400 ml-1">(<?= $m['role'] ?>)</span>
                    </div>
                </div>
                <?php if (has_role('admin')): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="remove_member">
                    <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $m['user_id'] ?>">
                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium"><i class="fas fa-times"></i></button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (has_role('admin') && !empty($available_users)): ?>
        <form method="POST" class="flex gap-2">
            <input type="hidden" name="action" value="add_member">
            <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
            <select name="user_id" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <option value="">Add member...</option>
                <?php foreach ($available_users as $u): ?>
                <option value="<?= $u['id'] ?>"><?= sanitize($u['full_name']) ?> (<?= $u['role'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-teal-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition"><i class="fas fa-plus"></i></button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<script>
document.querySelector('[name="type"]')?.addEventListener('change', function() {
    document.getElementById('class-select-wrap').style.display = this.value === 'class' ? 'block' : 'none';
});
</script>

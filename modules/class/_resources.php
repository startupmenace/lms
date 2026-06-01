<?php
$resources = db_get_all("SELECT r.*, u.full_name as uploaded_by_name FROM class_resources r LEFT JOIN users u ON r.uploaded_by=u.id WHERE r.class_id=? ORDER BY r.created_at DESC", [$class_id]);
?>
<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500"><?= count($resources) ?> resource(s)</p>
</div>

<form method="post" class="p-4 bg-gray-50 rounded-xl border border-gray-200 mb-6">
    <input type="hidden" name="action" value="add_resource">
    <input type="hidden" name="class_id" value="<?= $class_id ?>">
    <h4 class="font-bold text-gray-900 text-xs mb-3">Add Resource</h4>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
        <div>
            <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">Title</label>
            <input type="text" name="title" required class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
        </div>
        <div>
            <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">URL</label>
            <input type="url" name="url" required class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs" placeholder="https://...">
        </div>
        <div>
            <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">Type</label>
            <select name="resource_type" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
                <option value="link">Link</option>
                <option value="document">Document</option>
                <option value="video">Video</option>
                <option value="other">Other</option>
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label class="text-[10px] text-gray-500 uppercase font-medium block mb-1">Description (optional)</label>
        <input type="text" name="description" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
    </div>
    <div class="flex justify-end">
        <button type="submit" class="bg-teal-600 text-white px-6 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition">Add Resource</button>
    </div>
</form>

<div class="space-y-2">
    <?php if (empty($resources)): ?>
    <p class="text-gray-400 text-xs text-center py-8">No resources shared yet.</p>
    <?php else: ?>
    <?php foreach ($resources as $r): ?>
    <div class="flex items-center justify-between border border-gray-200 rounded-xl px-4 py-3 hover:border-teal-200 transition">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center text-white">
                <i class="fas fa-link text-xs"></i>
            </div>
            <div>
                <a href="<?= sanitize($r['url']) ?>" target="_blank" class="text-xs font-medium text-gray-900 hover:text-teal-700 transition"><?= sanitize($r['title']) ?></a>
                <p class="text-[10px] text-gray-400">
                    <?= sanitize($r['resource_type']) ?>
                    <?= $r['description'] ? ' · ' . sanitize($r['description']) : '' ?>
                    <?= $r['uploaded_by_name'] ? ' · by ' . sanitize($r['uploaded_by_name']) : '' ?>
                    · <?= format_date($r['created_at']) ?>
                </p>
            </div>
        </div>
        <form method="post" onsubmit="return confirm('Delete this resource?')">
            <input type="hidden" name="action" value="delete_resource">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <input type="hidden" name="class_id" value="<?= $class_id ?>">
            <button type="submit" class="text-gray-300 hover:text-red-500 transition"><i class="fas fa-trash text-xs"></i></button>
        </form>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

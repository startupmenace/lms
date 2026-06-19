<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('classes');
require_role('admin');

$page_title = 'Create Class';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $category = sanitize($_POST['category']);
    $section = sanitize($_POST['section'] ?? '');
    if ($section === 'other') {
        $section = sanitize($_POST['section_custom'] ?? '');
    }
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name) || empty($category)) {
        set_flash('error', 'Class name and category are required.');
    } else {
        $id = db_insert(
            "INSERT INTO classes (name, category, section, description) VALUES (?, ?, ?, ?)",
            [$name, $category, $section ?: null, $description ?: null]
        );
        if ($id) {
            set_flash('success', 'Class created successfully.');
            redirect("?id=$id");
        } else {
            set_flash('error', 'Failed to create class.');
        }
    }
}

$categories = db_get_all("SELECT DISTINCT category FROM classes WHERE is_active=1 ORDER BY category");

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900"><i class="fas fa-school text-teal-600 mr-2"></i> Create Class</h1>
        </div>
        <a href="index.php" class="text-gray-400 hover:text-gray-600 text-sm transition"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <?php if (has_flash('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-2 text-sm">
            <i class="fas fa-exclamation-circle"></i> <?= get_flash('error') ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required value="<?= sanitize($_POST['name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g., Class 8, Grade 4">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="category" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="">— Select Category —</option>
                            <?php
                            $cats = !empty($categories) ? array_column($categories, 'category') : ['Pre Primary', 'Primary', 'Middle', 'Secondary'];
                            foreach ($cats as $cat): ?>
                            <option value="<?= sanitize($cat) ?>" <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>><?= sanitize($cat) ?></option>
                            <?php endforeach; ?>
                            <option value="other">Other (type below)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stream</label>
                        <select name="section" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" onchange="toggleStreamInput(this)">
                            <option value="">— No Stream —</option>
                            <option value="Alpha" <?= ($_POST['section'] ?? '') === 'Alpha' ? 'selected' : '' ?>>Alpha</option>
                            <option value="Beta" <?= ($_POST['section'] ?? '') === 'Beta' ? 'selected' : '' ?>>Beta</option>
                            <option value="Gamma" <?= ($_POST['section'] ?? '') === 'Gamma' ? 'selected' : '' ?>>Gamma</option>
                            <option value="Delta" <?= ($_POST['section'] ?? '') === 'Delta' ? 'selected' : '' ?>>Delta</option>
                            <option value="Green" <?= ($_POST['section'] ?? '') === 'Green' ? 'selected' : '' ?>>Green</option>
                            <option value="Red" <?= ($_POST['section'] ?? '') === 'Red' ? 'selected' : '' ?>>Red</option>
                            <option value="Blue" <?= ($_POST['section'] ?? '') === 'Blue' ? 'selected' : '' ?>>Blue</option>
                            <option value="Gold" <?= ($_POST['section'] ?? '') === 'Gold' ? 'selected' : '' ?>>Gold</option>
                            <option value="A" <?= ($_POST['section'] ?? '') === 'A' ? 'selected' : '' ?>>A</option>
                            <option value="B" <?= ($_POST['section'] ?? '') === 'B' ? 'selected' : '' ?>>B</option>
                            <option value="C" <?= ($_POST['section'] ?? '') === 'C' ? 'selected' : '' ?>>C</option>
                            <option value="other">Other (type below)</option>
                        </select>
                        <input type="text" name="section_custom" id="section_custom" value="<?= sanitize($_POST['section_custom'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none mt-2" placeholder="Custom stream name..." style="display:none">
                    </div>
                </div>
                <script>
                function toggleStreamInput(sel) {
                    var custom = document.getElementById('section_custom');
                    if (sel.value === 'other') {
                        custom.style.display = 'block';
                        custom.focus();
                    } else {
                        custom.style.display = 'none';
                        custom.value = '';
                    }
                }
                <?php if (isset($_POST['section']) && $_POST['section'] === 'other'): ?>
                document.addEventListener('DOMContentLoaded', function() { document.getElementById('section_custom').style.display = 'block'; });
                <?php endif; ?>
                </script>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Optional description..."><?= sanitize($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
                        <i class="fas fa-save"></i> Create Class
                    </button>
                    <a href="index.php" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('students');
require_role('admin', 'teacher');

$page_title = 'Import Students';
$classes = db_get_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY name");
$class_map = [];
foreach ($classes as $c) $class_map[strtolower(trim($c['name']))] = $c['id'];

$preview = [];
$errors = [];
$imported = 0;
$duplicates = 0;

// ── Parse CSV on upload ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($tmp, 'r');
    if (!$handle) {
        set_flash('error', 'Could not read file.');
        redirect('import.php');
    }

    $headers = fgetcsv($handle);
    if (!$headers) {
        set_flash('error', 'Empty CSV file.');
        fclose($handle);
        redirect('import.php');
    }
    $headers = array_map('trim', $headers);

    $required = ['student_name'];
    $missing = array_diff($required, $headers);
    if (!empty($missing)) {
        set_flash('error', 'Missing required column: ' . implode(', ', $missing));
        fclose($handle);
        redirect('import.php');
    }

    $line = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $line++;
        $data = array_combine($headers, array_map('trim', $row));

        if (empty($data['student_name'])) {
            $errors[] = "Line $line: student_name is required";
            continue;
        }

        $class_id = null;
        $class_name_input = $data['class_name'] ?? '';
        if (!empty($class_name_input)) {
            $key = strtolower(trim($class_name_input));
            $class_id = $class_map[$key] ?? null;
            if (!$class_id) {
                $errors[] = "Line $line: class '{$class_name_input}' not found";
                continue;
            }
        }

        $preview[] = [
            'student_name' => $data['student_name'],
            'class_name' => $class_name_input,
            'class_id' => $class_id,
            'gender' => $data['gender'] ?? '',
            'date_of_birth' => $data['date_of_birth'] ?? '',
            'parent_name' => $data['parent_name'] ?? '',
            'parent_phone' => $data['parent_phone'] ?? '',
            'parent_email' => $data['parent_email'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'guardian_name' => $data['guardian_name'] ?? '',
            'guardian_phone' => $data['guardian_phone'] ?? '',
            'enrollment_id' => $data['enrollment_id'] ?? '',
        ];
    }
    fclose($handle);
}

// ── Confirm import ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    $rows = json_decode($_POST['import_data'], true);
    if (!empty($rows)) {
        foreach ($rows as $r) {
            $enrollment_id = !empty($r['enrollment_id']) ? $r['enrollment_id'] : 'STU-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));

            $existing = db_get_row("SELECT id FROM students WHERE enrollment_id=?", [$enrollment_id]);
            if ($existing) {
                $duplicates++;
                continue;
            }

            db_insert(
                "INSERT INTO students (class_id, enrollment_id, admission_date, date_of_birth, gender, address, city, parent_name, parent_phone, parent_email, guardian_name, guardian_phone)
                 VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $r['class_id'] ?: null,
                    $enrollment_id,
                    $r['date_of_birth'] ?: null,
                    $r['gender'] ?: null,
                    $r['address'] ?: null,
                    $r['city'] ?: null,
                    $r['student_name'],
                    $r['parent_phone'] ?: null,
                    $r['parent_email'] ?: null,
                    $r['guardian_name'] ?: null,
                    $r['guardian_phone'] ?: null,
                ]
            );
            $imported++;
        }
        if ($imported) set_flash('success', "$imported student(s) imported successfully.");
        if ($duplicates) set_flash('error', "$duplicates duplicate(s) skipped.");
        redirect('index.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900"><i class="fas fa-file-csv text-teal-600 mr-2"></i> Import Students</h1>
            <p class="text-sm text-gray-500 mt-1">Upload a CSV to bulk-import student records</p>
        </div>
        <a href="index.php" class="text-gray-400 hover:text-gray-600 text-sm transition"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="font-bold text-gray-900 text-sm mb-1">CSV Format</h3>
        <p class="text-xs text-gray-500 mb-3">Your CSV should include these columns. <strong>student_name</strong> is required. <code>class_name</code> must match an existing class name exactly.</p>
        <div class="overflow-x-auto">
            <table class="text-xs text-gray-600 w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-1 pr-3 font-medium">Column</th>
                        <th class="text-left py-1 pr-3 font-medium">Required</th>
                        <th class="text-left py-1 font-medium">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $cols = [
                        ['enrollment_id', 'No', 'Leave blank to auto-generate'],
                        ['student_name', 'Yes', 'Student full name'],
                        ['class_name', 'No', 'Must match class name in system (e.g. "Class 8")'],
                        ['gender', 'No', 'Male / Female / Other'],
                        ['date_of_birth', 'No', 'YYYY-MM-DD format'],
                        ['parent_name', 'No', 'Parent or guardian name'],
                        ['parent_phone', 'No', 'Parent phone number'],
                        ['parent_email', 'No', 'Parent email address'],
                        ['address', 'No', 'Home address'],
                        ['city', 'No', 'City / town'],
                        ['guardian_name', 'No', 'Guardian name (if different)'],
                        ['guardian_phone', 'No', 'Guardian phone'],
                    ];
                    ?>
                    <?php foreach ($cols as $c): ?>
                    <tr class="border-b border-gray-50">
                        <td class="py-1 pr-3 font-mono text-teal-700"><?= $c[0] ?></td>
                        <td class="py-1 pr-3"><?= $c[1] === 'Yes' ? '<span class="text-red-500 font-medium">Yes</span>' : 'No' ?></td>
                        <td class="py-1 text-gray-400"><?= $c[2] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <a href="<?= BASE_URL ?>/sample-students.csv" class="text-teal-600 hover:text-teal-800 text-xs font-medium"><i class="fas fa-download mr-1"></i> Download sample CSV</a>
        </div>
    </div>

    <!-- Upload Form -->
    <?php if (empty($preview)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Choose CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer">
            </div>
            <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
                <i class="fas fa-upload"></i> Preview CSV
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Preview Table -->
    <?php if (!empty($preview)): ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 text-sm">
                <i class="fas fa-eye text-teal-600 mr-1"></i> Preview
                <span class="text-gray-400 font-normal">(<?= count($preview) ?> rows)</span>
            </h3>
            <form method="post">
                <input type="hidden" name="confirm_import" value="1">
                <input type="hidden" name="import_data" value='<?= sanitize(json_encode($preview)) ?>'>
                <button type="submit" class="bg-teal-600 text-white px-5 py-1.5 rounded-lg text-xs font-medium hover:bg-teal-700 transition flex items-center gap-1">
                    <i class="fas fa-check"></i> Confirm Import
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-2.5 px-3 font-medium text-gray-500 text-xs">#</th>
                        <th class="text-left py-2.5 px-3 font-medium text-gray-500 text-xs">Student Name</th>
                        <th class="text-left py-2.5 px-3 font-medium text-gray-500 text-xs">Class</th>
                        <th class="text-left py-2.5 px-3 font-medium text-gray-500 text-xs">Gender</th>
                        <th class="text-left py-2.5 px-3 font-medium text-gray-500 text-xs">DOB</th>
                        <th class="text-left py-2.5 px-3 font-medium text-gray-500 text-xs">Parent Name</th>
                        <th class="text-left py-2.5 px-3 font-medium text-gray-500 text-xs">Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview as $i => $r): ?>
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="py-2 px-3 text-xs text-gray-400"><?= $i + 1 ?></td>
                        <td class="py-2 px-3 font-medium text-gray-900 text-xs"><?= sanitize($r['student_name']) ?></td>
                        <td class="py-2 px-3">
                            <?php if ($r['class_id']): ?>
                            <span class="bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded"><?= sanitize($r['class_name']) ?></span>
                            <?php else: ?>
                            <span class="text-gray-300 text-[10px]">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3 text-xs text-gray-500"><?= sanitize($r['gender'] ?: '—') ?></td>
                        <td class="py-2 px-3 text-xs text-gray-500"><?= sanitize($r['date_of_birth'] ?: '—') ?></td>
                        <td class="py-2 px-3 text-xs text-gray-500"><?= sanitize($r['parent_name'] ?: '—') ?></td>
                        <td class="py-2 px-3 text-xs text-gray-500"><?= sanitize($r['parent_phone'] ?: '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Errors -->
    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <h4 class="font-bold text-red-700 text-xs mb-2"><i class="fas fa-exclamation-triangle mr-1"></i> Skipped Rows (<?= count($errors) ?>)</h4>
        <ul class="space-y-1">
            <?php foreach ($errors as $e): ?>
            <li class="text-xs text-red-600"><?= sanitize($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

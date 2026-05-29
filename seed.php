<?php
/**
 * Seed script: Run once to populate the database with sample data
 * Run via browser: http://localhost/jewel-house-school/seed.php
 * Or via CLI: php seed.php
 */

require_once __DIR__ . '/config/database.php';

echo "Seeding database...\n";

// Create admin user if not exists (password: password)
$check = db_get_row("SELECT id FROM users WHERE email = 'admin@jewelhouse.sc.ke'");
if (!$check) {
    $password = password_hash('password', PASSWORD_DEFAULT);
    db_insert("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)",
        ['admin', 'admin@jewelhouse.sc.ke', $password, 'Admin User', 'admin']);
    echo "✓ Admin user created (admin@jewelhouse.sc.ke / password)\n";
} else {
    echo "• Admin user already exists\n";
}

// Create teacher
$teacher_check = db_get_row("SELECT id FROM users WHERE email = 'teacher@jewelhouse.sc.ke'");
if (!$teacher_check) {
    $password = password_hash('teacher123', PASSWORD_DEFAULT);
    db_insert("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)",
        ['teacher', 'teacher@jewelhouse.sc.ke', $password, 'Rahul Sharma', '9876543210', 'teacher']);
    echo "✓ Teacher created (teacher@jewelhouse.sc.ke / teacher123)\n";
}

// Sample students for each class
$classes = db_get_all("SELECT id, name FROM classes");
$sample_names = ['Aarav Patel', 'Vivaan Singh', 'Aditya Kumar', 'Vihaan Sharma', 'Arjun Verma',
    'Sai Reddy', 'Ananya Gupta', 'Diya Joshi', 'Ishita Mehta', 'Riya Saxena',
    'Aisha Khan', 'Myra Nair', 'Kiara Das', 'Navya Choudhury', 'Sara Iyer'];

foreach ($classes as $c) {
    $count = db_get_row("SELECT COUNT(*) as count FROM students WHERE class_id = ?", [$c['id']])['count'];
    if ($count == 0) {
        foreach ($sample_names as $i => $name) {
            $enroll = 'STU-' . date('Y') . '-' . str_pad($c['id'], 2, '0', STR_PAD_LEFT) . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            $dob = date('Y-m-d', strtotime("-" . (10 + $i) . " years"));
            db_insert("INSERT INTO students (class_id, enrollment_id, admission_date, date_of_birth, gender, blood_group, address, city, state, parent_name, parent_phone, parent_email, guardian_name, guardian_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$c['id'], $enroll, '2025-04-01', $dob, $i % 2 == 0 ? 'male' : 'female', ['A+','B+','O+','AB-'][$i % 4],
                 '123, Sample Street', 'Mumbai', 'Maharashtra', $name, '98765' . str_pad($i + 100, 5, '0', STR_PAD_LEFT),
                 strtolower(str_replace(' ', '.', $name)) . '@email.com', 'Mr. Guardian', '91234' . str_pad($i + 100, 5, '0', STR_PAD_LEFT)]);
        }
        echo "✓ " . count($sample_names) . " students created for " . $c['name'] . "\n";
    }
}

// Create a sample student user for login
$student_check = db_get_row("SELECT id FROM users WHERE email = 'student@jewelhouse.sc.ke'");
if (!$student_check) {
    $password = password_hash('student123', PASSWORD_DEFAULT);
    $student_user_id = db_insert("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)",
        ['student', 'student@jewelhouse.sc.ke', $password, 'Aarav Patel', 'student']);

    // Link to first student record
    $first_student = db_get_row("SELECT id FROM students ORDER BY id LIMIT 1");
    if ($first_student) {
        db_query("UPDATE students SET user_id = ? WHERE id = ?", [$student_user_id, $first_student['id']]);
        echo "✓ Student user linked to student ID " . $first_student['id'] . "\n";
    }
    echo "✓ Student user created (student@jewelhouse.sc.ke / student123)\n";
} else {
    echo "• Student user already exists\n";
}

echo "\nDone! You can now log in:\n";
echo "  Admin:   admin@jewelhouse.sc.ke / password\n";
echo "  Teacher: teacher@jewelhouse.sc.ke / teacher123\n";
echo "  Student: student@jewelhouse.sc.ke / student123\n";

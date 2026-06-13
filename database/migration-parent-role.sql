-- Migration: Parent role & student-parent linking
-- Run this AFTER the main schema and migration-role-permissions.sql

-- 1. Add parent role
INSERT IGNORE INTO roles (name, description, is_system) VALUES
('parent', 'Can view children\'s records — attendance, homework, tests, fees, notices, and communicate with school', 1);

-- 2. Student-parent relationship (many-to-many)
CREATE TABLE IF NOT EXISTS student_parents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    parent_user_id INT NOT NULL,
    relationship VARCHAR(50) DEFAULT 'parent',
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY student_parent (student_id, parent_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add student_id to leave_applications so parents can apply leave for their children
ALTER TABLE leave_applications ADD COLUMN student_id INT DEFAULT NULL AFTER user_id;

-- 4. Parent permissions
INSERT INTO role_permissions (role_id, module, can_view)
SELECT r.id, m.module, 1 FROM roles r CROSS JOIN (
    SELECT 'attendance' AS module UNION SELECT 'diary' UNION SELECT 'live-class'
    UNION SELECT 'holidays' UNION SELECT 'chat' UNION SELECT 'noticeboard'
    UNION SELECT 'fees' UNION SELECT 'tests' UNION SELECT 'class'
) m WHERE r.name = 'parent';

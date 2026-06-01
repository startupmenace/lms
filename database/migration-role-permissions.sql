-- Migration: Role-Based Access Control
-- Run this AFTER the main schema to add roles & permissions

ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL DEFAULT 'staff';

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_system TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    module VARCHAR(50) NOT NULL,
    can_view TINYINT(1) DEFAULT 1,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY role_module (role_id, module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default roles
INSERT INTO roles (name, description, is_system) VALUES
('admin', 'Full system access', 1),
('teacher', 'Can manage classes, exams, tests and attendance', 1),
('student', 'Can view own records and take tests', 1),
('manager', 'Can oversee operations including staff, exams and fees', 0),
('accountant', 'Can manage fees, payments and financial records', 0),
('supplier', 'Can view invoices and profile', 0);

-- Admin: all modules (checked at code level, no explicit rows needed)

-- Teacher permissions
INSERT INTO role_permissions (role_id, module, can_view)
SELECT r.id, m.module, 1 FROM roles r CROSS JOIN (
    SELECT 'students' AS module UNION SELECT 'attendance' UNION SELECT 'exams' UNION SELECT 'tests'
    UNION SELECT 'evaluation' UNION SELECT 'staff' UNION SELECT 'timetable' UNION SELECT 'leave'
    UNION SELECT 'fees' UNION SELECT 'noticeboard' UNION SELECT 'diary' UNION SELECT 'live-class'
    UNION SELECT 'holidays' UNION SELECT 'chat' UNION SELECT 'dashboard'
) m WHERE r.name = 'teacher';

-- Student permissions
INSERT INTO role_permissions (role_id, module, can_view)
SELECT r.id, m.module, 1 FROM roles r CROSS JOIN (
    SELECT 'diary' AS module UNION SELECT 'live-class' UNION SELECT 'holidays' UNION SELECT 'chat' UNION SELECT 'noticeboard'
) m WHERE r.name = 'student';

-- Manager permissions
INSERT INTO role_permissions (role_id, module, can_view)
SELECT r.id, m.module, 1 FROM roles r CROSS JOIN (
    SELECT 'students' AS module UNION SELECT 'attendance' UNION SELECT 'exams' UNION SELECT 'tests'
    UNION SELECT 'evaluation' UNION SELECT 'staff' UNION SELECT 'timetable' UNION SELECT 'leave'
    UNION SELECT 'fees' UNION SELECT 'noticeboard' UNION SELECT 'diary' UNION SELECT 'live-class'
    UNION SELECT 'holidays' UNION SELECT 'chat' UNION SELECT 'dashboard' UNION SELECT 'transport'
) m WHERE r.name = 'manager';

-- Accountant permissions
INSERT INTO role_permissions (role_id, module, can_view)
SELECT r.id, m.module, 1 FROM roles r CROSS JOIN (
    SELECT 'fees' AS module UNION SELECT 'dashboard' UNION SELECT 'students' UNION SELECT 'holidays'
    UNION SELECT 'diary' UNION SELECT 'live-class' UNION SELECT 'chat' UNION SELECT 'noticeboard'
) m WHERE r.name = 'accountant';

-- Supplier permissions
INSERT INTO role_permissions (role_id, module, can_view)
SELECT r.id, m.module, 1 FROM roles r CROSS JOIN (
    SELECT 'dashboard' AS module UNION SELECT 'holidays' UNION SELECT 'diary' UNION SELECT 'chat'
    UNION SELECT 'noticeboard' UNION SELECT 'live-class'
) m WHERE r.name = 'supplier';

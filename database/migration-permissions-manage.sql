ALTER TABLE role_permissions ADD COLUMN can_manage TINYINT(1) DEFAULT 0 AFTER can_view;

-- Give manage permission to roles that should have it
UPDATE role_permissions rp
JOIN roles r ON rp.role_id = r.id
SET rp.can_manage = 1
WHERE r.name IN ('admin', 'teacher', 'manager');

-- Admin gets manage on everything (handled at code level, but seed it too)
UPDATE role_permissions rp
JOIN roles r ON rp.role_id = r.id
SET rp.can_manage = 1
WHERE r.name = 'admin';

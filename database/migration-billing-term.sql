ALTER TABLE transactions ADD COLUMN `term` VARCHAR(50) DEFAULT NULL AFTER `fee_structure_id`;
ALTER TABLE transactions ADD COLUMN `session_year` VARCHAR(20) DEFAULT NULL AFTER `term`;
ALTER TABLE transactions ADD COLUMN `line_items` TEXT DEFAULT NULL AFTER `total_amount`;

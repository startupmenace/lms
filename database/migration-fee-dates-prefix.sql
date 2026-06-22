ALTER TABLE fee_structures ADD COLUMN `due_day` INT DEFAULT NULL COMMENT 'Day of month for monthly due (1-31)' AFTER `frequency`;
ALTER TABLE fee_structures ADD COLUMN `term_config` TEXT DEFAULT NULL COMMENT 'JSON: per-term prefix + due_date overrides' AFTER `due_day`;
ALTER TABLE transactions ADD COLUMN `due_date` DATE DEFAULT NULL AFTER `session_year`;

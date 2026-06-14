ALTER TABLE students ADD COLUMN `disabilities` TEXT DEFAULT NULL AFTER `guardian_phone`;
ALTER TABLE students ADD COLUMN `profile_image` VARCHAR(255) DEFAULT NULL AFTER `disabilities`;

ALTER TABLE `tests` ADD COLUMN `shuffle_questions` tinyint(1) NOT NULL DEFAULT 0 AFTER `instructions`;
ALTER TABLE `test_submissions` ADD COLUMN `question_order` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`question_order`)) AFTER `answers`;

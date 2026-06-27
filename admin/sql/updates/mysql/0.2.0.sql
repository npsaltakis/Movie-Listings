-- Schema version 0.2.0: unified field manager (system + custom), list/detail visibility

ALTER TABLE `#__movielist_fields`
    ADD COLUMN `is_system` TINYINT NOT NULL DEFAULT 0 AFTER `directory_id`,
    ADD COLUMN `field_key` VARCHAR(100) NOT NULL DEFAULT '' AFTER `is_system`,
    ADD COLUMN `show_in_list` TINYINT NOT NULL DEFAULT 0 AFTER `searchable`,
    ADD COLUMN `show_in_detail` TINYINT NOT NULL DEFAULT 1 AFTER `show_in_list`,
    ADD KEY `idx_system` (`is_system`);

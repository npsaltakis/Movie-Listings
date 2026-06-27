-- Schema version 0.3.0: repeatable (multiple) fields with an optional max-items limit

ALTER TABLE `#__movielist_fields`
    ADD COLUMN `is_multiple` TINYINT NOT NULL DEFAULT 0 AFTER `searchable`,
    ADD COLUMN `max_items` INT NOT NULL DEFAULT 0 AFTER `is_multiple`;

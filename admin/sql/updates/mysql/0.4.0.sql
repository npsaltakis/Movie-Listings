-- Schema version 0.4.0: composite (group) repeatable fields with multiple sub-fields per row

ALTER TABLE `#__movielist_fields`
    ADD COLUMN `multiple_mode` VARCHAR(20) NOT NULL DEFAULT 'single' AFTER `is_multiple`,
    ADD COLUMN `subfields` JSON NULL AFTER `max_items`;

-- Film / Script content types: categories declare their type, fields declare
-- which type they apply to (all/film/script).
ALTER TABLE `#__movielist_categories` ADD COLUMN `content_type` VARCHAR(10) NOT NULL DEFAULT 'film' AFTER `image`;
ALTER TABLE `#__movielist_fields` ADD COLUMN `apply_to` VARCHAR(10) NOT NULL DEFAULT 'all' AFTER `type`;

-- Existing script categories (created manually or by the FilmFreeway import).
UPDATE `#__movielist_categories` SET `content_type` = 'script' WHERE `title` LIKE '%script%' OR `title` LIKE '%σεναρι%';

-- Film-only among the standard fields.
UPDATE `#__movielist_fields` SET `apply_to` = 'film' WHERE `name` IN ('trailer', 'trailer_password', 'duration', 'cast');

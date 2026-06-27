--
-- Schema for com_movielist (Joomla 5 / 6)
--

-- Directories: top-level containers (multiple, unlike Mosets Tree single root)
CREATE TABLE IF NOT EXISTS `#__movielist_directories` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `asset_id`         INT UNSIGNED NOT NULL DEFAULT 0,
    `title`            VARCHAR(400) NOT NULL DEFAULT '',
    `alias`            VARCHAR(400) NOT NULL DEFAULT '',
    `description`      MEDIUMTEXT NULL,
    `image`            VARCHAR(1024) NOT NULL DEFAULT '',
    `params`           JSON NULL,
    `language`         CHAR(7) NOT NULL DEFAULT '*',
    `access`           INT UNSIGNED NOT NULL DEFAULT 1,
    `state`            TINYINT NOT NULL DEFAULT 1,
    `ordering`         INT NOT NULL DEFAULT 0,
    `checked_out`      INT UNSIGNED NULL,
    `checked_out_time` DATETIME NULL,
    `created`          DATETIME NULL,
    `created_by`       INT UNSIGNED NOT NULL DEFAULT 0,
    `modified`         DATETIME NULL,
    `modified_by`      INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_state` (`state`),
    KEY `idx_access` (`access`),
    KEY `idx_alias` (`alias`(100)),
    KEY `idx_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories: hierarchical tree, scoped per directory (adjacency list + level/path)
CREATE TABLE IF NOT EXISTS `#__movielist_categories` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `asset_id`         INT UNSIGNED NOT NULL DEFAULT 0,
    `directory_id`     INT UNSIGNED NOT NULL DEFAULT 0,
    `parent_id`        INT UNSIGNED NOT NULL DEFAULT 0,
    `level`            INT UNSIGNED NOT NULL DEFAULT 1,
    `path`             VARCHAR(1024) NOT NULL DEFAULT '',
    `title`            VARCHAR(400) NOT NULL DEFAULT '',
    `alias`            VARCHAR(400) NOT NULL DEFAULT '',
    `description`      MEDIUMTEXT NULL,
    `image`            VARCHAR(1024) NOT NULL DEFAULT '',
    `params`           JSON NULL,
    `metakey`          TEXT NULL,
    `metadesc`         TEXT NULL,
    `language`         CHAR(7) NOT NULL DEFAULT '*',
    `access`           INT UNSIGNED NOT NULL DEFAULT 1,
    `state`            TINYINT NOT NULL DEFAULT 1,
    `ordering`         INT NOT NULL DEFAULT 0,
    `checked_out`      INT UNSIGNED NULL,
    `checked_out_time` DATETIME NULL,
    `created`          DATETIME NULL,
    `created_by`       INT UNSIGNED NOT NULL DEFAULT 0,
    `modified`         DATETIME NULL,
    `modified_by`      INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_directory` (`directory_id`),
    KEY `idx_parent` (`parent_id`),
    KEY `idx_state` (`state`),
    KEY `idx_access` (`access`),
    KEY `idx_alias` (`alias`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Movies: the listings
CREATE TABLE IF NOT EXISTS `#__movielist_movies` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `asset_id`         INT UNSIGNED NOT NULL DEFAULT 0,
    `directory_id`     INT UNSIGNED NOT NULL DEFAULT 0,
    `catid`            INT UNSIGNED NOT NULL DEFAULT 0,
    `title`            VARCHAR(400) NOT NULL DEFAULT '',
    `alias`            VARCHAR(400) NOT NULL DEFAULT '',
    `original_title`   VARCHAR(400) NOT NULL DEFAULT '',
    `year`             SMALLINT UNSIGNED NULL,
    `duration`         INT UNSIGNED NULL,
    `country`          VARCHAR(255) NOT NULL DEFAULT '',
    `original_lang`    VARCHAR(255) NOT NULL DEFAULT '',
    `synopsis`         MEDIUMTEXT NULL,
    `director`         VARCHAR(400) NOT NULL DEFAULT '',
    `director_photo`   VARCHAR(1024) NOT NULL DEFAULT '',
    `director_bio`     MEDIUMTEXT NULL,
    `poster`           VARCHAR(1024) NOT NULL DEFAULT '',
    `trailer_url`      VARCHAR(1024) NOT NULL DEFAULT '',
    `params`           JSON NULL,
    `metakey`          TEXT NULL,
    `metadesc`         TEXT NULL,
    `language`         CHAR(7) NOT NULL DEFAULT '*',
    `access`           INT UNSIGNED NOT NULL DEFAULT 1,
    `state`            TINYINT NOT NULL DEFAULT 1,
    `featured`         TINYINT NOT NULL DEFAULT 0,
    `ordering`         INT NOT NULL DEFAULT 0,
    `hits`             INT UNSIGNED NOT NULL DEFAULT 0,
    `checked_out`      INT UNSIGNED NULL,
    `checked_out_time` DATETIME NULL,
    `created`          DATETIME NULL,
    `created_by`       INT UNSIGNED NOT NULL DEFAULT 0,
    `modified`         DATETIME NULL,
    `modified_by`      INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_directory` (`directory_id`),
    KEY `idx_catid` (`catid`),
    KEY `idx_state` (`state`),
    KEY `idx_access` (`access`),
    KEY `idx_featured` (`featured`),
    KEY `idx_alias` (`alias`(100)),
    KEY `idx_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Custom field definitions, scoped per directory
CREATE TABLE IF NOT EXISTS `#__movielist_fields` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `directory_id`  INT UNSIGNED NOT NULL DEFAULT 0,
    `is_system`     TINYINT NOT NULL DEFAULT 0,
    `field_key`     VARCHAR(100) NOT NULL DEFAULT '',
    `title`         VARCHAR(255) NOT NULL DEFAULT '',
    `name`          VARCHAR(255) NOT NULL DEFAULT '',
    `type`          VARCHAR(50) NOT NULL DEFAULT 'text',
    `label`         VARCHAR(255) NOT NULL DEFAULT '',
    `description`   TEXT NULL,
    `default_value` TEXT NULL,
    `options`       JSON NULL,
    `required`      TINYINT NOT NULL DEFAULT 0,
    `searchable`    TINYINT NOT NULL DEFAULT 0,
    `is_multiple`   TINYINT NOT NULL DEFAULT 0,
    `multiple_mode` VARCHAR(20) NOT NULL DEFAULT 'single',
    `max_items`     INT NOT NULL DEFAULT 0,
    `subfields`     JSON NULL,
    `show_in_list`   TINYINT NOT NULL DEFAULT 0,
    `show_in_detail` TINYINT NOT NULL DEFAULT 1,
    `ordering`      INT NOT NULL DEFAULT 0,
    `state`         TINYINT NOT NULL DEFAULT 1,
    `params`        JSON NULL,
    PRIMARY KEY (`id`),
    KEY `idx_directory` (`directory_id`),
    KEY `idx_state` (`state`),
    KEY `idx_name` (`name`),
    KEY `idx_system` (`is_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Values for custom fields, per movie
CREATE TABLE IF NOT EXISTS `#__movielist_field_values` (
    `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `field_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `movie_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `value`    MEDIUMTEXT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_field_movie` (`field_id`, `movie_id`),
    KEY `idx_movie` (`movie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Image gallery (stills / extra photos) per movie
CREATE TABLE IF NOT EXISTS `#__movielist_images` (
    `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `movie_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `filename` VARCHAR(1024) NOT NULL DEFAULT '',
    `caption`  VARCHAR(1024) NOT NULL DEFAULT '',
    `type`     VARCHAR(50) NOT NULL DEFAULT 'still',
    `ordering` INT NOT NULL DEFAULT 0,
    `state`    TINYINT NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_movie` (`movie_id`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Migrates Mosets Tree (#__mt_*) data into com_movielist.
 *
 * Mapping (decided with the user):
 *  - Top-level MT categories (festivals)        -> Directories
 *  - All deeper MT categories                   -> Categories (hierarchy preserved per directory)
 *  - MT links                                   -> Movies (title = link_name)
 *  - A canonical set of MT custom fields,
 *    derived from the latest (14th) festival    -> com_movielist custom fields (all custom, none system)
 *  - MT listing images + director photo         -> copied into media/com_movielist and referenced
 */
class MigrateModel extends BaseDatabaseModel
{
    /**
     * Where Mosets Tree keeps its files (relative to the site root).
     */
    private const MT_LISTINGS = 'media/com_mtree/images/listings/o';
    private const MT_ATTACH    = 'media/com_mtree/attachments';

    /**
     * Where we copy migrated files to (relative to the site root).
     */
    private const ML_LISTINGS = 'media/com_movielist/images/listings';
    private const ML_ATTACH    = 'media/com_movielist/images/attachments';

    /**
     * The canonical custom-field set, taken from the 14th festival.
     * cf_id => [name, label, type, required, show_in_detail, show_in_list, ordering]
     */
    private const FIELDS = [
        29 => ['greek_title',       'Ελληνικός Τίτλος - Greek Title',              'text',     0, 1, 1, 2],
        30 => ['director',          'Σκηνοθέτης - Director',                       'text',     1, 1, 1, 3],
        31 => ['producer',          'Παραγωγός - Producer',                        'text',     1, 1, 0, 4],
        32 => ['country',           'Χώρα / Χώρες Παραγωγής - Country',            'text',     1, 1, 1, 5],
        70 => ['year',              'Έτος Παραγωγής - Year',                       'text',     1, 1, 1, 7],
        55 => ['duration',          'Διάρκεια σε λεπτά - Duration (min)',          'number',   1, 1, 1, 8],
        40 => ['subtitle_language', 'Γλώσσα Υποτίτλων - Subtitle Language',        'text',     1, 1, 0, 20],
        42 => ['director_photo',    'Φωτογραφία Σκηνοθέτη - Director\'s Photo',    'media',    1, 1, 0, 21],
        43 => ['cast',              'Συντελεστές - Credits',                       'textarea', 1, 1, 0, 22],
        44 => ['synopsis_gr',       'Περίληψη (Ελληνικά) - Synopsis (GR)',         'editor',   0, 1, 0, 23],
        56 => ['synopsis_en',       'Synopsis (EN) - Περίληψη (Αγγλικά)',          'editor',   1, 1, 1, 24],
        46 => ['director_bio_gr',   'Βιογραφικό (Ελληνικά) - Director Bio (GR)',   'editor',   0, 1, 0, 25],
        57 => ['director_bio_en',   'Director Bio (EN) - Βιογραφικό (Αγγλικά)',    'editor',   1, 1, 1, 26],
        49 => ['trailer',           'Trailer',                                     'url',      0, 1, 0, 28],
    ];

    /**
     * Are the Mosets Tree tables present in this database?
     */
    public function mtPresent(): bool
    {
        $db     = $this->getDatabase();
        $tables = $db->getTableList();
        $prefix = $db->getPrefix();

        return \in_array($prefix . 'mt_cats', $tables, true)
            && \in_array($prefix . 'mt_links', $tables, true)
            && \in_array($prefix . 'mt_cl', $tables, true);
    }

    /**
     * Source / target counts for the dashboard.
     */
    public function getStatus(): array
    {
        $db = $this->getDatabase();

        $src = [
            'directories' => 'SELECT COUNT(*) FROM #__mt_cats WHERE cat_parent = 0',
            'categories'  => 'SELECT COUNT(*) FROM #__mt_cats WHERE cat_parent > 0',
            'movies'      => 'SELECT COUNT(*) FROM #__mt_links',
        ];
        $tgt = [
            'directories' => 'SELECT COUNT(*) FROM #__movielist_directories',
            'categories'  => 'SELECT COUNT(*) FROM #__movielist_categories',
            'movies'      => 'SELECT COUNT(*) FROM #__movielist_movies',
        ];

        $out = ['source' => [], 'target' => []];

        foreach ($src as $k => $q) {
            $out['source'][$k] = (int) $db->setQuery($q)->loadResult();
        }

        foreach ($tgt as $k => $q) {
            $out['target'][$k] = (int) $db->setQuery($q)->loadResult();
        }

        return $out;
    }

    /**
     * Ensure the source-id => target-id mapping table exists.
     */
    private function ensureMapTable(): void
    {
        $this->getDatabase()->setQuery(
            'CREATE TABLE IF NOT EXISTS `#__movielist_migration_map` (
                `type` VARCHAR(20) NOT NULL,
                `source_id` INT NOT NULL,
                `target_id` INT NOT NULL,
                PRIMARY KEY (`type`, `source_id`),
                KEY `idx_target` (`type`, `target_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();
    }

    private function mapSet(string $type, int $source, int $target): void
    {
        $db  = $this->getDatabase();
        $row = (object) ['type' => $type, 'source_id' => $source, 'target_id' => $target];

        try {
            $db->insertObject('#__movielist_migration_map', $row);
        } catch (\Throwable $e) {
            // Already mapped -> update.
            $db->setQuery(
                $db->getQuery(true)
                    ->update('#__movielist_migration_map')
                    ->set($db->quoteName('target_id') . ' = ' . (int) $target)
                    ->where($db->quoteName('type') . ' = ' . $db->quote($type))
                    ->where($db->quoteName('source_id') . ' = ' . (int) $source)
            )->execute();
        }
    }

    private function mapGet(string $type, int $source): int
    {
        $db = $this->getDatabase();

        return (int) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('target_id'))
                ->from($db->quoteName('#__movielist_migration_map'))
                ->where($db->quoteName('type') . ' = ' . $db->quote($type))
                ->where($db->quoteName('source_id') . ' = ' . (int) $source)
        )->loadResult();
    }

    /**
     * Map a Mosets field type to a com_movielist field type.
     */
    private function mlType(string $type): string
    {
        return $type;
    }

    /**
     * Step 1: seed the custom fields, then migrate directories and the category tree.
     * Returns the total number of links to migrate.
     */
    public function prepare(): int
    {
        $this->ensureMapTable();

        $this->migrateFields();
        $this->migrateDirectories();
        $this->migrateCategories();

        return (int) $this->getDatabase()->setQuery('SELECT COUNT(*) FROM #__mt_links')->loadResult();
    }

    /**
     * Create the canonical custom fields (idempotent via the map table).
     */
    private function migrateFields(): void
    {
        $db = $this->getDatabase();

        foreach (self::FIELDS as $cfId => $def) {
            if ($this->mapGet('field', $cfId) > 0) {
                continue;
            }

            [$name, $label, $type, $required, $detail, $list, $ordering] = $def;

            $row                 = new \stdClass();
            $row->directory_id   = 0;
            $row->is_system      = 0;
            $row->field_key      = '';
            $row->title          = $label;
            $row->name           = $name;
            $row->type           = $this->mlType($type);
            $row->label          = $label;
            $row->required       = (int) $required;
            $row->searchable     = \in_array($name, ['greek_title', 'director', 'country', 'cast'], true) ? 1 : 0;
            $row->show_in_list   = (int) $list;
            $row->show_in_detail = (int) $detail;
            $row->ordering       = (int) $ordering;
            $row->state          = 1;

            $db->insertObject('#__movielist_fields', $row);
            $this->mapSet('field', $cfId, (int) $db->insertid());
        }
    }

    /**
     * Top-level MT categories -> directories.
     */
    private function migrateDirectories(): void
    {
        $db   = $this->getDatabase();
        $cats = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['cat_id', 'cat_name', 'alias', 'cat_desc', 'cat_image', 'cat_published', 'ordering']))
                ->from($db->quoteName('#__mt_cats'))
                ->where($db->quoteName('cat_parent') . ' = 0')
                ->where($db->quoteName('cat_id') . ' > 0')
                ->order($db->quoteName('ordering') . ' ASC')
        )->loadObjectList();

        $now  = Factory::getDate()->toSql();
        $user = (int) Factory::getApplication()->getIdentity()->id;

        foreach ($cats as $c) {
            if ($this->mapGet('directory', (int) $c->cat_id) > 0) {
                continue;
            }

            $row              = new \stdClass();
            $row->title       = trim($c->cat_name);
            $row->alias       = $this->alias($c->alias, $c->cat_name);
            $row->description = $c->cat_desc;
            $row->image       = '';
            $row->language    = '*';
            $row->access      = 1;
            $row->state       = (int) $c->cat_published;
            $row->ordering    = (int) $c->ordering;
            $row->created     = $now;
            $row->created_by  = $user;

            $db->insertObject('#__movielist_directories', $row);
            $this->mapSet('directory', (int) $c->cat_id, (int) $db->insertid());
        }
    }

    /**
     * Deeper MT categories -> categories, preserving the hierarchy inside each directory.
     */
    private function migrateCategories(): void
    {
        $db  = $this->getDatabase();
        $all = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['cat_id', 'cat_parent', 'cat_name', 'alias', 'cat_desc', 'cat_published', 'ordering', 'lft']))
                ->from($db->quoteName('#__mt_cats'))
                ->where($db->quoteName('cat_id') . ' > 0')
                ->order($db->quoteName('lft') . ' ASC')
        )->loadObjectList();

        // Index by id and resolve each cat's top-level festival ancestor.
        $byId = [];
        foreach ($all as $c) {
            $byId[(int) $c->cat_id] = $c;
        }

        $now  = Factory::getDate()->toSql();
        $user = (int) Factory::getApplication()->getIdentity()->id;

        // Ordered by lft => parents always processed before children.
        foreach ($all as $c) {
            $catId = (int) $c->cat_id;

            if ((int) $c->cat_parent === 0) {
                continue; // a directory, already handled
            }

            if ($this->mapGet('category', $catId) > 0) {
                continue;
            }

            $parentMt = (int) $c->cat_parent;

            // Directory = the top-level ancestor.
            $dirId = $this->resolveDirectory($catId, $byId);

            if ($dirId === 0) {
                continue;
            }

            if (isset($byId[$parentMt]) && (int) $byId[$parentMt]->cat_parent === 0) {
                // Parent is a festival -> this is a first-level category.
                $parentId = 0;
                $level    = 1;
                $path     = '';
            } else {
                $parentId = $this->mapGet('category', $parentMt);
                $prow     = $parentId > 0 ? $this->loadCategory($parentId) : null;
                $level    = $prow ? ((int) $prow->level + 1) : 1;
                $path     = $prow ? ($prow->path . '/') : '';
            }

            $alias = $this->alias($c->alias, $c->cat_name);

            $row               = new \stdClass();
            $row->directory_id = $dirId;
            $row->parent_id    = $parentId;
            $row->level        = $level;
            $row->path         = $path . $alias;
            $row->title        = trim($c->cat_name);
            $row->alias        = $alias;
            $row->description   = $c->cat_desc;
            $row->image        = '';
            $row->language     = '*';
            $row->access       = 1;
            $row->state        = (int) $c->cat_published;
            $row->ordering     = (int) $c->ordering;
            $row->created      = $now;
            $row->created_by   = $user;

            $db->insertObject('#__movielist_categories', $row);
            $this->mapSet('category', $catId, (int) $db->insertid());
        }
    }

    private function loadCategory(int $id): ?object
    {
        $db = $this->getDatabase();

        return $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['id', 'level', 'path']))
                ->from($db->quoteName('#__movielist_categories'))
                ->where($db->quoteName('id') . ' = ' . (int) $id)
        )->loadObject() ?: null;
    }

    /**
     * Walk up MT cat_parent chain to the festival, return its directory id.
     */
    private function resolveDirectory(int $catId, array $byId): int
    {
        $guard = 0;
        $cur   = $catId;

        while (isset($byId[$cur]) && $guard++ < 50) {
            $parent = (int) $byId[$cur]->cat_parent;

            if ($parent === 0) {
                return $this->mapGet('directory', $cur);
            }

            $cur = $parent;
        }

        return 0;
    }

    /**
     * Step 2: migrate a slice of links (movies) with their field values and images.
     * Returns ['done' => int, 'images' => int].
     */
    public function migrateLinks(int $offset, int $limit): array
    {
        $db    = $this->getDatabase();
        $links = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['link_id', 'link_name', 'alias', 'link_published', 'ordering', 'link_featured', 'link_hits', 'link_created']))
                ->from($db->quoteName('#__mt_links'))
                ->order($db->quoteName('link_id') . ' ASC'),
            $offset,
            $limit
        )->loadObjectList();

        $fieldMap = $this->fieldMap();
        $now      = Factory::getDate()->toSql();
        $user     = (int) Factory::getApplication()->getIdentity()->id;
        $images   = 0;

        foreach ($links as $l) {
            $linkId = (int) $l->link_id;

            if ($this->mapGet('movie', $linkId) > 0) {
                continue;
            }

            // Resolve the primary category.
            $mainCat = (int) $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName('cat_id'))
                    ->from($db->quoteName('#__mt_cl'))
                    ->where($db->quoteName('link_id') . ' = ' . $linkId)
                    ->order($db->quoteName('main') . ' DESC'),
                0,
                1
            )->loadResult();

            $catid = $this->mapGet('category', $mainCat);
            $dirId = 0;

            if ($catid > 0) {
                $dirId = (int) $db->setQuery(
                    $db->getQuery(true)
                        ->select($db->quoteName('directory_id'))
                        ->from($db->quoteName('#__movielist_categories'))
                        ->where($db->quoteName('id') . ' = ' . (int) $catid)
                )->loadResult();
            } else {
                // Main category may itself be a festival (directory).
                $dirId = $this->mapGet('directory', $mainCat);
            }

            $row               = new \stdClass();
            $row->directory_id = $dirId;
            $row->catid        = $catid;
            $row->title        = trim($l->link_name) !== '' ? trim($l->link_name) : 'Untitled';
            $row->alias        = $this->alias($l->alias, $l->link_name);
            $row->state        = (int) $l->link_published;
            $row->featured     = (int) $l->link_featured > 0 ? 1 : 0;
            $row->ordering     = (int) $l->ordering;
            $row->hits         = (int) $l->link_hits;
            $row->language     = '*';
            $row->access       = 1;
            $row->created      = ($l->link_created && $l->link_created !== '0000-00-00 00:00:00') ? $l->link_created : $now;
            $row->created_by   = $user;

            $db->insertObject('#__movielist_movies', $row);
            $movieId = (int) $db->insertid();
            $this->mapSet('movie', $linkId, $movieId);

            $images += $this->migrateValues($linkId, $movieId, $fieldMap);
            $images += $this->migrateImages($linkId, $movieId);
        }

        return ['done' => \count($links), 'images' => $images];
    }

    /**
     * cf_id => movielist field id, for the canonical set.
     */
    private function fieldMap(): array
    {
        $map = [];

        foreach (array_keys(self::FIELDS) as $cfId) {
            $fid = $this->mapGet('field', $cfId);

            if ($fid > 0) {
                $map[$cfId] = $fid;
            }
        }

        return $map;
    }

    /**
     * Copy custom-field values for one link. Returns the number of files copied.
     */
    private function migrateValues(int $linkId, int $movieId, array $fieldMap): int
    {
        $db     = $this->getDatabase();
        $copied = 0;

        $values = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['cf_id', 'value', 'attachment']))
                ->from($db->quoteName('#__mt_cfvalues'))
                ->where($db->quoteName('link_id') . ' = ' . $linkId)
        )->loadObjectList();

        foreach ($values as $v) {
            $cfId = (int) $v->cf_id;

            if (!isset($fieldMap[$cfId])) {
                continue;
            }

            $value = (string) $v->value;

            // Director photo (image custom field) -> copy the attachment file.
            if ($cfId === 42) {
                $raw = (string) $db->setQuery(
                    $db->getQuery(true)
                        ->select($db->quoteName('raw_filename'))
                        ->from($db->quoteName('#__mt_cfvalues_att'))
                        ->where($db->quoteName('link_id') . ' = ' . $linkId)
                        ->where($db->quoteName('cf_id') . ' = 42'),
                    0,
                    1
                )->loadResult();

                if ($raw === '') {
                    continue;
                }

                $dest  = $this->copyFile(self::MT_ATTACH . '/' . $raw, self::ML_ATTACH . '/' . $raw);
                if ($dest === '') {
                    continue;
                }
                $value = $dest;
                $copied++;
            }

            if (trim($value) === '' || trim($value) === '-') {
                continue;
            }

            $row           = new \stdClass();
            $row->field_id = $fieldMap[$cfId];
            $row->movie_id = $movieId;
            $row->value    = $value;

            try {
                $db->insertObject('#__movielist_field_values', $row);
            } catch (\Throwable $e) {
                // duplicate (re-run) -> ignore
            }
        }

        return $copied;
    }

    /**
     * Copy listing images for one link into the gallery and set the poster.
     * Returns the number of files copied.
     */
    private function migrateImages(int $linkId, int $movieId): int
    {
        $db     = $this->getDatabase();
        $copied = 0;

        $imgs = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['filename', 'ordering']))
                ->from($db->quoteName('#__mt_images'))
                ->where($db->quoteName('link_id') . ' = ' . $linkId)
                ->order($db->quoteName('ordering') . ' ASC')
        )->loadObjectList();

        $first = '';

        foreach ($imgs as $i => $img) {
            $file = (string) $img->filename;
            $dest = $this->copyFile(self::MT_LISTINGS . '/' . $file, self::ML_LISTINGS . '/' . $file);

            if ($dest === '') {
                continue;
            }

            $copied++;

            if ($first === '') {
                $first = $dest;
            }

            $row           = new \stdClass();
            $row->movie_id = $movieId;
            $row->filename = $dest;
            $row->caption  = '';
            $row->type     = 'still';
            $row->ordering = (int) $img->ordering;
            $row->state    = 1;
            $db->insertObject('#__movielist_images', $row);
        }

        // First listing image becomes the poster.
        if ($first !== '') {
            $db->setQuery(
                $db->getQuery(true)
                    ->update('#__movielist_movies')
                    ->set($db->quoteName('poster') . ' = ' . $db->quote($first))
                    ->where($db->quoteName('id') . ' = ' . (int) $movieId)
            )->execute();
        }

        return $copied;
    }

    /**
     * Copy a file (root-relative paths). Returns the destination path on success, '' otherwise.
     */
    private function copyFile(string $relSrc, string $relDest): string
    {
        $src  = JPATH_ROOT . '/' . $relSrc;
        $dest = JPATH_ROOT . '/' . $relDest;

        if (!is_file($src)) {
            return '';
        }

        if (is_file($dest)) {
            return $relDest; // already copied
        }

        $dir = \dirname($dest);

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return '';
        }

        return @copy($src, $dest) ? $relDest : '';
    }

    private function alias(?string $alias, ?string $fallback): string
    {
        $alias = trim((string) $alias);

        if ($alias === '') {
            $alias = (string) $fallback;
        }

        $out = OutputFilter::stringUrlSafe($alias);

        return $out !== '' ? $out : 'item-' . substr(md5((string) $fallback . microtime()), 0, 8);
    }

    /**
     * Remove everything previously migrated (by the map) and clear the map.
     */
    public function reset(): void
    {
        $db = $this->getDatabase();
        $this->ensureMapTable();

        $delete = [
            'movie'     => '#__movielist_movies',
            'category'  => '#__movielist_categories',
            'directory' => '#__movielist_directories',
            'field'     => '#__movielist_fields',
        ];

        // Field values + images for migrated movies.
        $movieIds = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('target_id'))
                ->from($db->quoteName('#__movielist_migration_map'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('movie'))
        )->loadColumn();

        if ($movieIds) {
            $ids = implode(',', array_map('intval', $movieIds));
            $db->setQuery('DELETE FROM #__movielist_field_values WHERE movie_id IN (' . $ids . ')')->execute();
            $db->setQuery('DELETE FROM #__movielist_images WHERE movie_id IN (' . $ids . ')')->execute();
        }

        foreach ($delete as $type => $table) {
            $ids = $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName('target_id'))
                    ->from($db->quoteName('#__movielist_migration_map'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote($type))
            )->loadColumn();

            if ($ids) {
                $in = implode(',', array_map('intval', $ids));
                $db->setQuery('DELETE FROM ' . $db->quoteName($table) . ' WHERE id IN (' . $in . ')')->execute();
            }
        }

        $db->setQuery('TRUNCATE TABLE #__movielist_migration_map')->execute();
    }
}

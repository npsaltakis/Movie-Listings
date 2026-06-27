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
use Nickpsal\Component\Movielist\Administrator\Helper\FieldsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Imports FilmFreeway submission CSV exports into com_movielist.
 *
 * FilmFreeway has no organiser API, so the official path is the Submissions CSV
 * export. Each row becomes a Movie (imported unpublished for curation); the
 * submission category becomes a com_movielist category under the chosen directory.
 */
class FfimportModel extends BaseDatabaseModel
{
    /**
     * FilmFreeway CSV header => com_movielist custom-field name.
     */
    private const MAP = [
        'Project Title (Original Language)' => 'greek_title',
        'Directors'                         => 'director',
        'Producers'                         => 'producer',
        'Country of Origin'                 => 'country',
        'Trailer URL'                       => 'trailer',
        'Synopsis'                          => 'synopsis_en',
        'Synopsis (Original Language)'      => 'synopsis_gr',
        'Submitter Biography'               => 'director_bio_en',
    ];

    /**
     * Directories for the target dropdown.
     *
     * @return  array
     */
    public function getDirectories(): array
    {
        $db = $this->getDatabase();

        return $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['id', 'title']))
                ->from($db->quoteName('#__movielist_directories'))
                ->order($db->quoteName('ordering') . ' ASC, ' . $db->quoteName('title') . ' ASC')
        )->loadObjectList() ?: [];
    }

    /**
     * Import a FilmFreeway CSV.
     *
     * @param   string   $csvPath           Absolute path to the uploaded CSV.
     * @param   integer  $directoryId       Existing directory id (0 to create a new one).
     * @param   string   $newDirectoryTitle Title for a new directory (when $directoryId is 0).
     *
     * @return  array{movies:int, skipped:int, categories:int, directory:int}
     */
    public function import(string $csvPath, int $directoryId, string $newDirectoryTitle): array
    {
        if (!is_file($csvPath)) {
            throw new \RuntimeException('CSV file not found.');
        }

        $this->ensureMapTable();

        // Resolve / create the target directory.
        if ($directoryId <= 0) {
            $title = trim($newDirectoryTitle);

            if ($title === '') {
                throw new \RuntimeException('Choose a directory or enter a name for a new one.');
            }

            $directoryId = $this->createDirectory($title);
        }

        $fieldMap = $this->fieldIdByName();
        $handle   = fopen($csvPath, 'r');
        $header   = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            throw new \RuntimeException('The CSV is empty or unreadable.');
        }

        $col = array_flip($header); // column name => index

        $required = ['Project Title', 'Submission ID', 'Submission Categories'];
        foreach ($required as $r) {
            if (!isset($col[$r])) {
                fclose($handle);
                throw new \RuntimeException('This does not look like a FilmFreeway export (missing "' . $r . '").');
            }
        }

        $db        = $this->getDatabase();
        $now       = Factory::getDate()->toSql();
        $user      = (int) Factory::getApplication()->getIdentity()->id;
        $catCache  = [];
        $movies    = 0;
        $skipped   = 0;
        $newCats   = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $get = static fn (string $name) => trim((string) ($row[$col[$name]] ?? ''));

            $subId = $get('Submission ID');
            $title = $get('Project Title');

            if ($subId === '' || $title === '') {
                continue;
            }

            if ($this->mapGet('ff', $subId) > 0) {
                $skipped++;
                continue;
            }

            // Category from the (first) submission category.
            $catName = trim(explode(',', $get('Submission Categories'))[0]);
            $catid   = 0;

            if ($catName !== '') {
                if (!isset($catCache[$catName])) {
                    [$cid, $created]    = $this->resolveCategory($directoryId, $catName);
                    $catCache[$catName] = $cid;
                    $newCats           += $created ? 1 : 0;
                }

                $catid = $catCache[$catName];
            }

            // Movie row.
            $movie               = new \stdClass();
            $movie->directory_id = $directoryId;
            $movie->catid        = $catid;
            $movie->title        = $title;
            $movie->alias        = $this->alias($title, $subId);
            $movie->state        = 0; // unpublished -> curate before publishing
            $movie->language     = '*';
            $movie->access       = 1;
            $movie->created      = $now;
            $movie->created_by   = $user;

            $db->insertObject('#__movielist_movies', $movie);
            $movieId = (int) $db->insertid();
            $this->mapSet('ff', $subId, $movieId);

            // Custom-field values.
            $values = [];

            foreach (self::MAP as $csvName => $field) {
                if (isset($col[$csvName]) && isset($fieldMap[$field])) {
                    $v = $get($csvName);
                    if ($v !== '') {
                        $values[$fieldMap[$field]] = $v;
                    }
                }
            }

            // Derived fields.
            if (isset($fieldMap['year']) && ($d = $get('Completion Date')) !== '' && preg_match('/(\d{4})/', $d, $m)) {
                $values[$fieldMap['year']] = $m[1];
            }

            if (isset($fieldMap['duration']) && ($dur = $this->durationToMinutes($get('Duration'))) > 0) {
                $values[$fieldMap['duration']] = (string) $dur;
            }

            $creditsFieldId = $fieldMap['cast'] ?? null;

            if ($creditsFieldId) {
                $castText = implode("\n", array_filter([$this->cleanCast($get('Key Cast')), $this->cleanCast($get('Other Credits'))]));
                $rows     = FieldsHelper::parseCreditsRows($castText);
                if ($rows) {
                    $values[$creditsFieldId] = json_encode($rows);
                }
            }

            foreach ($values as $fieldId => $value) {
                $fv           = new \stdClass();
                $fv->field_id = (int) $fieldId;
                $fv->movie_id = $movieId;
                $fv->value    = $value;

                try {
                    $db->insertObject('#__movielist_field_values', $fv);
                } catch (\Throwable $e) {
                    // ignore duplicates
                }
            }

            $movies++;
        }

        fclose($handle);

        return [
            'movies'     => $movies,
            'skipped'    => $skipped,
            'categories' => $newCats,
            'directory'  => $directoryId,
        ];
    }

    /**
     * Strip literal field labels that submitters sometimes type into the cast/credits
     * fields (e.g. "KEY CAST", "Cast:", "Other Credits -").
     */
    private function cleanCast(string $value): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $value);
        $out   = [];

        foreach ($lines as $line) {
            $t = preg_replace('/^\s*(key\s*cast|cast|actors?|other\s*credits|credits)\s*[:\-–—]?\s*/iu', '', trim($line));
            $t = trim((string) $t);

            if ($t !== '' && !preg_match('/^(key\s*cast|cast|actors?|other\s*credits|credits)$/iu', $t)) {
                $out[] = $t;
            }
        }

        return implode("\n", $out);
    }

    /**
     * Remove all movies previously imported from FilmFreeway (kept categories/directory).
     *
     * @return  void
     */
    public function reset(): void
    {
        $db  = $this->getDatabase();
        $this->ensureMapTable();

        $ids = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('target_id'))
                ->from($db->quoteName('#__movielist_migration_map'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('ff'))
        )->loadColumn();

        if ($ids) {
            $in = implode(',', array_map('intval', $ids));
            $db->setQuery('DELETE FROM #__movielist_field_values WHERE movie_id IN (' . $in . ')')->execute();
            $db->setQuery('DELETE FROM #__movielist_movies WHERE id IN (' . $in . ')')->execute();
        }

        $db->setQuery(
            $db->getQuery(true)
                ->delete($db->quoteName('#__movielist_migration_map'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('ff'))
        )->execute();
    }

    /**
     * Convert "HH:MM:SS" (or "MM:SS") to whole minutes.
     */
    private function durationToMinutes(string $dur): int
    {
        if ($dur === '' || strpos($dur, ':') === false) {
            return (int) $dur;
        }

        $parts = array_map('intval', explode(':', $dur));

        if (\count($parts) === 3) {
            [$h, $m, $s] = $parts;
        } else {
            $h = 0;
            [$m, $s] = $parts;
        }

        return (int) round(($h * 3600 + $m * 60 + $s) / 60);
    }

    /**
     * Resolve a category by (directory, title), creating it if needed.
     *
     * @return  array{0:int,1:bool}  [category id, was-created]
     */
    private function resolveCategory(int $directoryId, string $title): array
    {
        $db  = $this->getDatabase();
        $cid = (int) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__movielist_categories'))
                ->where($db->quoteName('directory_id') . ' = ' . $directoryId)
                ->where($db->quoteName('title') . ' = :t')
                ->bind(':t', $title, ParameterType::STRING),
            0,
            1
        )->loadResult();

        if ($cid > 0) {
            return [$cid, false];
        }

        $alias = $this->alias($title, (string) $directoryId);
        $row               = new \stdClass();
        $row->directory_id = $directoryId;
        $row->parent_id    = 0;
        $row->level        = 1;
        $row->path         = $alias;
        $row->title        = $title;
        $row->alias        = $alias;
        $row->language     = '*';
        $row->access       = 1;
        $row->state        = 1;
        $row->created      = Factory::getDate()->toSql();
        $row->created_by   = (int) Factory::getApplication()->getIdentity()->id;

        $db->insertObject('#__movielist_categories', $row);

        return [(int) $db->insertid(), true];
    }

    private function createDirectory(string $title): int
    {
        $db                = $this->getDatabase();
        $row               = new \stdClass();
        $row->title        = $title;
        $row->alias        = $this->alias($title, 'dir');
        $row->language     = '*';
        $row->access       = 1;
        $row->state        = 1;
        $row->created      = Factory::getDate()->toSql();
        $row->created_by   = (int) Factory::getApplication()->getIdentity()->id;

        $db->insertObject('#__movielist_directories', $row);

        return (int) $db->insertid();
    }

    /**
     * Custom field name => id.
     */
    private function fieldIdByName(): array
    {
        $db   = $this->getDatabase();
        $rows = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['id', 'name', 'title', 'label']))
                ->from($db->quoteName('#__movielist_fields'))
                ->where($db->quoteName('is_system') . ' = 0')
        )->loadObjectList() ?: [];

        $map = [];
        foreach ($rows as $r) {
            $id   = (int) $r->id;
            $name = (string) $r->name;

            $map[$name] = $id;

            if (!isset($map['cast']) && $this->isCreditsField($r)) {
                $map['cast'] = $id;
            }
        }

        return $map;
    }

    /**
     * Accept user-created Actor/Cast/Credits group fields as the FilmFreeway
     * "Key Cast" target, even when their machine name is not exactly "cast".
     */
    private function isCreditsField(object $field): bool
    {
        $name  = strtolower((string) ($field->name ?? ''));
        $label = strtolower((string) (($field->label ?? '') . ' ' . ($field->title ?? '')));

        if (\in_array($name, ['cast', 'actor', 'actors', 'credits', 'key_cast', 'keycast'], true)) {
            return true;
        }

        return preg_match('/\b(actor|actors|cast|credits|key cast)\b/u', $label) === 1;
    }

    private function alias(string $title, string $suffix): string
    {
        $a = OutputFilter::stringUrlSafe($title);

        return ($a !== '' ? $a : 'item') . '-' . $suffix;
    }

    // --- source-id => target-id map (shared table, type 'ff') ---------------

    private function ensureMapTable(): void
    {
        $this->getDatabase()->setQuery(
            'CREATE TABLE IF NOT EXISTS `#__movielist_migration_map` (
                `type` VARCHAR(20) NOT NULL,
                `source_id` VARCHAR(64) NOT NULL,
                `target_id` INT NOT NULL,
                PRIMARY KEY (`type`, `source_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();
    }

    private function mapGet(string $type, string $source): int
    {
        $db = $this->getDatabase();

        return (int) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('target_id'))
                ->from($db->quoteName('#__movielist_migration_map'))
                ->where($db->quoteName('type') . ' = :t')
                ->where($db->quoteName('source_id') . ' = :s')
                ->bind(':t', $type, ParameterType::STRING)
                ->bind(':s', $source, ParameterType::STRING)
        )->loadResult();
    }

    private function mapSet(string $type, string $source, int $target): void
    {
        $db  = $this->getDatabase();
        $row = (object) ['type' => $type, 'source_id' => $source, 'target_id' => $target];

        try {
            $db->insertObject('#__movielist_migration_map', $row);
        } catch (\Throwable $e) {
            // already mapped
        }
    }
}

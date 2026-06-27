<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for the directory-scoped custom fields system.
 */
class FieldsHelper
{
    /**
     * Load active custom (non-system) field definitions. Field config is global.
     *
     * @param   integer  $directoryId  Unused (kept for signature compatibility; fields are global).
     *
     * @return  array
     */
    public static function getFields(int $directoryId = 0): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__movielist_fields'))
            ->where($db->quoteName('state') . ' = 1')
            ->where($db->quoteName('is_system') . ' = 0')
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Load every active field definition (system + custom), ordered.
     *
     * @return  array
     */
    public static function getAllFields(): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__movielist_fields'))
            ->where($db->quoteName('state') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Resolve the ordered, visible fields for a movie in a given context, with display values.
     * System fields read from the movie columns; custom fields from the value table.
     *
     * @param   object  $movie    The movie record (must expose the system columns).
     * @param   string  $context  Either 'list' or 'detail'.
     *
     * @return  array  Array of stdClass{key,label,type,raw,display,is_system}.
     */
    public static function getRenderFields(object $movie, string $context = 'detail'): array
    {
        $column = $context === 'list' ? 'show_in_list' : 'show_in_detail';

        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $fields = $db->setQuery(
            $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__movielist_fields'))
                ->where($db->quoteName('state') . ' = 1')
                ->where($db->quoteName($column) . ' = 1')
                ->order($db->quoteName('ordering') . ' ASC')
        )->loadObjectList() ?: [];

        if (!$fields) {
            return [];
        }

        // Custom field values for this movie, keyed by field name.
        $values  = isset($movie->id) ? self::getValues((int) $movie->id) : [];
        $rendered = [];

        foreach ($fields as $field) {
            if ((int) $field->is_system === 1) {
                $key = $field->field_key;
                $raw = ($key === 'category') ? ($movie->category_title ?? '') : ($movie->$key ?? '');
            } else {
                $raw = $values[$field->name] ?? '';
            }

            if ($raw === null || $raw === '' || $raw === []) {
                continue;
            }

            // Group (composite) repeatable field -> rows of labelled cells.
            if ((int) ($field->is_multiple ?? 0) === 1 && ($field->multiple_mode ?? 'single') === 'group') {
                $subfields = json_decode($field->subfields ?? '', true) ?: [];
                $meta      = [];

                foreach ($subfields as $i => $sf) {
                    $meta[self::subfieldName($sf, $i)] = [
                        'label' => Text::_($sf['label'] ?? ''),
                        'type'  => $sf['type'] ?? 'text',
                    ];
                }

                $rows = [];

                foreach ((array) $raw as $row) {
                    if (!\is_array($row)) {
                        continue;
                    }

                    $cells  = [];
                    $hasVal = false;

                    foreach ($meta as $n => $m) {
                        $v = $row[$n] ?? '';

                        if ($v !== '' && $v !== null) {
                            $hasVal = true;

                            if (\in_array($m['type'], ['media', 'image'], true)) {
                                $v = self::cleanImage((string) $v);
                            }
                        }

                        $cells[] = (object) ['label' => $m['label'], 'type' => $m['type'], 'value' => $v];
                    }

                    if ($hasVal) {
                        $rows[] = $cells;
                    }
                }

                if (!$rows) {
                    continue;
                }

                $o              = new \stdClass();
                $o->key         = $field->name;
                $o->label       = Text::_($field->label ?: $field->title);
                $o->type        = 'group';
                $o->is_group    = 1;
                $o->rows        = $rows;
                $o->values      = [];
                $o->display     = '';
                $o->is_system   = 0;
                $o->is_multiple = 1;

                $rendered[] = $o;

                continue;
            }

            // Normalise to a flat array of scalar values (handles repeatable subform rows).
            $vals = [];

            foreach ((array) $raw as $entry) {
                $v = \is_array($entry) ? ($entry['value'] ?? reset($entry)) : $entry;

                if ($v !== '' && $v !== null) {
                    $vals[] = $v;
                }
            }

            if (!$vals) {
                continue;
            }

            // Strip the media field metadata fragment from image paths.
            if (\in_array($field->type, ['media', 'image'], true)) {
                $vals = array_map(static fn ($v) => self::cleanImage((string) $v), $vals);
            }

            // Resolve list/radio option values to their labels.
            if (\in_array($field->type, ['list', 'select', 'radio', 'checkboxes'], true) && !empty($field->options)) {
                $options = json_decode($field->options, true) ?: [];
                $omap    = [];

                foreach ($options as $opt) {
                    if (isset($opt['value'])) {
                        $omap[$opt['value']] = $opt['name'] ?? $opt['value'];
                    }
                }

                $vals = array_map(static fn ($v) => $omap[$v] ?? $v, $vals);
            }

            $o            = new \stdClass();
            $o->key       = (int) $field->is_system === 1 ? $field->field_key : $field->name;
            $o->label     = Text::_($field->label ?: $field->title);
            $o->type      = $field->type;
            $o->values    = $vals;
            $o->display   = implode(', ', $vals);
            $o->is_system = (int) $field->is_system;
            $o->is_multiple = (int) ($field->is_multiple ?? 0);
            $o->is_group  = 0;

            $rendered[] = $o;
        }

        return $rendered;
    }

    /**
     * Inject the custom (non-system) fields into a movie form, under the "com_fields" group.
     *
     * @param   Form     $form         The form to extend.
     * @param   integer  $directoryId  Unused (fields are global).
     *
     * @return  void
     */
    public static function addFieldsToForm(Form $form, int $directoryId = 0): void
    {
        $fields = self::getFields();

        if (!$fields) {
            return;
        }

        $xml = new \SimpleXMLElement('<form><fields name="com_fields"></fields></form>');
        $fieldsNode = $xml->fields;

        foreach ($fields as $field) {
            $label   = $field->label ?: $field->title;
            $options = json_decode($field->options ?? '', true);

            if ((int) ($field->is_multiple ?? 0) === 1) {
                // Repeatable field -> a subform with a single inner field per row.
                $node = $fieldsNode->addChild('field');
                $node->addAttribute('name', $field->name);
                $node->addAttribute('type', 'subform');
                $node->addAttribute('label', $label);
                $node->addAttribute('multiple', 'true');
                $node->addAttribute('layout', 'joomla.form.field.subform.repeatable-table');

                $max  = (int) ($field->max_items ?? 0);
                $desc = trim((string) ($field->description ?? ''));

                if ($max > 0) {
                    $node->addAttribute('max', (string) $max);
                    $desc = trim($desc . ' (' . Text::sprintf('COM_MOVIELIST_FIELD_MAX_ITEMS_NOTE', $max) . ')');
                }

                if ($desc !== '') {
                    $node->addAttribute('description', $desc);
                }

                $subForm   = $node->addChild('form');
                $mode      = $field->multiple_mode ?? 'single';
                $subfields = json_decode($field->subfields ?? '', true);

                if ($mode === 'group' && \is_array($subfields) && $subfields) {
                    // Composite row: one inner field per defined sub-field (e.g. name + role).
                    foreach ($subfields as $i => $sf) {
                        $sfName = self::subfieldName($sf, $i);
                        $inner  = $subForm->addChild('field');
                        $inner->addAttribute('name', $sfName);
                        $inner->addAttribute('type', self::mapType($sf['type'] ?? 'text'));
                        $inner->addAttribute('label', $sf['label'] ?? $sfName);
                    }
                } else {
                    // Single value per row.
                    $inner = $subForm->addChild('field');
                    $inner->addAttribute('name', 'value');
                    $inner->addAttribute('type', self::mapType($field->type));
                    $inner->addAttribute('label', $label);

                    self::addOptionNodes($inner, $options);
                }

                continue;
            }

            $node = $fieldsNode->addChild('field');
            $node->addAttribute('name', $field->name);
            $node->addAttribute('type', self::mapType($field->type));
            $node->addAttribute('label', $label);

            if (!empty($field->description)) {
                $node->addAttribute('description', $field->description);
            }

            if ((int) $field->required === 1) {
                $node->addAttribute('required', 'true');
            }

            if ($field->default_value !== null && $field->default_value !== '') {
                $node->addAttribute('default', $field->default_value);
            }

            self::addOptionNodes($node, $options);
        }

        $form->load($xml->asXML());
    }

    /**
     * Append <option> children for list/radio/checkboxes fields.
     */
    private static function addOptionNodes(\SimpleXMLElement $node, $options): void
    {
        if (!\is_array($options)) {
            return;
        }

        foreach ($options as $opt) {
            $value   = $opt['value'] ?? ($opt['name'] ?? '');
            $text    = $opt['name']  ?? ($opt['value'] ?? '');
            $optNode = $node->addChild('option', htmlspecialchars($text));
            $optNode->addAttribute('value', $value);
        }
    }

    /**
     * Resolve a safe machine name for a group sub-field.
     */
    private static function subfieldName(array $sf, int $index): string
    {
        $name = trim((string) ($sf['name'] ?? ''));

        if ($name === '') {
            $name = strtolower(str_replace(' ', '_', trim((string) ($sf['label'] ?? ''))));
        }

        $name = preg_replace('/[^a-z0-9_]/', '', $name);

        return $name !== '' ? $name : 'field_' . $index;
    }

    /**
     * Map a stored field type to a Joomla form field type.
     */
    protected static function mapType(string $type): string
    {
        return match (strtolower($type)) {
            'textarea'  => 'textarea',
            'editor'    => 'editor',
            'list',
            'select'    => 'list',
            'radio'     => 'radio',
            'checkbox',
            'checkboxes' => 'checkboxes',
            'integer',
            'number'    => 'number',
            'date',
            'calendar'  => 'calendar',
            'url'       => 'url',
            'media',
            'image'     => 'media',
            'email'     => 'email',
            default     => 'text',
        };
    }

    /**
     * Strip the Joomla media field metadata fragment (#joomlaImage://...) from an
     * image path, leaving a plain, clean URL-safe path.
     *
     * @param   string  $path  The stored image value.
     *
     * @return  string
     */
    public static function cleanImage(string $path): string
    {
        $hash = strpos($path, '#');

        return $hash === false ? $path : substr($path, 0, $hash);
    }

    /**
     * Get stored custom field values for a movie, keyed by field name.
     *
     * @param   integer  $movieId  The movie id.
     *
     * @return  array
     */
    public static function getValues(int $movieId): array
    {
        if ($movieId <= 0) {
            return [];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['f.name', 'v.value']))
            ->from($db->quoteName('#__movielist_field_values', 'v'))
            ->join('INNER', $db->quoteName('#__movielist_fields', 'f') . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'))
            ->where($db->quoteName('v.movie_id') . ' = :movie')
            ->bind(':movie', $movieId, ParameterType::INTEGER);

        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        $values = [];

        foreach ($rows as $row) {
            $decoded            = json_decode($row->value, true);
            $values[$row->name] = $decoded !== null ? $decoded : $row->value;
        }

        return $values;
    }

    /**
     * Persist custom field values for a movie.
     *
     * @param   integer  $movieId  The movie id.
     * @param   array    $values   Map of field name => value.
     *
     * @return  void
     */
    public static function saveValues(int $movieId, array $values): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Resolve field name => definition.
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name', 'is_multiple', 'multiple_mode', 'max_items']))
            ->from($db->quoteName('#__movielist_fields'));
        $db->setQuery($query);
        $map = [];

        foreach ($db->loadObjectList() as $f) {
            $map[$f->name] = $f;
        }

        foreach ($values as $name => $value) {
            if (!isset($map[$name])) {
                continue;
            }

            $field   = $map[$name];
            $fieldId = (int) $field->id;

            if ((int) $field->is_multiple === 1 && \is_array($value)) {
                $isGroup = ($field->multiple_mode ?? 'single') === 'group';

                // Drop empty rows and enforce the max-items limit.
                $value = array_values(array_filter($value, static function ($row) use ($isGroup) {
                    if ($isGroup && \is_array($row)) {
                        foreach ($row as $sv) {
                            if ($sv !== '' && $sv !== null && $sv !== []) {
                                return true;
                            }
                        }

                        return false;
                    }

                    $v = \is_array($row) ? ($row['value'] ?? '') : $row;

                    return $v !== '' && $v !== null;
                }));

                $max = (int) $field->max_items;

                if ($max > 0 && \count($value) > $max) {
                    $value = \array_slice($value, 0, $max);
                }
            }

            $stored = \is_array($value) ? json_encode($value) : (string) $value;

            // Upsert.
            $check = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__movielist_field_values'))
                ->where($db->quoteName('field_id') . ' = :fid')
                ->where($db->quoteName('movie_id') . ' = :mid')
                ->bind(':fid', $fieldId, ParameterType::INTEGER)
                ->bind(':mid', $movieId, ParameterType::INTEGER);
            $db->setQuery($check);
            $existingId = (int) $db->loadResult();

            $row           = new \stdClass();
            $row->field_id = $fieldId;
            $row->movie_id = $movieId;
            $row->value    = $stored;

            if ($existingId > 0) {
                $row->id = $existingId;
                $db->updateObject('#__movielist_field_values', $row, 'id');
            } else {
                $db->insertObject('#__movielist_field_values', $row);
            }
        }
    }
}

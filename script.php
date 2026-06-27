<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            InstallerScriptInterface::class,
            new class () implements InstallerScriptInterface {
                private string $minimumJoomla = '5.0.0';
                private string $minimumPhp     = '8.2.0';

                public function install(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function update(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function uninstall(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function preflight(string $type, InstallerAdapter $adapter): bool
                {
                    if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
                        Log::add('com_movielist requires PHP ' . $this->minimumPhp . ' or newer.', Log::WARNING, 'jerror');

                        return false;
                    }

                    if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
                        Log::add('com_movielist requires Joomla ' . $this->minimumJoomla . ' or newer.', Log::WARNING, 'jerror');

                        return false;
                    }

                    return true;
                }

                public function postflight(string $type, InstallerAdapter $adapter): bool
                {
                    $this->seedSystemFields();

                    return true;
                }

                /**
                 * Seed the global system field definitions (standard movie attributes) so they can
                 * be reordered and toggled for list / detail views alongside custom fields.
                 * Idempotent: only inserts a system field if its field_key is not present yet.
                 */
                private function seedSystemFields(): void
                {
                    $db = Factory::getContainer()->get(DatabaseInterface::class);

                    // field_key, type, label, show_in_list, show_in_detail
                    $systemFields = [
                        ['original_title', 'text',     'COM_MOVIELIST_MOVIE_ORIGINAL_TITLE', 0, 1],
                        ['category',       'text',     'COM_MOVIELIST_FIELD_CATEGORY',       1, 1],
                        ['year',           'number',   'COM_MOVIELIST_MOVIE_YEAR',           1, 1],
                        ['duration',       'number',   'COM_MOVIELIST_MOVIE_DURATION',       0, 1],
                        ['country',        'text',     'COM_MOVIELIST_MOVIE_COUNTRY',        0, 1],
                        ['original_lang',  'text',     'COM_MOVIELIST_MOVIE_ORIGINAL_LANG',  0, 1],
                        ['director',       'text',     'COM_MOVIELIST_MOVIE_DIRECTOR',       1, 1],
                        ['director_photo', 'media',    'COM_MOVIELIST_MOVIE_DIRECTOR_PHOTO', 0, 1],
                        ['director_bio',   'editor',   'COM_MOVIELIST_MOVIE_DIRECTOR_BIO',   0, 1],
                        ['synopsis',       'editor',   'COM_MOVIELIST_MOVIE_SYNOPSIS',       0, 1],
                        ['trailer_url',    'url',      'COM_MOVIELIST_MOVIE_TRAILER_URL',    0, 1],
                    ];

                    // Existing system field keys.
                    $existing = $db->setQuery(
                        $db->getQuery(true)
                            ->select($db->quoteName('field_key'))
                            ->from($db->quoteName('#__movielist_fields'))
                            ->where($db->quoteName('is_system') . ' = 1')
                    )->loadColumn() ?: [];

                    $ordering = (int) $db->setQuery(
                        $db->getQuery(true)
                            ->select('COALESCE(MAX(' . $db->quoteName('ordering') . '), 0)')
                            ->from($db->quoteName('#__movielist_fields'))
                    )->loadResult();

                    foreach ($systemFields as $sf) {
                        [$key, $fType, $label, $inList, $inDetail] = $sf;

                        if (\in_array($key, $existing, true)) {
                            continue;
                        }

                        $ordering++;

                        $row                 = new \stdClass();
                        $row->directory_id   = 0;
                        $row->is_system      = 1;
                        $row->field_key      = $key;
                        $row->title          = $key;
                        $row->name           = $key;
                        $row->type           = $fType;
                        $row->label          = $label;
                        $row->show_in_list   = $inList;
                        $row->show_in_detail = $inDetail;
                        $row->ordering       = $ordering;
                        $row->state          = 1;

                        $db->insertObject('#__movielist_fields', $row);
                    }
                }
            }
        );
    }
};

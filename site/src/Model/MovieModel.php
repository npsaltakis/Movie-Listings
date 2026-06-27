<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Frontend single movie model.
 */
class MovieModel extends ItemModel
{
    protected function populateState()
    {
        $app = Factory::getApplication();
        $this->setState('movie.id', $app->getInput()->getInt('id'));
        $this->setState('params', $app->getParams());
    }

    /**
     * Load a single published movie with its custom field values.
     *
     * @param   integer  $pk  The movie id.
     *
     * @return  \stdClass|null
     */
    public function getItem($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState('movie.id'));

        if ($pk <= 0) {
            return null;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('a.*')
            ->select($db->quoteName('c.title', 'category_title'))
            ->select($db->quoteName('d.title', 'directory_title'))
            ->from($db->quoteName('#__movielist_movies', 'a'))
            ->join('LEFT', $db->quoteName('#__movielist_categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__movielist_directories', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('a.directory_id'))
            ->where($db->quoteName('a.id') . ' = :id')
            ->where($db->quoteName('a.state') . ' = 1')
            ->bind(':id', $pk, ParameterType::INTEGER);

        $db->setQuery($query);
        $item = $db->loadObject();

        if (!$item) {
            return null;
        }

        // Custom field values (only published fields), keyed for display.
        $fq = $db->getQuery(true)
            ->select($db->quoteName(['f.label', 'f.title', 'f.name', 'f.type', 'v.value']))
            ->from($db->quoteName('#__movielist_field_values', 'v'))
            ->join('INNER', $db->quoteName('#__movielist_fields', 'f') . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'))
            ->where($db->quoteName('v.movie_id') . ' = :movie')
            ->where($db->quoteName('f.state') . ' = 1')
            ->order($db->quoteName('f.ordering') . ' ASC')
            ->bind(':movie', $pk, ParameterType::INTEGER);
        $db->setQuery($fq);
        $item->fields = $db->loadObjectList() ?: [];

        // Gallery images (published), ordered.
        $gq = $db->getQuery(true)
            ->select($db->quoteName(['filename', 'caption', 'type']))
            ->from($db->quoteName('#__movielist_images'))
            ->where($db->quoteName('movie_id') . ' = :movie')
            ->where($db->quoteName('state') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC')
            ->bind(':movie', $pk, ParameterType::INTEGER);
        $db->setQuery($gq);
        $item->gallery = $db->loadObjectList() ?: [];

        return $item;
    }
}

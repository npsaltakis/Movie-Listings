<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Frontend list model for the categories grid (each card = a listing group, e.g. a festival year).
 */
class CategoriesModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'a.id', 'title', 'a.title', 'ordering', 'a.ordering'];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'asc')
    {
        $app = Factory::getApplication();

        $this->setState('filter.directory_id', $app->getInput()->getInt('directory_id', 0));
        $this->setState('filter.parent_id', $app->getInput()->getInt('parent_id', 0));

        $params = $app->getParams();
        $this->setState('params', $params);
        $this->setState('list.limit', (int) $params->get('categories_per_page', 0));

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.image', 'a.description', 'a.directory_id', 'a.parent_id', 'a.level']))
            ->from($db->quoteName('#__movielist_categories', 'a'))
            ->where($db->quoteName('a.state') . ' = 1');

        // Published movie count per category.
        $subQuery = $db->getQuery(true)
            ->select('COUNT(m.id)')
            ->from($db->quoteName('#__movielist_movies', 'm'))
            ->where($db->quoteName('m.catid') . ' = ' . $db->quoteName('a.id'))
            ->where($db->quoteName('m.state') . ' = 1');
        $query->select('(' . $subQuery . ') AS movie_count');

        $directory = (int) $this->getState('filter.directory_id');
        if ($directory > 0) {
            $query->where($db->quoteName('a.directory_id') . ' = :dir')->bind(':dir', $directory, ParameterType::INTEGER);
        }

        // When a parent is given show its children, otherwise show all (or top level if requested).
        $parent = (int) $this->getState('filter.parent_id');
        if ($parent > 0) {
            $query->where($db->quoteName('a.parent_id') . ' = :parent')->bind(':parent', $parent, ParameterType::INTEGER);
        }

        $query->order($db->escape($this->getState('list.ordering', 'a.ordering')) . ' ' . $db->escape($this->getState('list.direction', 'asc')));

        return $query;
    }
}

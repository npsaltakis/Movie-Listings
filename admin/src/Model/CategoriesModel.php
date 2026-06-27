<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List model for Categories.
 */
class CategoriesModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'directory_id', 'a.directory_id',
                'parent_id', 'a.parent_id',
                'level', 'a.level',
                'state', 'a.state',
                'ordering', 'a.ordering',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.directory_id, a.path', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $directory = $this->getUserStateFromRequest($this->context . '.filter.directory_id', 'filter_directory_id', '');
        $this->setState('filter.directory_id', $directory);

        $published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '');
        $this->setState('filter.state', $published);

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.directory_id', 'a.parent_id', 'a.level', 'a.path', 'a.state', 'a.access', 'a.ordering', 'a.language']))
            ->from($db->quoteName('#__movielist_categories', 'a'));

        // Join directory title.
        $query->select($db->quoteName('d.title', 'directory_title'))
            ->join('LEFT', $db->quoteName('#__movielist_directories', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('a.directory_id'));

        // Movie count per category.
        $query->select('(' . $db->getQuery(true)
                ->select('COUNT(m.id)')
                ->from($db->quoteName('#__movielist_movies', 'm'))
                ->where($db->quoteName('m.catid') . ' = ' . $db->quoteName('a.id')) . ') AS movie_count');

        // Filter by directory.
        $directory = $this->getState('filter.directory_id');

        if (is_numeric($directory)) {
            $directory = (int) $directory;
            $query->where($db->quoteName('a.directory_id') . ' = :directory')
                ->bind(':directory', $directory, ParameterType::INTEGER);
        }

        // Filter by published state.
        $state = $this->getState('filter.state');

        if (is_numeric($state)) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        } elseif ($state === '') {
            $query->whereIn($db->quoteName('a.state'), [0, 1]);
        }

        // Search.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = '%' . str_replace(' ', '%', trim($search)) . '%';
            $query->where('(' . $db->quoteName('a.title') . ' LIKE :s1 OR ' . $db->quoteName('a.alias') . ' LIKE :s2)')
                ->bind(':s1', $search)
                ->bind(':s2', $search);
        }

        $orderCol  = $this->state->get('list.ordering', 'a.directory_id, a.path');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}

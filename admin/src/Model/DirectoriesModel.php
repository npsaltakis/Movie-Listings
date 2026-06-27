<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List model for Directories.
 */
class DirectoriesModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'alias', 'a.alias',
                'state', 'a.state',
                'access', 'a.access',
                'language', 'a.language',
                'ordering', 'a.ordering',
                'created', 'a.created',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     */
    protected function populateState($ordering = 'a.ordering', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '');
        $this->setState('filter.state', $published);

        parent::populateState($ordering, $direction);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  QueryInterface
     */
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.image', 'a.state', 'a.access', 'a.ordering', 'a.language', 'a.created']))
            ->from($db->quoteName('#__movielist_directories', 'a'));

        // Movie count per directory
        $query->select('(' . $db->getQuery(true)
                ->select('COUNT(m.id)')
                ->from($db->quoteName('#__movielist_movies', 'm'))
                ->where($db->quoteName('m.directory_id') . ' = ' . $db->quoteName('a.id')) . ') AS movie_count');

        // Category count per directory
        $query->select('(' . $db->getQuery(true)
                ->select('COUNT(c.id)')
                ->from($db->quoteName('#__movielist_categories', 'c'))
                ->where($db->quoteName('c.directory_id') . ' = ' . $db->quoteName('a.id')) . ') AS category_count');

        // Filter by published state.
        $state = $this->getState('filter.state');

        if (is_numeric($state)) {
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $state, \Joomla\Database\ParameterType::INTEGER);
        } elseif ($state === '') {
            $query->whereIn($db->quoteName('a.state'), [0, 1]);
        }

        // Filter by search.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')
                    ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where('(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' . $db->quoteName('a.alias') . ' LIKE :search2)')
                    ->bind(':search1', $search)
                    ->bind(':search2', $search);
            }
        }

        // Ordering.
        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}

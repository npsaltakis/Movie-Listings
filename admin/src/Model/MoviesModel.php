<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List model for Movies.
 */
class MoviesModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'directory_id', 'a.directory_id',
                'catid', 'a.catid',
                'year', 'a.year',
                'state', 'a.state',
                'featured', 'a.featured',
                'ordering', 'a.ordering',
                'created', 'a.created',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.title', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $directory = $this->getUserStateFromRequest($this->context . '.filter.directory_id', 'filter_directory_id', '');
        $this->setState('filter.directory_id', $directory);

        $catid = $this->getUserStateFromRequest($this->context . '.filter.catid', 'filter_catid', '');
        $this->setState('filter.catid', $catid);

        $published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '');
        $this->setState('filter.state', $published);

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.directory_id', 'a.catid', 'a.year', 'a.director', 'a.poster', 'a.state', 'a.featured', 'a.access', 'a.ordering', 'a.language', 'a.created']))
            ->from($db->quoteName('#__movielist_movies', 'a'));

        $query->select($db->quoteName('c.title', 'category_title'))
            ->join('LEFT', $db->quoteName('#__movielist_categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'));

        $query->select($db->quoteName('d.title', 'directory_title'))
            ->join('LEFT', $db->quoteName('#__movielist_directories', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('a.directory_id'));

        $directory = $this->getState('filter.directory_id');
        if (is_numeric($directory)) {
            $directory = (int) $directory;
            $query->where($db->quoteName('a.directory_id') . ' = :dir')->bind(':dir', $directory, ParameterType::INTEGER);
        }

        $catid = $this->getState('filter.catid');
        if (is_numeric($catid)) {
            $catid = (int) $catid;
            $query->where($db->quoteName('a.catid') . ' = :cat')->bind(':cat', $catid, ParameterType::INTEGER);
        }

        $state = $this->getState('filter.state');
        if (is_numeric($state)) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')->bind(':state', $state, ParameterType::INTEGER);
        } elseif ($state === '') {
            $query->whereIn($db->quoteName('a.state'), [0, 1]);
        }

        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where('(' . $db->quoteName('a.title') . ' LIKE :s1 OR ' . $db->quoteName('a.director') . ' LIKE :s2 OR ' . $db->quoteName('a.original_title') . ' LIKE :s3)')
                    ->bind(':s1', $search)->bind(':s2', $search)->bind(':s3', $search);
            }
        }

        $orderCol  = $this->state->get('list.ordering', 'a.title');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}

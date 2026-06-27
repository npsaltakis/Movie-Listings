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
 * List model for custom Fields.
 */
class FieldsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'name', 'a.name',
                'type', 'a.type',
                'directory_id', 'a.directory_id',
                'state', 'a.state',
                'ordering', 'a.ordering',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'asc')
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

        $query->select($db->quoteName(['a.id', 'a.title', 'a.label', 'a.name', 'a.type', 'a.is_system', 'a.field_key', 'a.required', 'a.is_multiple', 'a.max_items', 'a.show_in_list', 'a.show_in_detail', 'a.state', 'a.ordering']))
            ->from($db->quoteName('#__movielist_fields', 'a'));

        $state = $this->getState('filter.state');
        if (is_numeric($state)) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')->bind(':state', $state, ParameterType::INTEGER);
        } elseif ($state === '') {
            $query->whereIn($db->quoteName('a.state'), [0, 1]);
        }

        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = '%' . str_replace(' ', '%', trim($search)) . '%';
            $query->where('(' . $db->quoteName('a.title') . ' LIKE :s1 OR ' . $db->quoteName('a.name') . ' LIKE :s2)')
                ->bind(':s1', $search)->bind(':s2', $search);
        }

        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}

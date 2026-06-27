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
 * Frontend list model for Movies.
 */
class MoviesModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'a.id', 'title', 'a.title', 'year', 'a.year', 'catid', 'a.catid'];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.title', $direction = 'asc')
    {
        $app = Factory::getApplication();

        // Menu-scoped filters (fixed by the menu item).
        $this->setState('filter.directory_id', $app->getInput()->getInt('directory_id', 0));
        $this->setState('filter.menu_catid', $app->getInput()->getInt('catid', 0));

        // User-driven filters (search form), remembered per request.
        $this->setState('filter.search', $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.year', (int) $app->getUserStateFromRequest($this->context . '.filter.year', 'filter_year', 0, 'int'));
        $this->setState('filter.catid', (int) $app->getUserStateFromRequest($this->context . '.filter.catid', 'filter_catid', 0, 'int'));

        $params = $app->getParams();
        $this->setState('params', $params);
        $this->setState('list.limit', (int) $params->get('movies_per_page', 20));

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('a.*')
            ->select($db->quoteName('c.title', 'category_title'))
            ->from($db->quoteName('#__movielist_movies', 'a'))
            ->join('LEFT', $db->quoteName('#__movielist_categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->where($db->quoteName('a.state') . ' = 1');

        $directory = (int) $this->getState('filter.directory_id');
        if ($directory > 0) {
            $query->where($db->quoteName('a.directory_id') . ' = :dir')->bind(':dir', $directory, ParameterType::INTEGER);
        }

        // Category: the menu-fixed category takes precedence; otherwise the user filter.
        $catid = (int) $this->getState('filter.menu_catid') ?: (int) $this->getState('filter.catid');
        if ($catid > 0) {
            $query->where($db->quoteName('a.catid') . ' = :cat')->bind(':cat', $catid, ParameterType::INTEGER);
        }

        $year = (int) $this->getState('filter.year');
        if ($year > 0) {
            $query->where($db->quoteName('a.year') . ' = :year')->bind(':year', $year, ParameterType::INTEGER);
        }

        // Free-text search across the main columns and any searchable custom field values.
        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->bind(':search', $like, ParameterType::STRING);

            $sub = $db->getQuery(true)
                ->select('1')
                ->from($db->quoteName('#__movielist_field_values', 'fv'))
                ->join('INNER', $db->quoteName('#__movielist_fields', 'f') . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('fv.field_id'))
                ->where($db->quoteName('fv.movie_id') . ' = ' . $db->quoteName('a.id'))
                ->where($db->quoteName('f.searchable') . ' = 1')
                ->where($db->quoteName('f.state') . ' = 1')
                ->where($db->quoteName('fv.value') . ' LIKE :search2');
            $query->bind(':search2', $like, ParameterType::STRING);

            $query->where(
                '(' . $db->quoteName('a.title') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.original_title') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.director') . ' LIKE :search'
                . ' OR EXISTS (' . $sub . '))'
            );
        }

        $query->order($db->escape($this->getState('list.ordering', 'a.title')) . ' ' . $db->escape($this->getState('list.direction', 'asc')));

        return $query;
    }

    /**
     * Distinct years available among the published movies in the current scope, descending.
     *
     * @return  array  Array of integer years.
     */
    public function getYears(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('year'))
            ->from($db->quoteName('#__movielist_movies'))
            ->where($db->quoteName('state') . ' = 1')
            ->where($db->quoteName('year') . ' > 0')
            ->order($db->quoteName('year') . ' DESC');

        $directory = (int) $this->getState('filter.directory_id');
        if ($directory > 0) {
            $query->where($db->quoteName('directory_id') . ' = :dir')->bind(':dir', $directory, ParameterType::INTEGER);
        }

        $db->setQuery($query);

        return array_map('intval', $db->loadColumn() ?: []);
    }

    /**
     * Categories available for the category filter (scoped to the menu directory if any).
     * Returns nothing when the menu already fixes a single category.
     *
     * @return  array  Array of stdClass{id,title}.
     */
    public function getFilterCategories(): array
    {
        if ((int) $this->getState('filter.menu_catid') > 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title']))
            ->from($db->quoteName('#__movielist_categories'))
            ->where($db->quoteName('state') . ' = 1')
            ->order($db->quoteName('path') . ' ASC');

        $directory = (int) $this->getState('filter.directory_id');
        if ($directory > 0) {
            $query->where($db->quoteName('directory_id') . ' = :dir')->bind(':dir', $directory, ParameterType::INTEGER);
        }

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }
}

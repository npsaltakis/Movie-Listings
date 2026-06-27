<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Model;

use Joomla\CMS\Factory;
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
        // Search may arrive flat (filter_search) or grouped (filter[search]); handle both.
        $app       = Factory::getApplication();
        $filterArr = (array) $app->getInput()->get('filter', [], 'array');

        if (\array_key_exists('search', $filterArr)) {
            $search = (string) $filterArr['search'];
            $app->setUserState($this->context . '.filter.search', $search);
        } else {
            $search = (string) $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '');
        }

        $this->setState('filter.search', $search);

        $directory = $this->getUserStateFromRequest($this->context . '.filter.directory_id', 'filter_directory_id', '');
        $this->setState('filter.directory_id', $directory);

        $catid = $this->getUserStateFromRequest($this->context . '.filter.catid', 'filter_catid', '');
        $this->setState('filter.catid', $catid);

        $published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '');
        $this->setState('filter.state', $published);

        parent::populateState($ordering, $direction);
    }

    /**
     * Which step of the directory/category/movie drill-down we are on.
     *
     * @return  string  'directories' | 'categories' | 'movies'
     */
    public function getBrowseMode(): string
    {
        if ((int) $this->getState('filter.catid') > 0) {
            return 'movies';
        }

        if ((int) $this->getState('filter.directory_id') > 0) {
            return 'categories';
        }

        return 'directories';
    }

    /**
     * Directories (festivals) with their movie counts, for the first browse step.
     *
     * @return  array
     */
    public function getBrowseDirectories(): array
    {
        $db  = $this->getDatabase();
        $sub = $db->getQuery(true)
            ->select('COUNT(' . $db->quoteName('x.id') . ')')
            ->from($db->quoteName('#__movielist_movies', 'x'))
            ->where($db->quoteName('x.directory_id') . ' = ' . $db->quoteName('a.id'));

        $query = $db->getQuery(true)
            ->select($db->quoteName(['a.id', 'a.title', 'a.state']))
            ->select('(' . $sub . ') AS movie_count')
            ->from($db->quoteName('#__movielist_directories', 'a'))
            ->order($db->quoteName('a.ordering') . ' ASC, ' . $db->quoteName('a.title') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Categories of the selected directory with their movie counts, for the second step.
     *
     * @return  array
     */
    public function getBrowseCategories(): array
    {
        $dir = (int) $this->getState('filter.directory_id');

        if ($dir <= 0) {
            return [];
        }

        $db  = $this->getDatabase();
        $sub = $db->getQuery(true)
            ->select('COUNT(' . $db->quoteName('x.id') . ')')
            ->from($db->quoteName('#__movielist_movies', 'x'))
            ->where($db->quoteName('x.catid') . ' = ' . $db->quoteName('a.id'));

        $query = $db->getQuery(true)
            ->select($db->quoteName(['a.id', 'a.title', 'a.level', 'a.state']))
            ->select('(' . $sub . ') AS movie_count')
            ->from($db->quoteName('#__movielist_categories', 'a'))
            ->where($db->quoteName('a.directory_id') . ' = ' . $dir)
            ->order($db->quoteName('a.path') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Resolve the directory / category titles for the breadcrumb.
     *
     * @return  array{directory: ?string, category: ?string}
     */
    public function getBrowseCrumb(): array
    {
        $db  = $this->getDatabase();
        $out = ['directory' => null, 'category' => null];

        if ($dir = (int) $this->getState('filter.directory_id')) {
            $out['directory'] = $db->setQuery(
                $db->getQuery(true)->select($db->quoteName('title'))
                    ->from($db->quoteName('#__movielist_directories'))
                    ->where($db->quoteName('id') . ' = ' . $dir)
            )->loadResult();
        }

        if ($cat = (int) $this->getState('filter.catid')) {
            $out['category'] = $db->setQuery(
                $db->getQuery(true)->select($db->quoteName('title'))
                    ->from($db->quoteName('#__movielist_categories'))
                    ->where($db->quoteName('id') . ' = ' . $cat)
            )->loadResult();
        }

        return $out;
    }

    /**
     * All directories for the move/copy listing picker.
     *
     * @return  array  Array of stdClass{id, title}.
     */
    public function getTargetDirectories(): array
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
     * All categories grouped by directory_id for the move/copy picker.
     * Returns stdClass{id, directory_id, label} so the template can cascade.
     *
     * @return  array
     */
    public function getTargetCategories(): array
    {
        $db   = $this->getDatabase();
        $rows = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['c.id', 'c.title', 'c.level', 'c.directory_id']))
                ->from($db->quoteName('#__movielist_categories', 'c'))
                ->where($db->quoteName('c.state') . ' >= 0')
                ->order($db->quoteName('c.directory_id') . ' ASC, ' . $db->quoteName('c.path') . ' ASC')
        )->loadObjectList() ?: [];

        $out = [];
        foreach ($rows as $r) {
            $indent   = str_repeat('— ', max(0, (int) $r->level - 1));
            $o              = new \stdClass();
            $o->id          = (int) $r->id;
            $o->directory_id = (int) $r->directory_id;
            $o->label       = $indent . $r->title;
            $out[]          = $o;
        }

        return $out;
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

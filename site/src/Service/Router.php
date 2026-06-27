<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Frontend SEF router for com_movielist.
 *
 * View tree:  categories  ->  movies (key: catid)  ->  movie (key: id)
 */
class Router extends RouterView
{
    /**
     * @var DatabaseInterface
     */
    private $db;

    public function __construct(SiteApplication $app, AbstractMenu $menu, DatabaseInterface $db)
    {
        $this->db = $db;

        $categories = new RouterViewConfiguration('categories');
        $this->registerView($categories);

        $movies = new RouterViewConfiguration('movies');
        $movies->setKey('catid')->setParent($categories);
        $this->registerView($movies);

        $movie = new RouterViewConfiguration('movie');
        $movie->setKey('id')->setParent($movies, 'catid');
        $this->registerView($movie);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Segment for the movies view (a category).
     */
    public function getMoviesSegment($id, $query)
    {
        $id = (int) $id;

        if ($id <= 0) {
            return [];
        }

        return [$id => $this->getCategoryAlias($id)];
    }

    /**
     * Resolve a category alias segment back to its id.
     */
    public function getMoviesId($segment, $query)
    {
        return $this->getCategoryId($segment, (int) ($query['directory_id'] ?? 0));
    }

    /**
     * Segment for a single movie.
     */
    public function getMovieSegment($id, $query)
    {
        $id = (int) $id;

        if ($id <= 0) {
            return [];
        }

        $db    = $this->db;
        $dbq   = $db->getQuery(true)
            ->select($db->quoteName('alias'))
            ->from($db->quoteName('#__movielist_movies'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($dbq);
        $alias = (string) $db->loadResult();

        return [$id => ($alias !== '' ? $alias : (string) $id)];
    }

    /**
     * Resolve a movie alias segment back to its id.
     */
    public function getMovieId($segment, $query)
    {
        // Segments arrive with ':' replacing '-' inside the alias.
        $alias = str_replace(':', '-', $segment);

        $db  = $this->db;
        $dbq = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__movielist_movies'))
            ->where($db->quoteName('alias') . ' = :alias')
            ->bind(':alias', $alias, ParameterType::STRING);

        // Scope to the parent category when known.
        if (!empty($query['catid'])) {
            $catid = (int) $query['catid'];
            $dbq->where($db->quoteName('catid') . ' = :catid')->bind(':catid', $catid, ParameterType::INTEGER);
        }

        $db->setQuery($dbq);

        return (int) $db->loadResult();
    }

    /**
     * The categories grid has no own key; nothing to add.
     */
    public function getCategoriesSegment($id, $query)
    {
        return [];
    }

    public function getCategoriesId($segment, $query)
    {
        return 1;
    }

    /**
     * Fetch a category alias by id.
     */
    private function getCategoryAlias(int $id): string
    {
        $db  = $this->db;
        $dbq = $db->getQuery(true)
            ->select($db->quoteName('alias'))
            ->from($db->quoteName('#__movielist_categories'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($dbq);
        $alias = (string) $db->loadResult();

        return $alias !== '' ? $alias : (string) $id;
    }

    /**
     * Resolve a category alias segment back to its id.
     */
    private function getCategoryId(string $segment, int $directoryId = 0): int
    {
        $alias = str_replace(':', '-', $segment);

        $db  = $this->db;
        $dbq = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__movielist_categories'))
            ->where($db->quoteName('alias') . ' = :alias')
            ->bind(':alias', $alias, ParameterType::STRING);

        if ($directoryId > 0) {
            $dbq->where($db->quoteName('directory_id') . ' = :dir')->bind(':dir', $directoryId, ParameterType::INTEGER);
        }

        $db->setQuery($dbq);

        return (int) $db->loadResult();
    }
}

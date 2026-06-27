<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Movie (listing) table.
 */
class MovieTable extends Table
{
    protected $_supportNullValue = true;

    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_movielist.movie';

        parent::__construct('#__movielist_movies', 'id', $db, $dispatcher);

        $this->setColumnAlias('published', 'state');
    }

    public function check(): bool
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        $this->title = htmlspecialchars_decode($this->title, ENT_QUOTES);

        if (trim($this->title) === '') {
            $this->setError('COM_MOVIELIST_ERROR_TITLE_REQUIRED');

            return false;
        }

        if ((int) $this->catid <= 0) {
            $this->setError('COM_MOVIELIST_ERROR_CATEGORY_REQUIRED');

            return false;
        }

        if (trim($this->alias) === '') {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        return true;
    }

    public function store($updateNulls = true): bool
    {
        $date = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();

        // Nullable numeric columns must be NULL (not '') for strict-mode MySQL.
        foreach (['year', 'duration'] as $numeric) {
            if ($this->$numeric === '' || $this->$numeric === false) {
                $this->$numeric = null;
            }
        }

        // Derive directory_id from the chosen category if not set explicitly.
        if ((int) $this->catid > 0 && (int) $this->directory_id <= 0) {
            $query = $this->_db->getQuery(true)
                ->select($this->_db->quoteName('directory_id'))
                ->from($this->_db->quoteName('#__movielist_categories'))
                ->where($this->_db->quoteName('id') . ' = ' . (int) $this->catid);
            $this->_db->setQuery($query);
            $this->directory_id = (int) $this->_db->loadResult();
        }

        if (!$this->id) {
            if (!(int) $this->created) {
                $this->created = $date;
            }

            if (empty($this->created_by)) {
                $this->created_by = $user->id;
            }

            if (!(int) $this->ordering) {
                $this->ordering = $this->getNextOrder(
                    $this->_db->quoteName('catid') . ' = ' . (int) $this->catid
                );
            }
        } else {
            $this->modified    = $date;
            $this->modified_by = $user->id;
        }

        return parent::store($updateNulls);
    }
}

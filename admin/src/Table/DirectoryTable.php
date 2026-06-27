<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Directory table.
 */
class DirectoryTable extends Table
{
    /**
     * Indicates that columns fully support the NULL value in the database.
     *
     * @var  boolean
     */
    protected $_supportNullValue = true;

    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_movielist.directory';

        parent::__construct('#__movielist_directories', 'id', $db, $dispatcher);

        $this->setColumnAlias('published', 'state');
    }

    /**
     * Overloaded check function.
     *
     * @return  boolean
     */
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

        if (trim($this->alias) === '') {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        return true;
    }

    /**
     * Override store to set created / modified dates and ordering.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean
     */
    public function store($updateNulls = true): bool
    {
        $date = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();

        if (!$this->id) {
            if (!(int) $this->created) {
                $this->created = $date;
            }

            if (empty($this->created_by)) {
                $this->created_by = $user->id;
            }

            if (!(int) $this->ordering) {
                $this->ordering = $this->getNextOrder();
            }
        } else {
            $this->modified    = $date;
            $this->modified_by = $user->id;
        }

        return parent::store($updateNulls);
    }
}

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
 * Category table (directory-scoped tree via parent_id + level + path).
 */
class CategoryTable extends Table
{
    protected $_supportNullValue = true;

    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_movielist.category';

        parent::__construct('#__movielist_categories', 'id', $db, $dispatcher);

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

        if ((int) $this->directory_id <= 0) {
            $this->setError('COM_MOVIELIST_ERROR_DIRECTORY_REQUIRED');

            return false;
        }

        if (trim($this->alias) === '') {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        // A category cannot be its own parent.
        if ((int) $this->parent_id === (int) $this->id && $this->id) {
            $this->setError('COM_MOVIELIST_ERROR_CATEGORY_PARENT_SELF');

            return false;
        }

        return true;
    }

    /**
     * Compute level and path from the parent before storing.
     */
    public function store($updateNulls = true): bool
    {
        $date = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();

        // Resolve level + path from parent.
        if ((int) $this->parent_id > 0) {
            $parent = clone $this;
            if ($parent->load((int) $this->parent_id)) {
                $this->level = (int) $parent->level + 1;
                $this->path  = ($parent->path !== '' ? $parent->path . '/' : '') . $this->alias;
            }
        } else {
            $this->level = 1;
            $this->path  = $this->alias;
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
                    $this->_db->quoteName('directory_id') . ' = ' . (int) $this->directory_id
                );
            }
        } else {
            $this->modified    = $date;
            $this->modified_by = $user->id;
        }

        return parent::store($updateNulls);
    }
}

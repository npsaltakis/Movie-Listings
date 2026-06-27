<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Custom field definition table.
 */
class FieldTable extends Table
{
    protected $_supportNullValue = true;

    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_movielist.field';

        parent::__construct('#__movielist_fields', 'id', $db, $dispatcher);

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

        if (trim($this->title) === '') {
            $this->setError('COM_MOVIELIST_ERROR_TITLE_REQUIRED');

            return false;
        }

        // Machine name: derive from title if empty, normalise to a safe slug with underscores.
        if (trim($this->name) === '') {
            $this->name = $this->title;
        }

        $this->name = str_replace('-', '_', ApplicationHelper::stringURLSafe($this->name));

        if (trim(str_replace('_', '', $this->name)) === '') {
            $this->name = 'field_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
        }

        if (!(int) $this->ordering) {
            $this->ordering = $this->getNextOrder();
        }

        return true;
    }
}

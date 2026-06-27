<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Fields list controller.
 */
class FieldsController extends AdminController
{
    public function getModel($name = 'Field', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Toggle the "Show in list" flag for the checked field(s).
     *
     * @return  void
     */
    public function togglelist(): void
    {
        $this->toggleColumn('show_in_list');
    }

    /**
     * Toggle the "Show in detail" flag for the checked field(s).
     *
     * @return  void
     */
    public function toggledetail(): void
    {
        $this->toggleColumn('show_in_detail');
    }

    /**
     * Flip a boolean column for the selected fields.
     *
     * @param   string  $column  The column to flip.
     *
     * @return  void
     */
    private function toggleColumn(string $column): void
    {
        $this->checkToken();

        $ids = (array) $this->input->get('cid', [], 'array');
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $col = $db->quoteName($column);
            $db->setQuery(
                'UPDATE ' . $db->quoteName('#__movielist_fields')
                . ' SET ' . $col . ' = CASE WHEN ' . $col . ' = 1 THEN 0 ELSE 1 END'
                . ' WHERE ' . $db->quoteName('id') . ' IN (' . implode(',', $ids) . ')'
            )->execute();
        }

        $this->setRedirect(Route::_('index.php?option=com_movielist&view=fields', false));
    }
}

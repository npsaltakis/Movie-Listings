<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Controller for the FilmFreeway CSV import tool.
 */
class FfimportController extends BaseController
{
    protected $default_view = 'ffimport';

    /**
     * Handle the uploaded CSV and run the import.
     */
    public function import()
    {
        $this->checkToken();

        $app   = Factory::getApplication();
        $file  = $app->getInput()->files->get('csvfile', null, 'raw');
        $dirId = $app->getInput()->getInt('directory_id', 0);
        $newD  = $app->getInput()->getString('new_directory', '');

        $redirect = Route::_('index.php?option=com_movielist&view=ffimport', false);

        if (!$file || empty($file['tmp_name']) || !empty($file['error'])) {
            $this->setRedirect($redirect, Text::_('COM_MOVIELIST_FF_NO_FILE'), 'error');

            return;
        }

        if (strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
            $this->setRedirect($redirect, Text::_('COM_MOVIELIST_FF_NOT_CSV'), 'error');

            return;
        }

        try {
            $res = $this->getModel('Ffimport')->import($file['tmp_name'], $dirId, $newD);
            $msg = Text::sprintf('COM_MOVIELIST_FF_DONE', $res['movies'], $res['skipped'], $res['categories']);
            $this->setRedirect($redirect, $msg, 'message');
        } catch (\Throwable $e) {
            $this->setRedirect($redirect, $e->getMessage(), 'error');
        }
    }

    /**
     * Remove everything previously imported from FilmFreeway.
     */
    public function reset()
    {
        $this->checkToken();

        try {
            $this->getModel('Ffimport')->reset();
            $this->setRedirect(Route::_('index.php?option=com_movielist&view=ffimport', false), Text::_('COM_MOVIELIST_FF_RESET_DONE'));
        } catch (\Throwable $e) {
            $this->setRedirect(Route::_('index.php?option=com_movielist&view=ffimport', false), $e->getMessage(), 'error');
        }
    }
}

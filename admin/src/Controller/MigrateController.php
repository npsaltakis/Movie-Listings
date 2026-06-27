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
use Joomla\CMS\Session\Session;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Controller for the Mosets Tree migration tool.
 */
class MigrateController extends BaseController
{
    protected $default_view = 'migrate';

    /**
     * AJAX: seed fields + migrate directories & categories. Returns the link total.
     */
    public function prepare()
    {
        $this->checkAjax();

        $model = $this->getModel('Migrate');

        try {
            $total = $model->prepare();
            $this->respond(['ok' => true, 'total' => $total]);
        } catch (\Throwable $e) {
            $this->respond(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: migrate one batch of links.
     */
    public function batch()
    {
        $this->checkAjax();

        $input  = $this->input;
        $offset = $input->getInt('offset', 0);
        $limit  = $input->getInt('limit', 25);
        $model  = $this->getModel('Migrate');

        try {
            $res = $model->migrateLinks($offset, $limit);
            $this->respond(['ok' => true] + $res);
        } catch (\Throwable $e) {
            $this->respond(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Clear all previously migrated data.
     */
    public function reset()
    {
        $this->checkToken();

        try {
            $this->getModel('Migrate')->reset();
            $msg  = Text::_('COM_MOVIELIST_MIGRATE_RESET_DONE');
            $type = 'message';
        } catch (\Throwable $e) {
            $msg  = $e->getMessage();
            $type = 'error';
        }

        $this->setRedirect(Route::_('index.php?option=com_movielist&view=migrate', false), $msg, $type);
    }

    private function checkAjax(): void
    {
        if (!Session::checkToken('request')) {
            $this->respond(['ok' => false, 'error' => Text::_('JINVALID_TOKEN')]);
        }
    }

    private function respond(array $data): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json');
        $app->sendHeaders();
        echo json_encode($data);
        $app->close();
    }
}

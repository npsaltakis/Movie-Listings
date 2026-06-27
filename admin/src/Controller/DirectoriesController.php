<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Directories list controller.
 */
class DirectoriesController extends AdminController
{
    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name.
     * @param   string  $prefix  The class prefix.
     * @param   array   $config  Configuration array.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     */
    public function getModel($name = 'Directory', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}

<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\View\Migrate;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Migration dashboard view.
 */
class HtmlView extends BaseHtmlView
{
    protected $status;
    protected $mtPresent;

    public function display($tpl = null)
    {
        $model           = $this->getModel();
        $this->mtPresent = $model->mtPresent();
        $this->status    = $this->mtPresent ? $model->getStatus() : null;

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_MOVIELIST_MIGRATE_TITLE'), 'loop');

        if ($this->mtPresent) {
            ToolbarHelper::custom('migrate.reset', 'trash', '', 'COM_MOVIELIST_MIGRATE_RESET', false);
        }
    }
}

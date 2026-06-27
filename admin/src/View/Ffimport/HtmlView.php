<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\View\Ffimport;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * FilmFreeway import dashboard.
 */
class HtmlView extends BaseHtmlView
{
    protected $directories;

    public function display($tpl = null)
    {
        $this->directories = $this->getModel()->getDirectories();

        ToolbarHelper::title(Text::_('COM_MOVIELIST_FF_TITLE'), 'upload');
        ToolbarHelper::custom('ffimport.reset', 'trash', '', 'COM_MOVIELIST_FF_RESET', false);

        parent::display($tpl);
    }
}

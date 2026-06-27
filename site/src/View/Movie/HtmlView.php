<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Site\View\Movie;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Frontend single movie view.
 */
class HtmlView extends BaseHtmlView
{
    protected $item;
    protected $params;

    public function display($tpl = null)
    {
        $this->item   = $this->get('Item');
        $this->params = $this->get('State')->get('params');

        if (!$this->item) {
            throw new GenericDataException(Text::_('COM_MOVIELIST_MOVIE_NOT_FOUND'), 404);
        }

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        parent::display($tpl);
    }
}

<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\View\Directory;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Directory edit view.
 */
class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;
    protected $state;

    public function display($tpl = null)
    {
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew   = ($this->item->id == 0);
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(
            Text::_($isNew ? 'COM_MOVIELIST_DIRECTORY_NEW' : 'COM_MOVIELIST_DIRECTORY_EDIT'),
            'folder'
        );

        $saveGroup = $toolbar->dropdownButton('save-group');
        $saveGroup->configure(
            function ($childBar) {
                $childBar->apply('directory.apply');
                $childBar->save('directory.save');
                $childBar->save2new('directory.save2new');
            }
        );

        $toolbar->cancel('directory.cancel', 'JTOOLBAR_CLOSE');
    }
}

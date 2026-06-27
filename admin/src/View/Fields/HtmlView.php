<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\View\Fields;

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
 * Fields list view.
 */
class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    public $filterForm;
    public $activeFilters;
    public $saveOrder = false;

    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Drag-and-drop ordering is available only when sorting by the ordering column.
        $this->saveOrder = $this->state->get('list.ordering', 'a.ordering') === 'a.ordering';

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $user    = Factory::getApplication()->getIdentity();
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_MOVIELIST_FIELDS'), 'list-2');

        if ($user->authorise('core.create', 'com_movielist')) {
            $toolbar->addNew('field.add');
        }

        if ($user->authorise('core.edit.state', 'com_movielist')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->publish('fields.publish')->listCheck(true);
            $childBar->unpublish('fields.unpublish')->listCheck(true);
        }

        if ($user->authorise('core.delete', 'com_movielist')) {
            $toolbar->delete('fields.delete', 'JTOOLBAR_DELETE')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }
    }
}

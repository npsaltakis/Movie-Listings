<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\View\Movies;

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
 * Movies list view.
 */
class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    public $filterForm;
    public $activeFilters;
    public $mode;
    public $directories;
    public $categories;
    public $crumb;

    public function display($tpl = null)
    {
        /** @var \Nickpsal\Component\Movielist\Administrator\Model\MoviesModel $model */
        $model       = $this->getModel();
        $this->state = $this->get('State');
        $this->mode  = $model->getBrowseMode();
        $this->crumb = $model->getBrowseCrumb();

        if ($this->mode === 'movies') {
            // Inside a category: the actual movies list (with search only).
            $this->items         = $this->get('Items');
            $this->pagination    = $this->get('Pagination');
            $this->filterForm    = $this->get('FilterForm');
            $this->activeFilters = $this->get('ActiveFilters');
        } elseif ($this->mode === 'categories') {
            $this->categories = $model->getBrowseCategories();
        } else {
            $this->directories = $model->getBrowseDirectories();
        }

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $user    = Factory::getApplication()->getIdentity();
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_MOVIELIST_MOVIES'), 'film');

        if ($user->authorise('core.create', 'com_movielist')) {
            $toolbar->addNew('movie.add');
        }

        if ($user->authorise('core.edit.state', 'com_movielist')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->publish('movies.publish')->listCheck(true);
            $childBar->unpublish('movies.unpublish')->listCheck(true);
        }

        if ($user->authorise('core.delete', 'com_movielist')) {
            $toolbar->delete('movies.delete', 'JTOOLBAR_DELETE')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }
    }
}

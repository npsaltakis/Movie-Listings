<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('multiselect');

$user      = Factory::getApplication()->getIdentity();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_movielist&view=categories'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="categoryList">
                        <caption class="visually-hidden"><?php echo Text::_('COM_MOVIELIST_CATEGORIES'); ?></caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-md-table-cell">
                                    <?php echo Text::_('COM_MOVIELIST_FIELD_DIRECTORY'); ?>
                                </th>
                                <th scope="col" class="w-5 text-center d-none d-md-table-cell">
                                    <?php echo Text::_('COM_MOVIELIST_HEADING_MOVIES'); ?>
                                </th>
                                <th scope="col" class="w-3 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?></td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'categories.', $user->authorise('core.edit.state', 'com_movielist'), 'cb'); ?>
                                    </td>
                                    <th scope="row">
                                        <span style="padding-left:<?php echo (max(0, (int) $item->level - 1) * 20); ?>px">
                                            <a href="<?php echo Route::_('index.php?option=com_movielist&task=category.edit&id=' . (int) $item->id); ?>">
                                                <?php echo $this->escape($item->title); ?>
                                            </a>
                                        </span>
                                        <div class="small text-muted"><?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?></div>
                                    </th>
                                    <td class="d-none d-md-table-cell"><?php echo $this->escape($item->directory_title); ?></td>
                                    <td class="text-center d-none d-md-table-cell"><?php echo (int) $item->movie_count; ?></td>
                                    <td class="text-center d-none d-md-table-cell"><?php echo (int) $item->id; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>

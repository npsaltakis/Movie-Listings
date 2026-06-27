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
$wa->useScript('multiselect')->useScript('table.columns');

$user      = Factory::getApplication()->getIdentity();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $this->saveOrder && $listDirn === 'asc';

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_movielist&task=fields.saveOrderAjax&tmpl=component&' . Factory::getSession()->getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}
?>
<form action="<?php echo Route::_('index.php?option=com_movielist&view=fields'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
                <?php else : ?>
                    <table class="table" id="fieldList">
                        <caption class="visually-hidden"><?php echo Text::_('COM_MOVIELIST_FIELDS'); ?></caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                                </td>
                                <td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_MOVIELIST_FIELD_TYPE'); ?></th>
                                <th scope="col" class="w-5 text-center"><?php echo Text::_('COM_MOVIELIST_FIELD_IN_LIST'); ?></th>
                                <th scope="col" class="w-5 text-center"><?php echo Text::_('COM_MOVIELIST_FIELD_IN_DETAIL'); ?></th>
                                <th scope="col" class="w-3 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody <?php if ($saveOrder) : ?>class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr class="row<?php echo $i % 2; ?>" data-draggable-group="0" data-item-id="<?php echo (int) $item->id; ?>">
                                    <td class="text-center">
                                        <?php
                                        $iconClass = '';
                                        if (!$saveOrder) {
                                            $iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::_('tooltipText', 'JORDERINGDISABLED');
                                        }
                                        ?>
                                        <span class="sortable-handler<?php echo $iconClass; ?>">
                                            <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                        </span>
                                        <?php if ($saveOrder) : ?>
                                            <input type="text" name="order[]" size="5" value="<?php echo (int) $item->ordering; ?>" class="width-20 text-area-order hidden">
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?></td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'fields.', $user->authorise('core.edit.state', 'com_movielist'), 'cb'); ?>
                                    </td>
                                    <th scope="row">
                                        <a href="<?php echo Route::_('index.php?option=com_movielist&task=field.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape(Text::_($item->label ?: $item->title)); ?>
                                        </a>
                                        <?php if ((int) $item->is_multiple === 1) : ?>
                                            <span class="badge bg-info text-dark" title="<?php echo Text::_('COM_MOVIELIST_FIELD_MULTIPLE'); ?>">
                                                <?php echo (int) $item->max_items > 0 ? Text::sprintf('COM_MOVIELIST_FIELD_MULTIPLE_BADGE', (int) $item->max_items) : Text::_('COM_MOVIELIST_FIELD_MULTIPLE_BADGE_INF'); ?>
                                            </span>
                                        <?php endif; ?>
                                        <div class="small text-muted"><code><?php echo $this->escape($item->is_system ? $item->field_key : $item->name); ?></code></div>
                                    </th>
                                    <td class="d-none d-md-table-cell"><?php echo $this->escape($item->type); ?></td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.state', [
                                            ['unpublish', 'COM_MOVIELIST_TOGGLE_IN_LIST_OFF'],
                                            ['publish', 'COM_MOVIELIST_TOGGLE_IN_LIST_ON'],
                                        ], $item->show_in_list, $i, 'fields.togglelist', true, 'cb', 'show_in_list'); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.state', [
                                            ['unpublish', 'COM_MOVIELIST_TOGGLE_IN_DETAIL_OFF'],
                                            ['publish', 'COM_MOVIELIST_TOGGLE_IN_DETAIL_ON'],
                                        ], $item->show_in_detail, $i, 'fields.toggledetail', true, 'cb', 'show_in_detail'); ?>
                                    </td>
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

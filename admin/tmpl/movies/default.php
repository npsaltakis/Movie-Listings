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
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('multiselect');

$user = Factory::getApplication()->getIdentity();
$base = 'index.php?option=com_movielist&view=movies';

$dirId = (int) $this->state->get('filter.directory_id');
$catId = (int) $this->state->get('filter.catid');

$linkDirectories = Route::_($base . '&filter_directory_id=&filter_catid=&limitstart=0');
$linkCategories  = Route::_($base . '&filter_directory_id=' . $dirId . '&filter_catid=&limitstart=0');
?>
<form action="<?php echo Route::_($base); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">

                <?php // Breadcrumb across the drill-down steps (theme-aware, no background). ?>
                <nav class="mb-3" aria-label="breadcrumb">
                    <?php if ($this->mode === 'directories') : ?>
                        <span class="fw-bold"><?php echo Text::_('COM_MOVIELIST_SUBMENU_DIRECTORIES'); ?></span>
                    <?php else : ?>
                        <a href="<?php echo $linkDirectories; ?>"><?php echo Text::_('COM_MOVIELIST_SUBMENU_DIRECTORIES'); ?></a>
                    <?php endif; ?>

                    <?php if (!empty($this->crumb['directory'])) : ?>
                        <span class="text-muted mx-1">/</span>
                        <?php if ($this->mode === 'movies') : ?>
                            <a href="<?php echo $linkCategories; ?>"><?php echo $this->escape($this->crumb['directory']); ?></a>
                        <?php else : ?>
                            <span class="fw-bold"><?php echo $this->escape($this->crumb['directory']); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($this->crumb['category'])) : ?>
                        <span class="text-muted mx-1">/</span>
                        <span class="fw-bold"><?php echo $this->escape($this->crumb['category']); ?></span>
                    <?php endif; ?>
                </nav>

                <?php // ------------------------------------------------------------ DIRECTORIES ?>
                <?php if ($this->mode === 'directories') : ?>
                    <?php if (empty($this->directories)) : ?>
                        <div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
                    <?php else : ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                            <?php foreach ($this->directories as $dir) : ?>
                                <?php $url = Route::_($base . '&filter_directory_id=' . (int) $dir->id . '&filter_catid=&limitstart=0'); ?>
                                <div class="col">
                                    <a href="<?php echo $url; ?>" class="card h-100 text-decoration-none movielist-browse-card">
                                        <div class="card-body d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-body">
                                                <span class="icon-folder me-2" aria-hidden="true"></span>
                                                <?php echo $this->escape($dir->title); ?>
                                            </span>
                                            <span class="badge bg-secondary rounded-pill"><?php echo (int) $dir->movie_count; ?></span>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php // ------------------------------------------------------------ CATEGORIES ?>
                <?php elseif ($this->mode === 'categories') : ?>
                    <p><a href="<?php echo $linkDirectories; ?>" class="btn btn-sm btn-outline-secondary mb-3">
                        <span class="icon-arrow-left" aria-hidden="true"></span> <?php echo Text::_('COM_MOVIELIST_BROWSE_BACK_DIRECTORIES'); ?>
                    </a></p>

                    <?php if (empty($this->categories)) : ?>
                        <div class="alert alert-info"><?php echo Text::_('COM_MOVIELIST_BROWSE_NO_CATEGORIES'); ?></div>
                    <?php else : ?>
                        <div class="list-group">
                            <?php foreach ($this->categories as $cat) : ?>
                                <?php $url = Route::_($base . '&filter_directory_id=' . $dirId . '&filter_catid=' . (int) $cat->id . '&limitstart=0'); ?>
                                <a href="<?php echo $url; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center<?php echo ((int) $cat->state === 0) ? ' text-muted' : ''; ?>">
                                    <span style="padding-left:<?php echo max(0, ((int) $cat->level - 1)) * 1.5; ?>rem;">
                                        <span class="icon-folder me-2" aria-hidden="true"></span>
                                        <?php echo $this->escape($cat->title); ?>
                                        <?php if ((int) $cat->state === 0) : ?>
                                            <span class="badge bg-warning text-dark ms-1"><?php echo Text::_('JUNPUBLISHED'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="badge bg-secondary rounded-pill"><?php echo (int) $cat->movie_count; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php // ------------------------------------------------------------ MOVIES ?>
                <?php else : ?>
                    <p><a href="<?php echo $linkCategories; ?>" class="btn btn-sm btn-outline-secondary mb-3">
                        <span class="icon-arrow-left" aria-hidden="true"></span> <?php echo Text::_('COM_MOVIELIST_BROWSE_BACK_CATEGORIES'); ?>
                    </a></p>

                    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                    <?php if (empty($this->items)) : ?>
                        <div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
                    <?php else : ?>
                        <table class="table" id="movieList">
                            <caption class="visually-hidden"><?php echo Text::_('COM_MOVIELIST_MOVIES'); ?></caption>
                            <thead>
                                <tr>
                                    <td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                                    <th scope="col" class="w-1 text-center">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $this->escape($this->state->get('list.direction')), $this->escape($this->state->get('list.ordering'))); ?>
                                    </th>
                                    <th scope="col" class="w-5 text-center"><?php echo Text::_('COM_MOVIELIST_MOVIE_POSTER'); ?></th>
                                    <th scope="col">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOVIELIST_MOVIE_TITLE', 'a.title', $this->escape($this->state->get('list.direction')), $this->escape($this->state->get('list.ordering'))); ?>
                                    </th>
                                    <th scope="col" class="w-3 text-center d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $this->escape($this->state->get('list.direction')), $this->escape($this->state->get('list.ordering'))); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->items as $i => $item) : ?>
                                    <tr class="row<?php echo $i % 2; ?>">
                                        <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?></td>
                                        <td class="text-center">
                                            <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'movies.', $user->authorise('core.edit.state', 'com_movielist'), 'cb'); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($item->poster)) : ?>
                                                <img src="<?php echo Uri::root() . $this->escape($item->poster); ?>" alt="" style="max-height:48px;max-width:36px;object-fit:cover;">
                                            <?php endif; ?>
                                        </td>
                                        <th scope="row">
                                            <a href="<?php echo Route::_('index.php?option=com_movielist&task=movie.edit&id=' . (int) $item->id); ?>">
                                                <?php echo $this->escape($item->title); ?>
                                            </a>
                                            <div class="small text-muted"><?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?></div>
                                        </th>
                                        <td class="text-center d-none d-md-table-cell"><?php echo (int) $item->id; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php echo $this->pagination->getListFooter(); ?>
                    <?php endif; ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <input type="hidden" name="filter_directory_id" value="<?php echo $dirId; ?>">
                <input type="hidden" name="filter_catid" value="<?php echo $catId; ?>">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>

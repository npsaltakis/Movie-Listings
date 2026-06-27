<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Nickpsal\Component\Movielist\Administrator\Helper\FieldsHelper;

$showPoster = (int) $this->params->get('show_poster', 1);

$input     = Factory::getApplication()->getInput();
$search    = (string) $this->state->get('filter.search');
$curYear   = (int) $this->state->get('filter.year');
$curCat    = (int) $this->state->get('filter.catid');
$menuCat   = (int) $this->state->get('filter.menu_catid');
$itemid    = $input->getInt('Itemid', 0);
// GET forms drop the action's query string, so the routing params travel as hidden inputs.
$formAction = Uri::getInstance()->toString(['path']);
?>
<div class="com-movielist movielist-movies">
    <form action="<?php echo $this->escape($formAction); ?>" method="get" class="movielist-filters mb-4">
        <input type="hidden" name="option" value="com_movielist">
        <input type="hidden" name="view" value="movies">
        <?php if ($itemid > 0) : ?>
            <input type="hidden" name="Itemid" value="<?php echo $itemid; ?>">
        <?php endif; ?>
        <div class="row g-2 align-items-end">
            <div class="col-sm-5">
                <label class="form-label small mb-1" for="filter_search"><?php echo Text::_('COM_MOVIELIST_FILTER_SEARCH'); ?></label>
                <input type="text" name="filter_search" id="filter_search" class="form-control"
                       value="<?php echo $this->escape($search); ?>"
                       placeholder="<?php echo $this->escape(Text::_('COM_MOVIELIST_FILTER_SEARCH_PLACEHOLDER')); ?>">
            </div>

            <?php if (!empty($this->filterCategories)) : ?>
                <div class="col-sm-3">
                    <label class="form-label small mb-1" for="filter_catid"><?php echo Text::_('COM_MOVIELIST_FILTER_CATEGORY'); ?></label>
                    <select name="filter_catid" id="filter_catid" class="form-select" onchange="this.form.submit()">
                        <option value="0"><?php echo Text::_('COM_MOVIELIST_FILTER_ALL_CATEGORIES'); ?></option>
                        <?php foreach ($this->filterCategories as $cat) : ?>
                            <option value="<?php echo (int) $cat->id; ?>"<?php echo $curCat === (int) $cat->id ? ' selected' : ''; ?>>
                                <?php echo $this->escape($cat->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (!empty($this->years)) : ?>
                <div class="col-sm-2">
                    <label class="form-label small mb-1" for="filter_year"><?php echo Text::_('COM_MOVIELIST_FILTER_YEAR'); ?></label>
                    <select name="filter_year" id="filter_year" class="form-select" onchange="this.form.submit()">
                        <option value="0"><?php echo Text::_('COM_MOVIELIST_FILTER_ALL_YEARS'); ?></option>
                        <?php foreach ($this->years as $year) : ?>
                            <option value="<?php echo $year; ?>"<?php echo $curYear === $year ? ' selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-sm-2 d-grid">
                <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_MOVIELIST_FILTER_GO'); ?></button>
            </div>
        </div>
    </form>

    <?php if (empty($this->items)) : ?>
        <p><?php echo Text::_('COM_MOVIELIST_NO_MOVIES'); ?></p>
    <?php else : ?>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-4">
            <?php foreach ($this->items as $item) : ?>
                <?php $link = Route::_('index.php?option=com_movielist&view=movie&id=' . (int) $item->id . '&catid=' . (int) $item->catid); ?>
                <div class="col">
                    <div class="card h-100 movielist-movie-card">
                        <a href="<?php echo $link; ?>" class="text-decoration-none">
                            <?php if ($showPoster && !empty($item->poster)) : ?>
                                <img class="card-img-top" src="<?php echo Uri::root() . $this->escape(FieldsHelper::cleanImage($item->poster)); ?>"
                                     alt="<?php echo $this->escape($item->title); ?>" style="aspect-ratio:2/3;object-fit:cover;">
                            <?php else : ?>
                                <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                     style="aspect-ratio:2/3;">
                                    <span class="display-6 text-muted">🎬</span>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <h3 class="card-title h6 mb-1">
                                <a href="<?php echo $link; ?>" class="text-decoration-none stretched-link">
                                    <?php echo $this->escape($item->title); ?>
                                </a>
                            </h3>
                            <?php foreach (FieldsHelper::getRenderFields($item, 'list') as $field) : ?>
                                <?php if (\in_array($field->type, ['media', 'image', 'group'], true) || $field->display === '') : ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <div class="small text-truncate movielist-field movielist-field-<?php echo $this->escape($field->key); ?>"
                                     title="<?php echo $this->escape(strip_tags((string) $field->display)); ?>">
                                    <?php echo $this->escape(strip_tags((string) $field->display)); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($this->pagination->pagesTotal > 1) : ?>
            <div class="mt-4"><?php echo $this->pagination->getListFooter(); ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>

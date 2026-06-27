<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$itemId = (int) $this->state->get('filter.itemid');
?>
<div class="com-movielist movielist-categories">
    <?php if (empty($this->items)) : ?>
        <p><?php echo Text::_('COM_MOVIELIST_NO_CATEGORIES'); ?></p>
    <?php else : ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($this->items as $item) : ?>
                <?php
                $link = Route::_('index.php?option=com_movielist&view=movies&catid=' . (int) $item->id);
                ?>
                <div class="col">
                    <a class="card h-100 text-decoration-none movielist-category-card" href="<?php echo $link; ?>">
                        <?php if (!empty($item->image)) : ?>
                            <img class="card-img-top" src="<?php echo Uri::root() . $this->escape($item->image); ?>"
                                 alt="<?php echo $this->escape($item->title); ?>" style="aspect-ratio:16/9;object-fit:cover;">
                        <?php else : ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                 style="aspect-ratio:16/9;">
                                <span class="display-6 text-muted">🎬</span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title h5"><?php echo $this->escape($item->title); ?></h3>
                            <span class="badge bg-primary">
                                <?php echo Text::plural('COM_MOVIELIST_N_MOVIES', (int) $item->movie_count); ?>
                            </span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($this->pagination->pagesTotal > 1) : ?>
            <div class="mt-4"><?php echo $this->pagination->getListFooter(); ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>

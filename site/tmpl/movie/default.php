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
use Nickpsal\Component\Movielist\Administrator\Helper\FieldsHelper;

$item     = $this->item;
$backLink = Route::_('index.php?option=com_movielist&view=movies&catid=' . (int) $item->catid);
$fields   = FieldsHelper::getRenderFields($item, 'detail');
?>
<div class="com-movielist movielist-movie">
    <p class="mb-3"><a href="<?php echo $backLink; ?>">&laquo; <?php echo $this->escape($item->category_title); ?></a></p>

    <div class="row">
        <?php if (!empty($item->poster)) : ?>
            <div class="col-md-4">
                <img class="img-fluid rounded shadow-sm mb-3" src="<?php echo Uri::root() . $this->escape(FieldsHelper::cleanImage($item->poster)); ?>"
                     alt="<?php echo $this->escape($item->title); ?>">
            </div>
        <?php endif; ?>

        <div class="<?php echo !empty($item->poster) ? 'col-md-8' : 'col-12'; ?>">
            <h1 class="movielist-movie-title"><?php echo $this->escape($item->title); ?></h1>

            <dl class="row movielist-fields">
                <?php foreach ($fields as $field) : ?>
                    <dt class="col-sm-3 movielist-field-label"><?php echo $this->escape($field->label); ?></dt>
                    <dd class="col-sm-9 movielist-field-value movielist-field-<?php echo $this->escape($field->key); ?>">
                        <?php switch ($field->type) :
                            case 'group': ?>
                                <ul class="list-unstyled mb-0 movielist-group">
                                    <?php foreach ($field->rows as $row) : ?>
                                        <li class="movielist-group-row d-flex flex-wrap gap-2 align-items-center mb-2">
                                            <?php foreach ($row as $cell) : ?>
                                                <?php if (\in_array($cell->type, ['media', 'image'], true)) : ?>
                                                    <?php if ($cell->value !== '') : ?>
                                                        <img class="rounded" style="max-height:56px" src="<?php echo Uri::root() . $this->escape(FieldsHelper::cleanImage($cell->value)); ?>" alt="">
                                                    <?php endif; ?>
                                                <?php elseif ($cell->type === 'editor') : ?>
                                                    <span class="movielist-cell"><?php echo strip_tags((string) $cell->value); ?></span>
                                                <?php else : ?>
                                                    <span class="movielist-cell"><?php echo $this->escape($cell->value); ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php break; ?>
                            <?php case 'media':
                            case 'image': ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($field->values as $img) : ?>
                                        <img class="img-fluid rounded" style="max-height:160px" src="<?php echo Uri::root() . $this->escape($img); ?>" alt="">
                                    <?php endforeach; ?>
                                </div>
                                <?php break; ?>
                            <?php case 'editor': ?>
                                <?php echo $field->display; // already filtered HTML ?>
                                <?php break; ?>
                            <?php case 'url': ?>
                                <a href="<?php echo $this->escape($field->display); ?>" target="_blank" rel="noopener">
                                    <?php echo $this->escape($field->display); ?>
                                </a>
                                <?php break; ?>
                            <?php default: ?>
                                <?php echo $this->escape($field->display); ?>
                        <?php endswitch; ?>
                    </dd>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>

    <?php if (!empty($item->gallery)) : ?>
        <div class="movielist-gallery mt-4">
            <h2 class="h4 mb-3"><?php echo Text::_('COM_MOVIELIST_GALLERY'); ?></h2>
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3">
                <?php foreach ($item->gallery as $img) : ?>
                    <div class="col">
                        <figure class="figure m-0">
                            <img class="figure-img img-fluid rounded shadow-sm"
                                 src="<?php echo Uri::root() . $this->escape(FieldsHelper::cleanImage($img->filename)); ?>"
                                 alt="<?php echo $this->escape($img->caption ?: $item->title); ?>"
                                 loading="lazy" style="aspect-ratio:3/2;object-fit:cover;width:100%;">
                            <?php if (!empty($img->caption)) : ?>
                                <figcaption class="figure-caption small"><?php echo $this->escape($img->caption); ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

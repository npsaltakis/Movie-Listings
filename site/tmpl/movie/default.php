<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Nickpsal\Component\Movielist\Administrator\Helper\FieldsHelper;

$item     = $this->item;
$backLink = Route::_('index.php?option=com_movielist&view=movies&catid=' . (int) $item->catid);
$fields   = FieldsHelper::getRenderFields($item, 'detail');

HTMLHelper::_('stylesheet', 'com_movielist/lightbox.css', ['version' => 'auto', 'relative' => true]);
HTMLHelper::_('stylesheet', 'com_movielist/site.css', ['version' => 'auto', 'relative' => true]);
HTMLHelper::_('script', 'com_movielist/lightbox.js', ['version' => 'auto', 'relative' => true]);

// Split the fields by presentation: short facts stay beside the poster,
// videos become embedded players, groups become tables and editor fields
// become full-width prose sections below.
$metaFields = [];
$videos     = [];
$groups     = [];
$sections   = [];

foreach ($fields as $field) {
    if ($field->type === 'group') {
        $groups[] = $field;
        continue;
    }

    if ($field->type === 'editor') {
        $sections[] = $field;
        continue;
    }

    if ($field->type === 'url') {
        $embed = FieldsHelper::videoEmbed((string) $field->display);

        if ($embed['type'] !== 'link') {
            $field->embed = $embed;
            $videos[]     = $field;
            continue;
        }
    }

    $metaFields[] = $field;
}
?>
<div class="com-movielist movielist-movie">
    <p class="movielist-back"><a href="<?php echo $backLink; ?>">&laquo; <?php echo $this->escape($item->category_title); ?></a></p>

    <div class="row movielist-hero">
        <?php if (!empty($item->poster)) : ?>
            <div class="col-md-4">
                <img class="img-fluid rounded shadow-sm mb-3 js-mlb" data-mlb-group="movie"
                     src="<?php echo Uri::root() . $this->escape(FieldsHelper::cleanImage($item->poster)); ?>"
                     alt="<?php echo $this->escape($item->title); ?>">
            </div>
        <?php endif; ?>

        <div class="<?php echo !empty($item->poster) ? 'col-md-8' : 'col-12'; ?>">
            <h1 class="movielist-movie-title"><?php echo $this->escape($item->title); ?></h1>

            <dl class="row movielist-fields">
                <?php foreach ($metaFields as $field) : ?>
                    <dt class="col-sm-4 movielist-field-label"><?php echo $this->escape($field->label); ?></dt>
                    <dd class="col-sm-8 movielist-field-value movielist-field-<?php echo $this->escape($field->key); ?>">
                        <?php switch ($field->type) :
                            case 'media':
                            case 'image': ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($field->values as $img) : ?>
                                        <img class="img-fluid rounded js-mlb movielist-field-image" data-mlb-group="field-<?php echo $this->escape($field->key); ?>" data-mlb-caption="<?php echo $this->escape($field->label); ?>" src="<?php echo Uri::root() . $this->escape($img); ?>" alt="<?php echo $this->escape($field->label); ?>">
                                    <?php endforeach; ?>
                                </div>
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

    <?php foreach ($videos as $field) : ?>
        <div class="movielist-section movielist-trailer">
            <h2 class="movielist-section-title"><?php echo $this->escape($field->label); ?></h2>
            <?php if ($field->embed['type'] === 'iframe') : ?>
                <div class="movielist-ratio">
                    <iframe src="<?php echo $this->escape($field->embed['src']); ?>"
                            title="<?php echo $this->escape($field->label . ' - ' . $item->title); ?>"
                            loading="lazy" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                </div>
            <?php else : ?>
                <div class="movielist-ratio">
                    <video src="<?php echo $this->escape($field->embed['src']); ?>" controls preload="metadata"></video>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php foreach ($groups as $field) : ?>
        <?php $head = $field->rows[0] ?? []; ?>
        <div class="movielist-section movielist-credits">
            <h2 class="movielist-section-title"><?php echo $this->escape($field->label); ?></h2>
            <div class="table-responsive">
                <table class="table table-sm movielist-credits-table">
                    <thead>
                        <tr>
                            <?php foreach ($head as $cell) : ?>
                                <th scope="col"><?php echo $this->escape($cell->label); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($field->rows as $row) : ?>
                            <tr>
                                <?php foreach ($row as $cell) : ?>
                                    <td>
                                        <?php if (\in_array($cell->type, ['media', 'image'], true)) : ?>
                                            <?php if ($cell->value !== '') : ?>
                                                <img class="rounded js-mlb movielist-cell-image" data-mlb-group="field-<?php echo $this->escape($field->key); ?>" src="<?php echo Uri::root() . $this->escape(FieldsHelper::cleanImage($cell->value)); ?>" alt="">
                                            <?php endif; ?>
                                        <?php elseif ($cell->type === 'editor') : ?>
                                            <?php echo strip_tags((string) $cell->value); ?>
                                        <?php else : ?>
                                            <?php echo $this->escape($cell->value); ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

    <?php foreach ($sections as $field) : ?>
        <div class="movielist-section movielist-prose movielist-field-<?php echo $this->escape($field->key); ?>">
            <h2 class="movielist-section-title"><?php echo $this->escape($field->label); ?></h2>
            <?php echo $field->display; // already filtered HTML ?>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($item->gallery)) : ?>
        <div class="movielist-section movielist-gallery">
            <h2 class="movielist-section-title"><?php echo Text::_('COM_MOVIELIST_GALLERY'); ?></h2>
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3">
                <?php foreach ($item->gallery as $img) : ?>
                    <div class="col">
                        <figure class="figure m-0">
                            <img class="figure-img img-fluid rounded shadow-sm js-mlb" data-mlb-group="gallery"
                                 data-mlb-caption="<?php echo $this->escape($img->caption); ?>"
                                 src="<?php echo Uri::root() . $this->escape(FieldsHelper::cleanImage($img->filename)); ?>"
                                 alt="<?php echo $this->escape($img->caption ?: $item->title); ?>"
                                 loading="lazy" style="aspect-ratio:3/2;object-fit:cover;width:100%;cursor:zoom-in;">
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

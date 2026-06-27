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

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

$customFields = $this->form->getGroup('com_fields');
?>
<form action="<?php echo Route::_('index.php?option=com_movielist&view=movie&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="movie-form" class="form-validate">

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'movieTab', ['active' => 'details']); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'movieTab', 'details', Text::_('COM_MOVIELIST_TAB_DETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php echo $this->form->renderField('title'); ?>
                <?php echo $this->form->renderField('alias'); ?>
            </div>
            <div class="col-lg-3">
                <?php echo $this->form->renderField('directory_id'); ?>
                <?php echo $this->form->renderField('catid'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'movieTab', 'media', Text::_('COM_MOVIELIST_TAB_MEDIA')); ?>
        <div class="row">
            <div class="col-lg-12">
                <?php echo $this->form->renderField('poster'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'movieTab', 'gallery', Text::_('COM_MOVIELIST_TAB_GALLERY')); ?>
        <div class="row">
            <div class="col-lg-12">
                <?php echo $this->form->renderField('gallery'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php if (!empty($customFields)) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'movieTab', 'customfields', Text::_('COM_MOVIELIST_TAB_CUSTOM_FIELDS')); ?>
            <div class="row">
                <div class="col-lg-9">
                    <?php foreach ($customFields as $field) : ?>
                        <?php echo $field->renderField(); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'movieTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
        <div class="row">
            <div class="col-lg-6">
                <?php echo $this->form->renderField('state'); ?>
                <?php echo $this->form->renderField('featured'); ?>
                <?php echo $this->form->renderField('access'); ?>
                <?php echo $this->form->renderField('language'); ?>
                <?php echo $this->form->renderField('ordering'); ?>
            </div>
            <div class="col-lg-6">
                <?php echo $this->form->renderField('metakey'); ?>
                <?php echo $this->form->renderField('metadesc'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>

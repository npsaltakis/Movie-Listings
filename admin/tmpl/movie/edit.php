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
use Nickpsal\Component\Movielist\Administrator\Helper\FieldsHelper;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

// Film/script field toggling: category id => content type map plus the
// type-specific field names (editors lose the CSS class to TinyMCE, so the
// JS locates fields by name).
$fieldApply = [];

foreach (FieldsHelper::getFields() as $customField) {
    $applyTo = $customField->apply_to ?? 'all';

    if ($applyTo === 'film' || $applyTo === 'script') {
        $fieldApply[$customField->name] = $applyTo;
    }
}

$this->getDocument()->addScriptOptions('com_movielist.fieldToggle', [
    'categoryTypes' => FieldsHelper::categoryTypeMap(),
    'fieldApply'    => $fieldApply,
]);
HTMLHelper::_('script', 'com_movielist/admin.js', ['version' => 'auto', 'relative' => true]);

// getGroup() also returns the inner fields of inline subforms (e.g. the
// name/role columns of a group field), so keep only the defined fields.
$fieldNames   = array_column(FieldsHelper::getFields(), 'name');
$customFields = array_filter(
    $this->form->getGroup('com_fields'),
    static fn ($field) => \in_array($field->fieldname, $fieldNames, true)
);
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

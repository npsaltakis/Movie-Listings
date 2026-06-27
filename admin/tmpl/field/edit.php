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
?>
<form action="<?php echo Route::_('index.php?option=com_movielist&view=field&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="field-form" class="form-validate">

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'fieldTab', ['active' => 'details']); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'fieldTab', 'details', Text::_('COM_MOVIELIST_TAB_DETAILS')); ?>
        <div class="row">
            <div class="col-lg-6">
                <?php echo $this->form->renderField('title'); ?>
                <?php echo $this->form->renderField('name'); ?>
                <?php echo $this->form->renderField('label'); ?>
                <?php echo $this->form->renderField('type'); ?>
                <?php echo $this->form->renderField('description'); ?>
                <?php echo $this->form->renderField('default_value'); ?>
            </div>
            <div class="col-lg-6">
                <?php echo $this->form->renderField('is_multiple'); ?>
                <?php echo $this->form->renderField('multiple_mode'); ?>
                <?php echo $this->form->renderField('max_items'); ?>
                <?php echo $this->form->renderField('show_in_list'); ?>
                <?php echo $this->form->renderField('show_in_detail'); ?>
                <?php echo $this->form->renderField('required'); ?>
                <?php echo $this->form->renderField('searchable'); ?>
                <?php echo $this->form->renderField('state'); ?>
                <?php echo $this->form->renderField('ordering'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <?php echo $this->form->renderField('subfields'); ?>
                <?php echo $this->form->renderField('options'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>

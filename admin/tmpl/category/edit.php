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
<form action="<?php echo Route::_('index.php?option=com_movielist&view=category&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="category-form" class="form-validate">

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details']); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_MOVIELIST_TAB_DETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php echo $this->form->renderField('title'); ?>
                <?php echo $this->form->renderField('alias'); ?>
                <?php echo $this->form->renderField('description'); ?>
            </div>
            <div class="col-lg-3">
                <?php echo $this->form->renderField('directory_id'); ?>
                <?php echo $this->form->renderField('parent_id'); ?>
                <?php echo $this->form->renderField('image'); ?>
                <?php echo $this->form->renderField('state'); ?>
                <?php echo $this->form->renderField('access'); ?>
                <?php echo $this->form->renderField('language'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>

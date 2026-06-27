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
?>
<form action="<?php echo Route::_('index.php?option=com_movielist&view=ffimport'); ?>"
      method="post" name="adminForm" id="adminForm" enctype="multipart/form-data" class="com-movielist-ffimport">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <p><?php echo Text::_('COM_MOVIELIST_FF_INTRO'); ?></p>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="csvfile"><?php echo Text::_('COM_MOVIELIST_FF_FILE'); ?></label>
                        <input type="file" name="csvfile" id="csvfile" accept=".csv" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="directory_id"><?php echo Text::_('COM_MOVIELIST_FF_DIRECTORY'); ?></label>
                        <select name="directory_id" id="directory_id" class="form-select">
                            <option value="0">— <?php echo Text::_('COM_MOVIELIST_FF_NEW_DIRECTORY'); ?> —</option>
                            <?php foreach ($this->directories as $dir) : ?>
                                <option value="<?php echo (int) $dir->id; ?>"><?php echo $this->escape($dir->title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="new_directory"><?php echo Text::_('COM_MOVIELIST_FF_NEW_DIRECTORY_TITLE'); ?></label>
                        <input type="text" name="new_directory" id="new_directory" class="form-control"
                               placeholder="<?php echo $this->escape(Text::_('COM_MOVIELIST_FF_NEW_DIRECTORY_HINT')); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg"
                            onclick="document.getElementById('adminForm').task.value='ffimport.import';">
                        <span class="icon-upload" aria-hidden="true"></span> <?php echo Text::_('COM_MOVIELIST_FF_START'); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><?php echo Text::_('COM_MOVIELIST_FF_NOTES'); ?></div>
                <div class="card-body small">
                    <ul class="mb-0 ps-3">
                        <li><?php echo Text::_('COM_MOVIELIST_FF_NOTE_UNPUBLISHED'); ?></li>
                        <li><?php echo Text::_('COM_MOVIELIST_FF_NOTE_CATEGORIES'); ?></li>
                        <li><?php echo Text::_('COM_MOVIELIST_FF_NOTE_IDEMPOTENT'); ?></li>
                        <li><?php echo Text::_('COM_MOVIELIST_FF_NOTE_MEDIA'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

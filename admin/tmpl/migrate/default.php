<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('script', 'com_movielist/migrate.js', ['version' => 'auto', 'relative' => true]);

$this->getDocument()->addScriptOptions('com_movielist.migrate', [
    'base'  => Uri::base(true) . '/index.php?option=com_movielist&format=raw',
    'token' => Session::getFormToken(),
]);
?>
<div class="com-movielist-migrate">
    <?php if (!$this->mtPresent) : ?>
        <div class="alert alert-warning">
            <h4 class="alert-heading"><?php echo Text::_('COM_MOVIELIST_MIGRATE_NO_MT'); ?></h4>
            <?php echo Text::_('COM_MOVIELIST_MIGRATE_NO_MT_DESC'); ?>
        </div>
    <?php else : ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header"><?php echo Text::_('COM_MOVIELIST_MIGRATE_OVERVIEW'); ?></div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-center"><?php echo Text::_('COM_MOVIELIST_MIGRATE_SOURCE'); ?></th>
                                    <th class="text-center"><?php echo Text::_('COM_MOVIELIST_MIGRATE_TARGET'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (['directories' => 'COM_MOVIELIST_SUBMENU_DIRECTORIES', 'categories' => 'COM_MOVIELIST_SUBMENU_CATEGORIES', 'movies' => 'COM_MOVIELIST_SUBMENU_MOVIES'] as $key => $lang) : ?>
                                    <tr>
                                        <th><?php echo Text::_($lang); ?></th>
                                        <td class="text-center"><?php echo (int) $this->status['source'][$key]; ?></td>
                                        <td class="text-center" id="tgt-<?php echo $key; ?>"><?php echo (int) $this->status['target'][$key]; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <p><?php echo Text::_('COM_MOVIELIST_MIGRATE_INTRO'); ?></p>

                        <button type="button" id="ml-migrate-start" class="btn btn-primary btn-lg">
                            <span class="icon-loop" aria-hidden="true"></span>
                            <?php echo Text::_('COM_MOVIELIST_MIGRATE_START'); ?>
                        </button>

                        <div id="ml-migrate-progress" class="mt-4 d-none">
                            <div class="progress" style="height:24px;">
                                <div id="ml-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%">0%</div>
                            </div>
                            <p class="mt-2 mb-0"><span id="ml-status" class="text-muted"></span></p>
                        </div>

                        <div id="ml-migrate-done" class="alert alert-success mt-4 d-none">
                            <span class="icon-check" aria-hidden="true"></span>
                            <span id="ml-done-text"></span>
                        </div>
                        <div id="ml-migrate-error" class="alert alert-danger mt-4 d-none"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><?php echo Text::_('COM_MOVIELIST_MIGRATE_MAPPING'); ?></div>
                    <div class="card-body small">
                        <ul class="mb-0 ps-3">
                            <li><?php echo Text::_('COM_MOVIELIST_MIGRATE_MAP_DIR'); ?></li>
                            <li><?php echo Text::_('COM_MOVIELIST_MIGRATE_MAP_CAT'); ?></li>
                            <li><?php echo Text::_('COM_MOVIELIST_MIGRATE_MAP_MOVIE'); ?></li>
                            <li><?php echo Text::_('COM_MOVIELIST_MIGRATE_MAP_FIELDS'); ?></li>
                            <li><?php echo Text::_('COM_MOVIELIST_MIGRATE_MAP_IMAGES'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form action="<?php echo \Joomla\CMS\Router\Route::_('index.php?option=com_movielist&view=migrate'); ?>" method="post" name="adminForm" id="adminForm">
        <input type="hidden" name="task" value="">
        <?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>
    </form>
</div>

<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Categories list controller.
 */
class CategoriesController extends AdminController
{
    public function getModel($name = 'Category', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Move or copy the selected categories to another directory / parent.
     * Move relocates the whole subtree and its movies; copy duplicates the subtree structure.
     *
     * @return  void
     */
    public function batchmove(): void
    {
        $this->checkToken();

        $input   = $this->input;
        $cid     = array_values(array_filter(ArrayHelper::toInteger((array) $input->get('cid', [], 'array'))));
        $action  = $input->getCmd('batch_action', 'move');
        $destDir = $input->getInt('batch_directory', 0);
        $destPar = $input->getInt('batch_parent', 0);
        $db      = Factory::getContainer()->get(DatabaseInterface::class);

        $back = Route::_('index.php?option=com_movielist&view=categories', false);

        if (!$cid) {
            $this->setRedirect($back, Text::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'), 'warning');

            return;
        }

        // A chosen parent dictates the directory.
        if ($destPar > 0) {
            $destDir = (int) $db->setQuery(
                $db->getQuery(true)->select($db->quoteName('directory_id'))
                    ->from($db->quoteName('#__movielist_categories'))->where($db->quoteName('id') . ' = ' . $destPar)
            )->loadResult();
        }

        if ($destDir <= 0) {
            $this->setRedirect($back, Text::_('COM_MOVIELIST_BATCH_NO_TARGET'), 'warning');

            return;
        }

        $count = 0;

        foreach ($cid as $id) {
            // Guard: cannot move a category under itself or its own descendant.
            if ($destPar > 0 && $this->isSelfOrDescendant($db, $id, $destPar)) {
                continue;
            }

            if ($action === 'copy') {
                $count += $this->copyCategory($db, $id, $destDir, $destPar) ? 1 : 0;
            } else {
                $count += $this->moveCategory($db, $id, $destDir, $destPar) ? 1 : 0;
            }
        }

        $msg = $action === 'copy'
            ? Text::sprintf('COM_MOVIELIST_BATCH_CAT_COPIED', $count)
            : Text::sprintf('COM_MOVIELIST_BATCH_CAT_MOVED', $count);

        $dest = Route::_('index.php?option=com_movielist&view=categories&filter[directory_id]=' . $destDir, false);
        $this->setRedirect($dest, $msg);
    }

    /**
     * Load a category row.
     */
    private function loadCat(DatabaseInterface $db, int $id): ?object
    {
        return $db->setQuery(
            $db->getQuery(true)->select('*')->from($db->quoteName('#__movielist_categories'))->where($db->quoteName('id') . ' = ' . $id)
        )->loadObject() ?: null;
    }

    /**
     * The subtree (root + descendants) of a category, ordered shallow-first.
     */
    private function subtree(DatabaseInterface $db, object $cat): array
    {
        return $db->setQuery(
            $db->getQuery(true)->select('*')
                ->from($db->quoteName('#__movielist_categories'))
                ->where($db->quoteName('directory_id') . ' = ' . (int) $cat->directory_id)
                ->where('(' . $db->quoteName('id') . ' = ' . (int) $cat->id
                    . ' OR ' . $db->quoteName('path') . ' LIKE ' . $db->quote($db->escape($cat->path, true) . '/%', false) . ')')
                ->order($db->quoteName('level') . ' ASC')
        )->loadObjectList() ?: [];
    }

    private function isSelfOrDescendant(DatabaseInterface $db, int $rootId, int $candidate): bool
    {
        if ($rootId === $candidate) {
            return true;
        }

        $root = $this->loadCat($db, $rootId);
        $cand = $this->loadCat($db, $candidate);

        if (!$root || !$cand) {
            return false;
        }

        return $cand->directory_id == $root->directory_id
            && (strpos((string) $cand->path, $root->path . '/') === 0);
    }

    /**
     * Relocate a category subtree (and its movies) to a new directory / parent.
     */
    private function moveCategory(DatabaseInterface $db, int $id, int $destDir, int $destPar): bool
    {
        $cat = $this->loadCat($db, $id);

        if (!$cat) {
            return false;
        }

        $parentLevel = 0;
        $parentPath  = '';

        if ($destPar > 0 && ($p = $this->loadCat($db, $destPar))) {
            $parentLevel = (int) $p->level;
            $parentPath  = $p->path;
        }

        $newRootLevel = $parentLevel + 1;
        $newRootPath  = ($parentPath !== '' ? $parentPath . '/' : '') . $cat->alias;
        $delta        = $newRootLevel - (int) $cat->level;
        $oldRootPath  = (string) $cat->path;
        $subtreeIds   = [];

        foreach ($this->subtree($db, $cat) as $node) {
            $subtreeIds[] = (int) $node->id;
            $newPath      = $newRootPath . substr((string) $node->path, \strlen($oldRootPath));
            $newLevel     = (int) $node->level + $delta;

            $row        = new \stdClass();
            $row->id    = (int) $node->id;
            $row->directory_id = $destDir;
            $row->level = $newLevel;
            $row->path  = $newPath;

            if ((int) $node->id === (int) $cat->id) {
                $row->parent_id = $destPar;
            }

            $db->updateObject('#__movielist_categories', $row, 'id');
        }

        // Move the movies of the subtree to the new directory.
        if ($subtreeIds) {
            $in = implode(',', $subtreeIds);
            $db->setQuery(
                'UPDATE ' . $db->quoteName('#__movielist_movies') . ' SET ' . $db->quoteName('directory_id') . ' = ' . $destDir
                . ' WHERE ' . $db->quoteName('catid') . ' IN (' . $in . ')'
            )->execute();
        }

        return true;
    }

    /**
     * Duplicate a category subtree structure (without movies) into a new directory / parent.
     */
    private function copyCategory(DatabaseInterface $db, int $id, int $destDir, int $destPar, bool $isRoot = true): bool
    {
        $cat = $this->loadCat($db, $id);

        if (!$cat) {
            return false;
        }

        $parentLevel = 0;
        $parentPath  = '';

        if ($destPar > 0 && ($p = $this->loadCat($db, $destPar))) {
            $parentLevel = (int) $p->level;
            $parentPath  = $p->path;
        }

        $alias = $cat->alias . ($isRoot ? '-copy-' . substr(md5(microtime()), 0, 5) : '');
        $path  = ($parentPath !== '' ? $parentPath . '/' : '') . $alias;

        $row = clone $cat;
        unset($row->id, $row->asset_id);
        $row->directory_id = $destDir;
        $row->parent_id    = $destPar;
        $row->level        = $parentLevel + 1;
        $row->alias        = $alias;
        $row->path         = $path;
        $row->created      = Factory::getDate()->toSql();
        $row->created_by   = (int) Factory::getApplication()->getIdentity()->id;

        $db->insertObject('#__movielist_categories', $row);
        $newId = (int) $db->insertid();

        // Recurse direct children.
        foreach ($db->setQuery(
            $db->getQuery(true)->select($db->quoteName('id'))
                ->from($db->quoteName('#__movielist_categories'))
                ->where($db->quoteName('parent_id') . ' = ' . $id)
                ->where($db->quoteName('directory_id') . ' = ' . (int) $cat->directory_id)
        )->loadColumn() as $childId) {
            $this->copyCategory($db, (int) $childId, $destDir, $newId, false);
        }

        return true;
    }
}

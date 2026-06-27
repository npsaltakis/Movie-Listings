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
 * Movies list controller.
 */
class MoviesController extends AdminController
{
    public function getModel($name = 'Movie', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Move or copy the selected movies to another category (possibly in another directory).
     *
     * @return  void
     */
    public function batchmove(): void
    {
        $this->checkToken();

        $input  = $this->input;
        $cid    = ArrayHelper::toInteger((array) $input->get('cid', [], 'array'));
        $cid    = array_values(array_filter($cid));
        $action = $input->getCmd('batch_action', 'move');
        $catid  = $input->getInt('batch_catid', 0);
        $db     = Factory::getContainer()->get(DatabaseInterface::class);

        $back = Route::_('index.php?option=com_movielist&view=movies', false);

        if (!$cid) {
            $this->setRedirect($back, Text::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'), 'warning');

            return;
        }

        if ($catid <= 0) {
            $this->setRedirect($back, Text::_('COM_MOVIELIST_BATCH_NO_TARGET'), 'warning');

            return;
        }

        // Destination directory follows the chosen category.
        $dir = (int) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('directory_id'))
                ->from($db->quoteName('#__movielist_categories'))
                ->where($db->quoteName('id') . ' = ' . $catid)
        )->loadResult();

        if ($dir <= 0) {
            $this->setRedirect($back, Text::_('COM_MOVIELIST_BATCH_NO_TARGET'), 'warning');

            return;
        }

        $count = 0;

        if ($action === 'copy') {
            foreach ($cid as $id) {
                $count += $this->copyMovie($db, $id, $dir, $catid) ? 1 : 0;
            }

            $msg = Text::sprintf('COM_MOVIELIST_BATCH_COPIED', $count);
        } else {
            $ids = implode(',', $cid);
            $db->setQuery(
                'UPDATE ' . $db->quoteName('#__movielist_movies')
                . ' SET ' . $db->quoteName('directory_id') . ' = ' . $dir
                . ', ' . $db->quoteName('catid') . ' = ' . $catid
                . ' WHERE ' . $db->quoteName('id') . ' IN (' . $ids . ')'
            )->execute();
            $count = \count($cid);
            $msg   = Text::sprintf('COM_MOVIELIST_BATCH_MOVED', $count);
        }

        $dest = Route::_('index.php?option=com_movielist&view=movies&filter_directory_id=' . $dir . '&filter_catid=' . $catid . '&limitstart=0', false);
        $this->setRedirect($dest, $msg);
    }

    /**
     * Duplicate one movie (row + custom field values + gallery images) into a target.
     */
    private function copyMovie(DatabaseInterface $db, int $id, int $dir, int $catid): bool
    {
        $movie = $db->setQuery(
            $db->getQuery(true)->select('*')->from($db->quoteName('#__movielist_movies'))->where($db->quoteName('id') . ' = ' . $id)
        )->loadObject();

        if (!$movie) {
            return false;
        }

        unset($movie->id, $movie->asset_id);
        $movie->directory_id = $dir;
        $movie->catid        = $catid;
        $movie->created      = Factory::getDate()->toSql();
        $movie->created_by   = (int) Factory::getApplication()->getIdentity()->id;
        $movie->hits         = 0;
        $movie->alias        = $movie->alias . '-copy-' . substr(md5(microtime()), 0, 5);

        $db->insertObject('#__movielist_movies', $movie);
        $newId = (int) $db->insertid();

        // Custom field values.
        foreach ($db->setQuery(
            $db->getQuery(true)->select($db->quoteName(['field_id', 'value']))
                ->from($db->quoteName('#__movielist_field_values'))->where($db->quoteName('movie_id') . ' = ' . $id)
        )->loadObjectList() as $v) {
            $v->movie_id = $newId;
            $db->insertObject('#__movielist_field_values', $v);
        }

        // Gallery images.
        foreach ($db->setQuery(
            $db->getQuery(true)->select($db->quoteName(['filename', 'caption', 'type', 'ordering', 'state']))
                ->from($db->quoteName('#__movielist_images'))->where($db->quoteName('movie_id') . ' = ' . $id)
        )->loadObjectList() as $img) {
            $img->movie_id = $newId;
            $db->insertObject('#__movielist_images', $img);
        }

        return true;
    }
}

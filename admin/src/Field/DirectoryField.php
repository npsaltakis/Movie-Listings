<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Form field listing all published directories.
 */
class DirectoryField extends ListField
{
    protected $type = 'Directory';

    protected function getOptions()
    {
        $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title']))
            ->from($db->quoteName('#__movielist_directories'))
            ->where($db->quoteName('state') . ' = 1')
            ->order($db->quoteName('title') . ' ASC');

        $db->setQuery($query);
        $items = $db->loadObjectList();

        $options = [];

        foreach ($items as $item) {
            $options[] = \Joomla\CMS\HTML\HTMLHelper::_('select.option', $item->id, $item->title);
        }

        return array_merge(parent::getOptions(), $options);
    }
}

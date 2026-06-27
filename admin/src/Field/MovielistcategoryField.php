<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Form field listing categories as an indented tree (optionally scoped by directory).
 */
class MovielistcategoryField extends ListField
{
    protected $type = 'Movielistcategory';

    protected function getOptions()
    {
        $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'level', 'directory_id', 'parent_id']))
            ->from($db->quoteName('#__movielist_categories'))
            ->where($db->quoteName('state') . ' = 1')
            ->order($db->quoteName('directory_id') . ' ASC, ' . $db->quoteName('path') . ' ASC');

        // Optional directory scoping passed via the field XML attribute.
        $directoryId = (int) $this->getAttribute('directory_id', 0);

        if ($directoryId > 0) {
            $query->where($db->quoteName('directory_id') . ' = ' . $directoryId);
        }

        $db->setQuery($query);
        $items = $db->loadObjectList();

        $options = [];

        // Allow choosing "no parent" (root of the directory tree).
        if ($this->getAttribute('allowroot', 'false') === 'true') {
            $options[] = HTMLHelper::_('select.option', 0, '— ' . Text::_('COM_MOVIELIST_CATEGORY_ROOT') . ' —');
        }

        foreach ($items as $item) {
            $prefix    = str_repeat('- ', max(0, (int) $item->level - 1));
            $options[] = HTMLHelper::_('select.option', $item->id, $prefix . $item->title);
        }

        return array_merge(parent::getOptions(), $options);
    }
}

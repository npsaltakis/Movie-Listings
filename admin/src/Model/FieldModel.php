<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Item model for a single custom Field definition.
 */
class FieldModel extends AdminModel
{
    public $typeAlias = 'com_movielist.field';

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_movielist.field',
            'field',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_movielist.edit.field.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Decode the JSON options column into the subform structure on load.
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item && !empty($item->options) && \is_string($item->options)) {
            $item->options = json_decode($item->options, true) ?: [];
        }

        if ($item && !empty($item->subfields) && \is_string($item->subfields)) {
            $item->subfields = json_decode($item->subfields, true) ?: [];
        }

        return $item;
    }

    /**
     * Encode the repeatable subform columns (options / subfields) back to JSON before saving.
     */
    public function save($data)
    {
        foreach (['options', 'subfields'] as $jsonCol) {
            if (isset($data[$jsonCol]) && \is_array($data[$jsonCol])) {
                $data[$jsonCol] = json_encode(array_values($data[$jsonCol]));
            }
        }

        return parent::save($data);
    }
}

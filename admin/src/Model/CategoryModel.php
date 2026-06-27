<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Item model for a single Category.
 */
class CategoryModel extends AdminModel
{
    public $typeAlias = 'com_movielist.category';

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_movielist.category',
            'category',
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
        $data = $app->getUserState('com_movielist.edit.category.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }
}

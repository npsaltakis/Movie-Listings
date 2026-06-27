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
 * Item model for a single Directory.
 */
class DirectoryModel extends AdminModel
{
    /**
     * @var  string
     */
    public $typeAlias = 'com_movielist.directory';

    /**
     * Get the form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data.
     *
     * @return  Form|boolean
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_movielist.directory',
            'directory',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Get data to be loaded into the form.
     *
     * @return  mixed
     */
    protected function loadFormData()
    {
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_movielist.edit.directory.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }
}

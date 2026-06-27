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
use Joomla\Database\ParameterType;
use Nickpsal\Component\Movielist\Administrator\Helper\FieldsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Item model for a single Movie.
 */
class MovieModel extends AdminModel
{
    public $typeAlias = 'com_movielist.movie';

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_movielist.movie',
            'movie',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Inject the directory-scoped custom fields once the form data (and thus the
     * directory_id) is known. preprocessForm runs after loadFormData, unlike getForm.
     *
     * @param   Form    $form   The form to be altered.
     * @param   mixed   $data   The associated data for the form.
     * @param   string  $group  The plugin group to process.
     *
     * @return  void
     */
    protected function preprocessForm(Form $form, $data, $group = 'content')
    {
        // Custom fields are global, so always inject them (under the com_fields group).
        FieldsHelper::addFieldsToForm($form);

        parent::preprocessForm($form, $data, $group);
    }

    protected function loadFormData()
    {
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_movielist.edit.movie.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Augment the loaded item with custom field values.
     *
     * @param   integer  $pk  The primary key.
     *
     * @return  \Joomla\CMS\Object\CMSObject|boolean
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item && !empty($item->id)) {
            $item->com_fields = FieldsHelper::getValues((int) $item->id);
            $item->gallery    = $this->getGallery((int) $item->id);
        }

        return $item;
    }

    /**
     * Load the gallery rows for a movie as subform-ready data.
     *
     * @param   integer  $movieId  The movie id.
     *
     * @return  array
     */
    protected function getGallery(int $movieId): array
    {
        if ($movieId <= 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['filename', 'caption', 'type']))
            ->from($db->quoteName('#__movielist_images'))
            ->where($db->quoteName('movie_id') . ' = :movie')
            ->order($db->quoteName('ordering') . ' ASC')
            ->bind(':movie', $movieId, ParameterType::INTEGER);
        $db->setQuery($query);

        $rows  = [];
        $index = 0;

        foreach ($db->loadObjectList() ?: [] as $row) {
            $rows['gallery' . $index++] = [
                'image'   => $row->filename,
                'caption' => $row->caption,
                'type'    => $row->type,
            ];
        }

        return $rows;
    }

    /**
     * Replace the gallery rows for a movie from submitted subform data.
     *
     * @param   integer  $movieId  The movie id.
     * @param   array    $rows     Submitted gallery rows.
     *
     * @return  void
     */
    protected function saveGallery(int $movieId, array $rows): void
    {
        if ($movieId <= 0) {
            return;
        }

        $db = $this->getDatabase();

        // Wipe existing rows, then re-insert in submitted order.
        $delete = $db->getQuery(true)
            ->delete($db->quoteName('#__movielist_images'))
            ->where($db->quoteName('movie_id') . ' = :movie')
            ->bind(':movie', $movieId, ParameterType::INTEGER);
        $db->setQuery($delete)->execute();

        $ordering = 0;

        foreach ($rows as $row) {
            $image = FieldsHelper::cleanImage(trim((string) ($row['image'] ?? '')));

            if ($image === '') {
                continue;
            }

            $record           = new \stdClass();
            $record->movie_id = $movieId;
            $record->filename = $image;
            $record->caption  = (string) ($row['caption'] ?? '');
            $record->type     = (string) ($row['type'] ?? 'still');
            $record->ordering = $ordering++;
            $record->state    = 1;

            $db->insertObject('#__movielist_images', $record);
        }
    }

    /**
     * Save the movie, then persist its custom field values.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean
     */
    public function save($data)
    {
        $customFields = $data['com_fields'] ?? [];
        $gallery      = $data['gallery'] ?? [];

        if (!parent::save($data)) {
            return false;
        }

        $movieId = (int) $this->getState($this->getName() . '.id');

        if ($movieId > 0 && \is_array($customFields)) {
            FieldsHelper::saveValues($movieId, $customFields);
        }

        if ($movieId > 0 && \is_array($gallery)) {
            $this->saveGallery($movieId, $gallery);
        }

        return true;
    }
}

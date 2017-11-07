<?php

namespace Drupal\word_link\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\Form;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\Entity;

/**
 * Class WordLinkSettingsForm.
 *
 * @ingroup word_link
 */
class WordLinkImportForm extends ConfigFormBase {

  const entity_type = 'word_link';

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'word_link.import_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'word_link.import_form',
    ];
  }

  /**
   * Defines the settings form for Word link entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('word_link.import_form');

    $form['file_upload'] = [
      '#type' => 'managed_file',
      '#title' => t('Import CSV File'),
      '#size' => 40,
      '#description' => t('Select the CSV file to be imported. '),
      '#required' => FALSE,
      '#autoupload' => TRUE,
      '#upload_validators' => array('file_validate_extensions' => array('csv'))
    ];


    $form['remove_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove all word links before import.'),
      '#default_value' => '',
    ];

    $form['additional_settings'] = [
      '#type' => 'details',
      '#fieldset' => 'advanced',
      '#title' => t('Additional settings'),
    ];

    $form['additional_settings']['delimiter'] = [
      '#type' => 'textfield',
      '#title' => t('Delimiter'),
      '#default_value' => $config->get('delimiter') ? $config->get('delimiter') : ';',
      '#required' => TRUE,
    ];

    $form['additional_settings']['enclosure'] = [
      '#type' => 'textfield',
      '#title' => t('Enclosure'),
      '#default_value' => $config->get('enclosure') ? $config->get('enclosure') : '"',
    ];

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Import');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('remove_all')) {
      if ($deleted = WordLinkImportForm::removeAll()) {
        drupal_set_message($this->t('Successfully deleted %count Word links entities.', ['%count' => $deleted]), 'warning');
      }
    }

    if ($file_upload = $form_state->getValue('file_upload')) {
      $file = File::load($file_upload[0]);
      $file->setPermanent();
      $file->save();

      $options = [
        'delimiter' => $form_state->getValue('delimiter'),
        'enclosure' => $form_state->getValue('enclosure'),
      ];
      if ($saved = WordLinkImportForm::importCSV($file, $options)) {
        drupal_set_message($this->t('Successfully imported %count Word links entities.', ['%count' => $saved]));
      }
    }
    // Redirect to word links list page.
    $form_state->setRedirect('entity.word_link.collection');
  }


  /**
   * To import data as Content type nodes.
   */

  public static function importCSV($file, $options = []) {
    // To get location of the csv file imported
    if (!$location = $file->uri->value) {
      return;
    }

    $saved = 0;
    if ("text/plain" == mime_content_type($location)) {
      if (($handle = fopen($location, "r")) !== FALSE) {
        $index = 0;
        while (($line = fgetcsv($handle, 4096, $options['delimiter'], $options['enclosure'])) !== FALSE) {
          $index++;
          if ($index == 1) {
            $fieldMap = array_flip($line);
            $entity_fields = WordLinkImportForm::getFields(self::entity_type);
          }
          elseif ($fieldMap) {
            $values = [
              'type' => self::entity_type,
              'status' => 1,
            ];
            foreach ($entity_fields as $field_name => $field) {
              if (isset($fieldMap[$field_name]) && FALSE !== $key = $fieldMap[$field_name]) {
                $values[$field_name] = $line[$key];
                // Set title
                if ($field_name == 'name') {
                  $values['title'] = $values[$field_name];
                }
                if ($field_name == 'except_list' && !empty($values[$field_name])) {
                  $values['visibility'] = 1;
                }
              }
            }

            if (isset($values['title'])) {
              $e = \Drupal::entityTypeManager()->getStorage(self::entity_type)->create($values);
              $e->save();
              $saved++;
            }

          }
        }
        fclose($handle);
      }
    }

    return $saved;
  }

  /**
   * Delete all word link entities.
   */
  protected static function removeAll() {
    $deleted = 0;
    while ($ids = \Drupal::entityQuery(self::entity_type)->range(0, 50)->execute()) {
      foreach ($ids as $id) {
        if ($entity = \Drupal::entityTypeManager()->getStorage(self::entity_type)->load($id)) {
          $entity->delete();
          $deleted++;
        };
      }
    }
    return $deleted;
  }

  /**
   * To get all Content Type Fields.
   */

  public static function getFields($contentType) {
    $fields = [];
    foreach (\Drupal::entityManager()->getFieldDefinitions($contentType, $contentType) as $field_name => $field_definition) {
      $fields[$field_definition->getName()] = [
        'type' => $field_definition->getType(),
        'setting' => $field_definition->getSettings()
      ];
    }
    return $fields;
  }
}

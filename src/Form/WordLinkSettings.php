<?php

namespace Drupal\word_link\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WordLinkSettings.
 */
class WordLinkSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'word_link.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'word_link_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Loading list Content types storage information.
    $content_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $options = [];
    // Get content Type information.
    foreach ($content_types as $contentType) {
      $options[$contentType->id()] = $contentType->label();
    }

    $config = $this->config('word_link.settings');
    $form['wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Choose content types in which words will be converted.'),
      '#fieldset' => 'wrapper',
    ];
    $form['wrapper']['word_link_content_types'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $config->get('word_link_content_types')? $config->get('word_link_content_types') : [],
    ];
    $form['word_link_highlight'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Highlight words.'),
      '#description' => $this->t('Highlight found words instead of replace it to links.'),
      '#default_value' => $config->get('word_link_highlight'),
    );
    $form['word_link_wrap_tag'] = array(
      '#type' => 'textfield',
      '#size' => 9,
      '#title' => $this->t('Wrap HTML tag'),
      '#description' => $this->t('Enter HTML tag which will be used to wrap word link. Be careful and enter only tag name (e.g. h1).'),
      '#default_value' =>$config->get('word_link_wrap_tag'),
    );
    $form['word_link_page_max_links'] = array(
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Max number links on the page'),
      '#description' => $this->t('Max number links on the page (including existance links also).'),
      '#default_value' =>$config->get('word_link_page_max_links'),
    );
    $form['word_link_tags_except'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Disallowed HTML tags'),
      '#description' => $this->t('A list of HTML tags that will be ignored. Never enter here tags that are not text. E.g. @tags.', array('@tags' => '<img>')),
      '#default_value' =>$config->get('word_link_tags_except'),
    );

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#fieldset' => 'advanced',
    ];
    $form['advanced']['word_link_reg_all'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Regular expression using to search all occuarance of the word'),
      '#description' => $this->t('The %s will replaced by searched words'),
      '#default_value' =>$config->get('word_link_reg_all'),
    );
    $form['advanced']['word_link_reg_words_only'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Regular expression using to search whole words only'),
      '#description' => $this->t('The %s will replaced by searched words'),
      '#default_value' =>$config->get('word_link_reg_words_only'),
    );

    return parent::buildForm($form, $form_state);
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
    parent::submitForm($form, $form_state);
    $settings = [
      'word_link_content_types',
      'word_link_tags_except',
      'word_link_boundary',
      'word_link_highlight',
      'word_link_wrap_tag',
      'word_link_reg_all',
      'word_link_reg_words_only',
      'word_link_page_max_links'
    ];
    foreach ($settings as $name) {
      $this->config('word_link.settings')
        ->set($name, $form_state->getValue($name))
        ->save();
    }

    // Clear cache;
    word_link_cache_clear();
  }

}

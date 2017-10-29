<?php

namespace Drupal\word_link\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Word link entity.
 *
 * @ingroup word_link
 *
 * @ContentEntityType(
 *   id = "word_link",
 *   label = @Translation("Word link"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\word_link\WordLinkListBuilder",
 *     "views_data" = "Drupal\word_link\Entity\WordLinkViewsData",
 *     "translation" = "Drupal\word_link\WordLinkTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\word_link\Form\WordLinkForm",
 *       "add" = "Drupal\word_link\Form\WordLinkForm",
 *       "edit" = "Drupal\word_link\Form\WordLinkForm",
 *       "delete" = "Drupal\word_link\Form\WordLinkDeleteForm",
 *     },
 *     "access" = "Drupal\word_link\WordLinkAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\word_link\WordLinkHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "word_link",
 *   data_table = "word_link_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer word link entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/word_link/{word_link}",
 *     "add-form" = "/admin/structure/word_link/add",
 *     "edit-form" = "/admin/structure/word_link/{word_link}/edit",
 *     "delete-form" = "/admin/structure/word_link/{word_link}/delete",
 *     "collection" = "/admin/structure/word_link",
 *   },
 *   field_ui_base_route = "word_link.settings"
 * )
 */
class WordLink extends ContentEntityBase implements WordLinkInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Word link entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Weight of the word. Lighter weights are higher up, heavier weights go down.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'type' => 'integer',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ]);

    $fields['url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL'))
      ->setDescription(t('The URL of the page to link to. External links must start with http:// or https:// and will be open in new window.'))
      ->setSettings([
        'max_length' => 90,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['url_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL Title'))
      ->setDescription(t('Title for the above URL. It will be embedded in the created link and appear as a tooltip when hovering the mouse over the link.'))
      ->setSettings([
        'max_length' => 90,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['class'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Class'))
      ->setDescription(t('Use this to add a class for the link. Default value is "word-link".'))
      ->setSettings([
        'max_length' => 90,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rel'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Rel attribute'))
      ->setDescription(t('Use this to add a rel attribute to the link.'))
      ->setSettings([
        'max_length' => 90,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['case_sensitive'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Case Sensitive'))
      ->setDescription(t('By default Word Link are case sensitive. Uncheck the checkbox if you want this particular Word Link to be case insensitive.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => 7,
      ]);

    $fields['whole_word_only'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Search whole words only'))
      //->setDescription(t('Search whole words only.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => 8,
      ]);

    $fields['page_max_links'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max word replacements'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'type' => 'integer',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ]);

     $fields['visibility'] = BaseFieldDefinition::create('boolean')
          ->setLabel(t('Show links on specific pages'))
          ->setDescription(t('By default Word Link will be replaced on all pages.'))
          ->setDefaultValue(FALSE)
          ->setDisplayOptions('view', [
            'type' => 'boolean',
            'weight' => 9,
          ])
          ->setDisplayOptions('form', [
            'settings' => ['display_label' => TRUE],
            'weight' => 9,
          ]);

    $fields['except_list'] = BaseFieldDefinition::create('string_long')
      ->setDescription(t('Specify pages by using their paths. Enter one path per line. E.g. node/1.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Word link is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'weight' => 11,
      ])
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => 11,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Word link entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE);

    return $fields;
  }

}

<?php

namespace Drupal\word_link;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Word link entities.
 *
 * @ingroup word_link
 */
class WordLinkListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'weight' => [
        'data' => $this->t('Weight'),
        'field' => 'weight',
        'sort' => 'ASC',
        'specifier' => 'weight',
      ],
      'name' => [
        'data' => $this->t('Name'),
        'field' => 'name',
        'specifier' => 'name',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'url' => [
        'data' => $this->t('URL'),
        'field' => 'url',
        'specifier' => 'url',
      ],
      'page_max_links' => [
        'data' => $this->t('Max replacements'),
        'field' => 'page_max_links',
        'specifier' => 'page_max_links',
      ],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\word_link\Entity\WordLink */
    //$row['id'] = $entity->id();
    $row['weight'] = $entity->weight->value;
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.word_link.edit_form',
      ['word_link' => $entity->id()]
    );
    $row['url'] = $entity->url->value;
    $row['page_max_links'] = ($entity->page_max_links->value)? $entity->page_max_links->value : '';
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->storage->getQuery();
    $entity_query->pager(20);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ids = $entity_query->execute();
    return $this->storage->loadMultiple($ids);
  }
}

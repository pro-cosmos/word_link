<?php

namespace Drupal\word_link\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Word link entities.
 */
class WordLinkViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}

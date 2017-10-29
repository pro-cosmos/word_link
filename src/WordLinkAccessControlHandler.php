<?php

namespace Drupal\word_link;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Word link entity.
 *
 * @see \Drupal\word_link\Entity\WordLink.
 */
class WordLinkAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\word_link\Entity\WordLinkInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished word link entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published word link entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit word link entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete word link entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add word link entities');
  }

}

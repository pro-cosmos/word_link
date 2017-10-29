<?php

namespace Drupal\word_link\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Word link entities.
 *
 * @ingroup word_link
 */
interface WordLinkInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Word link name.
   *
   * @return string
   *   Name of the Word link.
   */
  public function getName();

  /**
   * Sets the Word link name.
   *
   * @param string $name
   *   The Word link name.
   *
   * @return \Drupal\word_link\Entity\WordLinkInterface
   *   The called Word link entity.
   */
  public function setName($name);

  /**
   * Gets the Word link creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Word link.
   */
  public function getCreatedTime();

  /**
   * Sets the Word link creation timestamp.
   *
   * @param int $timestamp
   *   The Word link creation timestamp.
   *
   * @return \Drupal\word_link\Entity\WordLinkInterface
   *   The called Word link entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Word link published status indicator.
   *
   * Unpublished Word link are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Word link is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Word link.
   *
   * @param bool $published
   *   TRUE to set this Word link to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\word_link\Entity\WordLinkInterface
   *   The called Word link entity.
   */
  public function setPublished($published);

}

<?php

/**
 * @file
 * Contains word_link.page.inc.
 *
 * Page callback for Word link entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Word link templates.
 *
 * Default template: word_link.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_word_link(array &$variables) {
  // Fetch WordLink Entity Object.
  $word_link = $variables['elements']['#word_link'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

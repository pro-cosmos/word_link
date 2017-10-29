<?php

namespace Drupal\word_link;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\word_link\Entity\WordLink;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Entity;

/**
 * Class WordLinkReplacerService.
 */
class WordLinkReplacerService implements WordLinkReplacerServiceInterface {

  /**
   * Constructs a new WordLinkReplacerService object.
   */
  public function __construct() {

  }

  /**
   * Load the content and replace the matched strings with automatic links.
   */
  public function word_link_load_all() {
    $cache = &drupal_static(__FUNCTION__, []);
    if (empty($cache)) {
      $ids = \Drupal::entityQuery('word_link')
        ->condition('status', 1)
        ->sort('weight')
        ->execute();
      foreach (WordLink::loadMultiple($ids) as $row) {
        $cache[Unicode::strtolower($row->name->value)] = $row;
      };
    }

    return $cache;
  }


  /**
   * Find and convert defined word to link.
   *
   * @param string $entity
   *   Input $entity.
   * @param array $settings
   *   Array of filter settings.
   *
   * @return string
   *   String with converted words.
   */
  public function word_link_convert_text($entity, $settings) {
    global $base_url;

    $text = self::clean($entity->body->value);

    // Get current path. We need this to verify
    // if word will be converted on this page.
    $current_path = \Drupal::service('path.current')->getPath(); //current_path();

    // Get array of words.
    if ($words = $this->word_link_load_all()) {
      // Default HTML tag used in theme.
      $tag = 'a';
      // Get disallowed html tags and convert it for Xpath.
      if (!empty($settings->get('word_link_tags_except'))) {
        $disallowed = &drupal_static('word_link_disallowed_tags');
        if (!isset($disallowed)) {
          $disallowed_tags = preg_split('/\s+|<|>/', $settings->get('word_link_tags_except'), -1, PREG_SPLIT_NO_EMPTY);
          $disallowed = array();
          foreach ($disallowed_tags as $ancestor) {
            $disallowed[] = 'and not(ancestor::' . $ancestor . ')';
          }
          $disallowed = implode(' ', $disallowed);
        }
      }
      else {
        $disallowed = '';
      }

      // Create pattern.
      $patterns = &drupal_static('word_link_patterns');
      if (!isset($patterns)) {
        $path_alias = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
        $path = Unicode::strtolower($path_alias);

        foreach ($words as $word_key => $word) {
          if (empty($word->url->value)) {
            continue;
          }

          $url = str_replace($base_url . '/', '', $word->url->value);
          if (UrlHelper::isExternal($url)){
          $url = $word->url->value;
          }
          else {
           $url = '/' . ltrim($url, '/');
           $url = \Drupal::service('path.alias_manager')->getAliasByPath($url);
          }

          $match = FALSE;

          // Check if current path matches word except path.
          if (!empty($word->except_list->value)) {
            $match = \Drupal::service('path.matcher')->matchPath($path, $word->except_list->value);
            if ($path != $current_path) {
              $match = $match || \Drupal::service('path.matcher')->matchPath($current_path, $word->except_list->value);
            }
          }

          // Get visibility status and check if need to convert word on this page.
          $visibility = empty($word->except_list->value) || !isset($word->visibility->value) ? FALSE : $word->visibility->value;

          if ($url != $path && !$match && !$visibility || $url != $path && $visibility && $match) {
            // Replace duplicate backspaces.
            $word_find = preg_replace('/\s+/', ' ', trim($word->name->value));
            $word_find = preg_replace('/ /', '\\s+', preg_quote($word_find, '/'));

            if ($word->whole_word_only->value) {
              $regular = sprintf($settings->get('word_link_reg_words_only'), $word_find);
            }
            else {
              $regular = sprintf($settings->get('word_link_reg_all'), $word_find);
            }
            $regular = '/' . $regular . '/u';
            if (!$word->case_sensitive->value) {
              $regular .= 'i';
            }

            $patterns[] = $regular;
          }
        }
      }

      if ($patterns) {
        foreach ($patterns as $pattern) {
          $text = $this->word_link_convert_text_recursively($text, $pattern, $words, $disallowed, $settings, $tag);
        }
      }
    }

    return $text;
  }

  /**
   * Helper function for converting text.
   *
   * @param string $text
   *   Input text.
   * @param string $pattern
   *   Regular expression pattern.
   * @param array $words
   *   Array of all words.
   * @param string $disallowed
   *   Disallowed tags.
   * @param array $settings
   *   Array of filter settings.
   * @param string $tag
   *   Tag that will be used to replace word.
   */
  public function word_link_convert_text_recursively($text, $pattern, $words, $disallowed, $settings, $tag) {
    // Create DOM object.
    $dom = Html::load($text);
    $xpath = new \DOMXpath($dom);
    $page_links_count = $xpath->query("//a")->length;
    if ($settings->get('word_link_page_max_links') == 0 || $page_links_count < $settings->get('word_link_page_max_links')) {
      $text_nodes = $xpath->query('//text()[not(ancestor::a) ' . $disallowed . ']');
      $word_match_count = 0;
      foreach ($text_nodes as $original_node) {
        $text = $original_node->nodeValue;
        $match_count = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
        if ($match_count > 0) {
          $offset = 0;
          $parent = $original_node->parentNode;
          $next = $original_node->nextSibling;
          $parent->removeChild($original_node);

          foreach ($matches[0] as $delta => $match) {
            $match_text = $match[0];
            $match_pos = $match[1];
            $word = $words[Unicode::strtolower(trim($match_text))];
            $word_match_count++;

            if ($word) {
              $word_id = $word->id->value;
              $prefix = substr($text, $offset, $match_pos - $offset);
              $parent->insertBefore($dom->createTextNode($prefix), $next);

              $link = $dom->createDocumentFragment();

              $word_link_rendered = &drupal_static('word_link_rendered');
              // Get word link
              if (!isset($word_link_rendered[$word_id])) {
                if ($cache = \Drupal::cache()->get('word_link_rendered_' . $word_id)) {
                  $word_link_rendered[$word_id] = $cache->data;
                }
                else {
                  $url_external = UrlHelper::isExternal($word->url->value);
                  $target = $url_external ? '_blank' : '';

                  $url_options = array();
                  $url_path = NULL;

                  if ($url_external) {
                    $url_path = $word->url->value;
                  }
                  else {
                    $url_parts = parse_url($word->url->value);
                    $url_query = array();
                    if (isset($url_parts['query'])) {
                      parse_str($url_parts['query'], $url_query);
                    }
                    $url_options = array(
                      'query' => $url_query,
                      'fragment' => isset($url_parts['fragment']) ? $url_parts['fragment'] : '',
                    );

                    if (empty($url_parts['path'])) {
                      // Assuming that URL starts with #.
                      $url_options['external'] = TRUE;
                      $url_path = NULL;
                    }
                    else {
                      $url_path = $url_parts['path'];
                    }
                  }


                  $attributes = array_filter([
                    'href' => $word->url->value,
                    'title' => $word->url_title->value,
                    'class' => [$word->class->value],
                    'target' => $target,
                    'rel' => $word->rel->value
                  ]);
                  $attributes = [
                    'attributes' => $attributes
                  ];

                  $options = array_merge($url_options, $attributes);
                  if ($url_external) {
                    $url = Url::fromUri($url_path, $options);
                  }
                  else {
                    $url = Url::fromUserInput('/' . ltrim($url_path, '/'), $options);
                  }

                  $lnk = Link::fromTextAndUrl('{word}', $url);
                  $lnk_render = $lnk->toRenderable();
                  $word_link_rendered[$word_id] = \Drupal::service('renderer')->renderRoot($lnk_render);

                  if ($settings->get('word_link_highlight')) {
                    $html_tag = [
                      '#type' => 'html_tag',
                      '#tag' => 'b',
                      '#value' => '{word}',
                    ];
                    $html_tag = \Drupal::service('renderer')->renderRoot($html_tag);
                    $word_link_rendered[$word_id] = self::clean($html_tag);
                  }

                  if (!empty($settings->get('word_link_wrap_tag'))) {
                    $html_tag = [
                      '#type' => 'html_tag',
                      '#tag' => $settings->get('word_link_wrap_tag'),
                      '#value' => $word_link_rendered[$word_id],
                    ];
                    $html_tag = \Drupal::service('renderer')->renderRoot($html_tag);
                    $word_link_rendered[$word_id] = self::clean($html_tag);
                  }

                  \Drupal::cache()->set('word_link_rendered_' . $word_id, $word_link_rendered[$word_id], Cache::PERMANENT, ['word_link']);
                }
              }

              // Change word with corresponding link.
              $link->appendXML(str_replace('{word}', $match_text, $word_link_rendered[$word_id]));
              $page_links_count++;
              $parent->insertBefore($link, $next);
              $offset = $match_pos + strlen($match_text);
            }

            // Check maximum of one word links on the page.
            $exit = $word->page_max_links->value > 0 && $word->page_max_links->value == $word_match_count;
            // Check maximum of all word links on the page.
            if (!$exit){
              $exit = $page_links_count == $settings->get('word_link_page_max_links');
            }

            // If replaced last word match.
            if ($exit || $delta == $match_count - 1) {
              $suffix = substr($text, $offset);
              $parent->insertBefore($dom->createTextNode($suffix), $next);
              if ($exit){
                return Html::serialize($dom);
              }
            }

          }
        }
      }
    }

    return Html::serialize($dom);
  }

  /**
   * Remove symbols.
   * @param $str
   * @return mixed
   */
  public static function clean($str) {
    return str_replace(["\r", "\n"], '', $str);
  }
}
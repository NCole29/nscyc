<?php

namespace Drupal\tooltip_taxonomy\Services;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\TermInterface;

/**
 * Tooltip filter condition manager class.
 */
class TooltipManager {

  /**
   * Drupal Condition plugin for path.
   *
   * @var \Drupal\Core\Condition\ConditionPluginBase
   */
  protected $pathCondition;

  /**
   * Drupal condition plugin for content type.
   *
   * @var \Drupal\Core\Condition\ConditionPluginBase
   */
  protected $contentTypeCondition;

  /**
   * The field type manager.
   *
   * @var \Drupal\tooltip_taxonomy\Services\FieldTypeManager
   */
  protected $fieldTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * All conditions.
   *
   * @var array
   */
  protected $allConditions = [];

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs an TooltipConditionManager object.
   *
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   Plugin factory instance.
   * @param \Drupal\tooltip_taxonomy\Services\FieldTypeManager $field_type_manager
   *   Field type service instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entityTypeManager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Render service instance.
   */
  public function __construct(FactoryInterface $plugin_factory, FieldTypeManager $field_type_manager, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, RendererInterface $renderer) {
    $this->pathCondition = $plugin_factory->createInstance('request_path');
    $this->contentTypeCondition = $plugin_factory->createInstance('entity_bundle:node');
    $this->fieldTypeManager = $field_type_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->renderer = $renderer;
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * Check the path and content type.
   *
   * Return all conditions that match the restrictions.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The node being viewed.
   *
   * @return array
   *   All filter conditions matched.
   */
  public function checkPathAndContentType(ContentEntityBase $entity) {
    $all_con = $this->getAllFilterCondition();
    $match_cons = [];

    if (!empty($all_con)) {
      $this->allConditions = $all_con;
      // Check the path & content types.
      foreach ($all_con as $con) {
        $path_con = $con->get('path');
        if (!empty($path_con)) {
          $this->pathCondition->setConfiguration($path_con);
          if ($this->pathCondition->evaluate() ^ $this->pathCondition->isNegated()) {
            $content_type_con = $con->get('contentTypes');

            if (!empty($content_type_con) && $entity instanceof Node) {
              $this->contentTypeCondition->setConfiguration($content_type_con)
                ->setContextValue('node', $entity);
              if ($this->contentTypeCondition->evaluate()) {
                $match_cons[] = $con;
              }
            }
            else {
              // Either this condition apply to all content types
              // or the entity is not a Node.
              $match_cons[] = $con;
            }
          }
        }
      }
    }

    return $match_cons;
  }

  /**
   * Get all tooltip filter conditions.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   All filter conditions.
   */
  protected function getAllFilterCondition() {
    // Load the next release node.
    $cids = $this->entityTypeManager->getStorage('filter_condition')->getQuery()
      // ->exists('vids')
      ->accessCheck(TRUE)
      ->sort('weight')
      ->execute();

    if (empty($cids)) {
      return [];
    }

    // Load all nodes matched the conditions.
    return $this->entityTypeManager->getStorage('filter_condition')->loadMultiple($cids);
  }

  /**
   * Attach tooltip into a field text.
   *
   * @param string $view_mode
   *   View mode of the field.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity type.
   * @param string $field_name
   *   The field machine name.
   * @param array $field_value
   *   The field value.
   * @param array $tag
   *   Cache tags.
   *
   * @return string
   *   New text with tooltip markup.
   */
  public function addTooltip(string $view_mode, EntityInterface $entity, string $field_name, array $field_value, array &$tag) {
    $match_cons = $this->checkPathAndContentType($entity);
    // Taxonomy terms replacement array.
    $pattern_map = [];
    // Search keywords.
    $pattern_map['search'] = [];
    // Replace array.
    $pattern_map['replace'] = [];
    // Tags in which replacements should be skipped.
    $excluded_tags = [];

    foreach ($match_cons as $con) {
      $allowed_views = $con->get('view');
      // Text formats this condition applied to.
      $formats = $con->get('formats');
      // Only generate tooltip for certain text formats.
      if (!in_array($field_value['#format'], $formats)) {
        continue;
      }

      if (!empty($allowed_views)) {
        $not_all = FALSE;
        foreach ($allowed_views as $mode) {
          if ($mode !== '0') {
            $not_all = TRUE;
            break;
          }
        }

        if ($not_all && !in_array($view_mode, $allowed_views)) {
          // The current view mode doesn't match this condition.
          continue;
        }
      }

      $allowed_fields = $con->get('field');
      // Is there any selected field within this condition?
      if (!empty($allowed_fields)) {
        $field_key = $entity->getEntityTypeId() . '-' . $field_name;
        // Check if the field is a selected field in this condition.
        if (in_array($field_key, $allowed_fields)) {
          $allowed_html_tags = $con->get('allowed_html_tags');
          // Add the taxonomy terms from this condition into the pattern array.
          $this->addVocabularyReplacement($con->get('vids'), $pattern_map, $allowed_html_tags);
          // Merge excluded tags.
          $ex = $con->get('excluded_tags');
          if (!empty($ex)) {
            $parts = preg_split('/[\s,]+/', $ex);
            $parts = array_map(function ($t) {
              return strtolower(trim(trim($t), "<>/"));
            }, array_filter($parts));
            if (!empty($parts)) {
              foreach ($parts as $p) {
                $excluded_tags[] = $p;
              }
            }
          }
          // Cache tag.
          $tag[] = 'tooltip_taxonomy:' . $con->id();
        }
      }
      // No selected fields, all field applied.
      else {
        $allowed_html_tags = $con->get('allowed_html_tags');
        // Add the taxonomy terms from this condition into the pattern array.
        $this->addVocabularyReplacement($con->get('vids'), $pattern_map, $allowed_html_tags);
        // Merge excluded tags.
        $ex = $con->get('excluded_tags');
        if (!empty($ex)) {
          $parts = preg_split('/[\s,]+/', $ex);
          $parts = array_map(function ($t) {
            return strtolower(trim(trim($t), "<>/"));
          }, array_filter($parts));
          if (!empty($parts)) {
            foreach ($parts as $p) {
              $excluded_tags[] = $p;
            }
          }
        }
        // Cache tag.
        $tag[] = 'tooltip_taxonomy:' . $con->id();
      }
    }
    // Replace the taxonomy terms with tooltip markup.
    $pattern_check = $pattern_map['search'];
    foreach ($pattern_check as $k => $check) {
      if (!preg_match($check, $field_value['#text'])) {
        unset($pattern_check[$k]);
      }
    }
    if (count($pattern_check) > 0) {
      // Patch to remove tooltip if already exist in other MATCHING tooltips.
      foreach ($pattern_check as $small_key => $small) {
        foreach ($pattern_check as $big_key => $big) {
          if ($small_key == $big_key) {
            continue;
          }
          $big = preg_replace('(^\/\\\b|\\\b\/$)', '', $big);
          // Remove tooltip if duplicated.
          if (preg_match($small, $big) === 1) {
            unset($pattern_map['search'][$small_key]);
            unset($pattern_map['replace'][$small_key]);
          }
        }
      }
      if (!empty($excluded_tags)) {
        $excluded_tags = array_values(array_unique($excluded_tags));
      }
      $result = $this->replaceContent($field_value['#text'], $pattern_map, $excluded_tags);
    }
    else {
      return '';
    }

    return $result;
  }

  /**
   * Check if an vocabulary is used for tooltip.
   *
   * @param string $vid
   *   Vocabulary ID.
   *
   * @return array
   *   All conditions' ID in which the Vocabulary
   *   is used for tooltip.
   */
  public function hasTooltip(string $vid) {
    $all_cons = $this->getAllFilterCondition();
    $result = [];

    foreach ($all_cons as $con) {
      if (in_array($vid, $con->get('vids'))) {
        $result[] = $con->id();
      }
    }

    return $result;
  }

  /**
   * Add new taxonomy term pattern into the replacement array,.
   *
   * That will be used to replace the field text with tooltip markup.
   *
   * @param array $vids
   *   Vocabulary array.
   * @param array $pattern_map
   *   Replacement pattern array.
   * @param string $allowed_html_tags
   *   List of allowed HTML tags.
   *
   * @return int
   *   The number of taxonomy terms added.
   */
  protected function addVocabularyReplacement(array $vids, array &$pattern_map, $allowed_html_tags) {
    $count = 0;

    if (empty($vids)) {
      return 0;
    }

    if (empty($allowed_html_tags)) {
      $allowed_html_tags = '<b><i><strong><span><br><a>';
    }
    else {
      $allowed_html_tags = str_replace(' ', '', $allowed_html_tags);
    }

    foreach ($vids as $vid) {
      $term_ids = $this->loadAllTaxonomyTermIds($vid);
      foreach ($term_ids as $tid) {
        $term = $this->termStorage->load($tid);
        if (!$term instanceof TermInterface || !$term->access('view')) {
          continue;
        }

        /** @var \Drupal\taxonomy\TermInterface $term */
        $term = $this->entityRepository->getTranslationFromContext($term);

        // Use Drupal's XSS filter for robust sanitization.
        $allowed_tags_array = preg_split('/[<>]+/', $allowed_html_tags, -1, PREG_SPLIT_NO_EMPTY);
        $des = Xss::filter($term->get('description')->value, $allowed_tags_array);
        if (empty($des)) {
          continue;
        }
        $tooltip_render = [
          '#theme' => 'tooltip_taxonomy',
          '#tooltip_id' => $term->bundle() . '-' . $term->id(),
          '#term_name' => $term->getName(),
          '#description' => $des,
        ];
        $name_pattern = '/\b' . preg_quote($term->getName(), '/') . '\b/';
        // Check if there is a same term name.
        $name_key = array_search($name_pattern, $pattern_map['search']);
        if ($name_key === FALSE) {
          // New term.
          $pattern_map['search'][] = $name_pattern;
          $pattern_map['replace'][] = $this->renderer->renderInIsolation($tooltip_render);
        }
        else {
          // Overwrite existing term.
          // Conditions that has bigger weight value
          // will overwrite the same term name.
          $pattern_map['replace'][$name_key] = $this->renderer->renderInIsolation($tooltip_render);
        }
        $count++;
      }
    }

    return $count;
  }

  /**
   * Load all taxonomy terms of a vocabulary.
   *
   * @param string $vid
   *   Vocabulary ID.
   *
   * @return array
   *   All entities in the entity bundle.
   */
  protected function loadAllTaxonomyTermIds(string $vid) {
    return $this->termStorage->getQuery()
      ->accessCheck()
      ->condition('vid', $vid)
      ->execute();
  }

  /**
   * The replaceContent function.
   *
   * Replace taxonomy terms with tooltip for html content.
   * Avoid html markup tag and the attributes from being modified.
   *
   * @param string $html
   *   The original html markup.
   * @param array $pattern_map
   *   Replace patterns.
   * @param array $excluded_tags
   *   Lowercase HTML tag names in which replacements should be skipped.
   *
   * @return string
   *   The new html markup with tooltip.
   */
  protected function replaceContent(string $html, array $pattern_map, array $excluded_tags = []) {
    $content_start = 0;
    $tag_begin = strpos($html, '<');
    $length = strlen($html);

    // Build a list of parts where:
    // - ['t', string] is a text segment where replacements are allowed.
    // - ['g', string] is a single HTML tag markup copied as-is.
    // - ['x', string] is a protected excluded block copied as-is.
    $parts = [];

    // Normalize excluded tags to lowercase for comparison.
    $excluded_lookup = [];
    foreach ($excluded_tags as $t) {
      $t = strtolower(trim($t));
      if ($t !== '') {
        $excluded_lookup[$t] = TRUE;
      }
    }
    $void_tags = [
      'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    while ($tag_begin !== FALSE) {
      $tag_end = strpos($html, '>', $tag_begin);
      if ($tag_end === FALSE) {
        // Invalid HTML markup: return the original HTML.
        return $html;
      }
      // The end '>' should be included in.
      $tag_end++;

      if ($content_start < $tag_begin) {
        // Content before the tag.
        $parts[] = ['t', substr($html, $content_start, $tag_begin - $content_start)];
      }

      // Determine if this tag opens an excluded block we should protect.
      $tag_markup = substr($html, $tag_begin, $tag_end - $tag_begin);
      $handled = FALSE;
      if (preg_match('/^<\s*([a-zA-Z0-9]+)/', $tag_markup, $m)) {
        $name = strtolower($m[1]);
        $self_closing = (substr(trim($tag_markup), -2) === '/>') || in_array($name, $void_tags, TRUE);
        if (!$self_closing && isset($excluded_lookup[$name])) {
          // Skip until the matching closing tag of the same name
          // (handle nesting of the same tag).
          $depth = 1;
          $scan_pos = $tag_end;
          while ($depth > 0) {
            $next_lt = strpos($html, '<', $scan_pos);
            if ($next_lt === FALSE) {
              // Malformed HTML; protect the rest and finish.
              $parts[] = ['x', substr($html, $tag_begin)];
              return $this->reconstructWithReplace($parts, $pattern_map);
            }
            $next_gt = strpos($html, '>', $next_lt);
            if ($next_gt === FALSE) {
              $parts[] = ['x', substr($html, $tag_begin)];
              return $this->reconstructWithReplace($parts, $pattern_map);
            }
            $next_gt++;
            $inner_tag = substr($html, $next_lt, $next_gt - $next_lt);
            if (preg_match('/^<\s*\/\s*([a-zA-Z0-9]+)/', $inner_tag, $cm)) {
              if (strtolower($cm[1]) === $name) {
                $depth--;
              }
            }
            elseif (preg_match('/^<\s*([a-zA-Z0-9]+)/', $inner_tag, $om)) {
              $tname = strtolower($om[1]);
              $inner_self = (substr(trim($inner_tag), -2) === '/>') || in_array($tname, $void_tags, TRUE);
              if ($tname === $name && !$inner_self) {
                $depth++;
              }
            }
            $scan_pos = $next_gt;
          }
          $block_end = $scan_pos;
          $parts[] = ['x', substr($html, $tag_begin, $block_end - $tag_begin)];
          $content_start = $block_end;
          $tag_begin = strpos($html, '<', $block_end);
          $handled = TRUE;
        }
      }

      if (!$handled) {
        // Not an excluded opening tag: store this tag only.
        $parts[] = ['g', $tag_markup];
        $content_start = $tag_end;
        $tag_begin = strpos($html, '<', $tag_end);
      }
    }

    if ($content_start < $length) {
      // Content after the last tag.
      $parts[] = ['t', substr($html, $content_start, $length - $content_start)];
    }

    return $this->reconstructWithReplace($parts, $pattern_map);
  }

  /**
   * Reconstruct HTML from parts, applying replacements only to text parts.
   *
   * @param array $parts
   *   Array of ['t'|'g'|'x', string] parts.
   * @param array $pattern_map
   *   Pattern map with 'search' and 'replace'.
   *
   * @return string
   *   The reconstructed HTML.
   */
  private function reconstructWithReplace(array $parts, array $pattern_map): string {
    $out = '';
    foreach ($parts as $part) {
      [$type, $chunk] = $part;
      if ($type === 't') {
        $out .= preg_replace($pattern_map['search'], $pattern_map['replace'], $chunk);
      }
      else {
        // 'g' (tag) and 'x' (excluded block) are copied as-is.
        $out .= $chunk;
      }
    }
    return $out;
  }

}

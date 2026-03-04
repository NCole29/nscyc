<?php

namespace Drupal\tooltip_taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tooltip taxonomy' formatter.
 *
 * @FieldFormatter(
 *   id = "tooltip_taxonomy",
 *   label = @Translation("Tooltip Taxonomy"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TooltipTaxonomyFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getSetting('target_type') === 'taxonomy_term';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'allowed_html_tags' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    $element['allowed_html_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed HTML Tags'),
      '#default_value' => $this->getSetting('allowed_html_tags') ? $this->getSetting('allowed_html_tags') : '<b><i><strong><span><br><a>',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $args = [
      '@allowed_html_tags' => $this->getSetting('allowed_html_tags'),
    ];

    $summary[] = $this->t('Allowed HTML: @allowed_html_tags', $args);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if ($entity->id()) {
        $allowed_html_tags = $this->getSetting('allowed_html_tags');

        if (empty($allowed_html_tags)) {
          $allowed_html_tags = '<b><i><strong><span><br><a>';
        }

        $text = $entity->getDescription();
        if ($text) {
          $text = strip_tags($text, $allowed_html_tags);
        }

        $elements[$delta] = [
          '#theme' => 'tooltip_taxonomy',
          '#tooltip_id' => $entity->getRevisionId() . '-' . $entity->id(),
          '#term_name' => $entity->getName(),
          '#description' => $text,
        ];
      }
    }

    return $elements;
  }

}

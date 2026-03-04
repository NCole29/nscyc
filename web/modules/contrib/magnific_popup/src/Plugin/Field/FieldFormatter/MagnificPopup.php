<?php

namespace Drupal\magnific_popup\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Magnific Popup field formatter.
 *
 * @FieldFormatter(
 *   id = "magnific_popup",
 *   label = @Translation("Magnific Popup"),
 *   field_types = {
 *    "image"
 *   }
 * )
 */
class MagnificPopup extends ImageFormatterBase {

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'thumbnail_image_style' => '',
      'popup_image_style' => '',
      'gallery_type' => 'all_items',
      'vertical_fit' => 'true',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $image_styles = image_style_options(FALSE);

    $form['thumbnail_image_style'] = [
      '#title' => $this->t('Thumbnail Image Style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('thumbnail_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
    ];

    $form['popup_image_style'] = [
      '#title' => $this->t('Popup Image Style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('popup_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
    ];

    $form['gallery_type'] = [
      '#title' => $this->t('Gallery Type'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('gallery_type'),
      '#options' => $this->getGalleryTypes(),
    ];

    $form['vertical_fit'] = [
      '#title' => $this->t('Vertical Fit'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('vertical_fit'),
      '#options' => $this->getVerticalFit(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $image_styles = image_style_options(FALSE);
    $thumb_image_style = $this->getSetting('thumbnail_image_style');
    $popup_image_style = $this->getSetting('popup_image_style');
    // Check image styles exist or display 'Original Image'.
    $summary[] = $this->t('Thumbnail image style: @thumb_style. Popup image style: @popup_style', [
      '@thumb_style' => isset($image_styles[$thumb_image_style]) ? $thumb_image_style : 'Original Image',
      '@popup_style' => isset($image_styles[$popup_image_style]) ? $popup_image_style : 'Original Image',
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $thumb_image_style = $this->getSetting('thumbnail_image_style');
    $popup_image_style = $this->getSetting('popup_image_style');
    $gallery_type = $this->getSetting('gallery_type');
    $files = $this->getEntitiesToView($items, $langcode);

    foreach ($files as $delta => $file) {
      $image_uri = $file->getFileUri();
      $popup_image_path = !empty($popup_image_style) ? $this->entityTypeManager->getStorage('image_style')->load($popup_image_style)->buildUrl($image_uri) : $image_uri;
      $url = $this->fileUrlGenerator->generate($popup_image_path);
      $item = $file->_referringItem;
      $item_attributes = $file->_attributes;
      unset($file->_attributes);

      $item_attributes['class'][] = 'mfp-thumbnail';

      if ($gallery_type === 'first_item' && $delta > 0) {
        $item_attributes['class'][] = 'visually-hidden';
      }

      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $thumb_image_style,
        '#url' => $url,
        '#attached' => [
          'library' => [
            'magnific_popup/magnific_popup',
          ],
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL): array {
    $elements = parent::view($items, $langcode);
    $gallery_type = $this->getSetting('gallery_type');
    $vertical_fit = $this->getSetting('vertical_fit');

    $elements['#attributes']['class'][] = 'mfp-field';
    $elements['#attributes']['class'][] = 'mfp-' . Html::cleanCssIdentifier($gallery_type);
    $elements['#attributes']['data-vertical-fit'][] = "$vertical_fit";
    return $elements;
  }

  /**
   * Get an array of gallery types.
   *
   * @return array
   *   An array of gallery types for use in display settings.
   */
  protected function getGalleryTypes(): array {
    return [
      'all_items' => $this->t('Gallery: All Items Displayed'),
      'first_item' => $this->t('Gallery: First Item Displayed'),
      'separate_items' => $this->t('No Gallery: Display Each Item Separately'),
    ];
  }

  /**
   * Get an array of vertical fit values.
   *
   * @return array
   *   An array of values settings.
   */
  protected function getVerticalFit(): array {
    return [
      'true' => 'Fit image vertically (suitable for most images)',
      'false' => 'Fit image horizontally (suitable for very tall images)',
    ];
  }

}

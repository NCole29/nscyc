<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor_accordion\Kernel\CKEditor4To5Upgrade;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Kernel\SmartDefaultSettingsTest;

/**
 * @covers \Drupal\ckeditor_accordion\Plugin\CKEditor4To5Upgrade\Accordion
 * @group ckeditor_accordion
 * @group ckeditor5
 * @requires module ckeditor5
 * @internal
 */
class UpgradePathTest extends SmartDefaultSettingsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor',
    'ckeditor_test',
    'ckeditor5',
    'editor',
    'filter',
    // Test ckeditor_accordion.
    'ckeditor_accordion',
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3263384
    'ckeditor5_plugin_conditions_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'ckeditor_accordion_format',
      'name' => 'CKEditor Accordion test format',
    ])->save();
    Editor::create([
      'format' => 'ckeditor_accordion_format',
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'CKEditor Accordion toolbar',
                'items' => [
                  // @see https://www.drupal.org/project/codetag
                  'Accordion',
                ],
              ],
            ],
          ],
        ],
        'plugins' => [],
      ],
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function provider() {
    yield "ckeditor_accordion can be switched to CKEditor 5 without problems" => [
      'format_id' => 'ckeditor_accordion_format',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            'Accordion',
          ],
        ],
        'plugins' => [],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_messages' => [],
    ];
  }

}

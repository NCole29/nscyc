<?php

namespace Drupal\theme_switcher\Tests\Functional\Entity;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Describes tests of functionality for theme_switcher_rule entities.
 *
 * @group theme_switcher
 */
class ThemeSwitcherRuleEntityTest extends BrowserTestBase {

  use NodeCreationTrait;
  use TaxonomyTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'theme_switcher',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A privileged administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $administrativeUser;

  /**
   * A test node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $contentType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $theme_installer->install(['claro', 'olivero', 'stable9']);

    $this->administrativeUser = $this->createUser([
      'access content',
      'access administration pages',
      'administer theme switcher rules',
      'administer content types',
      'administer nodes',
      'administer taxonomy',
      'create theme switcher rules',
    ]);

    $this->contentType = $this->drupalCreateContentType();
  }

  /**
   * Tests creating, updating and deleting a theme switcher rule.
   */
  public function testCreateDelete(): void {
    $this->drupalLogin($this->administrativeUser);

    $this->drupalGet('/admin/config/system/theme_switcher');
    $this->assertSession()
      ->pageTextContains('There are no theme switcher rule entities yet.');

    // Creates the entity.
    $this->drupalGet('/admin/config/system/theme_switcher/add');
    $label = $this->randomString();
    $id = $this->randomMachineName();
    $edit = [
      'label' => $label,
      'id' => $id,
      'status' => '1',
      'theme' => 'olivero',
      'admin_theme' => 'olivero',
      'conjunction' => 'and',
      'visibility[response_status][status_codes][200]' => '200',
      'visibility[response_status][negate]' => '0',
      'visibility[entity_bundle:node][bundles][' . $this->contentType->id() . ']' => $this->contentType->id(),
      'visibility[entity_bundle:node][negate]' => '0',
    ];

    $this->submitForm($edit, 'Save');
    $this->assertSession()
      ->pageTextContainsOnce(sprintf("The Theme Switcher Rule '%s' has been created", $label));
    $this->assertSession()
      ->pageTextContains($id);
    $this->assertSession()
      ->pageTextContains('olivero');

    // Deletes the entity.
    $this->drupalGet('/admin/config/system/theme_switcher/delete/' . $id);
    $this->submitForm([], 'Delete');

    $this->assertSession()
      ->pageTextContainsOnce(sprintf("The Theme Switcher Rule '%s' has been deleted.", $label));
    $this->assertSession()
      ->pageTextContains('There are no theme switcher rule entities yet.');
  }

}

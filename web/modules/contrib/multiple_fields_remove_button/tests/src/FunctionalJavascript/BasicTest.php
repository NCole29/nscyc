<?php

namespace Drupal\Tests\multiple_fields_remove_button\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests the Multiple Fields Remove Button module.
 *
 * @group multiple_fields_remove_button
 */
class BasicTest extends WebDriverTestBase {
  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'taxonomy', 'field_ui', 'multiple_fields_remove_button'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createContentType(['type' => 'article']);
    $this->createEntityReferenceField('node', 'article', 'entity_reference_test', 'Entity Reference test', 'node', 'default', ['target_bundles' => ['article']], 2);
    EntityFormDisplay::load('node.article.default')
      ->setComponent('entity_reference_test', ['type' => 'entity_reference_autocomplete'])
      ->save();
    EntityViewDisplay::load('node.article.default')
      ->setComponent('entity_reference_test', ['type' => 'entity_reference_label'])
      ->save();
  }

  /**
   * Tests the normal operation of the module.
   */
  public function testHappyPath(): void {
    // Login as an admin user to set up the module.
    $this->drupalLogin($this->createUser([], 'site admin', TRUE));
    $this->drupalGet('node/add/article');
    $this->assertSession()->fieldExists('title[0][value]')->setValue('Node one');
    $this->assertSession()->buttonExists('Save')->press();
    $this->assertSession()->pageTextContains('article Node one has been created.');
    $this->drupalGet('node/add/article');
    $this->assertSession()->fieldExists('title[0][value]')->setValue('Node two');
    $this->assertSession()->buttonExists('Save')->press();
    $this->assertSession()->pageTextContains('article Node two has been created.');

    $this->drupalGet('node/add/article');
    $this->assertSession()->fieldExists('title[0][value]')->setValue('Node three');

    $this->assertSession()->fieldExists('entity_reference_test[0][target_id]')->setValue('Node one (1)');
    $this->assertSession()->fieldExists('entity_reference_test[1][target_id]')->setValue('Node two (2)');
    $this->assertSession()->buttonExists('Save')->press();
    $this->assertSession()->pageTextContains('article Node three has been created.');
    $this->assertSession()->linkExists('Node one');
    $this->assertSession()->linkExists('Node two');

    $this->drupalGet('node/3/edit');
    $this->assertSession()->buttonExists('entity_reference_test_und_0_remove_button');
    $this->assertSession()->buttonExists('entity_reference_test_und_1_remove_button')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->buttonExists('Save')->press();
    $this->assertSession()->pageTextContains('article Node three has been updated.');
    $this->assertSession()->linkExists('Node one');
    $this->assertSession()->linkNotExists('Node two');

    // Ensure the module can be uninstalled without everything breaking.
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->fieldExists('uninstall[multiple_fields_remove_button]')->check();
    $this->submitForm([], 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->fieldNotExists('uninstall[multiple_fields_remove_button]');

    $this->drupalGet('node/3/edit');
    $this->assertSession()->buttonNotExists('entity_reference_test_und_0_remove_button');

    // Install the module again to ensure it can be.
    $this->drupalGet('admin/modules');
    $this->assertSession()->fieldExists('modules[multiple_fields_remove_button][enable]')->check();
    $this->submitForm([], 'Install');
    $this->assertSession()->pageTextContains('Module Multiple Fields Remove Button has been enabled.');

    $this->drupalGet('node/3/edit');
    $this->assertSession()->buttonExists('entity_reference_test_und_0_remove_button');
  }

}

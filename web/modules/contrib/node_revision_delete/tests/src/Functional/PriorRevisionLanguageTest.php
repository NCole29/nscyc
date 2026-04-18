<?php

declare(strict_types=1);

namespace Drupal\Tests\node_revision_delete\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests prior revision display when admin language negotiation is active.
 */
#[Group('node_revision_delete')]
#[RunTestsInSeparateProcesses]
class PriorRevisionLanguageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'node',
    'node_revision_delete',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests prior revisions are shown when admin language differs from content.
   */
  public function testPriorRevisionsShownWithAdminLanguage(): void {
    // Add a second language.
    ConfigurableLanguage::createFromLangcode('nl')->save();

    // Create a content type with revisions.
    $this->drupalCreateContentType(['type' => 'page', 'revision' => TRUE]);

    // Create an admin user who can manage languages and delete revisions.
    $admin = $this->drupalCreateUser([
      'access administration pages',
      'administer languages',
      'administer nodes',
      'delete all revisions',
      'view all revisions',
      'edit any page content',
    ]);
    $this->drupalLogin($admin);

    // Enable "Account administration pages" language detection for the
    // interface language, positioned above the URL method.
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm([
      'language_interface[enabled][language-user-admin]' => TRUE,
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[weight][language-user-admin]' => -12,
      'language_interface[weight][language-url]' => -10,
    ], 'Save settings');

    // Enable "Use admin theme for content editing" so that node operation
    // routes (including revision delete) are marked as admin routes.
    $this->config('node.settings')->set('use_admin_theme', TRUE)->save();
    // Rebuild the router so the _admin_route option takes effect.
    $this->rebuildAll();

    // Set the user's preferred admin language to Dutch.
    $this->drupalGet('user/' . $admin->id() . '/edit');
    $this->submitForm([
      'preferred_admin_langcode' => 'nl',
    ], 'Save');

    // Create an English node with 5 revisions.
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'English node',
      'langcode' => 'en',
    ]);
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    for ($i = 2; $i <= 5; $i++) {
      $node->setTitle('English node v' . $i);
      $node = $node_storage->createRevision($node);
      $node->save();
    }

    // We have VIDs 1-5. Delete a non-active revision (the 4th) which has 3
    // prior revisions (VIDs 1-3).
    $all_vids = array_keys(
      $node_storage->getQuery()
        ->allRevisions()
        ->condition('nid', $node->id())
        ->sort('vid', 'ASC')
        ->accessCheck(FALSE)
        ->execute()
    );
    // Target the second-to-last revision.
    $target_vid = $all_vids[3];

    // Visit the revision delete confirmation page.
    $this->drupalGet("node/{$node->id()}/revisions/{$target_vid}/delete");
    $this->assertSession()->statusCodeEquals(200);

    // The "Delete prior revisions" details box must be visible.
    $this->assertSession()->pageTextContains('Delete prior revisions');
    $this->assertSession()->fieldExists('delete_prior_revisions');
  }

}

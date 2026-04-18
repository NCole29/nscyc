<?php

declare(strict_types=1);

namespace Drupal\Tests\node_revision_delete\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the nrd:delete-prior-revisions Drush command.
 *
 * @covers \Drupal\node_revision_delete\Commands\PriorRevisions
 */
#[Group('node_revision_delete')]
#[RunTestsInSeparateProcesses]
class DrushPriorRevisionsTest extends BrowserTestBase {

  use DrushTestTrait;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'page', 'revision' => TRUE]);
  }

  /**
   * Tests deleting prior revisions with the Drush command.
   */
  public function testDeletePriorRevisions(): void {
    // Create a node with 5 revisions (VIDs 1-5).
    $node = $this->drupalCreateNode(['type' => 'page', 'title' => 'Test v1']);
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    for ($i = 2; $i <= 5; $i++) {
      $node->setTitle('Test v' . $i);
      $node = $node_storage->createRevision($node);
      $node->save();
    }
    $nid = (int) $node->id();

    $all_vids = $this->getRevisionIds($nid);
    $this->assertCount(5, $all_vids);

    // With --no-interaction (always set by DrushTestTrait), confirm() defaults
    // to TRUE. Both confirms will be accepted: prior revisions are deleted AND
    // the target revision itself. So VIDs 1-4 will all be deleted.
    $this->drush('nrd:delete-prior-revisions', [$nid, $all_vids[3]]);

    // Only the latest revision (VID 5) should remain.
    $remaining = $this->getRevisionIds($nid);
    $this->assertCount(1, $remaining);
    $this->assertContains($all_vids[4], $remaining);
  }

  /**
   * Tests the commands with invalid IDs.
   */
  public function testInvalidIds(): void {
    $this->drush('nrd:delete-prior-revisions', [9999, 1], expected_return: 1);
    $this->assertStringContainsString('9999 is not a valid node id', $this->getOutputRaw());

    $node = $this->drupalCreateNode(['type' => 'page']);
    $this->drush('nrd:delete-prior-revisions', [$node->id(), 9999], expected_return: 1);
    $this->assertStringContainsString('9999 is not a valid revision id', $this->getOutputRaw());

    // The only revision is VID 1, there are no prior revisions.
    $this->drush('nrd:delete-prior-revisions', [$node->id(), $node->getRevisionId()]);
    $this->assertStringContainsString('No prior revision(s) found to delete', $this->getOutputRaw());
  }

  /**
   * Tests the --langcode option filters revisions by language.
   */
  public function testLangcodeOption(): void {
    ConfigurableLanguage::createFromLangcode('nl')->save();

    // Create an English node with 3 revisions.
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'EN v1',
      'langcode' => 'en',
    ]);
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    for ($i = 2; $i <= 3; $i++) {
      $node->setTitle('EN v' . $i);
      $node = $node_storage->createRevision($node);
      $node->save();
    }

    // Add a Dutch translation with 3 revisions.
    $translation = $node->addTranslation('nl', ['title' => 'NL v1']);
    $new_revision = $node_storage->createRevision($translation);
    $new_revision->save();
    for ($i = 2; $i <= 3; $i++) {
      $translation = $node_storage->load($node->id())->getTranslation('nl');
      $translation->setTitle('NL v' . $i);
      $new_revision = $node_storage->createRevision($translation);
      $new_revision->save();
    }

    $nid = (int) $node->id();
    $all_vids = $this->getRevisionIds($nid);
    // 3 EN revisions + 3 NL revisions = 6 total.
    $this->assertCount(6, $all_vids);

    // Delete only Dutch prior revisions before the latest VID.
    // With --no-interaction both confirms default to TRUE, so the target
    // revision itself would also be deleted. But the target VID is the latest
    // and is also English-affected, so we need to be careful. Use a mid-range
    // VID instead: delete Dutch revisions before the second Dutch VID.
    $nl_vids = $this->getRevisionIds($nid, 'nl');
    $this->assertCount(3, $nl_vids);
    // Target the last Dutch VID; prior Dutch revisions are the first 2.
    $target_nl_vid = $nl_vids[2];

    $this->drush('nrd:delete-prior-revisions', [$nid, $target_nl_vid], ['langcode' => 'nl']);

    // Verify which revisions remain.
    $this->assertCount(4, $this->getRevisionIds($nid));
    $this->assertCount(1, $this->getRevisionIds($nid, 'nl'));
    $this->assertCount(3, $this->getRevisionIds($nid, 'en'));

    $this->drush('nrd:delete-prior-revisions', [$nid, 3]);
    $this->assertCount(2, $this->getRevisionIds($nid));
    $this->assertCount(1, $this->getRevisionIds($nid, 'nl'));
    $this->assertCount(1, $this->getRevisionIds($nid, 'en'));
  }

  /**
   * Tests --langcode with no matching revisions.
   */
  public function testLangcodeNoMatch(): void {
    ConfigurableLanguage::createFromLangcode('nl')->save();

    // Create an English-only node with 3 revisions.
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'EN only',
      'langcode' => 'en',
    ]);
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    for ($i = 2; $i <= 3; $i++) {
      $node->setTitle('EN only v' . $i);
      $node = $node_storage->createRevision($node);
      $node->save();
    }
    $nid = (int) $node->id();
    $all_vids = $this->getRevisionIds($nid);
    $latest_vid = end($all_vids);

    // Ask for Dutch revisions on an English-only node.
    $this->drush('nrd:delete-prior-revisions', [$nid, $latest_vid], ['langcode' => 'nl'], expected_return: 1);
    $this->assertStringContainsString('3 is not a valid revision id for node 1 with the langcode nl.', $this->getOutputRaw());

    // Ask for a language code that doesn't exist.
    $this->drush('nrd:delete-prior-revisions', [$nid, $latest_vid], ['langcode' => 'de'], expected_return: 1);
    $this->assertStringContainsString('The language code de is not a valid language code.', $this->getOutputRaw());

    // All revisions should still exist.
    $this->assertCount(3, $this->getRevisionIds($nid));
  }

  /**
   * Gets all revision IDs for a node, sorted ascending.
   *
   * @param int $nid
   *   The node ID.
   * @param string|null $langcode
   *   Optional language code to filter by translation-affected revisions.
   *
   * @return int[]
   *   The revision IDs.
   */
  private function getRevisionIds(int $nid, ?string $langcode = NULL): array {
    // Clear the static cache to get fresh results after drush commands.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->allRevisions()
      ->condition('nid', $nid)
      ->sort('vid', 'ASC')
      ->accessCheck(FALSE);
    if ($langcode !== NULL) {
      $query->condition('langcode', $langcode);
      $query->condition('revision_translation_affected', 1);
    }
    return array_map('intval', array_keys($query->execute()));
  }

}

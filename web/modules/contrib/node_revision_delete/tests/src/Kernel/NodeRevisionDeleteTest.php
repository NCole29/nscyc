<?php

declare(strict_types=1);

namespace Drupal\Tests\node_revision_delete\Kernel;

use Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\NodeInterface;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the node revision delete plugins.
 *
 * @group node_revision_delete
 */
class NodeRevisionDeleteTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use ContentModerationTestTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'content_translation',
    'field',
    'filter',
    'language',
    'node',
    'node_revision_delete',
    'system',
    'text',
    'user',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'content_moderation',
      'system',
      'filter',
      'node',
    ]);

    // Add the Dutch language.
    ConfigurableLanguage::createFromLangcode('nl')->save();

    // Create a node type that allows revisions.
    $this->createContentType(['type' => 'page', 'revision' => TRUE]);
  }

  /**
   * Test the node revision delete "amount" plugin.
   */
  public function testNodeRevisionDeleteAmount(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Configure the default settings for the "amount" plugin to allow a maximum
    // of 5 revisions.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'amount' => [
          'status' => TRUE,
          'settings' => [
            'amount' => 5,
          ],
        ],
      ])
      ->save();

    // Create 10 revisions.
    $node = $this->createNode(['type' => 'page']);
    for ($i = 0; $i < 10; $i++) {
      $new_revision = $node_storage->createRevision($node);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that only 5 revisions remain.
    $this->assertRevisionCount(5, $node);

    // Override the default settings for page node type to allow a maximum of 3
    // revisions.
    $node_type = $this->container->get('entity_type.manager')->getStorage('node_type')->load('page');
    $node_type->setThirdPartySetting('node_revision_delete', 'amount', [
      'status' => TRUE,
      'settings' => [
        'amount' => 3,
      ],
    ]);
    $node_type->save();
    $this->container->get('plugin.manager.node_revision_delete')->resetCache();

    // Add a revision and run the queue.
    $new_revision = $node_storage->createRevision($node);
    $new_revision->save();
    $this->runNodeRevisionDeleteQueue();

    // Assert that only 3 revisions remain.
    $this->assertRevisionCount(3, $node);
  }

  /**
   * Test the node revision delete "created" plugin.
   */
  public function testNodeRevisionDeleteCreated() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Configure the default settings for the "created" plugin to allow
    // revisions to exist for a maximum of 5 months.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'created' => [
          'status' => TRUE,
          'settings' => [
            'age' => 5,
          ],
        ],
      ])
      ->save();

    // Create 10 revisions, each 1 month newer than the previous.
    $node = $this->createNode([
      'type' => 'page',
      'created' => strtotime('-10 months -1 day'),
      'changed' => strtotime('-10 months -1 day'),
    ]);
    for ($i = 9; $i >= 0; $i--) {
      $node->setChangedTime(strtotime('-' . $i . ' months -1 day'));
      $new_revision = $node_storage->createRevision($node);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that only 5 revisions remain. The items created 5 months ago
    // (and before) should have been deleted.
    $this->assertRevisionCount(5, $node);

    // Override the default settings for page node type to allow revisions to
    // exist for a maximum of 3 months.
    $node_type = $this->container->get('entity_type.manager')->getStorage('node_type')->load('page');
    $node_type->setThirdPartySetting('node_revision_delete', 'created', [
      'status' => TRUE,
      'settings' => [
        'age' => 3,
      ],
    ]);
    $node_type->save();
    $this->container->get('plugin.manager.node_revision_delete')->resetCache();

    // Add a revision and run the queue.
    $new_revision = $node_storage->createRevision($node);
    $new_revision->save();
    $this->runNodeRevisionDeleteQueue();

    // Assert that 4 revisions remain. The latest revision should be kept, and
    // 3 of the revisions that were created previously.
    $this->assertRevisionCount(4, $node);
  }

  /**
   * Test the node revision delete "drafts" plugin.
   */
  public function testNodeRevisionDeleteDrafts(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Add the editorial workflow for the node type.
    $workflow = $this->createEditorialWorkflow();
    $workflow_type = $workflow->getTypePlugin();
    $this->assertInstanceOf(ContentModerationInterface::class, $workflow_type);
    $workflow_type->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    // Configure the default settings for the "drafts" plugin to allow draft
    // revisions to exist for a maximum of 5 months.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'drafts' => [
          'status' => TRUE,
          'settings' => [
            'age' => 5,
          ],
        ],
      ])
      ->save();

    // Create 10 draft revisions, each a month newer than the previous.
    $node = $this->createNode([
      'type' => 'page',
      'created' => strtotime('-10 months -1 day'),
      'changed' => strtotime('-10 months -1 day'),
      'status' => NodeInterface::PUBLISHED,
      'moderation_state' => 'published',
    ]);
    for ($i = 9; $i >= 0; $i--) {
      $node->set('moderation_state', 'draft');
      $node->setChangedTime(strtotime('-' . $i . ' months -1 day'));
      $new_revision = $node_storage->createRevision($node, FALSE);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that 6 revisions remain. The draft items created 5 months ago
    // (and before) should have been deleted. The published revision should also
    // be kept.
    $this->assertRevisionCount(6, $node);

    // Override the default settings for page node type to allow draft revisions
    // to exist for a maximum of 3 months.
    $node_type = $this->container->get('entity_type.manager')->getStorage('node_type')->load('page');
    $node_type->setThirdPartySetting('node_revision_delete', 'drafts', [
      'status' => TRUE,
      'settings' => [
        'age' => 3,
      ],
    ]);
    $node_type->save();
    $this->container->get('plugin.manager.node_revision_delete')->resetCache();

    // Add a draft revision and run the queue.
    $node->set('moderation_state', 'draft');
    $new_revision = $node_storage->createRevision($node, FALSE);
    $new_revision->save();
    $this->runNodeRevisionDeleteQueue();

    // Assert that 5 revisions remain. The latest draft revision should be kept,
    // and 3 of the revisions that were created previously. The published
    // revision should also be kept.
    $this->assertRevisionCount(5, $node);
  }

  /**
   * Test the node revision delete plugin integration.
   */
  public function testNodeRevisionDeleteIntegration(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Add the editorial workflow for the node type.
    $workflow = $this->createEditorialWorkflow();
    $workflow_type = $workflow->getTypePlugin();
    $this->assertInstanceOf(ContentModerationInterface::class, $workflow_type);
    $workflow_type->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    // Configure the default settings for the plugins to allow a maximum of 2
    // older revisions. Only revisions created 5 months ago (and before) should
    // be kept, and drafts should be kept for 3 months.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'amount' => [
          'status' => TRUE,
          'settings' => [
            'amount' => 2,
          ],
        ],
        'created' => [
          'status' => TRUE,
          'settings' => [
            'age' => 5,
          ],
        ],
        'drafts' => [
          'status' => TRUE,
          'settings' => [
            'age' => 4,
          ],
        ],
      ])
      ->save();

    // Create 10 published revisions, each month newer than the previous.
    $node = $this->createNode([
      'type' => 'page',
      'created' => strtotime('-10 months - 1 day'),
      'changed' => strtotime('-10 months - 1 day'),
      'status' => NodeInterface::PUBLISHED,
      'moderation_state' => 'published',
    ]);
    for ($i = 9; $i >= 0; $i--) {
      $node->setChangedTime(strtotime('-' . $i . ' months - 1 day'));
      $new_revision = $node_storage->createRevision($node);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that 5 revisions remain. The items created 5 months ago (and
    // before) should have been deleted. Since the "created" plugin prevents
    // deleting some revisions, the "amount" plugin should not have any effect.
    $this->assertRevisionCount(5, $node);

    // Create 10 draft revisions for the same node, each 1 month newer than the
    // previous.
    for ($i = 9; $i >= 0; $i--) {
      $node->set('moderation_state', 'draft');
      $node->setChangedTime(strtotime('-' . ($i) . 'months - 1 day'));
      $new_revision = $node_storage->createRevision($node, FALSE);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that 9 revisions remain. The draft items created 4 months ago
    // (and before) should have been deleted. The published revisions should
    // also have been kept.
    $this->assertRevisionCount(9, $node);

    // Configure the default settings for the plugins to allow a maximum of 4
    // older revisions. Only revisions created 1 month ago (and before) should
    // be kept, and drafts should be kept for 3 months.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'amount' => [
          'status' => TRUE,
          'settings' => [
            'amount' => 4,
          ],
        ],
        'created' => [
          'status' => TRUE,
          'settings' => [
            'age' => 1,
          ],
        ],
        'drafts' => [
          'status' => TRUE,
          'settings' => [
            'age' => 3,
          ],
        ],
      ])
      ->save();
    $this->container->get('plugin.manager.node_revision_delete')->resetCache();

    // Add a draft revision and run the queue.
    $node->set('moderation_state', 'draft');
    $new_revision = $node_storage->createRevision($node, FALSE);
    $new_revision->save();
    $this->runNodeRevisionDeleteQueue();

    // Assert that 8 revisions remain. Since most revisions are more than 1
    // month old, they should be deleted, but the "amount" plugin should prevent
    // deleting the latest 4 published revisions. We previously created 3 draft
    // revisions that are less than 3 months old. They should still be kept. We
    // also just created a new draft revision, which should also be kept.
    $this->assertRevisionCount(8, $node);

    // Override the default settings for page node type to allow a maximum of 3
    // older revisions. Only revisions created 1 month ago (and before) should
    // be kept, and drafts should be kept for 2 months.
    $node_type = $this->container->get('entity_type.manager')->getStorage('node_type')->load('page');
    $node_type->setThirdPartySetting('node_revision_delete', 'amount', [
      'status' => TRUE,
      'settings' => [
        'amount' => 3,
      ],
    ]);
    $node_type->setThirdPartySetting('node_revision_delete', 'created', [
      'status' => TRUE,
      'settings' => [
        'age' => 1,
      ],
    ]);
    $node_type->setThirdPartySetting('node_revision_delete', 'drafts', [
      'status' => TRUE,
      'settings' => [
        'age' => 2,
      ],
    ]);
    $node_type->save();
    $this->container->get('plugin.manager.node_revision_delete')->resetCache();

    // Add a draft revision and run the queue.
    $node->set('moderation_state', 'draft');
    $new_revision = $node_storage->createRevision($node, FALSE);
    $new_revision->save();
    $this->runNodeRevisionDeleteQueue();

    // Assert that 7 revisions remain. Since most revisions are more than 1
    // month old, they should be deleted, but the "amount" plugin should prevent
    // deleting the latest 3 published revisions. We previously created 2 draft
    // revisions that are less than 2 months old. They should still be kept. We
    // also created 2 new draft revisions separately, which should also be kept.
    $this->assertRevisionCount(7, $node);
  }

  /**
   * Test the node revision delete for multilingual content.
   */
  public function testNodeRevisionDeleteMultiLingual(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Configure the default settings for the "amount" plugin to allow a maximum
    // of 5 revisions.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'amount' => [
          'status' => TRUE,
          'settings' => [
            'amount' => 5,
          ],
        ],
      ])
      ->save();

    // Create 10 revisions.
    $node = $this->createNode(['type' => 'page']);
    for ($i = 0; $i < 10; $i++) {
      $new_revision = $node_storage->createRevision($node);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that only 5 revisions remain.
    $this->assertRevisionCount(5, $node);

    // Translate the node to Dutch and create 10 more revisions.
    $translation = $node->addTranslation('nl', ['title' => 'Dutch title']);
    for ($i = 0; $i < 10; $i++) {
      $new_revision = $node_storage->createRevision($translation);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that 10 revisions remain. There should be 5 Dutch revisions, and 5
    // English revisions.
    $this->assertRevisionCount(10, $node);
  }

  /**
   * Runs the node revision delete queue for the test.
   */
  protected function runNodeRevisionDeleteQueue(): void {
    $queue = $this->container->get('queue')->get('node_revision_delete');
    $queue_worker = $this->container->get('plugin.manager.queue_worker')->createInstance('node_revision_delete');

    // Assert a queue item has been created.
    $this->assertEquals(1, $this->container->get('queue')->get('node_revision_delete')->numberOfItems());

    // Run the queue to allow revisions to be deleted.
    while ($item = $queue->claimItem()) {
      /** @var \stdClass $item */
      $queue_worker->processItem($item->data);
      $queue->deleteItem($item);
    }

    // Assert the queue item has been processed.
    $this->assertEquals(0, $this->container->get('queue')->get('node_revision_delete')->numberOfItems());
  }

  /**
   * Asserts that the number of revisions for a node is as expected.
   *
   * @param int $expected
   *   The expected number of revisions.
   * @param \Drupal\node\NodeInterface $node
   *   The node to count revisions for.
   */
  private function assertRevisionCount(int $expected, NodeInterface $node): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $query = $storage->getQuery()->allRevisions()->condition('nid', $node->id())->accessCheck(FALSE);
    $revision_ids = array_keys($query->execute());
    $this->assertCount($expected, $revision_ids, sprintf('Expected %d revisions, but found %d.', $expected, count($revision_ids)));
  }

}

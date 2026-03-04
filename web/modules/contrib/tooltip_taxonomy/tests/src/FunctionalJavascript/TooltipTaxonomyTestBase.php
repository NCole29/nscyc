<?php

namespace Drupal\Tests\tooltip_taxonomy\FunctionalJavascript;

use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Base class for Tooltip Taxonomy JavaScript functional tests.
 */
abstract class TooltipTaxonomyTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tooltip_taxonomy',
    'node',
    'taxonomy',
    'user',
    // Needed for text format handling.
    'filter',
  ];

  /**
   * The default theme used for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * An admin user with necessary permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The vocabulary used for testing.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * The content type used for testing.
   *
   * @var string
   */
  protected $contentType = 'page';

  /**
   * The text format used for testing.
   *
   * @var \Drupal\filter\Entity\FilterFormat
   */
  protected $textFormat;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Call parent::setUp() first to ensure the test environment is initialized.
    parent::setUp();

    // Create the 'page' content type.
    $this->drupalCreateContentType([
      'type' => $this->contentType,
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    // Create the vocabulary for testing.
    $this->vocabulary = Vocabulary::create([
      'vid' => 'tooltip_test_vocabulary',
      'name' => 'Tooltip Test Vocabulary',
    ]);
    $this->vocabulary->save();

    // Create the text format.
    $this->configureTextFormat();

    // Clear caches and rebuild container to recognize all definitions.
    drupal_flush_all_caches();
    \Drupal::service('kernel')->rebuildContainer();

    // Create an admin user with necessary permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer nodes',
      'bypass node access',
      'administer taxonomy',
      'administer site configuration',
      'administer filters',
      'access content',
      // Permission to use the custom text format.
      $this->textFormat->getPermissionName(),
    ]);
    $this->drupalLogin($this->adminUser);

    // Configure the tooltip_taxonomy module.
    $this->configureTooltipTaxonomy();
  }

  /**
   * Configures the custom text format for testing.
   */
  protected function configureTextFormat() {
    // Create the 'filtered_html' text format with limited allowed HTML tags.
    $this->textFormat = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            // Only allow <span> tags with class attribute.
            'allowed_html' => '<span class>',
          ],
        ],
        'filter_autop' => [
          'status' => 1,
        ],
      ],
    ]);
    $this->textFormat->save();
  }

  /**
   * Configures the tooltip_taxonomy module for testing.
   */
  protected function configureTooltipTaxonomy() {
    // Clear caches and rebuild container to ensure entity types are recognized.
    drupal_flush_all_caches();
    \Drupal::service('kernel')->rebuildContainer();

    // Use the correct entity type ID.
    $filter_condition_storage = \Drupal::entityTypeManager()->getStorage('filter_condition');

    // Create the filter condition entity with correct property names.
    $filter_condition = $filter_condition_storage->create([
      'cid' => 'test_filter_condition',
      'name' => 'Test Filter Condition',
      'vids' => [$this->vocabulary->id()],
      'contentTypes' => [
        'id' => 'entity_bundle:node',
        'bundles' => [$this->contentType => $this->contentType],
        'negate' => 0,
      ],
      'path' => ['id' => 'request_path', 'pages' => '', 'negate' => 0],
      'view' => [],
      'fields' => ['node-body' => 'node-body'],
      // Include the text format used.
      'formats' => [$this->textFormat->id() => $this->textFormat->id()],
      'weight' => 0,
      'allowed_html_tags' => '',
    ]);
    $filter_condition->save();
  }

  /**
   * Creates a taxonomy term.
   *
   * @param string $name
   *   The name of the term.
   * @param string $description
   *   (Optional) The description of the term.
   * @param array $additional_values
   *   (Optional) Additional values for the term.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The created taxonomy term.
   */
  protected function createTaxonomyTerm(string $name, string $description = '', array $additional_values = []): Term {
    $values = [
      'name' => $name,
      'description' => [
        'value' => $description,
        // Use the same text format as the node body.
        'format' => $this->textFormat->id(),
      ],
      'vid' => $this->vocabulary->id(),
    ] + $additional_values;

    $term = Term::create($values);
    $term->save();
    return $term;
  }

  /**
   * Creates a node with the given body content.
   *
   * @param string $title
   *   The title of the node.
   * @param string $body
   *   The body content of the node.
   * @param \Drupal\taxonomy\Entity\Term|null $term
   *   (Optional) The taxonomy term to include in the body.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node.
   */
  protected function createNodeWithBody(string $title, string $body, ?Term $term = NULL): Node {
    if ($term) {
      // Include the term's label in the body content to trigger the tooltip.
      $body .= ' ' . $term->label();
    }

    $node_values = [
      'type' => $this->contentType,
      'title' => $title,
      'body' => [
        'value' => $body,
        // Use the custom text format.
        'format' => $this->textFormat->id(),
      ],
      'status' => 1,
    ];

    // Use drupalCreateNode to create the node.
    $node = $this->drupalCreateNode($node_values);

    return $node;
  }

  /**
   * Gets the text content of the tooltip.
   *
   * @return string|null
   *   The tooltip text, or NULL if not found.
   */
  protected function getTooltipContent(): ?string {
    $tooltip_element = $this->assertSession()->waitForElement('css', '.tx-tooltip-text', 5000);
    return $tooltip_element ? $tooltip_element->getText() : NULL;
  }

}

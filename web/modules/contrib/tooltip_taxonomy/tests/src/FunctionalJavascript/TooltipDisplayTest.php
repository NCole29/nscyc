<?php

namespace Drupal\Tests\tooltip_taxonomy\FunctionalJavascript;

/**
 * Tests the tooltip display functionality.
 *
 * @group tooltip_taxonomy
 */
class TooltipDisplayTest extends TooltipTaxonomyTestBase {

  /**
   * Tests that the tooltip appears when hovering over a taxonomy term.
   */
  public function testTooltipDisplay() {
    // Step 1: Create a taxonomy term with a description.
    $term_name = 'Test Term';
    $term_description = 'This is the description for Test Term.';
    $term = $this->createTaxonomyTerm($term_name, $term_description);

    // Step 2: Create a node that includes the taxonomy term in the body.
    $node = $this->createNodeWithBody('Test Node', 'This is a test content.', $term);

    // Step 3: Log out to test as an anonymous user.
    $this->drupalLogout();

    // Step 4: Visit the content page.
    $this->drupalGet($node->toUrl());

    // Step 5: Verify that the '.tx-tooltip' element exists on page load.
    $this->assertSession()->elementExists('css', '.tx-tooltip');

    // Step 6: Find and hover over the taxonomy term link.
    $term_link = $this->getSession()->getPage()->find('css', '.tx-tooltip');
    $this->assertNotNull($term_link, 'Found the taxonomy term with tooltip.');
    $term_link->mouseOver();

    // Step 7: Verify the tooltip content.
    $tooltip_content = $this->getTooltipContent();
    $this->assertStringContainsString($term_description, $tooltip_content, 'Tooltip displays the correct description.');
  }

}

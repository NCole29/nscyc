<?php

namespace Drupal\Tests\tooltip_taxonomy\FunctionalJavascript;

/**
 * Tests user access control for tooltips.
 *
 * @group tooltip_taxonomy
 */
class UserAccessTest extends TooltipTaxonomyTestBase {

  /**
   * Tests that users without access cannot see tooltips for unpublished terms.
   */
  public function testUserAccessToTooltip() {

    // Step 1: Create a taxonomy term with a description.
    $term_name = 'Unpublished Term';
    $term_description = 'This is the description for Test Term.';
    $term = $this->createTaxonomyTerm($term_name, $term_description, ['status' => 0]);

    // Step 2: Create a node that includes the unpublished taxonomy term.
    $node = $this->createNodeWithBody('Access Test Node', 'Testing access with term.', $term);

    // Step 3: Log out to test as an anonymous user.
    $this->drupalLogout();

    // Step 4: Visit the content page.
    $this->drupalGet($node->toUrl());

    // Step 5: Verify that the 'tx-tooltip' element does not exist on page load.
    $this->assertSession()->elementNotExists('css', '.tx-tooltip');
  }

}

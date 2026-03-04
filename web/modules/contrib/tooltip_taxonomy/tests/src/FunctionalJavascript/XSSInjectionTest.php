<?php

namespace Drupal\Tests\tooltip_taxonomy\FunctionalJavascript;

/**
 * Tests that tooltips sanitize XSS content.
 *
 * @group tooltip_taxonomy
 */
class XSSInjectionTest extends TooltipTaxonomyTestBase {

  /**
   * Tests that XSS content is sanitized in tooltips.
   */
  public function testXssSanitization() {
    // Step 1: Create a taxonomy term with malicious XSS code.
    $malicious_script = '<script>alert("XSS")</script>';
    $term_name = 'XSS Term';
    $term_description = 'This is the description for Test Term.' . $malicious_script;
    $term = $this->createTaxonomyTerm($term_name, $term_description);

    // Step 2: Create a node that includes the malicious taxonomy term.
    $node = $this->createNodeWithBody('XSS Test Node', 'Testing XSS with term.', $term);

    // Step 3: Log out to test as an anonymous user.
    $this->drupalLogout();

    // Step 4: Visit the content page.
    $this->drupalGet($node->toUrl());

    // Step 5: Verify that the 'tx-tooltip' element exists on page load.
    $this->assertSession()->elementExists('css', '.tx-tooltip');

    // Step 6: Verify that the tooltip content is sanitized.
    $this->assertSession()->responseNotContains($malicious_script);

    // Step 7: Check that no alert was executed.
    $alertCalls = $this->getSession()->evaluateScript('window.alert.calls');
    $this->assertEmpty($alertCalls, 'An alert was executed.');
  }

}

<?php

namespace Drupal\Tests\permission_spreadsheet\Functional;

use Drupal\Core\Config\Config;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Provides base class for testing form.
 */
abstract class FormTestBase extends BrowserTestBase {

  /**
   * The path of the form page.
   */
  const PAGE_PATH = '';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['permission_spreadsheet'];

  /**
   * A user with permission to access admin pages and administer permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * The configuration object for the module.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $moduleConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer permissions', 'access administration pages']);
    $this->moduleConfig = $this->config('permission_spreadsheet.settings');
  }

  /**
   * Tests access restriction.
   */
  public function testAccess(): void {
    $assert_session = $this->assertSession();

    // Test access check.
    $regular_user = $this->drupalCreateUser();
    $this->drupalLogin($regular_user);
    $this->drupalGet(static::PAGE_PATH);
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet(static::PAGE_PATH);
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests form with Excel book format.
   */
  public function testProcessXlsx(): void {
    $this->doFormatSpecificTest('xlsx');
  }

  /**
   * Tests form with old Excel book format.
   */
  public function testProcessXls(): void {
    $this->doFormatSpecificTest('xls');
  }

  /**
   * Tests form with OpenDocument spreadsheet format.
   */
  public function testProcessOds(): void {
    $this->doFormatSpecificTest('ods');
  }

  /**
   * Tests form with comma separated value format.
   */
  public function testProcessCsv(): void {
    $this->doFormatSpecificTest('csv');
  }

  /**
   * Tests form with Tab separated value format.
   */
  public function testProcessTsv(): void {
    $this->doFormatSpecificTest('tsv');
  }

  /**
   * Tests form with specific format.
   *
   * @param string $format
   *   The format to test.
   */
  abstract protected function doFormatSpecificTest($format): void;

}

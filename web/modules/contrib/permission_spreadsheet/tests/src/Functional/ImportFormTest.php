<?php

namespace Drupal\Tests\permission_spreadsheet\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\permission_spreadsheet\PhpSpreadsheetHelperTrait;
use Drupal\permission_spreadsheet\RoleLoaderTrait;
use Drupal\user\Entity\Role;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Tests import form.
 *
 * @group permission_spreadsheet
 */
class ImportFormTest extends FormTestBase {

  use RoleLoaderTrait;
  use PhpSpreadsheetHelperTrait;
  use StringTranslationTrait;

  /**
   * The path of the form page.
   */
  const PAGE_PATH = 'admin/people/permissions/spreadsheet/import';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleConfig->set('import.text_revoked', "N\nx");
    $this->moduleConfig->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function doFormatSpecificTest($format): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->adminUser);
    $current_user_rid = $this->adminUser->getRoles(TRUE)[0];

    // Create admin role.
    Role::create([
      'id' => 'administrator',
      'label' => 'Administrator',
      'is_admin' => TRUE,
    ])->save();

    // Create test spreadsheet.
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue([5, 1], $current_user_rid);
    $sheet->setCellValue([4, 2], 'administer menu');
    $sheet->setCellValue([5, 2], 'Y');
    $sheet->setCellValue([4, 3], 'administer modules');
    $sheet->setCellValue([5, 3], 'N');
    $sheet->setCellValue([4, 4], 'administer site configuration');
    $sheet->setCellValue([5, 4], 'x');
    $sheet->setCellValue([4, 5], 'administer themes');
    $sheet->setCellValue([6, 1], 'administrator');

    $file_params = [
      'filename' => 'permission_spreadsheet_import.' . $format,
      'uri' => 'temporary://permission_spreadsheet_import.' . $format,
    ];
    $file = File::create($file_params);
    $file_real_path = $this->container->get('file_system')->realPath($file->getFileUri());
    $this->createWriter($format, $spreadsheet)->save($file_real_path);
    $file->save();
    $this->assertTrue($file->id(), 'Save import file to temporary directory.');

    // Submit import form.
    $edit = ['files[file]' => $file_real_path];
    $this->drupalGet(static::PAGE_PATH);
    $this->submitForm($edit, $this->t('Import'));
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains((string) $this->t('The permissions have been imported.'));

    // Check updated permissions.
    $roles = $this->loadRoles();
    $this->assertTrue($roles['administrator']->hasPermission('administer menu'), 'Check uneditable permission is protected.');

    $role = $roles[$current_user_rid];
    $this->assertTrue($role->hasPermission('administer menu'), "Check behavior for cell filled with 'Y'.");
    $this->assertTrue(!$role->hasPermission('administer modules'), "Check behavior for cell filled with 'N'.");
    $this->assertTrue(!$role->hasPermission('administer site configuration'), "Check behavior for cell filled with 'x'.");
    $this->assertTrue(!$role->hasPermission('administer themes'), 'Check behavior for empty cell.');

    // Revert changes.
    user_role_change_permissions($current_user_rid, [
      'administer menu' => 0,
      'administer modules' => 0,
      'administer site configuration' => 0,
      'administer themes' => 0,
    ]);
  }

}

<?php

namespace Drupal\Tests\permission_spreadsheet\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Tests export form.
 *
 * @group permission_spreadsheet
 */
class ExportFormTest extends FormTestBase {

  use StringTranslationTrait;

  /**
   * The path of the form page.
   */
  const PAGE_PATH = 'admin/people/permissions/spreadsheet/export';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleConfig->set('export.text_revoked', 'N');
    $this->moduleConfig->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function doFormatSpecificTest($format): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    // Submit export form.
    $edit = [];
    $edit['format'] = $format;
    $this->drupalGet(static::PAGE_PATH);
    $this->submitForm($edit, $this->t('Download'));
    $assert_session->statusCodeEquals(200);
    $assert_session->responseHeaderContains('Content-Disposition', 'attachment; filename=' . $this->moduleConfig->get('export.filename') . '.' . $format . '');

    // Save the exported file.
    $file_params = [
      'filename' => 'permission_spreadsheet_export.' . $format,
      'uri' => 'temporary://permission_spreadsheet_export.' . $format,
      'filemime' => $this->getSession()->getResponseHeader('Content-Type'),
    ];
    $file = File::create($file_params);
    file_put_contents($file->getFileUri(), $this->getSession()->getDriver()->getContent());
    $file->save();
    $this->assertTrue($file->id(), 'Save exported file to temporary directory.');

    // Test the exported file.
    $sheet = NULL;
    try {
      $reader = IOFactory::load($this->container->get('file_system')->realPath($file->getFileUri()));
      $sheet = $reader->getActiveSheet();
    }
    catch (\Exception) {
      $this->assertTrue($sheet !== NULL, 'Load exported file.');
    }

    $this->assertTrue($sheet->getCell([7, 1])->getValue() == $this->adminUser->getRoles(TRUE)[0], 'Role name is output correctly.');

    $values = [];
    for ($row = 2; strlen($permission = $sheet->getCell([4, $row])->getValue()); $row++) {
      $values[$permission] = trim($sheet->getCell([7, $row])->getValue());
    }
    $this->assertTrue($values['access administration pages'] == $this->moduleConfig->get('export.text_granted'), 'Granted permission is output correctly.');
    $this->assertTrue($values['administer site configuration'] == $this->moduleConfig->get('export.text_revoked'), 'Revoked permission is output correctly.');
  }

}

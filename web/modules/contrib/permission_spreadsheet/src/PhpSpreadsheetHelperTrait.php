<?php

namespace Drupal\permission_spreadsheet;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;

/**
 * Trait for using PhpSpreadsheet.
 */
trait PhpSpreadsheetHelperTrait {

  /**
   * Creates spreadsheet writer that corresponds to specified format.
   *
   * @param string $format
   *   The file format.
   * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
   *   The spreadsheet object.
   *
   * @return \PhpOffice\PhpSpreadsheet\Writer\IWriter
   *   An instance of spreadsheet writer that corresponds to specified format.
   */
  public function createWriter($format, $spreadsheet): IWriter {
    if ($format == 'tsv') {
      $writer = IOFactory::createWriter($spreadsheet, 'Csv');
      $writer->setDelimiter("\t");
    }
    else {
      $writer = IOFactory::createWriter($spreadsheet, ucwords($format));
    }
    return $writer;
  }

}

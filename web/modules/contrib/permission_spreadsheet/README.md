# Permission Spreadsheet

The Permission Spreadsheet module provides features to import/export
user permission via:

- Excel (*.xlsx, *.xls)
- OpenDocument Spreadsheet (*.ods)
- Comma separated values (*.csv)
- Tab separated values (*.tsv)

This module is useful on following cases:

- Site has many roles, so difficult to edit permissions on admin page.
- Copy permissions among the roles.


## Contents of this file

- Requirements
- Installation
- Configuration
- About spreadsheet format


## Requirements

This module requires the following:

- PHP 8.0 or greater
- Drupal core 10.0.0 or greater
- PhpSpreadsheet 2.2.0 or greater


## Installation

- Strongly recommend installing this module using composer:
  `composer require drupal/permission_spreadsheet`

- Also you can install PhpSpreadsheet separately using composer:
  `composer require phpoffice/phpspreadsheet:^1.3`


## Configuration

Visit `/admin/config/people/permission_spreadsheet`
(Administration > Configuration > People > Permission spreadsheet settings)


## About spreadsheet format

- Column A-C (1st-3rd) These columns are used to display permission info. Not
  necessary for importing so you can edit and reorder them between A-C, but do
  not remove to keep column D.
- Column D (4th) This column is necessary to detect permission. You can not
  move this column to other position.
- Column E- (5th-) Columns after column E contains marks that indicates
  permission is granted or revoked. Columns should be continuous, columns after
  break will be ignored. First row of these columns must contain role ID only.
  You can remove unwanted columns. e.g., if you don’t need to change
  permissions for ‘anonymous’ role, remove ‘anonymous’ column.
- Rows First row is header row, do not remove. You can remove unwanted rows.
  e.g., if you don’t need to change ‘administer menu’ permission, you can
  remove ‘administer menu’ row.

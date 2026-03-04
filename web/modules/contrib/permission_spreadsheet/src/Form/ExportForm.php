<?php

namespace Drupal\permission_spreadsheet\Form;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\permission_spreadsheet\PhpSpreadsheetHelperTrait;
use Drupal\permission_spreadsheet\RoleLoaderTrait;
use Drupal\user\PermissionHandlerInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Provides permission export form.
 */
class ExportForm extends FormBase {

  use RoleLoaderTrait;
  use PhpSpreadsheetHelperTrait;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected PermissionHandlerInterface $permissionHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->permissionHandler = $container->get('user.permissions');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->moduleExtensionList = $container->get('extension.list.module');
    $instance->token = $container->get('token');
    $instance->fileSystem = $container->get('file_system');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'permission_spreadsheet_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('permission_spreadsheet.settings');

    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => [
        'xlsx' => $this->t('Excel 2007 (.xlsx)'),
        'xls' => $this->t('Excel 2000/2002/2003 (.xls)'),
        'ods' => $this->t('OpenDocument spreadsheet (.ods)'),
        'csv' => $this->t('Comma separated (.csv)'),
        'tsv' => $this->t('Tab separated (.tsv)'),
      ],
      '#required' => TRUE,
    ];

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $form['settings']['tokens'] = [
        '#theme' => 'token_tree_link',
        '#global_types' => TRUE,
      ];
    }
    $form['settings']['filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filename'),
      '#default_value' => $config->get('export.filename'),
      '#description' => $this->t("Specify default filename (without extension) on download file. e.g., 'permissions'. You can use tokens."),
      '#required' => TRUE,
    ];
    $form['settings']['sheet_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sheet title'),
      '#default_value' => $config->get('export.sheet_title'),
      '#description' => $this->t("Specify default title for Excel sheet. e.g., 'Permissions'. You can use tokens."),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Output spreadsheet data.
    $format = $form_state->getValue('format');
    $temppath = $this->fileSystem->realpath($this->fileSystem->tempnam('temporary://', 'permissions'));
    $sheet_title = $this->token->replace($form_state->getValue('sheet_title'));

    try {
      $spreadsheet = $this->createSpreadsheet();
      $spreadsheet->getActiveSheet()->setTitle($sheet_title);
      $this->createWriter($format, $spreadsheet)->save($temppath);
    }
    catch (\Exception $ex) {
      $this->messenger()->addError($this->t('An error has occurred while writing spreadsheet file. @error', ['@error' => $ex->getMessage()]));
      return;
    }

    $filename = $this->token->replace($form_state->getValue('filename'));
    $response = new BinaryFileResponse($temppath);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename . '.' . $format);
    $response->deleteFileAfterSend();

    $form_state->setResponse($response);
  }

  /**
   * Creates permission spreadsheet.
   *
   * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
   *   A spreadsheet object.
   */
  protected function createSpreadsheet(): Spreadsheet {
    $config = $this->config('permission_spreadsheet.settings');
    $text_granted = $config->get('export.text_granted');
    $text_revoked = $config->get('export.text_revoked');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set header data.
    $system_column = 1;
    $set_header_cell = function ($column, $text) use ($sheet) {
      $sheet->getColumnDimensionByColumn($column)->setAutoSize(TRUE);
      $sheet->setCellValue([$column, 1], $text);

      $style = $sheet->getStyle([$column, 1]);
      $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
      $style->getFont()->setBold(TRUE);
    };

    $set_header_cell($system_column++, $this->t('(Module Name)'));
    $set_header_cell($system_column++, $this->t('(Permission Title)'));
    $set_header_cell($system_column++, $this->t('(Module)'));
    $set_header_cell($system_column++, $this->t('(Permission)'));

    // Set body data.
    $role_names = [];
    $role_permissions = [];
    $column = $system_column;
    foreach ($this->loadNonAdminRoles() as $rid => $role) {
      $role_names[$rid] = $role->label();
      $role_permissions[$rid] = $role->getPermissions();

      $sheet->setCellValue([$column, 1], $rid);
      $sheet->getColumnDimensionByColumn($column)->setAutoSize(TRUE);
      $sheet->getStyle([$column, 1])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
      $column++;
    }
    $sheet->getStyle(Coordinate::stringFromColumnIndex($system_column) . ':' . Coordinate::stringFromColumnIndex($column))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $permissions = $this->permissionHandler->getPermissions();
    $module_names = [];
    $row = 2;
    foreach ($permissions as $permission_name => $permission) {
      $provider = $permission['provider'];
      if (!isset($module_names[$provider])) {
        $module_names[$provider] = $this->moduleExtensionList->getName($provider);
      }

      $column = 1;
      $sheet->setCellValue([$column++, $row], $module_names[$provider]);
      $sheet->setCellValue([$column++, $row], strip_tags((string) $permission['title']));
      $sheet->setCellValue([$column++, $row], $provider);
      $sheet->setCellValue([$column++, $row], $permission_name);

      foreach (array_keys($role_names) as $role) {
        $has_permission = in_array($permission_name, $role_permissions[$role]);
        $sheet->setCellValue([$column, $row], $has_permission ? $text_granted : $text_revoked);
        $column++;
      }

      $row++;
    }

    // Add borders.
    $sheet->getStyle('D1:D' . ($row - 1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($column - 1) . '1')->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle('A2:' . Coordinate::stringFromColumnIndex($column - 1) . ($row - 1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

    return $spreadsheet;
  }

}

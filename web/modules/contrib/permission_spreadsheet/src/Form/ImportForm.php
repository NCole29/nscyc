<?php

namespace Drupal\permission_spreadsheet\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;
use Drupal\file\FileStorageInterface;
use Drupal\permission_spreadsheet\RoleLoaderTrait;
use Drupal\user\PermissionHandlerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides permission import form.
 */
class ImportForm extends FormBase {

  use RoleLoaderTrait;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected PermissionHandlerInterface $permissionHandler;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected FileStorageInterface $fileStorage;

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
    $instance->moduleExtensionList = $container->get('extension.list.module');
    $instance->fileStorage = $container->get('entity_type.manager')->getStorage('file');
    $instance->fileSystem = $container->get('file_system');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'permission_spreadsheet_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File to import'),
      '#required' => TRUE,
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'xlsx xls ods csv tsv'],
      ],
      '#process' => [
        [ManagedFile::class, 'processManagedFile'],
        [$this, 'processImportFileElement'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 10,
    ];
    $form['actions']['preview'] = [
      '#type' => 'submit',
      '#value' => $this->t('Preview'),
      '#ajax' => [
        'callback' => [$this, 'previewAjaxCallback'],
        'wrapper' => 'preview',
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];

    $form['preview'] = [
      '#markup' => '<div id="preview" class="preview"></div>',
      '#weight' => 20,
    ];

    $form['#attached']['library'][] = 'permission_spreadsheet/import';

    return $form;
  }

  /**
   * Render API callback: Expands ajax callback for import file element.
   */
  public function processImportFileElement(&$element, FormStateInterface $form_state, &$complete_form): array {
    $config = $this->config('permission_spreadsheet.settings');
    if ($config->get('import.auto_preview')) {
      $element['upload_button']['#ajax']['callback'] = [$this, 'importFileUploadAjaxCallback'];
    }
    $element['remove_button']['#ajax']['callback'] = [$this, 'importFileUploadAjaxCallback'];
    return $element;
  }

  /**
   * Ajax callback for import file element.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response of the ajax upload.
   */
  public function importFileUploadAjaxCallback(array &$form, FormStateInterface $form_state, Request $request): AjaxResponse {
    $response = ManagedFile::uploadAjaxCallback($form, $form_state, $request);
    $response->addCommand(new ReplaceCommand('#preview', $this->buildPreview($form_state)));
    return $response;
  }

  /**
   * Ajax callback for preview button.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response of the ajax upload.
   */
  public function previewAjaxCallback(array &$form, FormStateInterface $form_state, Request $request): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#preview', $this->buildPreview($form_state)));
    return $response;
  }

  /**
   * Builds preview content.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   A renderable array containing preview content.
   */
  protected function buildPreview(FormStateInterface $form_state): array {
    $build = [
      '#prefix' => '<div id="preview" class="preview">',
      '#suffix' => '</div>',
    ];
    $build['status'] = [
      '#type' => 'status_messages',
    ];
    $build['title'] = [
      '#plain_text' => $this->t('Preview changes'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];

    $module_names = [];
    $role_permissions = $this->getDifferences($form_state);
    if (!empty($role_permissions)) {
      $role_names = [];
      foreach ($this->loadNonAdminRoles() as $rid => $role) {
        $role_names[$rid] = $role->label();
      }

      $all_permissions = $this->permissionHandler->getPermissions();
      foreach ($role_permissions as $rid => $permissions) {
        $build[$rid] = [
          '#type' => 'table',
          '#caption' => $role_names[$rid] . ' [' . $rid . ']',
          '#header' => [
            '',
            $this->t('Module Name'),
            $this->t('Permission Title'),
            $this->t('Module'),
            $this->t('Permission'),
          ],
          '#empty' => $this->t('No changes found.'),
        ];

        foreach ($permissions as $permission => $is_granted) {
          if (!isset($all_permissions[$permission])) {
            continue;
          }

          $provider = $all_permissions[$permission]['provider'];
          if (!isset($module_names[$provider])) {
            $module_names[$provider] = $this->moduleExtensionList->getName($provider);
          }

          $class = $is_granted ? 'granted' : 'revoked';
          $row = [
            '#attributes' => ['class' => $class],
          ];
          $row[] = [
            '#plain_text' => $is_granted ? '+' : '-',
          ];
          $row[] = [
            '#plain_text' => $module_names[$provider],
          ];
          $row[] = [
            '#plain_text' => strip_tags((string) $all_permissions[$permission]['title']),
          ];
          $row[] = [
            '#plain_text' => $provider,
          ];
          $row[] = [
            '#plain_text' => $permission,
          ];

          $build[$rid][] = $row;
        }
      }

      $build['notes'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['notes']],
      ];
      $build['notes']['granted'] = [
        '#plain_text' => $this->t('+ Granted'),
        '#prefix' => '<span class="granted">',
        '#suffix' => '</span>',
      ];
      $build['notes']['revoked'] = [
        '#plain_text' => $this->t('- Revoked'),
        '#prefix' => '<span class="revoked">',
        '#suffix' => '</span>',
      ];
    }
    elseif ($role_permissions !== FALSE) {
      $build['empty'] = [
        '#plain_text' => $this->t('No changes found.'),
      ];
    }
    else {
      unset($build['title']);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file = $this->fileStorage->load($form_state->getValue('file')[0] ?? 0);
    if ($file) {
      $form_state->set('uploaded_file', $file);
    }
    else {
      $form_state->setErrorByName('file', $this->t('Uploaded file seems to be deleted. Please upload again.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#id']) && $triggering_element['#id'] != 'edit-submit') {
      return;
    }

    $role_permissions = $this->getDifferences($form_state);
    if (!empty($role_permissions)) {
      foreach ($role_permissions as $rid => $permissions) {
        user_role_change_permissions($rid, $permissions);
      }

      $this->messenger()->addStatus($this->t('The permissions have been imported.'));
    }
    else {
      $this->messenger()->addStatus($this->t('No permissions were imported because there was no changes.'));
    }

    // Delete uploaded file.
    $file = $form_state->get('uploaded_file');
    if ($file) {
      try {
        $file->delete();
      }
      catch (\Exception) {
      }
    }
  }

  /**
   * Gets differences between current permissions and uploaded permissions.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array|bool
   *   An array containing differences for each role or FALSE if any error was
   *   occurred.
   */
  protected function getDifferences(FormStateInterface $form_state): array|bool {
    $file = $form_state->get('uploaded_file');
    if (!$file) {
      return FALSE;
    }

    // Load existing permissions.
    $all_permissions = [];
    foreach ($this->permissionHandler->getPermissions() as $permission_name => $permission) {
      $all_permissions[$permission_name] = 0;
    }

    $existing_role_permissions = [];
    foreach ($this->loadNonAdminRoles() as $rid => $role) {
      $existing_role_permissions[$rid] = $all_permissions;

      foreach ($role->getPermissions() as $permission) {
        $existing_role_permissions[$rid][$permission] = 1;
      }
    }

    // Load permissions from uploaded file.
    $path = $this->fileSystem->realpath($file->getFileUri());
    try {
      $spreadsheet = IOFactory::load($path);
      $sheet = $spreadsheet->getActiveSheet();
    }
    catch (\Exception) {
      return FALSE;
    }

    $changed_role_permissions = [];
    $config = $this->config('permission_spreadsheet.settings');
    $revoked_texts = array_map('trim', explode("\n", $config->get('import.text_revoked')));

    for ($column = 5; strlen($rid = ($sheet->getCell([$column, 1])->getValue() ?? '')); $column++) {
      for ($row = 2; strlen($permission = ($sheet->getCell([4, $row])->getValue() ?? '')); $row++) {
        $cell_value = trim($sheet->getCell([$column, $row])->getValue() ?? '');
        $is_granted = strlen($cell_value) && !in_array($cell_value, $revoked_texts);
        $changed_role_permissions[$rid][$permission] = (int) $is_granted;
      }
    }

    // Get differences.
    $differences = [];
    foreach ($changed_role_permissions as $rid => $permissions) {
      if (isset($existing_role_permissions[$rid])) {
        $differences_item = array_diff_assoc($permissions, $existing_role_permissions[$rid]);
        if (!empty($differences_item)) {
          $differences[$rid] = $differences_item;
        }
      }
    }

    return $differences;
  }

}

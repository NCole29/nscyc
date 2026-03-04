<?php

namespace Drupal\civicrm_member_roles\Batch;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\civicrm_member_roles\CivicrmMemberRoles;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Provides batch processing for member role sync.
 */
class Sync {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The civicrm member role utility service.
   *
   * @var \Drupal\civicrm_member_roles\CivicrmMemberRoles
   */
  protected $civicrmMemberRoles;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Sync constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   * @param \Drupal\civicrm_member_roles\CivicrmMemberRoles $civicrmMemberRoles
   *   The civicrm member role service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(TranslationInterface $stringTranslation, CivicrmMemberRoles $civicrmMemberRoles, MessengerInterface $messenger) {
    $this->stringTranslation = $stringTranslation;
    $this->civicrmMemberRoles = $civicrmMemberRoles;
    $this->messenger = $messenger;
  }

  /**
   * Get the batch.
   *
   * @return array
   *   A batch API array for syncing user memberships and roles.
   */
  public function getBatch() {
    $batch = [
      'title' => $this->t('Updating Users...'),
      'operations' => [],
      'init_message' => $this->t('Starting Update'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('An error occurred during processing'),
      'finished' => [$this, 'finished'],
    ];

    $batch['operations'][] = [[$this, 'process'], []];

    return $batch;
  }

  /**
   * Batch API process callback.
   *
   * @param mixed $context
   *   Batch API context data.
   */
  public function process(&$context) {
    $civicrmMemberRoles = $this->civicrmMemberRoles;

    if (!isset($context['sandbox']['cids'])) {
      $context['sandbox']['cids'] = $civicrmMemberRoles->getSyncContactIds();
      $context['sandbox']['max'] = count($context['sandbox']['cids']);
      $context['results']['processed'] = 0;
    }

    $cid = array_shift($context['sandbox']['cids']);
    if ($account = $civicrmMemberRoles->getContactAccount($cid)) {
      $civicrmMemberRoles->syncContact($cid, $account);
    }
    $context['results']['processed']++;

    if (count($context['sandbox']['cids']) > 0) {
      $context['finished'] = 1 - (count($context['sandbox']['cids']) / $context['sandbox']['max']);
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Batch API success indicator.
   * @param array $results
   *   Batch API results array.
   */
  public function finished($success, array $results) {
    if ($success) {
      $message = $this->stringTranslation->formatPlural($results['processed'], 'One user processed.', '@count users processed.');
      $this->messenger->addStatus($this->t('%message', ['%message' => $message]));
    }
    else {
      $message = $this->t('Encountered errors while performing sync.');
      $this->messenger->addError($this->t('%message', ['%message' => $message]));
    }

  }

}

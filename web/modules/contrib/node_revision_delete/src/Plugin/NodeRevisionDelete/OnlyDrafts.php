<?php

namespace Drupal\node_revision_delete\Plugin\NodeRevisionDelete;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node_revision_delete\Attribute\NodeRevisionDelete;
use Drupal\node_revision_delete\Plugin\NodeRevisionDeleteBase;

/**
 * Deletes unpublished revisions older than the active revision as specified.
 */
#[NodeRevisionDelete(
  id: 'only_drafts',
  label: new TranslatableMarkup('Delete unpublished revisions older than the active revision as specified.'),
)]
class OnlyDrafts extends NodeRevisionDeleteBase {

  /**
   * {@inheritdoc}
   */
  public function checkRevisions(array $revision_ids, int $active_vid): array {
    $revision_statuses = [];

    $age = strtotime('-' . $this->configuration['age'] . 'months');

    foreach ($revision_ids as $vid) {
      $revision_id = $vid;
      // We are only interested in draft revisions older that the active
      // revision.
      $can_delete = NULL;
      if ($revision_id < $active_vid) {
        /** @var \Drupal\node\NodeInterface $revision */
        $revision = $this->entityTypeManager->getStorage('node')->loadRevision($revision_id);
        if (!$revision->isPublished()) {
          // The timestamp of the created revision is stored in the changed
          // field.
          $can_delete = $revision->getChangedTime() < $age;
        }
      }

      $revision_statuses[$revision_id] = $can_delete;
    }

    return $revision_statuses;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $options = [1 => '1 ' . $this->t('month')];
    for ($i = 2; $i <= 24; $i++) {
      $options[$i] = $i . ' ' . $this->t('months');
    }
    $form['age'] = [
      '#type' => 'select',
      '#title' => $this->t('The minimum amount of months an unpublished revision older than than active revision must be kept for'),
      '#description' => $this->t('After this time, unpublished revisions older than the active revision can be deleted. The minimum age of revisions is always respected, regardless of other settings.'),
      '#options' => $options,
      '#empty_value' => 0,
      '#empty_option' => '0 ' . $this->t('months'),
      '#default_value' => $this->configuration['age'] ?? 0,
    ];
    return $form;
  }

}

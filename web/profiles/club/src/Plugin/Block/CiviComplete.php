<?php

namespace Drupal\club\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'CiviComplete' Block.
 *
 * @Block(
 *   id = "club_civicomplete",
 *   admin_label = @Translation("CiviComplete"),
 *   category = @Translation("Club")
 * )
 */
class CiviComplete extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\club\Form\CiviComplete');
    return $form;
  }
}

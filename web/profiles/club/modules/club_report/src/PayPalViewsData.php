<?php

namespace Drupal\club_report;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Class implementing EntityViewsDataInterface exposes custom entity to views.
 * Reference this class in PayPal.php annotation handlers.
  */

class PayPalViewsData extends EntityViewsData implements EntityViewsDataInterface {

/**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['club_paypal']['contribution_id']['relationship'] = [
      'title' => $this->t('Contribution record'),
      'help' => $this->t('CiviCRM contribution record for this PayPal payment.'),
      // Table that we join with.
      'base' => 'civicrm_contribution',
      'base field' => 'id',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      // Default label for relationship in the UI.
      'label' => $this->t('CiviCRM contribution'),
    ];

    return $data;
  }
}

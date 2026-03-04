<?php

namespace Drupal\club_report;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface defining the club_paypal entity.
 *
 * We have this interface so that we can join the other interfaces it extends.
 *
 * @ingroup club
 */
interface PayPalInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface, EntityPublishedInterface {

}

<?php

namespace Drupal\club_ride;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface defining a bike_routes entity.
 *
 * We have this interface so that we can join the other interfaces it extends.
 *
 * @ingroup club
 */
interface ClubRouteInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface, EntityPublishedInterface {

}

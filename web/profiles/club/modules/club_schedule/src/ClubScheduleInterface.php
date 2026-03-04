<?php

namespace Drupal\club_schedule;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface defining a club_schedule entity.
 *
 * We have this interface so that we can join the other interfaces it extends.
 *
 * @ingroup club
 */
interface ClubScheduleInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface, EntityPublishedInterface {

}

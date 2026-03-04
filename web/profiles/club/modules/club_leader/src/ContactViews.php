<?php

namespace Drupal\club_leader;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Class implementing EntityViewsDataInterface exposes custom entity to views.
 * Reference this class in ClubContacts.php annotation under handlers: 
 *   "views_data" = "Drupal\club_leader\ContactViews",
 */

class ContactViews extends EntityViewsData implements EntityViewsDataInterface {
}

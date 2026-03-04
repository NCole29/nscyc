<?php

namespace Drupal\club_ride\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
  * Class RouteSubscriber.
  *
  * @package Drupal\club_ride\Routing
  */
  class RouteSubscriber extends RouteSubscriberBase {

   /**
    * {@inheritdoc}
    */
    protected function alterRoutes(RouteCollection $collection) {
      // 1) View replaces the default club_contacts listing -> force display in admin theme
	    // 2) Display advanced help topics in admin theme

      if ($route = $collection->get('view.club_contacts.admin') or
	      $route = $collection->get('advanced_help.help')) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

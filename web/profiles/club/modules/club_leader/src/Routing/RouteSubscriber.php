<?php

namespace Drupal\club_leader\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
  * Class RouteSubscriber.
  *
  * @package Drupal\club\Routing
  */
  class RouteSubscriber extends RouteSubscriberBase {

   /**
    * {@inheritdoc}
    */
    protected function alterRoutes(RouteCollection $collection) {
      // 1) View replaces the default leader listing -> force view to display in admin theme

      if ($route = $collection->get('view.club_contacts.admin')) {
            $route->setOption('_admin_route', TRUE);
      }
    }
  }

<?php

namespace Drupal\club_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
  * Class RouteSubscriber.
  *
  * @package Drupal\club_user\Routing
  */
  class RouteSubscriber extends RouteSubscriberBase {

   /**
    * {@inheritdoc}
    */
    protected function alterRoutes(RouteCollection $collection) {
      // Change path to personal contact form from '/user/{user}/contact' to '/contact/{user}'.
      // Remove "/user" because the User Account blocks appear in left sidebar for Pages with path "/user/*".
      if ($route = $collection->get('entity.user.contact_form')) {
        $route->setPath('/contact/{user}/contact');
      }
    }
  }

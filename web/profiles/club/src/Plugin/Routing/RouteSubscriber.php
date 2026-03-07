<?php

namespace Drupal\club\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
  * Class RouteSubscriber.
  *
  * @package Drupal\club\Routing
  */
class RouteSubscriber extends RouteSubscriberBase implements EventSubscriberInterface {

 /**
  * {@inheritdoc}
  */
  protected function alterRoutes(RouteCollection $collection) {

    // Alter the path to the personal contact form .
    if ($route = $collection->get('entity.user.contact_form')) {
      $route->setPath('/contact/{user}/contact');
    }

  }
}

<?php

namespace Drupal\views_local_tasks\Plugin\Derivative;

use Drupal\views\Plugin\Derivative\ViewsLocalTask;

/**
 * Provides local task definitions for all views configured as local tasks.
 */
class CustomViewsLocalTask extends ViewsLocalTask {

  /**
   * Alters base_route and parent_id into the views local tasks.
   */
  public function alterLocalTasks(&$local_tasks) {
    $view_route_names = $this->state->get('views.view_route_names');

    foreach ($this->getApplicableMenuViews() as $pair) {
      list($view_id, $display_id) = $pair;
      /** @var $executable \Drupal\views\ViewExecutable */
      $executable = $this->viewStorage->load($view_id)->getExecutable();

      $executable->setDisplay($display_id);
      $menu = $executable->display_handler->getOption('menu');

      // We already have set the base_route for default tabs.
      if (in_array($menu['type'], ['tab'])) {
        $plugin_id = 'view.' . $executable->storage->id() . '.' . $display_id;
        $view_route_name = $view_route_names[$executable->storage->id() . '.' . $display_id];

        // Don't add a local task for views which override existing routes.
        if ($view_route_name != $plugin_id) {
          unset($local_tasks[$plugin_id]);
          continue;
        }

        // Find out the parent route.
        // @todo Find out how to find both the root and parent tab.
        $path = $executable->display_handler->getPath();
        $split = explode('/', $path);
        array_pop($split);
        $path = implode('/', $split);

        $pattern = '/' . str_replace('%', '{}', $path);
        if ($routes = $this->routeProvider->getRoutesByPattern($pattern)) {
          foreach ($routes->all() as $name => $route) {
            $local_tasks['views_view:' . $plugin_id]['base_route'] = $name;

            // Create a local task for that view.
            if (!empty($menu['local_task_link_title'])) {
              $local_tasks['views_view_local_task:' . $plugin_id] = $local_tasks['views_view:' . $plugin_id];

              // Title of the local task.
              $local_tasks['views_view_local_task:' . $plugin_id]['title'] = $menu['local_task_link_title'];

              // Parent ID of the local task.
              if (isset($menu['local_task_parent'])) {
                $parent = NULL;
                if ($menu['local_task_parent'] === '_custom') {
                  if (!empty($menu['local_task_custom_parent_route'])) {
                    $parent = $menu['local_task_custom_parent_route'];
                  }
                }
                else {
                  $parent = $menu['local_task_parent'];
                }
                $local_tasks['views_view_local_task:' . $plugin_id]['parent_id'] = $parent;
              }

              // Weight of the local task.
              if (isset($menu['local_task_weight'])) {
                $local_tasks['views_view_local_task:' . $plugin_id]['weight'] = $menu['local_task_weight'];
              }
            }

            // Don't display in the menu if the link is local task only.
            if (!empty($menu['as_local_task']) && isset($local_tasks['views_view:' . $plugin_id])) {
              unset($local_tasks['views_view:' . $plugin_id]);
            }

            // Skip after the first found route.
            break;
          }
        }

      }
    }
  }

}

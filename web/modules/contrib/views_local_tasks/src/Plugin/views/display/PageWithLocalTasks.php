<?php

namespace Drupal\views_local_tasks\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\Page;
use Drupal\views\Views;

class PageWithLocalTasks extends Page {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'menu':
        $menu = $this->getOption('menu');

        // Only display the parent selector if Menu UI module is enabled.
        $menu_parent = $menu['menu_name'] . ':' . $menu['parent'];
        if (\Drupal::moduleHandler()->moduleExists('menu_ui')) {
          if (isset($form['menu']['context']['#suffix'])) {
            unset($form['menu']['context']['#suffix']);
          }

          $viewsMenuLinks = Views::getApplicableViews('uses_menu_links');
          $viewsExtraMenuLinks = [];
          foreach ($viewsMenuLinks as $viewsMenuLink) {
            $viewsExtraMenuLinks["views_view:view.{$viewsMenuLink[0]}.{$viewsMenuLink[1]}"] = "{$viewsMenuLink[0]} ({$viewsMenuLink[1]})";
          }

          $extraMenuOptions = [];
          $extraMenuOptions = $viewsExtraMenuLinks;
          $extraMenuOptions['_custom'] = $this->t('Custom');
          $form['menu']['parent']['#default_value'] = $menu_parent;

          $form['menu']['as_local_task'] = [
            '#type' => 'checkbox',
            '#default_value' => !empty($menu['as_local_task']),
            '#title' => $this->t('Local task only'),
            '#description' => $this->t('If there are at least 2 local tasks for the selected parent, the link will display in a local task instead of a menu item. If checked, the menu tab won\'t be created. Only a local task will be added to that page if the local task\'s title is filled.'),
            '#states' => [
              'visible' => [
                [
                  ':input[name="menu[type]"]' => ['value' => 'tab'],
                ],
              ],
            ],
          ];
          $form['menu']['local_task_link_title'] = [
            '#title' => $this->t('Local task link title'),
            '#type' => 'textfield',
            '#default_value' => isset($menu['local_task_link_title']) ?
              $menu['local_task_link_title'] :
              NULL,
            '#description' => $this->t('Leave empty for not creating local task.'),
            '#states' => [
              'visible' => [
                [
                  ':input[name="menu[type]"]' => ['value' => 'tab'],
                ],
              ],
            ],
          ];

          $form['menu']['local_task_parent'] = [
            '#type' => 'select',
            '#default_value' => isset($menu['local_task_parent']) ? $menu['local_task_parent'] : NULL,
            '#title' => $this->t('Local task parent'),
            '#options' => $extraMenuOptions,
            '#description' => $this->t('Every local task needs to be set to a parent. If it\'s a views page, then select the proper page. If the parent\'s ID is defined in a module\'s modulename.links.task.yml file, then you have to select the "Custom" option and type in the correct ID to the "Local task custom parent route" field. For example these are defined in a custom code:<br><ul><li>Content page: system.admin_content</li><li>Media page: entity.media.collection</li><li>Comment page: comment.admin</li></ul><a href="@link" target="_blank">Further details about local tasks.</a>', [
              '@link' => 'https://www.drupal.org/docs/drupal-apis/menu-api/providing-module-defined-local-tasks',
            ]),
            '#states' => [
              'visible' => [
                [
                  ':input[name="menu[type]"]' => ['value' => 'tab'],
                ],
              ],
            ],
          ];

          $form['menu']['local_task_custom_parent_route'] = [
            '#title' => $this->t('Local task custom parent route'),
            '#type' => 'textfield',
            '#default_value' => isset($menu['local_task_custom_parent_route']) ?
              $menu['local_task_custom_parent_route'] :
              NULL,
            '#states' => [
              'visible' => [
                [
                  ':input[name="menu[type]"]' => ['value' => 'tab'],
                  ':input[name="menu[local_task_parent]"]' => ['value' => '_custom'],
                ],
              ],
            ],
          ];

          $form['menu']['local_task_weight'] = [
            '#suffix' => '</div>',
            '#title' => $this->t('Local task weight'),
            '#type' => 'textfield',
            '#default_value' => isset($menu['local_task_weight']) ? $menu['local_task_weight'] : 0,
            '#size' => 5,
            '#states' => [
              'visible' => [
                ':input[name="menu[type]"]' => ['value' => 'tab'],
              ],
            ],
          ];
        }
        break;
    }
  }

}

<?php

namespace Drupal\permission_spreadsheet;

/**
 * Trait for loading non-admin roles.
 */
trait RoleLoaderTrait {

  /**
   * Loads all roles.
   *
   * @return \Drupal\user\Entity\Role[]
   *   An array containing roles and permissions.
   */
  protected function loadRoles(): array {
    return \Drupal::service('entity_type.manager')->getStorage('user_role')->loadMultiple();
  }

  /**
   * Loads non-admin roles.
   *
   * @return \Generator<\Drupal\user\Entity\Role>
   *   Generator of key-value pair containing role id and role entity.
   */
  protected function loadNonAdminRoles(): \Generator {
    foreach ($this->loadRoles() as $rid => $role) {
      if (!$role->isAdmin()) {
        yield $rid => $role;
      }
    }
  }

}

<?php

declare(strict_types=1);

namespace Drupal\civicrm_entity\Plugin\SectionStorage;

use Drupal\layout_builder\LayoutBuilderEnabledInterface;
use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage;

/**
 * Defaults section storage with civicrm_entity dynamic-bundle support.
 *
 * Subclasses core's "defaults" plugin to widen ::isSupported() so that
 * Layout Builder accepts civicrm_entity-exposed dynamic bundles whose
 * canonical layout lives on the generic-bundle display.
 *
 * civicrm_entity exposes per-CiviCRM-type bundles dynamically (e.g.
 * civicrm_event.cl_education_conference). The canonical Layout Builder
 * layout lives on the generic-bundle display (civicrm_event.civicrm_event
 * .default), and {@see civicrm_entity_entity_view_display_alter()} copies
 * it onto per-bundle displays at runtime.
 *
 * As of drupal:11.3.0, {@see \Drupal\layout_builder\SectionStorage\
 * SupportAwareSectionStorageManagerInterface::notSupported()} is invoked
 * from {@see \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay
 * ::buildMultiple()} before any rendering; it asks each section storage
 * plugin whether the (entity type, bundle, view mode) is supported. The
 * parent's ::isSupported() does a strict config-storage lookup that
 * bypasses the alter hook, so dynamic bundles are reported as unsupported
 * and Layout Builder rendering is skipped.
 *
 * This subclass keeps the parent's check first (so non-civicrm_entity
 * entity types are unaffected) and then falls back to consulting the
 * generic-bundle display when the entity type is exposed by
 * civicrm_entity.
 *
 * Wiring is done by {@see \Drupal\civicrm_entity\Hook\SectionStorageHooks
 * ::sectionStorageAlter()} which replaces the class on the existing
 * "defaults" plugin definition — but only on drupal:11.3.0+, where the
 * support-aware gate exists. On earlier versions this class is never
 * activated.
 *
 * @internal
 *   Plugin classes are internal. This class intentionally extends an
 *   @internal core class; civicrm_entity maintains compatibility on bumps.
 */
final class CivicrmEntityDefaultsSectionStorage extends DefaultsSectionStorage {

  /**
   * {@inheritdoc}
   */
  public function isSupported(string $entity_type_id, string $bundle, string $view_mode): bool {
    // Non-civicrm_entity entity types behave exactly like the parent.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);
    if (!$entity_type || !$entity_type->get('civicrm_entity')) {
      return parent::isSupported($entity_type_id, $bundle, $view_mode);
    }

    // civicrm_entity entity types: first try the parent's strict check
    // (works for any bundle/view mode combo with a saved display).
    if (parent::isSupported($entity_type_id, $bundle, $view_mode)) {
      return TRUE;
    }

    // Per-bundle displays are not persisted for civicrm_entity dynamic
    // bundles; their layout is propagated from the generic-bundle display
    // at runtime via hook_entity_view_display_alter(). Consult that
    // display (always at the "default" view mode — the canonical source
    // of truth) to determine whether Layout Builder is enabled,
    // regardless of the view mode being rendered.
    $generic = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load(sprintf('%s.%s.default', $entity_type_id, $entity_type_id));

    return $generic instanceof LayoutBuilderEnabledInterface
      && $generic->isLayoutBuilderEnabled();
  }

}

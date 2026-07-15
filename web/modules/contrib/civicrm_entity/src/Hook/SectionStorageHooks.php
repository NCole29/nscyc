<?php

declare(strict_types=1);

namespace Drupal\civicrm_entity\Hook;

use Drupal\civicrm_entity\Plugin\SectionStorage\CivicrmEntityDefaultsSectionStorage;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Layout Builder section storage hook implementations for civicrm_entity.
 */
final class SectionStorageHooks {

  /**
   * The Drupal core version that introduced the support-aware gate.
   *
   * SupportAwareSectionStorageManagerInterface::notSupported() was added in
   * drupal:11.3.0 and is invoked from LayoutBuilderEntityViewDisplay
   * ::buildMultiple() to decide whether Layout Builder should render. The
   * civicrm_entity subclass only exists to satisfy that gate for dynamic
   * bundles, so it is pointless — and the override semantics are different —
   * on older versions where the gate does not exist.
   */
  private const SUPPORT_GATE_VERSION = '11.3.0';

  /**
   * Replaces the "defaults" section storage class with the civicrm_entity
   * subclass so the dynamic-bundle support fallback applies.
   *
   * Only takes effect on drupal:11.3.0+, where the support-aware gate exists.
   * On earlier versions core's behaviour is correct as-is and the class is
   * left untouched.
   *
   * @see \Drupal\civicrm_entity\Plugin\SectionStorage\CivicrmEntityDefaultsSectionStorage
   */
  #[Hook('layout_builder_section_storage_alter')]
  public function sectionStorageAlter(array &$definitions): void {
    if (!version_compare(\Drupal::VERSION, self::SUPPORT_GATE_VERSION, '>=')) {
      return;
    }
    if (isset($definitions['defaults'])) {
      $definitions['defaults']->setClass(CivicrmEntityDefaultsSectionStorage::class);
    }
  }

}

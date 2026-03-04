<?php

namespace Drupal\club\Plugin\Linkit\Substitution;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\linkit\SubstitutionInterface;
use Drupal\Core\GeneratedUrl;


/**
 * A substitution plugin - change the canonical URL of a user entity to point to contact form.
 *
 * @Substitution(
 *   id = "canonical",
 *   label = @Translation("Canonical URL"),
 * )
 */
class Canonical extends PluginBase implements SubstitutionInterface {

  /**
   * {@inheritdoc}
   */
  public function getUrl(EntityInterface $entity) {

    if ($entity->getEntityTypeId() == 'user') {
      $user = $entity->toUrl('canonical')->toString();
      $user .= '/contact';

      $url = new GeneratedUrl();
      $url->setGeneratedUrl($user);
      $url->addCacheableDependency($entity);

      return $url;
    }
    else {
      return $entity->toUrl('canonical')->toString(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->hasLinkTemplate('canonical');
  }

}

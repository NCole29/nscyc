<?php

namespace Drupal\tooltip_taxonomy\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete an Example.
 */
class FilterConditionDeleteForm extends EntityConfirmFormBase {

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * Constructs a new FilterConditionDeleteForm.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   */
  public function __construct(CacheTagsInvalidatorInterface $cacheTagsInvalidator) {
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name ?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Form\ConfirmFormInterface::getCancelUrl()
   */
  public function getCancelUrl() {
    return new Url('entity.tooltip_taxonomy.config');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Invalidate the cache tag for this condition.
    $this->cacheTagsInvalidator->invalidateTags(['tooltip_taxonomy:' . $this->entity->id()]);

    $this->entity->delete();
    $this->messenger()->addMessage($this->t('%label has been deleted.', ['%label' => $this->entity->label()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}

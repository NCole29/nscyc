<?php

namespace Drupal\club_leader;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Link;
/**
 * Provides a list controller for club_contacts entity.
 *
 * @ingroup club
 */
class ContactListBuilder extends EntityListBuilder {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a new ContactListBuilder object - Pass 3 arguments to createInstance().
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $storage);

    $this->urlGenerator = $url_generator;
  }

  /*
   * We extend EntityListBuilder which has createInstance, but we need it here because we add url_generator to the container
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('url_generator')
    );
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('<p>Use Operations column to edit/delete contact record. Click name to view/edit Drupal user account.</p><p>Drupal role is automatically assigned to user, and cleared, based on position and end date. See default role per position at left.
       Additional roles may be assigned but must be cleared manually from the Drupal user account page which is accessed via the hyperlink on "Name."',
       ['@adminlink' => $this->urlGenerator->generateFromRoute('club_leader.contact_settings'),
      ]),
    ];
    $build['table'] = parent::render();

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for operations
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    //$header['contact_id'] = $this->t('Contact ID');
    $header = [
      'name' =>  $this->t('Name'),
      'position' => $this->t('Club Position'),
      'role' =>  $this->t('Drupal Roles'),
      'start_date' => $this->t('Start Date'),
      'end_date' => $this->t('End Date'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $user = $entity->contact->entity;
    $roles = NULL; // Initialize variables that are NULL if person is not in database.
    $link = NULL;

    // If person in database, link Name to user record and get roles
    if (!empty($user)) {

      $userid = $entity->contact->target_id;
      $link = Url::fromRoute('entity.user.canonical', ['user' => $userid]);

      // Get roles excluding "authenticated"
      $roles = implode(", ",$user->getRoles());
      $roles = str_replace("authenticated, ","",$roles);
      $roles = str_replace("authenticated","",$roles);

      $row['name']['data'] = [
        '#type' => 'link',
        '#title' => $entity->name->value,
        '#url' => $link,
      ];
    } else {
      $row['name']['data'] = $entity->name->value;
    }
    $row['position'] = $entity->position->entity->label();
    $row['role'] = $roles;
    $row['start_date'] = $entity->start_date->value;
    $row['end_date'] = $entity->end_date->value;

    return $row + parent::buildRow($entity);
  }
}

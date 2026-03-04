<?php

namespace Drupal\club_leader\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\club_leader\ContactInterface;


/**
 * Defines the 'club_contacts' entity type.
 *
 *
 * @ContentEntityType(
 *   id = "club_contacts",
 *   label = @Translation("Club leaders"),
 *   base_table = "club_contacts",
 *   entity_keys = {
 *     "id" = "contact_id",
 *     "uuid" = "uuid",
 *     "owner" = "author",
 *     "published" = "published",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\club_leader\ContactListBuilder",
 *     "views_data" = "Drupal\club_leader\ContactViews",
 *     "form" = {
 *       "default" = "Drupal\club_leader\Form\ContactForm",
 *       "delete" = "Drupal\club_leader\Form\ContactDeleteForm",
 *     },
 *     "access" = "Drupal\club_leader\ContactAccessControlHandler",
 *   },
 *   admin_permission = "administer club leader fields",
 *   links = {
 *     "canonical" =   "/admin/structure/club_contacts/{club_contacts}",
 *     "edit-form" =   "/admin/structure/club_contacts/{club_contacts}/edit",
 *     "delete-form" = "/admin/structure/club_contacts/{club_contacts}/delete",
 *     "collection" =  "/admin/structure/club_contacts/list"
 *   },
 *   field_ui_base_route = "club_leader.contact_settings",
 *   list_cache_contexts = { "user" },
 * )
 *
 */
class ClubContacts extends ContentEntityBase implements ContactInterface {

  use EntityChangedTrait, EntityOwnerTrait, EntityPublishedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['contact_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Contact ID'))
      ->setDescription(t('The ID of the Contact entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Contact entity.'))
      ->setReadOnly(TRUE);

    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Feed ID'))
      ->setDescription(t('Unique ID for feeds import.'))
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    // contact (autocomplete field) - select person and store ID
    // Entity reference field, holds the reference to the user object:
  	//  - used to link contact form for CURRENT contacts
    //  - used to fill Member Name field (to retain name if user is deleted)
    $fields['contact'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Name'))
      ->setDescription(t('Enter name and select from list of club members.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', array(
        'include_anonymous' => FALSE,
      ))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'match_limit' => 10,
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Member name, saved as text
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Edited name'))
      ->setDescription(t('Name: uploaded or autofilled.'))
      ->setSettings([
        'max_length' => 50,
    		'placeholder' => 'Filled from contact field',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -3,
          ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Club position
    $fields['position'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Club position'))
      ->setDescription(t('Officer or coordinator position.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'views')
      ->setSetting('handler_settings', [
        'view' => [
          'view_name' => 'club_positions',
          'display_name' => 'not_eliminated',
          'arguments' => [],
        ]
      ])
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'string',
        'weight' => -1,
        ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -1,
          ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Year of start date
    $fields['year'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Year'))
      ->setDescription(t('Year of start date.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'integer',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);
            
    // Owner field of the contact.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('Author.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

      $fields['board_term'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Board term'))
      ->setDescription(t('Enter year(s) of term - e.g., "2022" or "2022 to 2024"'))
      ->setSettings([
        'max_length' => 25,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 0,
          ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of ContentEntityExample entity.'));
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields += static::publishedBaseFieldDefinitions($entity_type);

    return $fields;
  }

  }

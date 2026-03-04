<?php

namespace Drupal\club_report\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\club_report\PayPalInterface;

/**
 * Defines the 'club_paypal' entity type.
 *
 * @ContentEntityType(
 *   id = "club_paypal",
 *   label = @Translation("Club: PayPal notifications"),
 *   base_table = "club_paypal",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "author",
 *     "published" = "published",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\club_report\PayPalListBuilder",
 *     "views_data" = "Drupal\club_report\PayPalViewsData",
 *     "form" = {
 *       "default" = "Drupal\club_report\Form\PayPalForm",
 *     },
 *     "access" = "Drupal\club_report\PayPalAccessControlHandler",
 *   },
 *   admin_permission = "administer Club PayPal data",
 *   links = {
 *     "canonical" = "/admin/structure/club_paypal/{club_paypal}",
 *     "edit-form" = "/admin/structure/club_paypal/{club_paypal}/edit",
 *     "collection" = "/admin/structure/club_paypal/list"
 *   },
 *   field_ui_base_route = "club_report.paypal_settings",
 * )
 *
 */
class PayPal extends ContentEntityBase implements PayPalInterface {

  use EntityChangedTrait, EntityOwnerTrait, EntityPublishedTrait;
  
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

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID from CiviCRM System Log'))
      ->setDescription(t('Source: civicrm_system_log.'))
      ->setDisplayConfigurable('view', TRUE);

    // Standard field, unique.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the route entity.'))
      ->setReadOnly(TRUE);

    // Fields extracted from civicrm_system_log JSON field ('context')  
    $fields['contact_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Contact ID'))
      ->setDescription(t('CiviCRM Contact ID.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE);

      $fields['contribution_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Contribution ID'))
      ->setDescription(t('Contribution ID.'))
      ->setDisplayOptions('view', array(
          'label' => 'inline',
          'type' => 'integer',
          'weight' => 9,
        ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Payer name.'))
      ->setDisplayOptions('view', array(
          'label' => 'inline',
          'type' => 'string',
          'weight' => 2,
        ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('Payer email.'))
      ->setDisplayOptions('view', array(
          'label' => 'inline',
          'type' => 'email',
          'weight' => 3,
        ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['item'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Item'))
      ->setDescription(t('Item name.'))
      ->setDisplayOptions('view', array(
          'label' => 'inline',
          'type' => 'string',
          'weight' => 6,
        ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Payment amount'))
      ->setDescription(t('Payment amount.'))
      ->setDisplayOptions('view', array(
          'label' => 'inline',
          'type' => 'decimal',
          'weight' => 7,
        ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('PayPal transaction status.'))
      ->setDisplayOptions('view', array(
          'label' => 'inline',
          'type' => 'string',
          'weight' => 8,
        ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Payment date'))
      ->setDescription(t('Date payment was made through website.'))
      ->setSettings([
        'datetime_type' => 'datetime',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'long',
        ]
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['log_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Log date'))
      ->setDescription(t('Date the PayPal notification was logged by CiviCRM.'))
      ->setSettings([
        'datetime_type' => 'datetime',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'long',
        ]
      ])
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

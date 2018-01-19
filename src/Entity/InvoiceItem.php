<?php

namespace Drupal\commerce_order_invoice\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the order item entity class.
 *
 * @ContentEntityType(
 *   id = "invoice_item",
 *   label = @Translation("Invoice item"),
 *   label_singular = @Translation("invoice item"),
 *   label_plural = @Translation("invoice items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count invoice item",
 *     plural = "@count invoice items",
 *   ),
 *   bundle_label = @Translation("order item type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_order_invoice\Event\InvoiceItemEvent",
 *     "storage" = "Drupal\commerce_order_invoice\InvoiceItemStorage",
 *     "access" = "Drupal\commerce\EmbeddedEntityAccessControlHandler",
 *     "views_data" = "Drupal\commerce_order_invoice\InvoiceItemViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "inline_form" = "Drupal\commerce_order_invoice\Form\InvoiceItemInlineForm",
 *   },
 *   base_table = "invoice_item",
 *   admin_permission = "administer commerce_order_invoice",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "invoice_item_id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "title",
 *   },
 *   bundle_entity_type = "commerce_order_item_type",
 *   field_ui_base_route = "entity.commerce_order_item_type.edit_form",
 * )
 */
class InvoiceItem extends OrderItem {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getInvoice() {
    return $this->get('invoice_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoiceId() {
    return $this->get('invoice_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = CommerceContentEntityBase::baseFieldDefinitions($entity_type);

    // The order backreference, populated by Order::postSave().
    $fields['invoice_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Invoice'))
      ->setDescription(t('The parent invoice.'))
      ->setSetting('target_type', 'invoice')
      ->setReadOnly(TRUE);

    $fields['purchased_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Purchased entity'))
      ->setDescription(t('The purchased entity.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The order item title.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 512,
      ]);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The number of purchased units.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 0)
      ->setDefaultValue(1)
      ->setDisplayOptions('form', [
        'type' => 'commerce_quantity',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Unit price'))
      ->setDescription(t('The price of a single unit.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_unit_price',
        'weight' => 2,
        'settings' => [
          'require_confirmation' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['overridden_unit_price'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Overridden unit price'))
      ->setDescription(t('Whether the unit price is overridden.'))
      ->setDefaultValue(FALSE);

    $fields['adjustments'] = BaseFieldDefinition::create('commerce_adjustment')
      ->setLabel(t('Adjustments'))
      ->setRequired(FALSE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total price'))
      ->setDescription(t('The total price of the order item.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the order item was created.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the order item was last edited.'))
      ->setRequired(TRUE);

    return $fields;
  }

}

<?php

namespace Drupal\commerce_order_invoice\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Invoice entity.
 *
 * @ingroup commerce_order_invoice
 *
 * @ContentEntityType(
 *   id = "invoice",
 *   label = @Translation("Invoice"),
 *   label_singular = @Translation("invoice"),
 *   label_plural = @Translation("invoices"),
 *   label_count = @PluralTranslation(
 *     singular = "@count invoice",
 *     plural = "@count invoices",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_order_invoice\InvoiceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_order_invoice\Form\InvoiceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "permission_provider" =
 *   "Drupal\commerce_order_invoice\InvoicePermissionProvider",
 *     "access" = "Drupal\commerce_order_invoice\InvoiceAccessControlHandler",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "invoice",
 *   admin_permission = "administer commerce_order_invoice",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "invoice_number",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/invoices/{invoice}",
 *     "add-form" = "/admin/commerce/invoices/create",
 *     "edit-form" = "/admin/commerce/invoices/{invoice}/edit",
 *     "delete-form" = "/admin/commerce/invoices/{invoice}/delete",
 *     "collection" = "/admin/commerce/invoices",
 *   },
 *   field_ui_base_route = "invoice.settings"
 * )
 */
class Invoice extends Order implements InvoiceInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * Gets the label for the invoice.
   *
   * @return string
   *   The label as string.
   */
  public function label() {
    return $this->isLocked() ? t('Invoice %invoicenumber', ['%invoicenumber' => $this->getInvoiceNumber()]) : t('Draft invoice');
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
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
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    unset($fields['state'], $fields['order_number'], $fields['total_price'], $fields['billing_profile']);

    $fields['billing_profile'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Billing information'))
      ->setDescription(t('Billing profile'))
      ->setSetting('target_type', 'profile')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['customer']])
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'invoice_billing_profile',
        'weight' => -10,
        'settings' => [],
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_items'] = BaseFieldDefinition::create('entity_reference')
      ->setName('invoice_items')
      ->setLabel('Invoice items')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'invoice_item')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
        'weight' => -9,
        'settings' => [
          'override_labels' => TRUE,
          'label_singular' => 'invoice item',
          'label_plural' => 'invoice items',
        ],
      ])
      ->setDisplayOptions('view', [
        'type' => 'invoice_item_table',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', FALSE);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Commerce order'))
      ->setDescription(t('The commerce order to associate to this invoice. If you want to add order items to an invoice, click the invoice button on the order.'))
      ->setSetting('target_type', 'commerce_order')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Invoice entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Invoice number'))
      ->setDescription(t('The invoice number, formatted.'))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ]);

    $fields['invoice_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Invoice date'))
      ->setDescription(t('The invoice date.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -11,
      ]);

    // Below are overridden fields from the order entity.
    $fields['total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total price'))
      ->setDescription(t('The total price of the invoice.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'invoice_total_summary',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->get('invoice_items')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setItems(array $invoice_items) {
    $this->set('invoice_items', $invoice_items);
    $this->recalculateTotalPrice();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoiceNumber() {
    return $this->get('invoice_number')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoiceTimestamp() {
    return $this->get('invoice_date')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    if ($orders = $this->get('order_id')->referencedEntities()) {
      return $orders[0];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * This is a hard validation avoiding double invoices at all cost.
   */
  public function preSave(EntityStorageInterface $storage) {
    CommerceContentEntityBase::preSave($storage);
    $this->setRefreshState(self::REFRESH_ON_SAVE);
    $this->recalculateTotalPrice();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    CommerceContentEntityBase::postSave($storage, $update);

    // Ensure there's a back-reference on each order item.
    foreach ($this->getItems() as $invoice_item) {
      if ($invoice_item->invoice_id->isEmpty()) {
        $invoice_item->invoice_id = $this->id();
        $invoice_item->save();
      }
    }
  }

  /**
   * Gets the invoice date.
   *
   * @return string
   *   The formatted invoice date.
   */
  public function getInvoiceDate() {
    return \Drupal::service('date.formatter')
      ->format($this->getInvoiceTimestamp(), 'html_date');
  }

  /**
   * Unlocks an invoice.
   *
   * As invoices cannot be modified, we disallow unlocking.
   */
  public function unlock() {
    drupal_set_message(t('Confirmed invoices can not be unlocked.'), 'error');
    return FALSE;
  }

  /**
   * Deletes an invoice.
   *
   * As confirmed invoices cannot be deleted, we avoid it.
   */
  public function delete() {
    if ($this->isLocked()) {
      drupal_set_message(t('Confirmed invoices can not be deleted.'), 'error');
      return NULL;
    }
    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function hasItems() {
    return !$this->get('invoice_items')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(OrderItemInterface $invoice_item) {
    if (!$this->hasItem($invoice_item)) {
      $this->get('invoice_items')->appendItem($invoice_item);
      $this->recalculateTotalPrice();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem(OrderItemInterface $invoice_item) {
    $index = $this->getItemIndex($invoice_item);
    if ($index !== FALSE) {
      $this->get('invoice_items')->offsetUnset($index);
      $this->recalculateTotalPrice();
    }
    return $this;
  }

  /**
   * Gets the index of the given invoice item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $invoice_item
   *   The order item.
   *
   * @return int|bool
   *   The index of the given order item, or FALSE if not found.
   */
  protected function getItemIndex(OrderItemInterface $invoice_item) {
    $values = $this->get('invoice_items')->getValue();
    $invoice_item_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($invoice_item->id(), $invoice_item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    CommerceContentEntityBase::postDelete($storage, $entities);

    // Delete the order items of a deleted order.
    $order_items = [];
    /** @var \Drupal\commerce_order_invoice\Entity\InvoiceInterface $entity */
    foreach ($entities as $entity) {
      foreach ($entity->getItems() as $order_item) {
        $order_items[$order_item->id()] = $order_item;
      }
    }
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::service('entity_type.manager')
      ->getStorage('invoice_item');
    $order_item_storage->delete($order_items);
  }

}

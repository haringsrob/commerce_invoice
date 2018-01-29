<?php

namespace Drupal\commerce_invoice\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Invoice entity.
 *
 * @ingroup commerce_invoice
 *
 * @ContentEntityType(
 *   id = "commerce_invoice",
 *   label = @Translation("Invoice"),
 *   label_singular = @Translation("invoice"),
 *   label_plural = @Translation("invoices"),
 *   label_count = @PluralTranslation(
 *     singular = "@count invoice",
 *     plural = "@count invoices",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_invoice\InvoiceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_invoice\Form\InvoiceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "permission_provider" =
 *   "Drupal\commerce_invoice\InvoicePermissionProvider",
 *     "access" = "Drupal\commerce_invoice\InvoiceAccessControlHandler",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_invoice",
 *   admin_permission = "administer commerce_invoice",
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
 *     "canonical" = "/admin/commerce/invoices/{commerce_invoice}",
 *     "add-form" = "/admin/commerce/invoices/create",
 *     "edit-form" = "/admin/commerce/invoices/{commerce_invoice}/edit",
 *     "delete-form" = "/admin/commerce/invoices/{commerce_invoice}/delete",
 *     "collection" = "/admin/commerce/invoices",
 *   },
 *   field_ui_base_route = "commerce_invoice.settings"
 * )
 */
class Invoice extends CommerceContentEntityBase implements InvoiceInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(
    EntityStorageInterface $storage_controller,
    array &$values
  ) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['store_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store'))
      ->setDescription(t('The store to which the order belongs.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Customer'))
      ->setDescription(t('The customer.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback(
        'Drupal\commerce_order\Entity\Order::getCurrentUserId'
      )
      ->setTranslatable(TRUE)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'author',
          'weight' => 0,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Contact email'))
      ->setDescription(t('The email address associated with the order.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'string',
          'weight' => 0,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP address'))
      ->setDescription(t('The IP address of the order.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 128)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'string',
          'weight' => 0,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'hidden',
          'weight' => 0,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_profile'] = BaseFieldDefinition::create(
      'entity_reference_revisions'
    )
      ->setLabel(t('Billing information'))
      ->setDescription(t('Billing profile'))
      ->setSetting('target_type', 'profile')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['customer']])
      ->setTranslatable(TRUE)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'invoice_billing_profile',
          'weight' => -10,
          'settings' => [],
        ]
      )
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
      ->setDisplayOptions(
        'form',
        [
          'type' => 'inline_entity_form_complex',
          'weight' => -9,
          'settings' => [
            'override_labels' => TRUE,
            'label_singular' => 'invoice item',
            'label_plural' => 'invoice items',
          ],
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'type' => 'invoice_item_table',
          'weight' => 0,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Commerce order'))
      ->setDescription(
        t(
          'The commerce order to associate to this invoice. If you want to add order items to an invoice, click the invoice button on the order.'
        )
      )
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
      ->setDisplayOptions(
        'form',
        [
          'type' => 'datetime_timestamp',
          'weight' => 10,
        ]
      );

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the order was last edited.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['placed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Placed'))
      ->setDescription(t('The time when the order was placed.'))
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'timestamp',
          'weight' => 0,
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Completed'))
      ->setDescription(t('The time when the order was completed.'))
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'timestamp',
          'weight' => 0,
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Invoice date'))
      ->setDescription(t('The invoice date.'))
      ->setSettings(['datetime_type' => 'date'])
      ->setDisplayOptions(
        'form',
        [
          'type' => 'datetime_default',
          'weight' => -11,
        ]
      )
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_due_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Invoice due date'))
      ->setDescription(t('The invoice due date.'))
      ->setSettings(['datetime_type' => 'date'])
      ->setDisplayOptions(
        'form',
        [
          'type' => 'datetime_default',
          'weight' => -11,
          'description' => t(
            'The due date of the invoice, defaults to the invoice date + the amount of days configured in the settings'
          ),
        ]
      )
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['adjustments'] = BaseFieldDefinition::create('commerce_adjustment')
      ->setLabel(t('Adjustments'))
      ->setRequired(FALSE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'commerce_adjustment_default',
          'weight' => 0,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Below are overridden fields from the order entity.
    $fields['total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total price'))
      ->setDescription(t('The total price of the invoice.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'hidden',
          'type' => 'invoice_total_summary',
          'weight' => 0,
        ]
      )
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['locked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Locked'))
      ->setSettings(
        [
          'on_label' => t('Yes'),
          'off_label' => t('No'),
        ]
      )
      ->setDefaultValue(FALSE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * This is a hard validation avoiding double invoices at all cost.
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->recalculateTotalPrice();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a back-reference on each order item.
    foreach ($this->getItems() as $invoice_item) {
      if ($invoice_item->invoice_id->isEmpty()) {
        $invoice_item->invoice_id = $this->id();
        $invoice_item->save();
      }
    }
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
  public static function postDelete(
    EntityStorageInterface $storage,
    array $entities
  ) {
    CommerceContentEntityBase::postDelete($storage, $entities);

    // Delete the order items of a deleted order.
    $order_items = [];
    /** @var \Drupal\commerce_invoice\Entity\InvoiceInterface $entity */
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

  /**
   * Gets the label for the invoice.
   *
   * @return string
   *   The label as string.
   */
  public function label() {
    return $this->isLocked() ? t(
      'Invoice %invoicenumber',
      ['%invoicenumber' => $this->getInvoiceNumber()]
    ) : t('Draft invoice');
  }

  /**
   * {@inheritdoc}
   */
  public function lock() {
    $this->set('locked', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return !empty($this->get('locked')->value);
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
   * {@inheritdoc}
   */
  public function getInvoiceNumber() {
    return $this->get('invoice_number')->getString();
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
  public function setItems(array $invoice_items) {
    $this->set('invoice_items', $invoice_items);
    $this->recalculateTotalPrice();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function recalculateTotalPrice() {
    /** @var \Drupal\commerce_price\Price $total_price */
    $total_price = NULL;
    if ($this->hasItems()) {
      foreach ($this->getItems() as $order_item) {
        $order_item_total = $order_item->getTotalPrice();
        $total_price = $total_price ? $total_price->add(
          $order_item_total
        ) : $order_item_total;
      }
      foreach ($this->collectAdjustments() as $adjustment) {
        if (!$adjustment->isIncluded()) {
          $total_price = $total_price->add($adjustment->getAmount());
        }
      }
    }
    $this->total_price = $total_price;

    return $this;
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
  public function getItems() {
    return $this->get('invoice_items')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function collectAdjustments() {
    $adjustments = [];
    foreach ($this->getItems() as $order_item) {
      foreach ($order_item->getAdjustments() as $adjustment) {
        // Order item adjustments apply to the unit price, they
        // must be multiplied by quantity before they are used.
        $multiplied_adjustment = new Adjustment(
          [
            'type' => $adjustment->getType(),
            'label' => $adjustment->getLabel(),
            'amount' => $adjustment->getAmount()
              ->multiply($order_item->getQuantity()),
            'percentage' => $adjustment->getPercentage(),
            'source_id' => $adjustment->getSourceId(),
            'included' => $adjustment->isIncluded(),
            'locked' => $adjustment->isLocked(),
          ]
        );
        $adjustments[] = $multiplied_adjustment;
      }
    }
    foreach ($this->getAdjustments() as $adjustment) {
      $adjustments[] = $adjustment;
    }

    return $adjustments;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustments() {
    return $this->get('adjustments')->getAdjustments();
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
   */
  public function getInvoiceDateString() {
    return $this->get('invoice_date')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoiceDueDateString() {
    return $this->get('invoice_due_date')->getString();
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
    $invoice_item_ids = array_map(
      function ($value) {
        return $value['target_id'];
      },
      $values
    );

    return array_search($invoice_item->id(), $invoice_item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getStore() {
    return $this->getTranslatedReferencedEntity('store_id');
  }

  /**
   * {@inheritdoc}
   */
  public function setStore(StoreInterface $store) {
    $this->set('store_id', $store->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreId() {
    return $this->get('store_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreId($store_id) {
    $this->set('store_id', $store_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomer() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomer(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($mail) {
    $this->set('mail', $mail);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIpAddress() {
    return $this->get('ip_address')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setIpAddress($ip_address) {
    $this->set('ip_address', $ip_address);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlacedTime() {
    return $this->get('placed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlacedTime($timestamp) {
    $this->set('placed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletedTime() {
    return $this->get('completed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompletedTime($timestamp) {
    $this->set('completed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtotalPrice() {
    /** @var \Drupal\commerce_price\Price $subtotal_price */
    $subtotal_price = NULL;
    if ($this->hasItems()) {
      foreach ($this->getItems() as $order_item) {
        $order_item_total = $order_item->getTotalPrice();
        $subtotal_price = $subtotal_price ? $subtotal_price->add(
          $order_item_total
        ) : $order_item_total;
      }
    }
    return $subtotal_price;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPrice() {
    if (!$this->get('total_price')->isEmpty()) {
      return $this->get('total_price')->first()->toPrice();
    }
  }

}

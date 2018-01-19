<?php

namespace Drupal\commerce_order_invoice;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\OrderItemStorage;

/**
 * Defines the order item storage.
 */
class InvoiceItemStorage extends OrderItemStorage {

  /**
   * {@inheritdoc}
   */
  public function createFromPurchasableEntity(PurchasableEntityInterface $entity, array $values = []) {
    $values += [
      'type' => $entity->getOrderItemTypeId(),
      'title' => $entity->getOrderItemTitle(),
      'purchased_entity' => $entity,
      'unit_price' => $entity->getPrice(),
    ];
    return self::create($values);
  }

}

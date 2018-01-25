<?php

namespace Drupal\commerce_invoice\Entity;

use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Provides an interface for defining Invoice entities.
 *
 * @ingroup commerce_invoice
 */
interface InvoiceInterface {

  /**
   * Gets the Invoice creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Invoice.
   */
  public function getCreatedTime();

  /**
   * Sets the Invoice creation timestamp.
   *
   * @param int $timestamp
   *   The Invoice creation timestamp.
   *
   * @return \Drupal\commerce_invoice\Entity\InvoiceInterface
   *   The called Invoice entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the order for the invoice.
   *
   * @return \Drupal\commerce_order\Entity\Order
   *   The order.
   */
  public function getOrder();

  /**
   * Gets the invoice date.
   *
   * @return string
   *   The formatted invoice date.
   */
  public function getInvoiceDate();

  /**
   * Collects all adjustments that belong to the order.
   *
   * Unlike getAdjustments() which returns only order adjustments,
   * this method returns both order and order item adjustments.
   *
   * Important:
   * The returned order item adjustments are multiplied by quantity,
   * so that they can be safely added to the order adjustments.
   *
   * @return \Drupal\commerce_order\Adjustment[]
   *   The adjustments.
   */
  public function collectAdjustments();

  /**
   * Gets the order subtotal price.
   *
   * Represents a sum of all order item totals.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order subtotal price, or NULL.
   */
  public function getSubtotalPrice();

  /**
   * Gets the order total price.
   *
   * Represents a sum of all order item totals along with adjustments.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order total price, or NULL.
   */
  public function getTotalPrice();

  /**
   * Gets the order items.
   *
   * @return \Drupal\commerce_invoice\Entity\InvoiceItem[]
   *   The order items.
   */
  public function getItems();

  /**
   * Gets the store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface|null
   *   The store entity, or null.
   */
  public function getStore();

  /**
   * Sets the store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store entity.
   *
   * @return $this
   */
  public function setStore(StoreInterface $store);

  /**
   * Gets the store ID.
   *
   * @return int
   *   The store ID.
   */
  public function getStoreId();

  /**
   * Sets the store ID.
   *
   * @param int $store_id
   *   The store ID.
   *
   * @return $this
   */
  public function setStoreId($store_id);

  /**
   * Gets the customer user ID.
   *
   * @return int|null
   *   The customer user ID, or NULL in case the order is anonymous.
   */
  public function getCustomerId();
}

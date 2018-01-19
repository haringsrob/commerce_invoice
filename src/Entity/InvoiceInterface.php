<?php

namespace Drupal\commerce_order_invoice\Entity;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides an interface for defining Invoice entities.
 *
 * @ingroup commerce_order_invoice
 */
interface InvoiceInterface extends OrderInterface {

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
   * @return \Drupal\commerce_order_invoice\Entity\InvoiceInterface
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

}

<?php

namespace Drupal\commerce_order_invoice;

/**
 * Class InvoiceInterface.
 *
 * @package Drupal\commerce_order_invoice
 */
interface InvoiceBaseInterface {

  /**
   * Gets the invoice number.
   *
   * @return int
   *   The raw invoice number.
   */
  public function getInvoiceNumber();

  /**
   * Gets the invoice timestamp.
   *
   * @return int
   *   The invoicing date timestamp.
   */
  public function getInvoiceTimestamp();

}

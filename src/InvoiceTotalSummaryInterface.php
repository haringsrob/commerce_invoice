<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce_invoice\Entity\InvoiceInterface;

interface InvoiceTotalSummaryInterface {

  /**
   * Builds the totals for the given invoice.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice
   *   The invoice.
   *
   * @return array
   *   An array of totals with the following elements:
   *     - subtotal: The order subtotal price.
   *     - adjustments: An array of adjustment totals:
   *         - type: The adjustment type.
   *         - label: The adjustment label.
   *         - total: The adjustment total price.
   *         - weight: The adjustment weight, taken from the adjustment type.
   *     - total: The invoice total price.
   */
  public function buildTotals(InvoiceInterface $invoice);

}

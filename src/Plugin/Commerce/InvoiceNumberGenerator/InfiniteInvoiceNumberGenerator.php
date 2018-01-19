<?php

namespace Drupal\commerce_order_invoice\Plugin\Commerce\InvoiceNumberGenerator;

use Drupal\commerce_order_invoice\InvoiceNumber;

/**
 * Provides the infinite order number generator.
 *
 * @CommerceInvoiceNumberGenerator(
 *   id = "infinite",
 *   label = @Translation("Infinite"),
 *   description = @Translation("One single number, that is never reset and
 *   incremented at each invoice number generation"),
 * )
 */
class InfiniteInvoiceNumberGenerator extends InvoiceNumberGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function generate(InvoiceNumber $last_invoice_number = NULL) {
    $invoice_number = $last_invoice_number;
    if (NULL === $invoice_number) {
      // No order number provided, create fresh one.
      $current_year = date('Y');
      $current_month = date('m');
      $invoice_number = new InvoiceNumber(0, $current_year, $current_month);
    }
    $invoice_number->increment();
    return $invoice_number;
  }

}

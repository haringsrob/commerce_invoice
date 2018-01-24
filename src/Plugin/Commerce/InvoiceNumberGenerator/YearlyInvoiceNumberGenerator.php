<?php

namespace Drupal\commerce_invoice\Plugin\Commerce\InvoiceNumberGenerator;

use Drupal\commerce_invoice\InvoiceNumber;

/**
 * Provides the yearly invoice number generator.
 *
 * @CommerceInvoiceNumberGenerator(
 *   id = "yearly",
 *   label = @Translation("Yearly"),
 *   description = @Translation("Reset every year, with an ID incremented at each invoice number generation"),
 * )
 */
class YearlyInvoiceNumberGenerator extends InvoiceNumberGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function generate(InvoiceNumber $last_invoice_number = NULL) {
    $invoice_number = $last_invoice_number;
    $current_year = date('Y');
    $current_month = date('m');
    if (NULL === $invoice_number || $current_year != $invoice_number->getYear()) {
      // Either no order number has been provided or the period does not match.
      $invoice_number = new InvoiceNumber(0, $current_year, $current_month);
    }
    $invoice_number->increment();
    return $invoice_number;
  }

}

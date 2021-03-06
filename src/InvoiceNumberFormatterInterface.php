<?php

namespace Drupal\commerce_invoice;

/**
 * Defines the order number formatter interface.
 */
interface InvoiceNumberFormatterInterface {

  const PATTERN_PLACEHOLDER_INVOICE_NUMBER = '{invoice_number}';

  const PATTERN_PLACEHOLDER_YEAR = '{year}';

  const PATTERN_PLACEHOLDER_MONTH = '{month}';

  /**
   * Returns a formatted order number based on the given invoice number value
   * object.
   *
   * Note: this function should be called by generateAndSetOrderNumber()
   * internally. It is exposed to be able to simulate order formatting, e.g. on
   * configuration pages.
   *
   * @param \Drupal\commerce_invoice\InvoiceNumber $order_number
   *   The order number value object.
   * @param int|null $number_padding
   *   Pad the order number with leading zeroes. 0 or less means no padding.
   *   Leave NULL to use the value set in site configuration. Set a value to
   *   override the setting for this call (e.g. for preview on settings page).
   * @param string|null $pattern
   *   The order number pattern. Leave NULL to use the value set in site
   *   configuration. Set a value to override the setting for this call (e.g.
   *   for preview on settings page).
   *
   * @return string
   *   The formatted order number.
   */
  public function format(InvoiceNumber $order_number, $number_padding = NULL, $pattern = NULL);

}

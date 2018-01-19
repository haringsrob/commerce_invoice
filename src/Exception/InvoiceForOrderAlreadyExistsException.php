<?php

namespace Drupal\commerce_order_invoice\Exception;

/**
 * Exception class for already existing invoices.
 */
class InvoiceForOrderAlreadyExistsException extends \Exception {

  /**
   * {@inheritdoc}
   */
  public function __construct($message = '', $code = 0, \Throwable $previous = NULL) {
    if ($message === '') {
      $message = 'An invoice already exists for this order.';
    }
    parent::__construct($message, $code, $previous);
  }

}

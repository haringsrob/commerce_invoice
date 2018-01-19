<?php

namespace Drupal\commerce_order_invoice;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Default order number formatter implementation.
 */
class InvoiceNumberFormatter implements InvoiceNumberFormatterInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new OrderNumberFormatter object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function format(InvoiceNumber $order_number, $number_padding = NULL, $pattern = NULL) {
    $formatted_number = (string) $order_number->getIncrementNumber();
    $config = $this->configFactory->get('commerce_order_invoice.settings');

    $number_padding = (int) (NULL === $number_padding ? $config->get('invoice_number_padding') : $number_padding);
    if ($number_padding && $number_padding > 0) {
      $formatted_number = str_pad($formatted_number, $number_padding, '0', STR_PAD_LEFT);
    }

    $pattern = NULL === $pattern ? $config->get('invoice_number_pattern') : $pattern;
    $search = [
      self::PATTERN_PLACEHOLDER_INVOICE_NUMBER,
      self::PATTERN_PLACEHOLDER_YEAR,
      self::PATTERN_PLACEHOLDER_MONTH,
    ];
    $replace = [
      $formatted_number,
      $order_number->getYear(),
      $order_number->getMonth(),
    ];
    $formatted_number = str_replace($search, $replace, $pattern);

    return $formatted_number;
  }

}

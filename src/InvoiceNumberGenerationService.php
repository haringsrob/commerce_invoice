<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Default order number service implementation.
 */
class InvoiceNumberGenerationService implements InvoiceNumberGenerationServiceInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The key/value storage collection.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * The locking layer instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The order number formatter.
   *
   * @var \Drupal\commerce_invoice\InvoiceNumberFormatterInterface
   */
  protected $invoiceNumberFormatter;

  /**
   * The order number generator manager.
   *
   * @var \Drupal\commerce_invoice\InvoiceNumberGeneratorManager
   */
  protected $invoiceNumberGeneratorManager;

  /**
   * Constructs a new OrderNumberGenerationService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value factory.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The locking layer instance.
   * @param \Drupal\commerce_invoice\InvoiceNumberFormatterInterface $invoice_number_formatter
   *   The order number formatter.
   * @param \Drupal\commerce_invoice\InvoiceNumberGeneratorManager $invoice_number_generator_manager
   *   The order number generator manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeyValueFactoryInterface $key_value_factory, LockBackendInterface $lock, InvoiceNumberFormatterInterface $invoice_number_formatter, InvoiceNumberGeneratorManager $invoice_number_generator_manager) {
    $this->configFactory = $config_factory;
    $this->keyValueStore = $key_value_factory->get('commerce_invoice');
    $this->lock = $lock;
    $this->invoiceNumberFormatter = $invoice_number_formatter;
    $this->invoiceNumberGeneratorManager = $invoice_number_generator_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function generateAndSetInvoiceNumber() {
    $config = $this->configFactory->get('commerce_invoice.settings');

    /** @var \Drupal\commerce_invoice\Plugin\Commerce\InvoiceNumberGenerator\InvoiceNumberGeneratorInterface $generator */
    $generator = $this->invoiceNumberGeneratorManager->createInstance($config->get('invoice_number_generator'));

    while (!$this->lock->acquire('commerce_invoice.generator')) {
      $this->lock->wait('commerce_invoice.generator');
    }

    $last_order_number = $this->keyValueStore->get('last_invoice_number');
    if (empty($last_order_number) || !($last_order_number instanceof InvoiceNumber)) {
      $last_order_number = NULL;
    }

    $invoice_number = $generator->generate($last_order_number);
    $invoice_number_formatted = $this->invoiceNumberFormatter->format($invoice_number);
    $invoice_number->setValue($invoice_number_formatted);

    // We check the value of the counter and keep incrementing until the value
    // is unique.
    while (\Drupal::database()
      ->query('SELECT invoice_number FROM {invoice} WHERE invoice_number = :invoice_number', [':invoice_number' => $invoice_number_formatted])
      ->fetchField()) {
      $invoice_number->increment();
      $invoice_number = $generator->generate($last_order_number);
      $invoice_number_formatted = $this->invoiceNumberFormatter->format($invoice_number);
      $invoice_number->setValue($invoice_number_formatted);
    }

    $this->keyValueStore->set('last_invoice_number', $invoice_number);
    $this->lock->release('commerce_invoice.generator');
    return $invoice_number_formatted;
  }

}

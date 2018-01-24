<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce_invoice\Annotation\CommerceInvoiceNumberGenerator;
use Drupal\commerce_invoice\Plugin\Commerce\InvoiceNumberGenerator\InvoiceNumberGeneratorInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of invoice number generator plugins.
 *
 * @see \Drupal\commerce_invoice\Annotation\CommerceInvoiceNumberGenerator
 * @see plugin_api
 */
class InvoiceNumberGeneratorManager extends DefaultPluginManager {

  /**
   * Constructs a new InvoiceNumberGeneratorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Commerce/InvoiceNumberGenerator', $namespaces, $module_handler, InvoiceNumberGeneratorInterface::class, CommerceInvoiceNumberGenerator::class);

    $this->alterInfo('commerce_order_number_generator_info');
    $this->setCacheBackend($cache_backend, 'commerce_order_number_generator_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The invoice number generator %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}

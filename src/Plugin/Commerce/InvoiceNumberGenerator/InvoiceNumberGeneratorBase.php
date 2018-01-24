<?php

namespace Drupal\commerce_invoice\Plugin\Commerce\InvoiceNumberGenerator;

use Drupal\Core\Plugin\PluginBase;

/**
 * Abstract base class for invoice number generators.
 */
abstract class InvoiceNumberGeneratorBase extends PluginBase implements InvoiceNumberGeneratorInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

}

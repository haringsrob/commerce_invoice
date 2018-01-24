<?php

namespace Drupal\commerce_invoice\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the invoice number generator plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\InvoiceNumberGenerator.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceInvoiceNumberGenerator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The invoice number generator label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The invoice number generator description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}

<?php

namespace Drupal\commerce_invoice\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'commerce_order_item_table' formatter.
 *
 * @FieldFormatter(
 *   id = "invoice_item_table",
 *   label = @Translation("Invoice item table"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class InvoiceItemTable extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $order = $items->getEntity();
    return [
      '#type' => 'view',
      // @todo Allow the view to be configurable.
      '#name' => 'invoice_item_table',
      '#arguments' => [$order->id()],
      '#embed' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type === 'invoice' && $field_name === 'invoice_items';
  }

}

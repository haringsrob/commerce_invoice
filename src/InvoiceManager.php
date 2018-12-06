<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce_invoice\Entity\Invoice;
use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\commerce_invoice\Entity\InvoiceItem;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Class InvoiceManager.
 */
class InvoiceManager {

  public function createInvoiceForOrder(OrderInterface $order): InvoiceInterface {
    $invoice = Invoice::create(['order_id' => [$order]]);

    $order_fields = $order->getFields(FALSE);
    $invoice_fields = $invoice->getFields(FALSE);

    $matching_fields = array_intersect_key($order_fields, $invoice_fields);

    // Exclude some fields that are already configured or should be ignored.
    unset(
      $matching_fields['order_id'],
      $matching_fields['uuid'],
      $matching_fields['changed'],
      $matching_fields['placed'],
      $matching_fields['completed'],
      $matching_fields['locked'],
      $matching_fields['created']
    );

    $this->copyOrderItemsToInvoice($order, $invoice);

    // Copy remaining data to the invoice.
    foreach ($matching_fields as $field_index => $field_data) {
      $invoice->set($field_index, $order->get($field_index)->getValue());
    }
    $invoice->save();
    return $invoice;
  }

  /**
   * Copies order items to the invoice.
   *
   * @todo: This code needs to be improved.
   */
  private function copyOrderItemsToInvoice(OrderInterface $order, InvoiceInterface $invoice): void {
    $items_order = $order->get('order_items')->referencedEntities();
    $items_invoice = [];

    $needed_fields = [
      'type',
      'purchased_entity',
      'title',
      'quantity',
      'unit_price',
      'overridden_unit_price',
      'adjustments',
      'data',
    ];

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $item */
    foreach ($items_order as $item) {
      foreach ($needed_fields as $field) {
        if ('unit_price' === $field) {
          $priceField = $item->get($field);
          if (!$priceField->isEmpty()) {
            $values[$field] = [
              'number' => $priceField->first()->getValue()['number'],
              'currency_code' => $priceField->first()
                ->getValue()['currency_code'],
            ];
          }
        }
        elseif ('adjustments' === $field) {
          $values[$field] = $item->get($field);
        }
        else {
          $values[$field] = $item->get($field)->getString();
        }
      }
      if (isset($values)) {
        $invoice_item = InvoiceItem::create($values);
        $invoice_item->save();
        $items_invoice[] = $invoice_item->id();
        unset($values);
      }
    }
    $invoice->set('invoice_items', $items_invoice);
  }

}

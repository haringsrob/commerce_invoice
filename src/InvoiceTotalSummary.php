<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\AdjustmentTransformerInterface;

/**
 * Invoice total summary.
 */
class InvoiceTotalSummary implements InvoiceTotalSummaryInterface {

  /**
   * The adjustment transformer.
   *
   * @var \Drupal\commerce_order\AdjustmentTransformerInterface
   */
  protected $adjustmentTransformer;

  /**
   * Constructs a new OrderTotalSummary object.
   *
   * @param \Drupal\commerce_order\AdjustmentTransformerInterface $adjustment_transformer
   *   The adjustment transformer.
   */
  public function __construct(AdjustmentTransformerInterface $adjustment_transformer) {
    $this->adjustmentTransformer = $adjustment_transformer;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTotals(InvoiceInterface $invoice) {
    $adjustments = $invoice->collectAdjustments();
    $adjustments = $this->adjustmentTransformer->processAdjustments($adjustments);
    // Convert the adjustments to arrays.
    $adjustments = array_map(function (Adjustment $adjustment) {
      return $adjustment->toArray();
    }, $adjustments);
    // Provide the "total" key for backwards compatibility reasons.
    foreach ($adjustments as $index => $adjustment) {
      $adjustments[$index]['total'] = $adjustments[$index]['amount'];
    }

    return [
      'subtotal' => $invoice->getSubtotalPrice(),
      'adjustments' => $adjustments,
      'total' => $invoice->getTotalPrice(),
    ];
  }

}

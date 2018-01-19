<?php

namespace Drupal\commerce_order_invoice;

use Drupal\commerce_order_invoice\Entity\InvoiceInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Invoice entities.
 *
 * @ingroup commerce_order_invoice
 */
class InvoiceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['invoice_number'] = $this->t('Invoice number');
    $header['status'] = $this->t('Invoice status');
    $header['date'] = $this->t('Invoice date');
    $header['order'] = $this->t('Order');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $invoice) {
    /* @var $invoice \Drupal\commerce_order_invoice\Entity\Invoice */
    $row['id'] = $invoice->get('id')->getString();
    $row['invoice_number'] = $invoice->get('invoice_number')->getString();
    $row['status'] = $invoice->isLocked() ? t('Complete') : t('Draft');
    $row['date'] = $invoice->getInvoiceDate();
    $row['commerce_order'] = t('No order associated');
    if ($order = $invoice->getOrder()) {
      $row['commerce_order'] = $order->toLink('View order');
    }
    return $row + parent::buildRow($invoice);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $invoice) {
    /** @var \Drupal\commerce_order_invoice\Entity\InvoiceInterface $invoice */
    $operations = [];
    if ($invoice->access('view') && $invoice->hasLinkTemplate('canonical')) {
      $operations['view'] = [
        'title' => $this->t('View invoice'),
        'weight' => -100,
        'url' => $invoice->toUrl(),
      ];
    }
    return $operations + parent::getDefaultOperations($invoice);
  }

  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as renderable array for table.html.twig.
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t('There are no @labels yet. Invoices can be created from the orders page.', ['@label' => $this->entityType->getLabel()]);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return t('Invoices');
  }

}

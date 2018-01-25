<?php

namespace Drupal\commerce_invoice\Plugin\Field\FieldWidget;

use Drupal\commerce_order\Plugin\Field\FieldWidget\BillingProfileWidget;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of 'invoice_billing_profile'.
 *
 * @FieldWidget(
 *   id = "invoice_billing_profile",
 *   label = @Translation("Billing information"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class InvoiceBillingProfileWidget extends BillingProfileWidget {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition
  ) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type === 'invoice' && $field_name === 'billing_profile';
  }

}

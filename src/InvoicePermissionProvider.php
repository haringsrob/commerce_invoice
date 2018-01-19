<?php

namespace Drupal\commerce_order_invoice;

use Drupal\commerce\EntityPermissionProvider;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides permissions for invoices.
 */
class InvoicePermissionProvider extends EntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildPermissions($entity_type);
    $permissions['view invoice']['title'] = (string) t('View any invoice');

    return $permissions;
  }

}

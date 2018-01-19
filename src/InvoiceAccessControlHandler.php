<?php

namespace Drupal\commerce_order_invoice;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Invoice entity.
 *
 * @see \Drupal\commerce_order_invoice\Entity\Invoice.
 */
class InvoiceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_order_invoice\Entity\InvoiceInterface $entity */
    switch ($operation) {
      case 'view':
        if ($account->hasPermission('administer commerce_order_invoice')) {
          return AccessResult::allowedIfHasPermission($account, 'administer commerce_order_invoice');
        }
        return AccessResult::allowedIfHasPermission($account, 'view own invoice entities');

      case 'update':
        if ($entity->isLocked()) {
          return AccessResult::forbidden('Invoice is confirmed and can not be modified.');
        }
        return parent::checkAccess($entity, $operation, $account);

      case 'delete':
        if ($entity->isLocked()) {
          return AccessResult::forbidden('Invoice is confirmed and can not be deleted.');
        }
        return parent::checkAccess($entity, $operation, $account);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add invoice entities');
  }

}

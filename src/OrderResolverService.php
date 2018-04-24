<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce_order\Entity\Order;

/**
 * Class OrderResolverService.
 */
class OrderResolverService {

  /**
   * Get the order from the current route.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The entity or null.
   */
  public function getOrderFromRoute() {
    $routeParameters = \Drupal::routeMatch()->getParameters();
    $order = $routeParameters->get('commerce_order');

    if (!$order instanceof Order && $order !== NULL) {
      $order = Order::load($order);
    }

    return $order;
  }

}

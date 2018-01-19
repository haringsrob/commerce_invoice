<?php

namespace Drupal\commerce_order_invoice;

use Drupal\commerce_order\Entity\Order;

/**
 * Class OrderResolverService.
 */
class OrderResolverService {

  /**
   * Constructs a new OrderResolverService object.
   */
  public function __construct() {

  }

  /**
   * Get the order from the current route.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The entity or null.
   */
  public function getOrderFromRoute() {
    $routeParameters = \Drupal::routeMatch()->getParameters();
    $order = $routeParameters->get('commerce_order');

    if (!$order instanceof Order) {
      $order = Order::load($order);
    }

    return $order;
  }

}

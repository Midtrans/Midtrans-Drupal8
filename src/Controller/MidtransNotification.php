<?php

namespace Drupal\commerce_midtrans\Controller;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\Core\Access\AccessException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the endpoint for payment notifications.
 */
class MidtransNotification {
  /**
   * Provides the "notify" page.
   *
   * Also called the "IPN", "status", "webhook" page by payment providers.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $commerce_payment_gateway
   *   The payment gateway.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function notifyPage(Request $request) {
    $raw_notification = json_decode(file_get_contents('php://input'), TRUE);
    $order_id = $raw_notification['order_id'];

    if (empty($order_id)) {
      return new Response('Bad Request, orderID is required', 400);
    }

    //get order by order id
    $order = \Drupal\commerce_order\Entity\Order::load($order_id);
    if (!$order) {
      \Drupal::logger('commerce_midtrans')->error('orderID : '.$order_id. ' not found');
      return new Response('Bad Request', 400);
    }

    $payment_gateway = $order->get('payment_gateway')->first()->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();

    if (!$payment_gateway_plugin instanceof SupportsNotificationsInterface) {
      throw new AccessException('Invalid payment gateway provided.');
    }

    $response = $payment_gateway_plugin->onNotify($request);
    if (!$response) {
      $response = new Response('', 200);
    }

    return $response;
  }
}

<?php

namespace Drupal\commerce_midtrans\Controller;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\Core\Access\AccessException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
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
    $raw_notification = json_decode(file_get_contents('php://input'), true);
    $order_id = $raw_notification['order_id'];
    
    //get order by order id
    $order = \Drupal\commerce_order\Entity\Order::load($order_id);
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

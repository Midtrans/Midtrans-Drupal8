<?php

namespace Drupal\commerce_midtrans\Controller;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides the finish url for midtrans payment gateway.
 */
class MidtransFinishUrl extends ControllerBase {
  /**
   * Return finish url
   */
  public function finishPage(Request $request) {
    $get_payment = FALSE;
    $params = \Drupal::request()->request->all();

    // bca_klikpay will redirect to this page with query params ?id=transaction_id
    if ($request->query->get('id')) {
      $transaction_id = $request->query->get('id');
      $get_payment = $this->getPaymentByTransactionId($transaction_id);
    }
    else if (isset($params['response'])) {
      // akulaku, cimb_clicks, danamon_online, bri_epay will redirect to this page with POST method
      $response = json_decode($params['response'], TRUE);
      $transaction_id = $response['transaction_id'];
      $get_payment = $this->getPaymentByTransactionId($transaction_id);
    }
    else if ($request->query->get('order_id')) {
      // other payment methods use this for redirection
      $transaction_id = $request->query->get('order_id');
      $get_payment = $this->getPaymentByTransactionId($transaction_id, TRUE);
    }

    if (!$get_payment) {
      $base_url = \Drupal::request()->getSchemeAndHttpHost().base_path();
      $response = new RedirectResponse($base_url);
      $response->send();
    }

    $config = $get_payment->getPaymentGateway()->getPluginConfiguration();
    $result = $this->getStatus($config, $transaction_id);
    if (!$result) {
      $payment_path = 'checkout/'.$get_payment->getOrderId().'/payment/return';
      $base_url = \Drupal::request()->getSchemeAndHttpHost().base_path().$payment_path;
      $response = new RedirectResponse($base_url);
      $response->send();
    }

    $message = $this->buildMessage($result);
    return [
      '#markup' => $message
    ];
  }

  protected function getPaymentByTransactionId($transaction_id, $use_order_id = FALSE) {
    $entity = \Drupal::entityTypeManager();
    $storage = $entity->getStorage('commerce_payment');

    if ($use_order_id) {
      $params = array('order_id' => $transaction_id);
    }
    else {
      $params = array('remote_id' => $transaction_id);
    }
    $get_payments = $storage->loadByProperties($params);
    return reset($get_payments);
  }

  protected function getStatus($config, $transaction_id) {
    \Midtrans\Config::$serverKey =  $config['server_key'];
    \Midtrans\Config::$isProduction = ($config['mode'] == 'production') ? TRUE : FALSE;

    try {
      return \Midtrans\Transaction::status($transaction_id);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  protected function buildMessage($data) {
    $message = '<p><strong>Thank you, here is the detail payment from Midtrans</strong><br />';
    $message .= 'Order ID: '.$data->order_id.'<br>';
    $message .= 'Transaction ID: '.$data->transaction_id.'<br>';
    $message .= 'Transaction Status: '.ucwords($data->transaction_status).'<br>';
    $message .= 'Payment Type: '.$this->detailPaymentType($data).'<br>';
    $message .= 'You can view your order on your account page when logged in.';

    return $message;
  }

  protected function detailPaymentType($data) {
    $result = '-';
    if ($data->payment_type == 'credit_card') {
      $result =  ucwords($data->card_type).' Card - Mask Card: '.$data->masked_card;
    }
    else if ($data->payment_type == 'qris') {
      $issuer = isset($data->issuer) ? ' - Issuer: '.ucwords($data->issuer) : '';
      $result = 'QRIS - Acquirer: '.ucwords($data->acquirer).$issuer;
    } else if ($data->payment_type == 'cstore') {
      $result = ucwords($data->store).' - Payment Code: '.$data->payment_code;
    }
    else if ($data->payment_type == 'echannel') {
      $result = 'Mandiri Bill - Bill Number: '.$data->bill_key;
    }
    else if ($data->payment_type == 'bank_transfer') {
      if (isset($data->permata_va_number)){
        $result = 'Permata VA - '.$data->permata_va_number;
      }
      else {
        $result = strtoupper($data->va_numbers[0]->bank).' VA - '.$data->va_numbers[0]->va_number;
      }
    }
    else {
      $result = ucwords($data->payment_type);
    }

    return $result;
  }
}

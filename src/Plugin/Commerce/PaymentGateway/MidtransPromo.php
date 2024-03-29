<?php

namespace Drupal\midtrans_commerce\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Controller;
use Drupal\commerce_payment;
require_once(dirname(dirname(__DIR__)) . '/../../lib/midtrans/Midtrans.php');

/**
 * Provides the Midtrans Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "midtrans_promo",
 *   label = "Midtrans Promo Payment",
 *   display_label = "Promo Payment via Midtrans",
 *    forms = {
 *     "offsite-payment" = "Drupal\midtrans_commerce\PluginForm\MidtransPromoForm",
 *   },
*   modes= {
 *     "sandbox" = "Sandbox",
 *     "production" = "Production"
 *   },
 * )
 */
class MidtransPromo extends OffsitePaymentGatewayBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'merchant_id' => '',
        'server_key' => '',
        'client_key' => '',
        'enable_3ds' => '1',
        'enable_redirect' => '',
        'enable_savecard' => '',
        'method_enabled' => '',
        'min_amount' => '',
        'bin_number' => '',
        'discount_type' => '',
        'max_discount' => '',
        'discount_amount' => '',
        'custom_expiry' => '',
        'custom_field' => '',
        'enable_override_notification' => '1',
        'notification_url' => '',
        'enable_log_for_http_notification' => '1',
        'enable_log_for_exception' => '1',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#description' => $this->t('Input your Midtrans Merchant ID (e.g M012345). Get the ID <a class="config_info" href="#" target="_blank">here</a>'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];

    $form['server_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server Key'),
      '#description' => $this->t('Input your Midtrans Server Key. Get the key <a class="config_info" href="#" target="_blank">here</a>'),
      '#default_value' => $this->configuration['server_key'],
      '#required' => TRUE,
    ];

    $form['client_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Key'),
      '#description' => $this->t('Input your Midtrans Client Key. Get the key <a class="config_info" href="#" target="_blank">here</a>'),
      '#default_value' => $this->configuration['client_key'],
      '#required' => TRUE,
    ];

    $form['enable_3ds'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable 3D Secure'),
      '#description' => $this->t('You must enable 3D Secure. Please contact us if you wish to disable this feature in the Production environment.'),
      '#default_value' => $this->configuration['enable_3ds'],
    ];

    $form['enable_redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect Payment Page'),
      '#default_value' => $this->configuration['enable_redirect'],
      '#description' => $this->t('This will redirect customer to Midtrans hosted payment page instead of popup payment page on your website. <br>Leave it disabled if you are not sure.'),
    ];

    $form['enable_savecard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Save Card'),
      '#default_value' => $this->configuration['enable_savecard'],
      '#description' => $this->t('This will allow your customer to save their card on the payment popup, for faster payment flow on the following purchase'),
    ];

    $form['discount_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Discount Type'),
      '#options' => ['percentage' => 'Percentage','flat_amount' => 'Flat Amount'],
      '#default_value' => $this->configuration['discount_type'],
    ];

    $form['discount_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Discount Amount'),
      '#default_value' => $this->configuration['discount_amount'],
      '#description' => $this->t('Enter the discount value. <br>If you choose <b>percentage</b> discount example: 10<br>If you choose <b>flat amount</b> discount example: 5000'),
      '#required' => TRUE,
    ];

    $form['min_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimal Transaction Amount'),
      '#default_value' => $this->configuration['min_amount'],
      '#description' => $this->t('Minimal transaction amount allowed to be paid with discount promo (amount in IDR, without comma or period) example: 500000 </br> if the transaction amount is below this value, customer won\'t get discount.<br>Leave it disabled if you are not sure.'),
      '#required' => TRUE,
    ];

    $form['max_discount'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Discount Amount'),
      '#default_value' => $this->configuration['max_discount'],
      '#description' => $this->t('Maximun discount amount allowed (amount in IDR, without comma or period) example: 500000 </br> if the discount amount is above this value, customer will get maximum discount.<br>Leave it disabled if you are not sure.'),
      '#required' => TRUE,
    ];

    $form['method_enabled'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed Payment Method'),
      '#description' => $this->t('Customize allowed payment method, separate payment method code with coma. e.g: bank_transfer,credit_card. <br>Leave it default if you are not sure.'),
      '#default_value' => $this->configuration['method_enabled'],
    ];

    $form['bin_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed CC BINs'),
      '#default_value' => $this->configuration['bin_number'],
      '#description' => $this->t('Fill with CC BIN numbers (or bank name) that you want to allow to use this payment button. </br> Separate BIN number with coma Example: 4,5,4811,bni,mandiri <br>Leave it default if you are not sure.'),
    ];

    $form['custom_expiry'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Expiry'),
      '#default_value' => $this->configuration['custom_expiry'],
      '#description' => $this->t('This will allow you to set custom duration on how long the transaction available to be paid.<br>Options: <code>days, hours, minutes</code><br>Example: 5 minutes'),
    ];

    $form['custom_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Fields'),
      '#default_value' => $this->configuration['custom_field'],
      '#description' => $this->t('This will allow you to set custom fields that will be displayed on Midtrans dashboard. Up to 3 fields are available, separate by coma (,)<br>Example: Order from web, Processed'),
    ];

    $form['enable_override_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Override Notification'),
      '#description' => $this->t('By default this module will auto override notification URL rather than using URL from Midtrans configuration page.<br>
       In case this method fails, try disabling override notification feature.<br><br>
       <b>Payment Notification URL</b> : <code>'.\Drupal::request()->getSchemeAndHttpHost().base_path().'payment/notify/midtrans</code><br>
       Copy this endpoint URL and put to the <b>Payment Notification URL</b> field in the <a target="_blank" href="https://dashboard.midtrans.com/settings/vtweb_configuration">Midtrans configuration page.</a><br><br>The notification URL value is an autogenerated attempt based on Drupal default config, it may generate invalid url in some extreme cases of custom config. Use this as reference only.'),
      '#default_value' => $this->configuration['enable_override_notification'],
    ];

    $form['notification_url'] = [
      '#type' => 'hidden',
      '#value' => \Drupal::request()->getSchemeAndHttpHost().base_path().'payment/notify/midtrans',
    ];

    $form['enable_log_for_http_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Notification Log'),
      '#default_value' => $this->configuration['enable_log_for_http_notification'],
      '#description' => $this->t('The plugins will store log in Reports > Recent log messages. The default value is Enable.<br>Sample Notification: <code>Handling received HTTP Notification: orderID 32 - bank_transfer - settlement</code>'),
    ];

    $form['enable_log_for_exception'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Throw Exception Log'),
      '#default_value' => $this->configuration['enable_log_for_exception'],
      '#description' => $this->t('The plugins will store log in Reports > Recent log messages when receive error response from Midtrans. The default value is Enable.'),
    ];

    $form['midtrans_admin_module'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['id' => 'midtrans-admin-module-promo'],
    ];

    $js_settings = [
      'id' => '#midtrans-admin-module-promo',
      'module' => 'midtrans_promo'
    ];
    $form['#attached']['drupalSettings']['midtrans_commerce'] = $js_settings;
    $form['#attached']['library'][] = 'midtrans_commerce/adminmodule';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['server_key'] = $values['server_key'];
      $this->configuration['client_key'] = $values['client_key'];
      $this->configuration['enable_3ds'] = $values['enable_3ds'];
      $this->configuration['enable_redirect'] = $values['enable_redirect'];
      $this->configuration['enable_savecard'] = $values['enable_savecard'];
      $this->configuration['discount_type'] = $values['discount_type'];
      $this->configuration['discount_amount'] = $values['discount_amount'];
      $this->configuration['max_discount'] = $values['max_discount'];
      $this->configuration['method_enabled'] = $values['method_enabled'];
      $this->configuration['min_amount'] = $values['min_amount'];
      $this->configuration['bin_number'] = $values['bin_number'];
      $this->configuration['custom_expiry'] = $values['custom_expiry'];
      $this->configuration['custom_field'] = $values['custom_field'];
      $this->configuration['enable_override_notification'] = $values['enable_override_notification'];
      $this->configuration['notification_url'] = $values['notification_url'];
      $this->configuration['enable_log_for_http_notification'] = $values['enable_log_for_http_notification'];
      $this->configuration['enable_log_for_exception'] = $values['enable_log_for_exception'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function loadPaymentByOrderId($order_id) {
    /** @var \Drupal\commerce_payment\PaymentStorage $storage */
    $storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment_by_order_id = $storage->loadByProperties(['order_id' => $order_id]);
    return reset($payment_by_order_id);
  }

  protected function fetchAndSetMidtransApiCredentials() {
    \Midtrans\Config::$serverKey =  $this->getConfiguration()['server_key'];
    \Midtrans\Config::$isProduction = ($this->getMode() == 'production') ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $this->fetchAndSetMidtransApiCredentials();
    $payment = $this->loadPaymentByOrderId($order->id());
    $response = \Midtrans\Transaction::status($payment->getRemoteId());

    if($payment->getState()->value != 'complete'){
      if (isset($_GET["pdf"])) {
        if (substr($_GET["pdf"],0,4) == 'http'){
          $pdf = $_GET["pdf"];
          $this->messenger()->addMessage(
            $this->t('Please complete your payment as instructed <a href="' . $pdf . '" target="_blank">here.</a>'));
        }
      }
    }

    $message = '<p><strong>Here is the detail payment from Midtrans</strong><br />';
    $message .= 'Order ID: '.$response->order_id.'<br>';
    $message .= 'Transaction ID: '.$response->transaction_id.'<br>';
    $message .= 'Transaction Status: '.ucwords($response->transaction_status).'<br>';
    $message .= 'Payment Type: '.$this->detailPaymentType($response).'<br>';

    $this->messenger()->addMessage($this->t($message));
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

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $this->fetchAndSetMidtransApiCredentials();
    $notification = new \Midtrans\Notification();
    $response = $notification->getResponse();
    $payment = $this->loadPaymentByOrderId($response->order_id);
    $configuration = $payment->getPaymentGateway()->getPlugin()->getConfiguration();
    $order = $payment->getOrder();

    // when use snap popup, some payment methods like akulaku or direct debit, will redirect to 3rd party website
    // and never returns to the site, causes the order still unplaced (draft) and stuck in checkout
    // and make sure that the order is placed when got status pending (order was created in midtrans)
    if ($order->getState()->getId() == 'draft' && $response->transaction_status == 'pending') {
      $order_state = $order->getState();
      $order_state->applyTransitionById('place');
      $order->unlock();
      $order->save();
    }

    // change payment remote id with transaction id, also update amount if there is promo
    $payment->setRemoteId($response->transaction_id);
    $payment->setAmount($order->getTotalPrice());
    $payment->save();

    if ($configuration['enable_log_for_http_notification']) {
      $message = 'Handling received HTTP Notification: orderID '.$response->order_id.' - '.$response->payment_type.' - '.$response->transaction_status;
      \Drupal::logger('midtrans_commerce')->info($message);
    }

    // add notif to order activity
    if (\Drupal::moduleHandler()->moduleExists('commerce_log')) {
      $notif_params = array(
        'order_id' => $response->order_id,
        'transaction_id' => $response->transaction_id,
        'transaction_status' => ucwords($response->transaction_status),
        'payment_type' => $this->detailPaymentType($response)
      );
      $order_activity = \Drupal::entityTypeManager()->getStorage('commerce_log');
      $order_activity->generate($order, 'midtrans_commerce_notification', $notif_params)->save();
    }

    if ($response->transaction_status == 'capture'){
        if ($response->fraud_status == 'accept'){
          $payment->setRemoteState($response->transaction_status);
          $payment->setState('complete');
          $payment->save();
        }
        else if ($response->fraud_status == 'challenge'){
          $payment->setRemoteState($response->transaction_status);
          $payment->setState('challenge');
          $payment->save();
        }
    }
    else if ($response->transaction_status == 'cancel'){
      $payment->setRemoteState($response->transaction_status);
      $payment->setState('cancelled');
      $payment->save();
    }
    else if ($response->transaction_status == 'expire'){
      $payment->setRemoteState($response->transaction_status);
      $payment->setState('cancelled');
      $payment->save();
    }
    else if ($response->transaction_status == 'deny'){
      $payment->setRemoteState($response->transaction_status);
      $payment->setState('failed');
      $payment->save();
    }
    else if ($response->transaction_status == 'pending'){
      $payment->setRemoteState($response->transaction_status);
      $payment->setState('pending');
      $payment->save();
    }
    else if ($response->transaction_status == 'settlement'){
      $payment->setRemoteState($response->transaction_status);
      $payment->setState('complete');
      $payment->save();
    }
  }
}

<?php

namespace Drupal\midtrans_commerce\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase as InstallmentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Controller;
use Drupal\commerce_payment;
require_once(dirname(dirname(__DIR__)) . '/../../lib/midtrans/Midtrans.php');

/**
 * Provides the Midtrans Installment Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "midtrans_installment",
 *   label = "Midtrans Online Installment",
 *   display_label = "Credit Card Installment via Midtrans",
 *    forms = {
 *     "offsite-payment" = "Drupal\midtrans_commerce\PluginForm\MidtransInstallmentForm",
 *   },
 *   modes= {
 *     "sandbox" = "Sandbox",
 *     "production" = "Production"
 *   },
 * )
 */
class MidtransInstallment extends InstallmentGatewayBase {
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
        'min_amount' => '500000',
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
      '#description' => $this->t('This will redirect customer to Midtrans hosted payment page instead of popup payment page on your website. <br>Leave it disabled if you are not sure.'),
      '#default_value' => $this->configuration['enable_redirect'],
    ];

    $form['enable_savecard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Save Card'),
      '#default_value' => $this->configuration['enable_savecard'],
      '#description' => $this->t('This will allow your customer to save their card on the payment popup, for faster payment flow on the following purchase'),
    ];

    $form['min_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimal Transaction Amount'),
      '#description' => $this->t('Minimal transaction amount allowed to be paid with installment (amount in IDR, without comma or period) example: 500000 </br> if the transaction amount is below this value, customer will be redirected to Credit Card fullpayment page.'),
      '#default_value' => $this->configuration['min_amount'],
    ];

    $form['custom_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Fields'),
      '#description' => $this->t('This will allow you to set custom fields that will be displayed on Midtrans dashboard. Up to 3 fields are available, separate by coma (,)<br>Example: Order from web, Processed'),
      '#default_value' => $this->configuration['custom_field'],
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
      '#attributes' => ['id' => 'midtrans-admin-module-installment'],
    ];

    $js_settings = [
      'id' => '#midtrans-admin-module-installment',
      'module' => 'midtrans_installment'
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
      $this->configuration['min_amount'] = $values['min_amount'];
      $this->configuration['enable_redirect'] = $values['enable_redirect'];
      $this->configuration['enable_savecard'] = $values['enable_savecard'];
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

    $installment = isset($response->installment_term) ? ' - Installment: '.$response->installment_term. ' Months' : '';
    $payment_type = ucwords($response->card_type).' Card - Mask Card: '.$response->masked_card.$installment;

    $message = '<p><strong>Here is the detail payment from Midtrans</strong><br />';
    $message .= 'Order ID: '.$response->order_id.'<br>';
    $message .= 'Transaction ID: '.$response->transaction_id.'<br>';
    $message .= 'Transaction Status: '.ucwords($response->transaction_status).'<br>';
    $message .= 'Payment Type: '.$payment_type.'<br>';

    $this->messenger()->addMessage($this->t($message));
  }

  public function onCancel(OrderInterface $order, Request $request) {
    // clear snap token, if snap token was expired, can use new snap token
    // only available for cc transaction
    $order->setData('snap_token', NULL);
    $order->save();

    $this->messenger()->addMessage($this->t('You have canceled checkout at @gateway but may resume the checkout process here when you are ready.', [
      '@gateway' => $this->getDisplayLabel(),
    ]));
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

    // change payment remote id with transaction id
    $payment->setRemoteId($response->transaction_id);
    $payment->save();

    if ($configuration['enable_log_for_http_notification']) {
      $message = 'Handling received HTTP Notification: orderID '.$response->order_id.' - '.$response->payment_type.' - '.$response->transaction_status;
      \Drupal::logger('midtrans_commerce')->info($message);
    }

    // add notif to order activity
    if (\Drupal::moduleHandler()->moduleExists('commerce_log')) {
      $installment = isset($response->installment_term) ? ' - Installment: '.$response->installment_term. ' Months' : '';
      $payment_type = ucwords($response->card_type).' Card - Mask Card: '.$response->masked_card.$installment;
      $notif_params = array(
        'order_id' => $response->order_id,
        'transaction_id' => $response->transaction_id,
        'transaction_status' => ucwords($response->transaction_status),
        'payment_type' => $payment_type
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

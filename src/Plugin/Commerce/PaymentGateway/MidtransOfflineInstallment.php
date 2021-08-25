<?php

namespace Drupal\commerce_midtrans\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase as OfflineInstallmentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Controller;
use Drupal\commerce_payment;

/**
 * Provides the Midtrans Offline Installment Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "midtrans_installmentoff",
 *   label = "Midtrans Offline Installment",
 *   display_label = "Credit Card Installment for any bank via Midtrans",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_midtrans\PluginForm\MidtransOfflineInstallmentForm",
 *   },
 *   modes= {
 *     "sandbox" = "Sandbox",
 *     "production" = "Production"
 *   },
 * )
 */
class MidtransOfflineInstallment extends OfflineInstallmentGatewayBase {
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
        'installment_term' => '3,6,12',
        'acquiring_bank' => '',
        'min_amount' => '500000',
        'bin_number' => '',
        'custom_field' => '',
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

    $form['installment_term'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Installment Terms'),
      '#description' => $this->t('Input the desired Installment Terms. Separate with coma. e.g: 3,6,12'),
      '#default_value' => $this->configuration['installment_term'],
      '#required' => TRUE,
    ];

    $form['acquiring_bank'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Acquiring Bank'),
      '#description' => $this->t('Input the desired acquiring bank. e.g: bni </br>Leave blank if you are not sure'),
      '#default_value' => $this->configuration['acquiring_bank'],
    ];

    $form['min_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimal Transaction Amount'),
      '#description' => $this->t('Minimal transaction amount allowed to be paid with installment (amount in IDR, without comma or period) example: 500000 </br> if the transaction amount is below this value, customer will be redirected to Credit Card fullpayment page.'),
      '#default_value' => $this->configuration['min_amount'],
    ];

    $form['bin_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed CC BINs'),
      '#description' => $this->t('Fill with CC BIN numbers (or bank name) that you want to allow to use this payment button. </br> Separate BIN number with coma Example: 4,5,4811,bni,mandiri'),
      '#default_value' => $this->configuration['bin_number'],
    ];

    $form['custom_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Fields'),
      '#default_value' => $this->configuration['custom_field'],
      '#description' => $this->t('This will allow you to set custom fields that will be displayed on Midtrans dashboard. Up to 3 fields are available, separate by coma (,)<br>Example: Order from web, Processed'),
    ];

    $form['midtrans_admin_module'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['id' => 'midtrans-admin-module-installmentoff'],
    ];
    $form['#attached']['library'][] = 'commerce_midtrans/adminmodule';

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
      $this->configuration['installment_term'] = $values['installment_term'];
      $this->configuration['acquiring_bank'] = $values['acquiring_bank'];
      $this->configuration['min_amount'] = $values['min_amount'];
      $this->configuration['bin_number'] = $values['bin_number'];
      $this->configuration['custom_field'] = $values['custom_field'];
    }
  }

    /**
     * {@inheritdoc}
     */
    protected function loadPaymentByOrderId2($order_id) {
        /** @var \Drupal\commerce_payment\PaymentStorage $storage */
        $storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment_by_order_id = $storage->loadByProperties(['remote_id' => $order_id]);
        return reset($payment_by_order_id);
    }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // $logger = \Drupal::logger('commerce_midtrans');
    drupal_set_message('Thank you for placing your order');
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    \Midtrans\Config::$serverKey =  $this->getConfiguration()['server_key'];
    \Midtrans\Config::$isProduction = ($this->getMode() == 'production') ? TRUE : FALSE;
    $response = new \Midtrans\Notification();
    /** @var \Drupal\commerce_payment\PaymentStorage $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    //$payment = $payment_storage->loadByRemoteId($response->order_id);
    /** @var \Drupal\commerce_order\Entity\Order $order */
    //$order = $payment->getOrder();

    //error_log('Response from Midtrans : '. print_r($response, TRUE)); //debugan
    $payment = $this->loadPaymentByOrderId2($response->order_id);

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
?>

<?php

namespace Drupal\commerce_midtrans\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\HasPaymentInstructionsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Controller;
use Drupal\commerce_payment;
use Midtrans\Config;
use Midtrans\Notification;

/**
 * Provides the Midtrans Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "midtrans",
 *   label = "Midtrans",
 *   display_label = "Online Payment via Midtrans",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_midtrans\PluginForm\MidtransForm",
 *   },
*   modes= {
 *     "sandbox" = "Sandbox",
 *     "production" = "Production"
 *   },
 * )
 */
class Midtrans extends OffsitePaymentGatewayBase{
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
        'custom_expiry' => '',
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
      '#description' => $this->t('Input your Midtrans Merchant ID (e.g M012345). Get the ID <a href="https://dashboard.sandbox.midtrans.com/settings/config_info" target="_blank">here</a>'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];

    $form['server_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server key'),
      '#description' => $this->t('Input your Midtrans Server Key. Get the key <a href="https://dashboard.sandbox.midtrans.com/settings/config_info" >here</a> for Sandbox and <a href="https://dashboard.midtrans.com/settings/config_info">here</a> for Production.'),
      '#default_value' => $this->configuration['server_key'],
      '#required' => TRUE,
    ];

    $form['client_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client key'),
      '#description' => $this->t('Input your Midtrans Client Key. Get the key <a href="https://dashboard.sandbox.midtrans.com/settings/config_info" >here</a> for Sandbox and <a href="https://dashboard.midtrans.com/settings/config_info">here</a> for Production.'),
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

    $form['custom_expiry'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Expiry'),
      '#default_value' => $this->configuration['custom_expiry'],
      '#description' => $this->t('This will allow you to set custom duration on how long the transaction available to be paid.<br>example: 45 minutes'),
    ];

    $form['custom_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Fields'),
      '#default_value' => $this->configuration['custom_field'],
      '#description' => $this->t('This will allow you to set custom fields that will be displayed on Midtrans dashboard. Up to 3 fields are available, separate by coma (,)<br>Example: Order from web, Processed'),
    ];

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
      $this->configuration['custom_expiry'] = $values['custom_expiry'];
      $this->configuration['custom_field'] = $values['custom_field'];
    }
  }

    /**
     * {@inheritdoc}
     */
    protected function loadPaymentByOrderId($order_id) {
        /** @var \Drupal\commerce_payment\PaymentStorage $storage */
        $storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment_by_order_id = $storage->loadByProperties(['remote_id' => $order_id]);
        return reset($payment_by_order_id);
    }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $this->loadPaymentByOrderId($order->id());
    $status = $payment->getState()->value;
    $pdf = $_GET["pdf"];

    if($payment->getState()->value != 'complete'){
      if ($_GET["pdf"]){
        if (substr($_GET["pdf"],0,4) == 'http'){
          $this->messenger()->addMessage(
            $this->t('Please complete your payment as instructed <a href="' . $pdf . '" target="_blank">here.</a>'));
        }
        else{
          $this->messenger()->addMessage($this->t('Please complete your payment'));
        }
      }
      else{
        $this->messenger()->addMessage($this->t('Thank you for your payment.'));
      }
    }

    else{
      $this->messenger()->addMessage($this->t('Thank you for your payment.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    Config::$serverKey =  $this->getConfiguration()['server_key'];
    Config::$isProduction = ($this->getMode() == 'production') ? TRUE : FALSE;
    $response = new Notification();
    /** @var \Drupal\commerce_payment\PaymentStorage $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    //$payment = $payment_storage->loadByRemoteId($response->order_id);
    /** @var \Drupal\commerce_order\Entity\Order $order */
    //$order = $payment->getOrder();

    //error_log('Response from Midtrans : '. print_r($response, TRUE)); //debugan
    $payment = $this->loadPaymentByOrderId($response->order_id);

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

  /**
   * Builds the payment instructions.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @return array
   *   A render array containing the payment instructions.
   */
  // public function buildPaymentInstructions(PaymentInterface $payment) {
  //   $instructions = [
  //     '#type' => 'processed_text',
  //     '#text' => $this->t('Thank you for your payment with @gateway.',
  //       ['@gateway' => $this->getLabel()],
  //       ['context' => 'Midtrans payment instructions']
  //     ),
  //     '#format' => 'plain_text',
  //   ];
  //   // error_log(print_r( $payment->getOrder()->id(),TRUE));

  //   return $instructions;
  // }
}
?>

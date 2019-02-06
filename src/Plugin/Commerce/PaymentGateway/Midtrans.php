<?php

namespace Drupal\commerce_midtrans\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Controller;
use Drupal\commerce_payment;

/**
 * Provides the Midtrans Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "midtrans",
 *   label = "Midtrans (Online Payment via Midtrans)",
 *   display_label = "Midtrans",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_midtrans\PluginForm\MidtransForm",
 *   },
*   modes= {
 *     "sandbox" = "Sandbox",
 *     "production" = "Production"
 *   },
 * )
 */
class Midtrans extends OffsitePaymentGatewayBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
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
    // $logger = \Drupal::logger('commerce_midtrans');    
    drupal_set_message('Thank you for placing your order'); 
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    \Veritrans_Config::$serverKey =  $this->getConfiguration()['server_key'];
    \Veritrans_Config::$isProduction = ($this->getMode() == 'production') ? TRUE : FALSE;
    $response = new \Veritrans_Notification();
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
}
?>
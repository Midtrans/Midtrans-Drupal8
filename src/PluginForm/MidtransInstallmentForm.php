<?php

namespace Drupal\commerce_midtrans\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteInstallmentForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MidtransInstallmentForm extends BasePaymentOffsiteInstallmentForm {
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $gateway_mode = $payment_gateway_plugin->getMode();
    $configuration = $payment_gateway_plugin->getConfiguration();

    if (version_compare(\Drupal::VERSION, "9.0.0", ">=")) {
      $plugin_info = \Drupal::service('extension.list.module')->getExtensionInfo('commerce_midtrans');
      $commerce_info = \Drupal::service('extension.list.module')->getExtensionInfo('commerce');
    }
    else {
      $plugin_info = system_get_info('module','commerce_midtrans');
      $commerce_info = system_get_info('module','commerce');
    }

    $items = [];
    foreach ($order->getItems() as $order_item) {
      $items[] = ([
        'id' => $order_item->getPurchasedEntity()->getSku(),
        'price' => ceil($order_item->getUnitPrice()->getNumber()),
        'quantity' => ceil($order_item->getQuantity()),
        'name' => $order_item->label(),
      ]);
    }

    $adjustment = $order->collectAdjustments();
    if ($adjustment){
    $array_keys = array_keys($adjustment);
      foreach($array_keys as $key){
        if ($adjustment[$key]->getType() != 'tax'){
          $items[] = ([
            'id' => $adjustment[$key]->getType(),
            'price' => ceil($adjustment[$key]->getAmount()->getNumber()),
            'quantity' => 1,
            'name' => $adjustment[$key]->getLabel(),
          ]);
        }
      }
    }

    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billingAddress */
    $CustomerDetails = $order->getBillingProfile()->get('address')->first();

    $snap_script_url = ($gateway_mode == 'production') ? "https://app.midtrans.com/snap/snap.js" : "https://app.sandbox.midtrans.com/snap/snap.js";
    \Midtrans\Config::$isProduction = ($gateway_mode == 'production') ? TRUE : FALSE;
    \Midtrans\Config::$serverKey = $configuration['server_key'];
    \Midtrans\Config::$is3ds = ($configuration['enable_3ds'] == 1) ? TRUE : FALSE;
    $mixpanel_key = ($gateway_mode == 'production') ? "17253088ed3a39b1e2bd2cbcfeca939a" : "9dcba9b440c831d517e8ff1beff40bd9";

    $params = array(
      'transaction_details' => array(
        'order_id' => $payment->getOrder()->id(),
        'gross_amount' => $order->getTotalPrice()->getNumber(),
      ),
      'item_details' => $items,
      'customer_details' => array(
        'first_name' => $CustomerDetails->getGivenName(),
        'last_name' => $CustomerDetails->getFamilyName(),
        'email' => $order->getEmail(),
        //'phone' => ,
        'billing_address' => array(
          'first_name' => $CustomerDetails->getGivenName(),
          'last_name' => $CustomerDetails->getFamilyName(),
          'address' => $CustomerDetails->getAddressLine1() . ' ' . $CustomerDetails->getAddressLine2(),
          //'country_code' => $CustomerDetails->getCountryCode(),
          'city' => $CustomerDetails->getLocality(),
          'postal_code' => $CustomerDetails->getPostalCode(),
          'country' => $CustomerDetails->getCountryCode(),
          //'phone' => ,
        ),
      ),
      'enabled_payments' => ['credit_card'],
      'callbacks' => array(
        'finish' => $form['#return_url'],
        'error' => $form['#cancel_url'],
      ),
    );

    //add installment params
    if($payment->getAmount()->getNumber() >= $configuration['min_amount']){
          $terms = array(3,6,9,12,15,18,21,24,27,30,33,36);
          $params['credit_card']['installment']['required'] = true;
          $params['credit_card']['installment']['terms'] = array(
              'bri' => $terms,
              'maybank' => $terms,
              'bri' => $terms,
              'bni' => $terms,
              'mandiri' => $terms,
              'cimb' => $terms,
              'bca' => $terms
          );
    };
    // add save card params
    if ($configuration['enable_savecard']){
      $params['user_id'] = crypt( $order->getEmail() , \Midtrans\Config::$serverKey );
      $params['credit_card']['save_card'] = true;
    }

    //add custom fields params
    $custom_fields_params = explode(", ",$configuration['custom_field']);
      if ( !empty($custom_fields_params[0]) ){
          $params['custom_field1'] = $custom_fields_params[0];
          $params['custom_field2'] = !empty($custom_fields_params[1]) ? $custom_fields_params[1] : null;
          $params['custom_field3'] = !empty($custom_fields_params[2]) ? $custom_fields_params[2] : null;
      };
    //error_log('amount '. print_r($params, TRUE)); //debugan

    // set remote id for payment
    $order_id = $order->id();
    $payments = \Drupal::entityTypeManager() ->getStorage('commerce_payment') ->loadByProperties([ 'order_id' => [$order_id], ]);
    if (!$payments){
      $payment->setRemoteId($order_id);
      $payment->save();
    }

    if (!$configuration['enable_redirect']){
      $snapToken = \Midtrans\Snap::getSnapToken($params);
      try {
      // Redirect to Midtrans SNAP PopUp page.
      }

      catch (Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
        error_log($e->getMessage());
      }
    }
    else{
      try{
        // Redirect to Midtrans SNAP Redirect page.
        $redirect_url = \Midtrans\Snap::createTransaction($params)->redirect_url;
        $response = new RedirectResponse($redirect_url);
        $response->send();
      }

      catch (Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
        error_log($e->getMessage());
      }
    }

    $js_settings = [
      'data' => [
        'snapUrl' => $snap_script_url,
        'clientKey' => $configuration['client_key'],
        'snapToken' => $snapToken,
        'merchantID' => $configuration['merchant_id'],
        'cmsName' => 'Drupal',
        'cmsVersion' => \Drupal::VERSION,
        'pluginName' => 'Midtrans Online Installment',
        'pluginVersion' => $plugin_info['version'],
        'mixpanelKey' => $mixpanel_key,
        'returnUrl' => $form['#return_url'],
        'cancelUrl' => $form['#cancel_url']
      ],
    ];

    $form['snap-button'] = [
      '#type' => 'button',
      '#value' => $this->t('Pay via Midtrans'),
      '#attributes' => ['id' => 'midtrans-checkout'],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromUri($form['#cancel_url']),
    ];

    $form['#attached']['drupalSettings']['commerce_midtrans'] = $js_settings;
    $form['#attached']['library'][] = 'commerce_midtrans/checkout';
    return $form;
  }

}

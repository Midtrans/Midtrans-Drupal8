<?php

namespace Drupal\commerce_midtrans\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentPromoOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderInterface;

class MidtransPromoForm extends BasePaymentPromoOffsiteForm {

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

    $snap_script_url = ($gateway_mode == 'production') ? "https://app.midtrans.com/snap/snap.js" : "https://app.sandbox.midtrans.com/snap/snap.js";
    \Midtrans\Config::$isProduction = ($gateway_mode == 'production') ? TRUE : FALSE;
    \Midtrans\Config::$serverKey = $configuration['server_key'];
    \Midtrans\Config::$is3ds = ($configuration['enable_3ds'] == 1) ? TRUE : FALSE;
    $mixpanel_key = ($gateway_mode == 'production') ? "17253088ed3a39b1e2bd2cbcfeca939a" : "9dcba9b440c831d517e8ff1beff40bd9";

    // set remote id for payment
    $order_id = $order->id();
    $payments = \Drupal::entityTypeManager() ->getStorage('commerce_payment') ->loadByProperties([ 'order_id' => [$order_id], ]);
    if (!$payments){
      $payment->setRemoteId($order_id);
      $payment->setAmount(new Price($order->getTotalPrice()->getNumber(), 'IDR'));
      $payment->save();
    }

    $params = $this->buildTransactionParams($order, $configuration, $form);
    if (!$configuration['enable_redirect']){
      try {
        // Redirect to Midtrans SNAP PopUp page.
        $snap_token = \Midtrans\Snap::getSnapToken($params);
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
        'snapToken' => $snap_token,
        'merchantID' => $configuration['merchant_id'],
        'cmsName' => 'Drupal',
        'cmsVersion' => \Drupal::VERSION,
        'pluginName' => 'Midtrans Promo',
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

  private function buildTransactionParams($order, $configuration, $form) {
    $items = [];
    $total_item = 0;
    foreach ($order->getItems() as $order_item) {
      $items[] = ([
        'id' => $order_item->getPurchasedEntity()->getSku(),
        'price' => intval($order_item->getUnitPrice()->getNumber()),
        'quantity' => intval($order_item->getQuantity()),
        'name' => $order_item->label(),
      ]);
      $total_item += intval($order_item->getUnitPrice()->getNumber()) * intval($order_item->getQuantity());
    }

    if($order->getTotalPrice()->getNumber() >= $configuration['min_amount']){
      if ($configuration['discount_type'] == 'percentage'){
        $total_discount = intval($total_item * $configuration['discount_amount'] / 100);
      }
      else{
        $total_discount = $configuration['discount_amount'];
      }

      if($total_discount >= $configuration['max_discount']){
        $total_discount = -($configuration['max_discount']);
      }
      else{
        $total_discount = -($total_discount);
      }

      $order->addAdjustment(new Adjustment([
        'type' => 'promotion',
        'label' => t('Midtrans Promo Payment'),
        'amount' => new Price($total_discount, 'IDR'),
        'source_id' => 'midtranspromo',
      ]));
      $order->setRefreshState(OrderInterface::REFRESH_SKIP);
      $order->save();
    }

    $adjustment = $order->collectAdjustments();
    if ($adjustment){
    $array_keys = array_keys($adjustment);
      foreach($array_keys as $key){
        if ($adjustment[$key]->getType() != 'tax'){
          $items[] = ([
            'id' => $adjustment[$key]->getType(),
            'price' => intval($adjustment[$key]->getAmount()->getNumber()),
            'quantity' => 1,
            'name' => $adjustment[$key]->getLabel(),
          ]);
        }
      }
    }

    $customer_details = $order->getBillingProfile()->get('address')->first();
    $params = array(
      'transaction_details' => array(
        'order_id' => $order->id(),
        'gross_amount' => intval($order->getTotalPrice()->getNumber()),
      ),
      'item_details' => $items,
      'customer_details' => array(
        'first_name' => $customer_details->getGivenName(),
        'last_name' => $customer_details->getFamilyName(),
        'email' => $order->getEmail(),
        //'phone' => ,
        'billing_address' => array(
          'first_name' => $customer_details->getGivenName(),
          'last_name' => $customer_details->getFamilyName(),
          'address' => $customer_details->getAddressLine1() . ' ' . $customer_details->getAddressLine2(),
          'city' => $customer_details->getLocality(),
          'postal_code' => $customer_details->getPostalCode(),
          'country' => $customer_details->getCountryCode(),
          //'phone' => ,
        ),
      ),
      'callbacks' => array(
        'finish' => $form['#return_url'],
        'error' => $form['#cancel_url'],
      ),
    );

    // add enabled_payments
    if (strlen($configuration['method_enabled']) > 0){
      $enabled_payments = explode(',', $configuration['method_enabled']);
      $params['enabled_payments'] = $enabled_payments;
    }

    // add bin params
    if (strlen($configuration['bin_number']) > 0){
      $bins = explode(',', $configuration['bin_number']);
      $params['credit_card']['whitelist_bins'] = $bins;
    }

    // add savecard params
    if ($configuration['enable_savecard']){
      $params['user_id'] = crypt( $order->getEmail(), \Midtrans\Config::$serverKey);
      $params['credit_card']['save_card'] = true;
    }

    //add custom expiry params
    $custom_expiry_params = explode(" ",$configuration['custom_expiry']);
    if ( !empty($custom_expiry_params[1]) && !empty($custom_expiry_params[0]) ){
      $params['expiry'] = array(
        'unit' => $custom_expiry_params[1],
        'duration'  => (int)$custom_expiry_params[0],
      );
    };

    //add custom fields params
    $custom_fields_params = explode(", ",$configuration['custom_field']);
    if ( !empty($custom_fields_params[0]) ){
      $params['custom_field1'] = $custom_fields_params[0];
      $params['custom_field2'] = !empty($custom_fields_params[1]) ? $custom_fields_params[1] : null;
      $params['custom_field3'] = !empty($custom_fields_params[2]) ? $custom_fields_params[2] : null;
    };

    return $params;
  }
}

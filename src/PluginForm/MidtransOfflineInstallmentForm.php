<?php

namespace Drupal\commerce_midtrans\PluginForm;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteInstallmentOfflineForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MidtransOfflineInstallmentForm extends BasePaymentOffsiteInstallmentOfflineForm {

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
    
    $items = [];
    foreach ($order->getItems() as $order_item) {
      $items[] = ([
        'id' => $order_item->getPurchasedEntity()->getSku(),
        'name' => $order_item->label(),
        'quantity' => substr($order_item->getQuantity(),0,strpos($order_item->getQuantity(), ".")),
        'price' => substr($order_item->getUnitPrice()->getNumber(),0,strpos($order_item->getUnitPrice()->getNumber(), ".")),
      ]);
    }
    //error_log(print_r($items, TRUE)); //debugan
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billingAddress */
    $CustomerDetails = $order->getBillingProfile()->get('address')->first();
    
    $snap_script_url = ($gateway_mode == 'production') ? "https://app.midtrans.com/snap/snap.js" : "https://app.sandbox.midtrans.com/snap/snap.js";
    \Veritrans_Config::$isProduction = ($gateway_mode == 'production') ? TRUE : FALSE;
    \Veritrans_Config::$serverKey = $configuration['server_key'];
    \Veritrans_Config::$is3ds = ($configuration['enable_3ds'] == 1) ? TRUE : FALSE;

    $params = array(
      'transaction_details' => array(
        'order_id' => $payment->getOrder()->id(),
        'gross_amount' => $payment->getAmount()->getNumber(),
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

    //add minimal amount params
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
    
    // add bin params
    if (strlen($configuration['bin_number']) > 0){
      $bins = explode(',', $configuration['bin_number']);
      $params['credit_card']['whitelist_bins'] = $bins;
    }
    
    // add savecard params
    if ($configuration['enable_savecard']){
      $params['user_id'] = crypt( $order->getEmail() , \Veritrans_Config::$serverKey );
      $params['credit_card']['save_card'] = true;
    }
    
    // add custom field params      
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
      $snapToken = \Veritrans_Snap::getSnapToken($params); 
      error_log($snapToken);

      try {
      // Redirect to Midtrans SNAP PopUp page.
      ?>
        <script src="<?php echo $snap_script_url;?>" data-client-key="<?php echo $configuration['client_key'];?>"></script>
        <script type="text/javascript">
        var retryCount = 0;
        var snapExecuted = false;
        var intervalFunction = 0;
      // Continously retry to execute SNAP popup if fail, with 1000ms delay between retry
        intervalFunction = setInterval(function() {
          try{
            snap.pay('<?=$snapToken?>', 
            {
              skipOrderSummary : true,
              onError: function(result){
                window.location = "<?php echo $form['#cancel_url'];?>";
              },
            });
            snapExecuted = true; // if SNAP popup executed, change flag to stop the retry.
          }

          catch (e){ 
            retryCount++;
            if(retryCount >= 10){
              location.reload(); 
              return;
            }

          console.log(e);
          console.log("Snap not ready yet... Retrying in 1000ms!");
          }

          finally {
            if (snapExecuted) {
              clearInterval(intervalFunction);
            }
          }
        }, 1000);

        </script>
      <?php  
      }
      
      catch (Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
        error_log($e->getMessage());
      }
    }
    else{
      // Redirect to Midtrans SNAP Redirect page.      
      $redirect_url = \Veritrans_Snap::createTransaction($params)->redirect_url;
      $response = new RedirectResponse($redirect_url);
      $response->send();
    }
    $form = $this->buildRedirectForm($form, $form_state, '', $params, '');        
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, $redirect_url, array $data, $redirect_method = BasePaymentOffsiteForm::REDIRECT_GET) {
    $helpMessage = t('Please wait while the payment server loads. If nothing happens,');
    $form['commerce_message'] = [
      '#markup' => '<div class="checkout-help">' . $helpMessage . '<a href=""> click me.</a>' . '</div>',
      '#weight' => -10,
    ];
    return $form;
  }
}
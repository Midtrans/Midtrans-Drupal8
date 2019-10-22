<?php

namespace Drupal\commerce_midtrans\PluginForm;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransForm extends BasePaymentOffsiteForm {
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
    $info = system_get_info('module','commerce_midtrans');

    $items = [];
    $total_item = 0;
    foreach ($order->getItems() as $order_item) {
      $items[] = ([
        'id' => $order_item->getPurchasedEntity()->getSku(),
        'price' => intval($order_item->getUnitPrice()->getNumber()),
        'quantity' => intval($order_item->getQuantity()),
        'name' => $order_item->label(),
      ]);
      $total_item = $total_item + (intval($order_item->getUnitPrice()->getNumber()) * intval($order_item->getQuantity()));
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

    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billingAddress */
    $CustomerDetails = $order->getBillingProfile()->get('address')->first();

    $snap_script_url = ($gateway_mode == 'production') ? "https://app.midtrans.com/snap/snap.js" : "https://app.sandbox.midtrans.com/snap/snap.js";
    Config::$isProduction = ($gateway_mode == 'production') ? TRUE : FALSE;
    Config::$serverKey = $configuration['server_key'];
    Config::$is3ds = ($configuration['enable_3ds'] == 1) ? TRUE : FALSE;
    $mixpanel_key = ($gateway_mode == 'production') ? "17253088ed3a39b1e2bd2cbcfeca939a" : "9dcba9b440c831d517e8ff1beff40bd9";

    $params = array(
      'transaction_details' => array(
        'order_id' => $payment->getOrder()->id(),
        'gross_amount' => intval($order->getTotalPrice()->getNumber()),
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
      'callbacks' => array(
        'finish' => $form['#return_url'],
        'error' => $form['#cancel_url'],
      ),
    );
    // add savecard params
    if ($configuration['enable_savecard']){
      $params['user_id'] = crypt( $order->getEmail() , Config::$serverKey );
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
    // error_log(print_r($params, TRUE)); //debugan
    // set remote id for payment
    $order_id = $order->id();
    $payments = \Drupal::entityTypeManager() ->getStorage('commerce_payment') ->loadByProperties([ 'order_id' => [$order_id], ]);
    if (!$payments){
      $payment->setRemoteId($order_id);
      $payment->save();
    }

//     $current_uri = \Drupal::request()->getRequestUri();
// $lala =\Drupal::request()->getSchemeAndHttpHost();
// $sad = $lala . $current_uri;
//     error_log($sad);

// $numda = strpos($sad, 'payment');
// $pending = substr($sad, 0, strpos($sad, 'payment')) . 'complete';
// error_log($pending);

    if (!$configuration['enable_redirect']){
      try {
        $snapToken = Snap::getSnapToken($params);
        // Redirect to Midtrans SNAP PopUp page.
      ?>
        <!-- start Mixpanel -->
        <script type="text/javascript">(function(c,a){if(!a.__SV){var b=window;try{var d,m,j,k=b.location,f=k.hash;d=function(a,b){return(m=a.match(RegExp(b+"=([^&]*)")))?m[1]:null};f&&d(f,"state")&&(j=JSON.parse(decodeURIComponent(d(f,"state"))),"mpeditor"===j.action&&(b.sessionStorage.setItem("_mpcehash",f),history.replaceState(j.desiredHash||"",c.title,k.pathname+k.search)))}catch(n){}var l,h;window.mixpanel=a;a._i=[];a.init=function(b,d,g){function c(b,i){var a=i.split(".");2==a.length&&(b=b[a[0]],i=a[1]);b[i]=function(){b.push([i].concat(Array.prototype.slice.call(arguments,0)))}}var e=a;"undefined"!==typeof g?e=a[g]=[]:g="mixpanel";e.people=e.people||[];e.toString=function(b){var a="mixpanel";"mixpanel"!==g&&(a+="."+g);b||(a+=" (stub)");return a};e.people.toString=function(){return e.toString(1)+".people (stub)"};l="disable time_event track track_pageview track_links track_forms track_with_groups add_group set_group remove_group register register_once alias unregister identify name_tag set_config reset opt_in_tracking opt_out_tracking has_opted_in_tracking has_opted_out_tracking clear_opt_in_out_tracking people.set people.set_once people.unset people.increment people.append people.union people.track_charge people.clear_charges people.delete_user people.remove".split(" ");for(h=0;h<l.length;h++)c(e,l[h]);var f="set set_once union unset remove delete".split(" ");e.get_group=function(){function a(c){b[c]=function(){call2_args=arguments;call2=[c].concat(Array.prototype.slice.call(call2_args,0));e.push([d,call2])}}for(var b={},d=["get_group"].concat(Array.prototype.slice.call(arguments,0)),c=0;c<f.length;c++)a(f[c]);return b};a._i.push([b,d,g])};a.__SV=1.2;b=c.createElement("script");b.type="text/javascript";b.async=!0;b.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:"file:"===c.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";d=c.getElementsByTagName("script")[0];d.parentNode.insertBefore(b,d)}})(document,window.mixpanel||[]);mixpanel.init("<?php echo $mixpanel_key ?>");</script>
        <!-- end Mixpanel -->
        <script src="<?php echo $snap_script_url;?>" data-client-key="<?php echo $configuration['client_key'];?>"></script>
        <script type="text/javascript">
          function MixpanelTrackResult(snap_token, merchant_id, cms_name, cms_version, plugin_name, plugin_version, status, result) {
            var eventNames = {
              pay: 'pg-pay',
              success: 'pg-success',
              pending: 'pg-pending',
              error: 'pg-error',
              close: 'pg-close'
            };
            mixpanel.track(eventNames[status], {
              merchant_id: merchant_id,
              cms_name: cms_name,
              cms_version: cms_version,
              plugin_name: plugin_name,
              plugin_version: plugin_version,
              snap_token: snap_token,
              payment_type: result ? result.payment_type: null,
              order_id: result ? result.order_id: null,
              status_code: result ? result.status_code: null,
              gross_amount: result && result.gross_amount ? Number(result.gross_amount) : null,
            });
          }
          var MID_SNAP_TOKEN = "<?=$snapToken?>";
          var MID_MERCHANT_ID = "<?=$configuration['merchant_id'];?>";
          var MID_CMS_NAME = "drupal 8";
          var MID_CMS_VERSION = "<?=\Drupal::VERSION?>";
          var MID_PLUGIN_NAME = "fullpayment";
          var MID_PLUGIN_VERSION = "<?=$info['version']?>";

          var retryCount = 0;
          var snapExecuted = false;
          var intervalFunction = 0;
        // Continously retry to execute SNAP popup if fail, with 1000ms delay between retry
        intervalFunction = setInterval(function() {
          try{
            snap.pay(MID_SNAP_TOKEN,{
              skipOrderSummary : true,
              onSuccess: function(result){
                MixpanelTrackResult(MID_SNAP_TOKEN, MID_MERCHANT_ID, MID_CMS_NAME, MID_CMS_VERSION, MID_PLUGIN_NAME,MID_PLUGIN_VERSION, 'success', result);
                window.location = '<?php echo $form['#return_url'];?>';
              },
              onPending: function(result){
                MixpanelTrackResult(MID_SNAP_TOKEN, MID_MERCHANT_ID, MID_CMS_NAME, MID_CMS_VERSION, MID_PLUGIN_NAME, MID_PLUGIN_VERSION, 'pending', result);
                window.location = "<?php echo $form['#return_url'];?>?&pdf="+result.pdf_url;
                // alert('awaiting payment');
              },
              onError: function(result){
                MixpanelTrackResult(MID_SNAP_TOKEN, MID_MERCHANT_ID, MID_CMS_NAME, MID_CMS_VERSION, MID_PLUGIN_NAME, MID_PLUGIN_VERSION, 'error', result);
                window.location = "<?php echo $form['#cancel_url'];?>";
              },
              onClose: function(){
                MixpanelTrackResult(MID_SNAP_TOKEN, MID_MERCHANT_ID, MID_CMS_NAME, MID_CMS_VERSION, MID_PLUGIN_NAME, MID_PLUGIN_VERSION, 'close', null);
              }
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
              MixpanelTrackResult(MID_SNAP_TOKEN, MID_MERCHANT_ID, MID_CMS_NAME, MID_CMS_VERSION, MID_PLUGIN_NAME, MID_PLUGIN_VERSION, 'pay', null);
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
      try{
        // Redirect to Midtrans SNAP Redirect page.
        $redirect_url = Snap::createTransaction($params)->redirect_url;
        $response = new RedirectResponse($redirect_url);
        $response->send();
      }

      catch (Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
        error_log($e->getMessage());
      }
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

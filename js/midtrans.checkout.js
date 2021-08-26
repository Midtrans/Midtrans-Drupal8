(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.initMidtransModule = {
    attach: function (context) {
      var $midtransModule = $(context).find('#midtrans-checkout');
      if ($midtransModule.length > 0) {
        var midtransSettings = drupalSettings.commerce_midtrans;
        $midtransModule.once('init-midtrans-checkout').each(function () {
          var script = document.createElement('script');
          script.type = 'text/javascript';
          script.src = midtransSettings.data.snapUrl;
          script.setAttribute('data-client-key', midtransSettings.data.clientKey);
          script.onload = function () {
            snap.pay(midtransSettings.data.snapToken, {
              skipOrderSummary : true,
              onSuccess: function(result){
                MixpanelTrackResult(midtransSettings.data.snapToken, midtransSettings.data.merchantID, midtransSettings.data.cmsName, midtransSettings.data.cmsVersion, midtransSettings.data.pluginName,midtransSettings.data.pluginVersion, 'success', result);
                window.location = midtransSettings.data.returnUrl;
              },
              onPending: function(result){
                MixpanelTrackResult(midtransSettings.data.snapToken, midtransSettings.data.merchantID, midtransSettings.data.cmsName, midtransSettings.data.cmsVersion, midtransSettings.data.pluginName, midtransSettings.data.pluginVersion, 'pending', result);
                window.location = midtransSettings.data.returnUrl + "?pdf="+result.pdf_url;
              },
              onError: function(result){
                MixpanelTrackResult(midtransSettings.data.snapToken, midtransSettings.data.merchantID, midtransSettings.data.cmsName, midtransSettings.data.cmsVersion, midtransSettings.data.pluginName, midtransSettings.data.pluginVersion, 'error', result);
                window.location = midtransSettings.data.cancelUrl;
              },
              onClose: function(){
                MixpanelTrackResult(midtransSettings.data.snapToken, midtransSettings.data.merchantID, midtransSettings.data.cmsName, midtransSettings.data.cmsVersion, midtransSettings.data.pluginName, midtransSettings.data.pluginVersion, 'close', null);
              }
            });
          };
          var s = document.getElementsByTagName('script')[0];
          s.parentNode.insertBefore(script, s);
        });

        var payButton = document.getElementById('midtrans-checkout');
        payButton.addEventListener('click', function (e) {
          e.preventDefault();
          snap.pay(midtransSettings.data.snapToken, {
            skipOrderSummary : true,
            onSuccess: function(result){
              MixpanelTrackResult(midtransSettings.data.snapToken, midtransSettings.data.merchantID, midtransSettings.data.cmsName, midtransSettings.data.cmsVersion, midtransSettings.data.pluginName,midtransSettings.data.pluginVersion, 'success', result);
              window.location = midtransSettings.data.returnUrl;
            },
            onPending: function(result){
              MixpanelTrackResult(midtransSettings.data.snapToken, midtransSettings.data.merchantID, midtransSettings.data.cmsName, midtransSettings.data.cmsVersion, midtransSettings.data.pluginName, midtransSettings.data.pluginVersion, 'pending', result);
              window.location = midtransSettings.data.returnUrl + "?pdf="+result.pdf_url;
            },
            onError: function(result){
              MixpanelTrackResult(midtransSettings.data.snapToken, midtransSettings.data.merchantID, midtransSettings.data.cmsName, midtransSettings.data.cmsVersion, midtransSettings.data.pluginName, midtransSettings.data.pluginVersion, 'error', result);
              window.location = midtransSettings.data.cancelUrl;
            },
            onClose: function(){
              MixpanelTrackResult(midtransSettings.data.snapToken, midtransSettings.data.merchantID, midtransSettings.data.cmsName, midtransSettings.data.cmsVersion, midtransSettings.data.pluginName, midtransSettings.data.pluginVersion, 'close', null);
            }
          });
        });

      }
    }
  };

(function(c,a){if(!a.__SV){var b=window;try{var d,m,j,k=b.location,f=k.hash;d=function(a,b){return(m=a.match(RegExp(b+"=([^&]*)")))?m[1]:null};f&&d(f,"state")&&(j=JSON.parse(decodeURIComponent(d(f,"state"))),"mpeditor"===j.action&&(b.sessionStorage.setItem("_mpcehash",f),history.replaceState(j.desiredHash||"",c.title,k.pathname+k.search)))}catch(n){}var l,h;window.mixpanel=a;a._i=[];a.init=function(b,d,g){function c(b,i){var a=i.split(".");2==a.length&&(b=b[a[0]],i=a[1]);b[i]=function(){b.push([i].concat(Array.prototype.slice.call(arguments,0)))}}var e=a;"undefined"!==typeof g?e=a[g]=[]:g="mixpanel";e.people=e.people||[];e.toString=function(b){var a="mixpanel";"mixpanel"!==g&&(a+="."+g);b||(a+=" (stub)");return a};e.people.toString=function(){return e.toString(1)+".people (stub)"};l="disable time_event track track_pageview track_links track_forms track_with_groups add_group set_group remove_group register register_once alias unregister identify name_tag set_config reset opt_in_tracking opt_out_tracking has_opted_in_tracking has_opted_out_tracking clear_opt_in_out_tracking people.set people.set_once people.unset people.increment people.append people.union people.track_charge people.clear_charges people.delete_user people.remove".split(" ");for(h=0;h<l.length;h++)c(e,l[h]);var f="set set_once union unset remove delete".split(" ");e.get_group=function(){function a(c){b[c]=function(){call2_args=arguments;call2=[c].concat(Array.prototype.slice.call(call2_args,0));e.push([d,call2])}}for(var b={},d=["get_group"].concat(Array.prototype.slice.call(arguments,0)),c=0;c<f.length;c++)a(f[c]);return b};a._i.push([b,d,g])};a.__SV=1.2;b=c.createElement("script");b.type="text/javascript";b.async=!0;b.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:"file:"===c.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";d=c.getElementsByTagName("script")[0];d.parentNode.insertBefore(b,d)}})(document,window.mixpanel||[]);mixpanel.init(drupalSettings.commerce_midtrans.data.mixpanelKey);

})(jQuery, Drupal, drupalSettings);

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

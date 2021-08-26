// This file for dynamically put sandbox/production text label on API keys field, according to selected API env mode.
// For each of Midtrans Modules (fullpayment, installment, promo etc)

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.initMidtransModule = {
    attach: function (context) {
      var midtransSettings = drupalSettings.commerce_midtrans;
      var midtransModule = $(context).find(midtransSettings.id);
      if (midtransModule.length > 0) {
        updateEnvMode(midtransSettings.module);
      }
    }
  };

  function updateEnvMode(moduleName) {
    var selectorName = moduleName.replace('_', '-');
    var mode = $("input[type='radio'][name='configuration[" +moduleName+ "][mode]']:checked").val();
    updateTextLabel(selectorName, mode);

    $("input[type='radio'][name='configuration[" +moduleName+ "][mode]']").on('change', function() {
        updateTextLabel(selectorName, this.value);
    });
  }

  function updateTextLabel(selectorName, mode) {
    var hrefs = document.querySelectorAll('.config_info');
    var textLabel = capitalizeLabel(mode);
    var subdomain = (mode == 'sandbox') ? 'dashboard.sandbox' : 'dashboard';
    document.querySelector('label[for^="edit-configuration-' +selectorName+ '-server-key"]').innerText = "Server Key " +textLabel;
    document.querySelector('label[for^="edit-configuration-' +selectorName+ '-client-key"]').innerText = "Client Key " +textLabel;
    hrefs.forEach(function(e){
        e.href = 'https://' +subdomain+ '.midtrans.com/settings/config_info'
    });
  }

  function capitalizeLabel(string) {
    return string[0].toUpperCase() + string.slice(1);
  }

})(jQuery, Drupal, drupalSettings);

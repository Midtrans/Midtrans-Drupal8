(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.initMidtransModule = {
    attach: function (context) {
      var midtransModule = $(context).find('#midtrans-admin-module');
      if (midtransModule.length > 0) {
        var hrefs = document.querySelectorAll('.config_info');
        var mode = $("input[type='radio'][name='configuration[midtrans][mode]']:checked").val();
        if (mode == 'sandbox'){
          document.querySelector('label[for^="edit-configuration-midtrans-server-key"]').innerText = "Server Key Sandbox";
          document.querySelector('label[for^="edit-configuration-midtrans-client-key"]').innerText = "Client Key Sandbox";
          hrefs.forEach(function(e){
              e.href = 'https://dashboard.sandbox.midtrans.com/settings/config_info'
          });
        } else {
          document.querySelector('label[for^="edit-configuration-midtrans-server-key"]').innerText = "Server Key Production";
          document.querySelector('label[for^="edit-configuration-midtrans-client-key"]').innerText = "Client Key Production";
          hrefs.forEach(function(e){
              e.href = 'https://dashboard.midtrans.com/settings/config_info'
          });
        }

        $("input[type='radio'][name='configuration[midtrans][mode]']").on('change', function() {
            if (this.value == 'sandbox'){
              document.querySelector('label[for^="edit-configuration-midtrans-server-key"]').innerText = "Server Key Sandbox";
              document.querySelector('label[for^="edit-configuration-midtrans-client-key"]').innerText = "Client Key Sandbox";
              hrefs.forEach(function(e){
                  e.href = 'https://dashboard.sandbox.midtrans.com/settings/config_info'
              });
            } else {
              document.querySelector('label[for^="edit-configuration-midtrans-server-key"]').innerText = "Server Key Production";
              document.querySelector('label[for^="edit-configuration-midtrans-client-key"]').innerText = "Client Key Production";
              hrefs.forEach(function(e){
                  e.href = 'https://dashboard.midtrans.com/settings/config_info'
              });
            }
        });
      }

      // installment
      var midtransModuleInstallment = $(context).find('#midtrans-admin-module-installment');
      if (midtransModuleInstallment.length > 0) {
        var hrefs = document.querySelectorAll('.config_info');
        var mode = $("input[type='radio'][name='configuration[midtrans_installment][mode]']:checked").val();
        if (mode == 'sandbox'){
          document.querySelector('label[for^="edit-configuration-midtrans-installment-server-key"]').innerText = "Server Key Sandbox";
          document.querySelector('label[for^="edit-configuration-midtrans-installment-client-key"]').innerText = "Client Key Sandbox";
          hrefs.forEach(function(e){
              e.href = 'https://dashboard.sandbox.midtrans.com/settings/config_info'
          });
        } else {
          document.querySelector('label[for^="edit-configuration-midtrans-installment-server-key"]').innerText = "Server Key Production";
          document.querySelector('label[for^="edit-configuration-midtrans--installment-client-key"]').innerText = "Client Key Production";
          hrefs.forEach(function(e){
              e.href = 'https://dashboard.midtrans.com/settings/config_info'
          });
        }

        $("input[type='radio'][name='configuration[midtrans_installment][mode]']").on('change', function() {
            if (this.value == 'sandbox'){
              document.querySelector('label[for^="edit-configuration-midtrans-installment-server-key"]').innerText = "Server Key Sandbox";
              document.querySelector('label[for^="edit-configuration-midtrans-installment-client-key"]').innerText = "Client Key Sandbox";
              hrefs.forEach(function(e){
                  e.href = 'https://dashboard.sandbox.midtrans.com/settings/config_info'
              });
            } else {
              document.querySelector('label[for^="edit-configuration-midtrans-installment-server-key"]').innerText = "Server Key Production";
              document.querySelector('label[for^="edit-configuration-midtrans-installment-client-key"]').innerText = "Client Key Production";
              hrefs.forEach(function(e){
                  e.href = 'https://dashboard.midtrans.com/settings/config_info'
              });
            }
        });
      }

      // installment off
      var midtransModuleInstallmentOff = $(context).find('#midtrans-admin-module-installmentoff');
      if (midtransModuleInstallmentOff.length > 0) {
        var hrefs = document.querySelectorAll('.config_info');
        var mode = $("input[type='radio'][name='configuration[midtrans_installmentoff][mode]']:checked").val();
        if (mode == 'sandbox'){
          document.querySelector('label[for^="edit-configuration-midtrans-installmentoff-server-key"]').innerText = "Server Key Sandbox";
          document.querySelector('label[for^="edit-configuration-midtrans-installmentoff-client-key"]').innerText = "Client Key Sandbox";
          hrefs.forEach(function(e){
              e.href = 'https://dashboard.sandbox.midtrans.com/settings/config_info'
          });
        } else {
          document.querySelector('label[for^="edit-configuration-midtrans-installmentoff-server-key"]').innerText = "Server Key Production";
          document.querySelector('label[for^="edit-configuration-midtrans-installmentoff-client-key"]').innerText = "Client Key Production";
          hrefs.forEach(function(e){
              e.href = 'https://dashboard.midtrans.com/settings/config_info'
          });
        }

        $("input[type='radio'][name='configuration[midtrans_installmentoff][mode]']").on('change', function() {
            if (this.value == 'sandbox'){
              document.querySelector('label[for^="edit-configuration-midtrans-installmentoff-server-key"]').innerText = "Server Key Sandbox";
              document.querySelector('label[for^="edit-configuration-midtrans-installmentoff-client-key"]').innerText = "Client Key Sandbox";
              hrefs.forEach(function(e){
                  e.href = 'https://dashboard.sandbox.midtrans.com/settings/config_info'
              });
            } else {
              document.querySelector('label[for^="edit-configuration-midtrans-installmentoff-server-key"]').innerText = "Server Key Production";
              document.querySelector('label[for^="edit-configuration-midtrans-installmentoff-client-key"]').innerText = "Client Key Production";
              hrefs.forEach(function(e){
                  e.href = 'https://dashboard.midtrans.com/settings/config_info'
              });
            }
        });
      }

      // promo
      var midtransModulePromo = $(context).find('#midtrans-admin-module-promo');
      if (midtransModulePromo.length > 0) {
        var hrefs = document.querySelectorAll('.config_info');
        var mode = $("input[type='radio'][name='configuration[midtrans_promo][mode]']:checked").val();
        if (mode == 'sandbox'){
          document.querySelector('label[for^="edit-configuration-midtrans-promo-server-key"]').innerText = "Server Key Sandbox";
          document.querySelector('label[for^="edit-configuration-midtrans-promo-client-key"]').innerText = "Client Key Sandbox";
          hrefs.forEach(function(e){
              e.href = 'https://dashboard.sandbox.midtrans.com/settings/config_info'
          });
        } else {
          document.querySelector('label[for^="edit-configuration-midtrans-promo-server-key"]').innerText = "Server Key Production";
          document.querySelector('label[for^="edit-configuration-midtrans-promo-client-key"]').innerText = "Client Key Production";
          hrefs.forEach(function(e){
              e.href = 'https://dashboard.midtrans.com/settings/config_info'
          });
        }

        $("input[type='radio'][name='configuration[midtrans_promo][mode]']").on('change', function() {
            if (this.value == 'sandbox'){
              document.querySelector('label[for^="edit-configuration-midtrans-promo-server-key"]').innerText = "Server Key Sandbox";
              document.querySelector('label[for^="edit-configuration-midtrans-promo-client-key"]').innerText = "Client Key Sandbox";
              hrefs.forEach(function(e){
                  e.href = 'https://dashboard.sandbox.midtrans.com/settings/config_info'
              });
            } else {
              document.querySelector('label[for^="edit-configuration-midtrans-promo-server-key"]').innerText = "Server Key Production";
              document.querySelector('label[for^="edit-configuration-midtrans-promo-client-key"]').innerText = "Client Key Production";
              hrefs.forEach(function(e){
                  e.href = 'https://dashboard.midtrans.com/settings/config_info'
              });
            }
        });
      }


    }
  };

})(jQuery, Drupal, drupalSettings);

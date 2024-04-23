"use strict";

jQuery(document).ready(function() {
    var confirmOrderBtn = jQuery('#confirm_order');
    var url = null;
    var host = window.ecpHost || 'paymentpage.ecommpay.com';

    if (confirmOrderBtn.length === 0) {
        confirmOrderBtn = jQuery('#payment-confirmation [type=submit]');
        url = window.paymentUrlWithConfirm;

        confirmOrderBtn.on('click', function(e) {
            var isEcommpay = $('.payment-options [type=radio]:checked')
                .closest('.payment-option')
                .find('label')
                .text().match(/Ecommpay/); 
            if (!isEcommpay) {
                return;
            }
            e.stopPropagation();
            triggerPopup(e);
        });
    } else {
        url = confirmOrderBtn.attr('href');

        confirmOrderBtn.on('click', function(e) {
            triggerPopup(e);
        });
    }

    var redirectUrl = false;
    var isProcessing = false;
    var isJsLoaded = false;

    function triggerPopup(e) {
        e.preventDefault();

        if (!isJsLoaded) {
            console.log('js is not loaded yet');
            return;
        }

        if (redirectUrl) {
            showPopup(redirectUrl);
            return;
        }

        if (isProcessing) {
            return;
        }

        jQuery.ajax({
            method: 'POST',
            url: url,
            dataType: 'json',
            data: {
                is_ajax: true
            },
            success: function(response) {
                isProcessing = false;
                if (response.success) {
                    redirectUrl = response.cardRedirectUrl;
                    showPopup(redirectUrl);
                    return;
                }
                alert(response.error);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                isProcessing = false;
                alert(textStatus);
            }
        })
    }

    function showPopup(url) {
        var link = document.createElement('a');
        link.href = url;
        var params = link.search.replace(/^\?/, '');
        var config = parseParams(params);
        config.onPaymentSuccess = function() {
            window.location.replace(config.merchant_success_url);
        };

        config.onPaymentFail = function() {
            window.location.replace(config.merchant_fail_url);
        };

        console.log(config);
        EPayWidget.run(config);
    }

    function parseParams(str) {
        return str.split('&').reduce(function (params, param) {
            var paramSplit = param.split('=').map(function (value) {
                return decodeURIComponent(value.replace('+', ' '));
            });
            params[paramSplit[0]] = paramSplit[1];
            return params;
        }, {});
    }

    jQuery('head').append('<link rel="stylesheet" href="https://' + host + '/shared/merchant.css" type="text/css" />');

    var head= document.getElementsByTagName('head')[0];
    var script= document.createElement('script');
    script.type= 'text/javascript';
    script.src= 'https://' + host + '/shared/merchant.js';
    script.async = true;
    script.onload = function() {
        isJsLoaded = true;
    };
    head.appendChild(script);
});

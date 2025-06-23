/**
 * @file payment.js
 * @description Handles payment processing
 * @global jQuery, $
 */

'use strict'

console.log('payment.js loaded')

const MESSAGES = {
  SUBMIT: 'epframe.embedded_mode.submit',
  CHECK_VALIDATION: 'epframe.embedded_mode.check_validation',
}

class EcommpayPaymentHandler {
  constructor() {
    this.isProcessing = false
    this.redirectUrl = null
    this.paymentConfig = null
    this.orderId = null
    this.host = window.ECOMMPAY_HOST || 'https://paymentpage.ecommpay.com'
    this.cardDisplayMode = window.ECOMMPAY_CARD_DISPLAY_MODE
    this.init()
  }

  init() {
    this.queueEcommpayResources()
    this.setupPaymentButtons()
    if (this.isPaymentMethodEnabled('card') && this.cardDisplayMode == 'embedded') {
      this.refreshPaymentConfig().then(() => {
        this.initEmbeddedMode()
      })
    }
    this.setupApplePay()
  }

  queueEcommpayResources() {
    $('head')
      .append(
        $('<link>')
          .attr('rel', 'stylesheet')
          .attr('type', 'text/css')
          .attr('href', this.host + '/shared/merchant.css')
      )
      .append(
        $('<script>')
          .attr('type', 'text/javascript')
          .attr('src', this.host + '/shared/merchant.js')
      )
  }

  setupPaymentButtons() {
    const confirmOrderBtn = $('#payment-confirmation button[type=submit]')
    confirmOrderBtn.on('click', (e) => {
      e.preventDefault()
      e.stopPropagation()
      this.handlePaymentButtonClick(e)
    })
  }

  handlePaymentButtonClick() {
    if (!this.isEmbeddedModeSelected()) {
      this.createOrder()
      return
    }
    if (this.isProcessing) {
      console.log('Payment is already processing')
      return
    }
    this.checkValidation()
  }

  checkValidation() {
    this.sendIframePostMessage({
      message: MESSAGES.CHECK_VALIDATION,
      from_another_domain: true,
    })
  }

  submitIframe() {
    console.log('Submitting iframe...')
    this.showLoader()
    this.isProcessing = true

    $.ajax({
      url: window.ECOMMPAY_CHECK_CART_URL,
      method: 'POST',
      data: {
        amount: this.paymentAmount,
      },
      success: (response) => {
        console.log('Cart check response:', response)
        if (!response.amount_is_equal) {
          console.log('Cart amount changed, reloading page')
          window.location.reload()
          return
        }

        this.sendIframePostMessage({
          message: MESSAGES.SUBMIT,
          fields: {},
          from_another_domain: true,
        })
      },
      error: (jqXHR, textStatus, errorThrown) => {
        console.error('Cart check failed:', { jqXHR, textStatus, errorThrown })
        this.showError('Error checking cart: ' + (errorThrown || textStatus))
        this.isProcessing = false
        this.hideLoader()
      },
    })
  }

  sendIframePostMessage(message) {
    console.log('Sending post message:', message)
    window.postMessage(JSON.stringify(message), '*')
  }

  /**
   * Returns selected payment method
   * f.e. 'card'
   * @returns {*|jQuery}
   */
  getSelectedPaymentMethod() {
    const checkedOptions = $('.payment-options [type=radio]:checked')
    if (checkedOptions.length === 0) {
      return null
    }
    const selectedPaymentOption = checkedOptions[0].id // f.e. 'payment-option-2'
    return $('#' + selectedPaymentOption + '-additional-information input[name=payment_method_code]').val()
  }

  isPaymentMethodEnabled(name) {
    return $('input[name=payment_method_code][value=' + name + ']').length > 0
  }

  isEmbeddedModeSelected() {
    return this.getSelectedPaymentMethod() === 'card' && this.cardDisplayMode === 'embedded'
  }

  isPopupModeSelected() {
    return this.getSelectedPaymentMethod() === 'card' && this.cardDisplayMode === 'popup'
  }

  refreshPaymentConfig() {
    return new Promise((resolve, reject) => {
      if (typeof EPayWidget === 'undefined') {
        setTimeout(() => {
          this.refreshPaymentConfig().then(() => {
            resolve()
          })
        }, 100)
        return
      }
      const _this = this
      $.ajax({
        url: window.ECOMMPAY_PAYMENT_INFO_URL,
        method: 'GET',
        dataType: 'json',
        data: {
          payment_method: this.getSelectedPaymentMethod(),
          order_id: this.orderId,
        },
        success: function (data) {
          if (!data.success) {
            _this.showError('Failed to initialize payment page: ' + data.error)
            return
          }
          _this.paymentConfig = data.payment_config
          console.log('Payment config refreshed.', _this.paymentConfig)
          resolve()
        },
        error: function (jqXHR, textStatus, errorThrown) {
          _this.showError('Failed to initialize payment page: ' + textStatus)
          reject()
        },
      })
    })
  }

  getPaymentConfig(paramsObject) {
    return {
      ...paramsObject,
      onLoaded: () => {
        console.log('Payment widget loaded')
        jQuery('#ecommpay-iframe').height('auto')
        this.hideLoader()
      },
      onEmbeddedModeCheckValidationResponse: (data) => {
        console.log('Validation response received:', data)
        this.handleValidationResponse(data)
      },
      onEnterKeyPressed: () => {
        console.log('Enter key pressed')
        $('#payment-confirmation button[type=submit]').click()
      },
      onPaymentSent: () => {
        console.log('Payment sent - showing loader')
        this.isProcessing = true
        this.showLoader()
      },
      onSubmitClarificationForm: () => {
        console.log('Submitting clarification form - showing loader')
        this.showLoader()
      },
      onShowClarificationPage: () => {
        console.log('Showing clarification page - hiding loader')
        this.isProcessing = false
        this.hideLoader()
        this.handleClarification()
      },
      onEmbeddedModeRedirect3dsParentPage: (data) => {
        console.log('3DS redirect - showing loader')
        this.showLoader()
        this.handle3DSRedirect(data)
      },
      onPaymentSuccess: (data) => {
        console.log('Payment success - showing loader')
        this.showLoader()
        this.handlePaymentSuccess(data)
      },
      onCardVerifySuccess: (data) => {
        console.log('Card verification success - showing loader')
        this.showLoader()
        this.handlePaymentSuccess(data)
      },
      onPaymentFail: (data) => {
        console.log('Payment failed - showing loader')
        this.showLoader()
        this.handlePaymentFail(data)
      },
      onCardVerifyFail: (data) => {
        console.log('Card verification failed - showing loader')
        this.showLoader()
        this.handlePaymentFail(data)
      },
    }
  }

  initEmbeddedMode() {
    const config = this.getPaymentConfig(this.paymentConfig)
    config.frame_mode = this.cardDisplayMode
    console.log('Initializing embedded mode with config:', config)
    EPayWidget.run(config, 'POST')
  }

  openPopup(paramsObject) {
    try {
      const config = this.getPaymentConfig(paramsObject)
      config.frame_mode = this.cardDisplayMode
      console.log('Initializing popup mode with config:', config)
      EPayWidget.run(config, 'GET')
    } catch (error) {
      console.error('Payment error:', error)
      this.showError(error.message || 'Payment request failed. Please try again.')
      this.isProcessing = false
      this.hideLoader()
    }
  }

  buildRedirectUrl(baseUrl, paramsObject) {
    const redirectUrl = new URL(baseUrl)
    Object.entries(paramsObject).forEach(([key, value]) => {
      redirectUrl.searchParams.set(key, value)
    })
    return redirectUrl.toString();
  }

  makeRedirect(url) {
    this.isProcessing = true
    this.showLoader()
    console.log('Redirecting to:', url)
    window.location.replace(url)
  }

  handleValidationResponse(data) {
    if (data && Object.keys(data).length > 0) {
      this.showError(Object.values(data)[0])
      return
    }

    this.createOrder()
  }

  createOrder() {
    this.showLoader()
    this.isProcessing = true
    console.log('Creating order...')
    const _this = this

    const data = {payment_method: _this.getSelectedPaymentMethod()}
    if (this.isEmbeddedModeSelected()) {
      data.payment_id = this.paymentConfig.payment_id;
    }

    $.ajax({
      url: window.ECOMMPAY_PAYMENT_URL,
      method: 'POST',
      dataType: 'json',
      data: data,
      success: (response) => {
        console.log('Create order response:', response)

        if (!response || typeof response !== 'object') {
          console.error('Invalid response format:', response)
          _this.showError('Invalid server response format')
          _this.isProcessing = false
          _this.hideLoader()
          return
        }

        if (!response.success) {
          console.error('Create order failed:', response.error)
          _this.showError(response.error || "Error processing order. Can't load Order status.")
          _this.isProcessing = false
          _this.hideLoader()
          return
        }

        // Store cart state
        if (window.prestashop && window.prestashop.cart) {
          localStorage.setItem(
            'ecommpay_cart_state',
            JSON.stringify({
              timestamp: Date.now(),
              cartId: window.prestashop.cart.id,
            })
          )
        }

        if (response.order_id) {
          _this.orderId = response.order_id
        }

        if (_this.isEmbeddedModeSelected()) {
          _this.sendIframePostMessage({
            message: MESSAGES.SUBMIT,
            fields: {},
            from_another_domain: true,
          })
        } else if (_this.isPopupModeSelected()) {
          _this.openPopup(response.redirect_params);
        } else {
          _this.makeRedirect(
            _this.buildRedirectUrl(response.redirect_host, response.redirect_params)
          )
        }
      },
      error: (jqXHR, textStatus, errorThrown) => {
        console.error('Create order error:', { jqXHR, textStatus, errorThrown })
        _this.showError('Error processing order: ' + (errorThrown || textStatus))
      },
    })
  }



  handlePaymentSuccess(data) {
    const orderId = data?.payment?.id || data?.order_id
    let successUrl = data?.redirect_success_url || window.ECOMMPAY_SUCCESS_URL

    if (successUrl) {
      if (orderId) {
        successUrl += (successUrl.includes('?') ? '&' : '?') + 'order_id=' + orderId
      }
      this.makeRedirect(successUrl)
    } else {
      this.showError('Payment successful but redirect URL is missing')
    }
  }

  handlePaymentFail(data) {
    const failUrl = data?.redirect_fail_url || window.ECOMMPAY_FAIL_URL
    if (failUrl) {
      this.makeRedirect(failUrl)
    } else {
      this.showError('Payment failed and redirect URL is missing')
    }
  }

  handle3DSRedirect(data) {
    if (!data) {
      this.showError('Invalid 3DS response')
      return
    }

    // 3DS v2
    if (data.threeds2?.redirect) {
      const { url, params } = data.threeds2.redirect
      if (url) {
        localStorage.setItem('ecommpay_3ds_params', JSON.stringify(params))
        this.makeRedirect(url)
        return
      }
    }

    // 3DS v1
    if (data.acs?.acs_url) {
      const { acs_url, pa_req, md, term_url } = data.acs
      if (acs_url) {
        localStorage.setItem(
          'ecommpay_3ds_params',
          JSON.stringify({
            pa_req,
            md,
            term_url,
          })
        )
        this.makeRedirect(acs_url)
        return
      }
    }

    const form = document.createElement('form')
    form.setAttribute('method', data.method)
    form.setAttribute('action', data.url)
    form.setAttribute('style', 'display:none;')
    form.setAttribute('name', '3dsForm')

    for (let k in data.body) {
      const input = document.createElement('input')
      input.name = k
      input.value = data.body[k]
      form.appendChild(input)
    }

    document.body.appendChild(form)
    form.submit()
  }

  /**
   * Handle clarification
   */
  handleClarification() {
    this.clarificationRunning = true
    this.isProcessing = false
    this.hideLoader()

    const submitData = {
      message: MESSAGES.SUBMIT.message,
      fields: {},
      from_another_domain: true,
    }
    this.sendIframePostMessage(submitData)
  }

  showLoader() {
    const loader = $('#ecommpay-loader')
    loader.fadeIn(200)
  }

  hideLoader() {
    const loader = $('#ecommpay-loader')
    loader.fadeOut(200)
  }

  showError(message) {
    $('#ecommpay-errors-container').html(message).show()
  }

  setupApplePay() {
    const applePayMethod = $('input[name=payment_method_code][value=applepay]')
    const applePayMethodIndex = applePayMethod.closest('div.additional-information')
      .attr('id').split('-')[2]
    console.log('Apple Pay method index:', applePayMethodIndex)

    const container = $('div#payment-option-'+applePayMethodIndex+'-container')
    const additionalInfo = $('div#payment-option-'+applePayMethodIndex+'-additional-information')
    if (applePayMethod.length && !this.isApplePayAllowed()) {
      container.remove()
      additionalInfo.remove()
    }
  }

  isApplePayAllowed() {
    return Object.prototype.hasOwnProperty.call(window, 'ApplePaySession') && ApplePaySession.canMakePayments()
  }
}

// Initialize payment handler when document is ready
$(() => {
  if (window.ECOMMPAY_HOST) {
    window.ECPPaymentHandler = new EcommpayPaymentHandler()
  }
})

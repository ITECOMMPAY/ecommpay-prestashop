/**
 * @file payment.js
 * @description Handles payment processing
 * @global jQuery, $
 */

'use strict'

const MESSAGES = {
  SUBMIT: 'epframe.embedded_mode.submit',
  CHECK_VALIDATION: 'epframe.embedded_mode.check_validation',
}

const PLACE_ORDER_BUTTON_SELECTOR = '#payment-confirmation button[type=submit]'

class EcommpayPaymentHandler {
  constructor() {
    this.isProcessing = false
    this.paymentConfig = null
    this.orderId = null
    this.widgetInstance = null
    this.validationResolve = null
    this.isClarificationRunnig = false
    this.host = window.ECOMMPAY_HOST || 'https://paymentpage.ecommpay.com'
    this.cardDisplayMode = window.ECOMMPAY_CARD_DISPLAY_MODE
    this.init().then()
  }

  async init() {
    this.queueEcommpayResources()
    this.setupPaymentButtons()
    this.checkForPaymentDeclinedMessage()
    this.setupApplePay()
    if (this.isPaymentMethodEnabled('card') && this.cardDisplayMode === 'embedded') {
      await this.waitForMerchantJS()
      await this.reloadEmbeddedIframe()
    }
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
    const confirmOrderBtn = $(PLACE_ORDER_BUTTON_SELECTOR)
    confirmOrderBtn.on('click', async (e) => {
      e.preventDefault()
      e.stopPropagation()
      if (this.isProcessing) {
        return
      }
      this.isProcessing = true
      if (this.isEmbeddedModeSelected()) {
        return await this.handleEmbeddedMode()
      }
      return await this.handleRedirectAndPopup()
    })
  }

  checkForPaymentDeclinedMessage() {
    if (window.ECOMMPAY_PAYMENT_DECLINED_MESSAGE) {
      this.showError(window.ECOMMPAY_PAYMENT_DECLINED_MESSAGE)
      this.clearPaymentDeclinedMessage()
    }
  }

  clearPaymentDeclinedMessage() {
    $.ajax({
      url: window.ECOMMPAY_CLEAR_MESSAGE_URL,
      method: 'POST',
      error: (jqXHR, textStatus, errorThrown) => {
        console.error('Failed to clear message:', textStatus)
      },
    })
  }

  waitForMerchantJS() {
    return new Promise((resolve) => {
      const check = () => {
        if (window.EPayWidget) {
          resolve()
        } else {
          setTimeout(check, 100)
        }
      }
      check()
    })
  }

  async reloadEmbeddedIframe() {
    try {
      this.initEmbeddedMode(await this.refreshPaymentConfig(this.getSelectedPaymentMethod(), this.orderId))
    } catch (error) {
      this.showError(error.message)
      console.error(error)
    }
  }

  async handleRedirectAndPopup() {
    try {
      const { redirect_host, redirect_params } = await this.createOrder(this.getSelectedPaymentMethod())
      if (this.isPopupModeSelected()) {
        this.hideLoader()
        this.openPopup(redirect_params)
      } else {
        window.location.replace(this.buildRedirectUrl(redirect_host, redirect_params))
      }
    } catch (error) {
      this.isProcessing = false
      this.hideLoader()
      console.error(error)
      this.showError(error.message)
    }
  }

  async handleEmbeddedMode() {
    if (this.isClarificationRunnig) {
      if (!(await this.validateCardForm())) {
        return
      }
      this.showLoader()
      this.submitCardForm()
      this.isClarificationRunnig = false
      return
    }

    try {
      if (!(await this.validateCardForm())) {
        return
      }
      this.showLoader()
      await this.checkAmountIsEqual(this.paymentConfig?.payment_amount)
      const { order_id } = await this.createOrder(this.getSelectedPaymentMethod(), this.paymentConfig?.payment_id)
      this.orderId = order_id
      this.submitCardForm()
    } catch (error) {
      console.error(error)
      this.isProcessing = false
      this.hideLoader()
      this.showError(error.message)
    }
  }

  checkAmountIsEqual(paymentAmount) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: window.ECOMMPAY_CHECK_CART_URL,
        method: 'POST',
        data: {
          amount: paymentAmount,
        },
        success: (response) => {
          if (!response.amount_is_equal) {
            window.location.reload()
            return
          }
          resolve()
        },
        error: (jqXHR, textStatus, errorThrown) => {
          console.error('Cart check failed:', { jqXHR, textStatus, errorThrown })
          this.showError('Error checking cart: ' + (errorThrown || textStatus))
          this.isProcessing = false
          this.hideLoader()
          reject('Error checking cart: ' + (errorThrown || textStatus))
        },
      })
    })
  }

  validateCardForm() {
    return new Promise((resolve, reject) => {
      this.widgetInstance.sendPostMessage({
        message: MESSAGES.CHECK_VALIDATION,
        from_another_domain: true,
      })
      this.validationResolve = resolve
    })
  }

  submitCardForm(fields) {
    this.widgetInstance.sendPostMessage({
      message: MESSAGES.SUBMIT,
      fields: fields || {},
      from_another_domain: true,
    })
  }

  refreshPaymentConfig(paymentMethod, orderId) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: window.ECOMMPAY_PAYMENT_INFO_URL,
        method: 'GET',
        dataType: 'json',
        data: {
          payment_method: paymentMethod,
          order_id: orderId,
        },
        success: (data) => {
          if (!data.success) {
            return reject(new Error('Failed to fetch card payment page parameters: ' + data.error))
          }
          this.paymentConfig = data.payment_config ?? null
          resolve(data.payment_config ?? null)
        },
        error: (jqXHR, textStatus, errorThrown) => {
          reject(new Error('Failed to fetch card payment page parameters: ' + textStatus))
        },
      })
    })
  }

  getPaymentConfig(paramsObject) {
    return {
      ...paramsObject,
    }
  }

  initEmbeddedMode(paymentConfig) {
    const config = this.getPaymentConfig(paymentConfig)
    config.onLoaded = () => {
      jQuery('#ecommpay-iframe').height('auto')
      this.hideLoader()
    }
    config.onPaymentFail = async (data) => {
      this.hideLoader()
      this.isProcessing = false
      try {
        await this.restoreCart()
      } catch (error) {
        console.error(error)
        this.showError(error.message)
      }
      await this.reloadEmbeddedIframe()
    }
    config.onEmbeddedModeCheckValidationResponse = (data) => {
      if (data && Object.keys(data).length > 0) {
        this.showError(Object.values(data)[0])
        this.isProcessing = false
        this.validationResolve(false)
        return
      }
      this.validationResolve(true)
    }
    config.onEnterKeyPressed = () => {
      $(PLACE_ORDER_BUTTON_SELECTOR).click()
    }
    config.onPaymentSent = () => {
      this.showLoader()
    }
    config.onSubmitClarificationForm = () => {
      this.showLoader()
    }
    config.onShowClarificationPage = () => {
      this.isClarificationRunnig = true
      this.isProcessing = false
      this.hideLoader()
    }
    config.onEmbeddedModeRedirect3dsParentPage = (data) => {
      this.handle3DSRedirect(data)
    }
    try {
      this.widgetInstance = EPayWidget.run(config, 'POST')
    } catch (error) {
      console.error(error)
      throw new Error('An error occurred while opening the embedded payment page: ' + error.message)
    }
  }

  openPopup(paramsObject) {
    const config = this.getPaymentConfig(paramsObject)
    config.frame_mode = this.cardDisplayMode
    config.onExit = async () => {
      await this.restoreCartForPopup()
    }
    config.onDestroy = async () => {
      await this.restoreCartForPopup()
    }
    try {
      this.widgetInstance = EPayWidget.run(config, 'GET')
    } catch (error) {
      console.error(error)
      throw new Error('An error occurred while opening the payment popup page: ' + error.message)
    }
  }

  buildRedirectUrl(baseUrl, paramsObject) {
    const redirectUrl = new URL(baseUrl)
    Object.entries(paramsObject).forEach(([key, value]) => {
      redirectUrl.searchParams.set(key, value)
    })
    return redirectUrl.toString()
  }

  createOrder(paymentMethod, paymentId) {
    this.showLoader()
    return new Promise((resolve, reject) => {
      const data = { payment_method: paymentMethod }
      if (paymentId) {
        data.payment_id = paymentId
      }
      $.ajax({
        url: window.ECOMMPAY_PAYMENT_URL,
        method: 'POST',
        dataType: 'json',
        data: data,
        success: (response) => {
          if (!response || typeof response !== 'object') {
            return reject(new Error('Invalid server response format'))
          }
          if (!response.success) {
            return reject(new Error(response.error || 'Error processing order'))
          }
          resolve(response)
        },
        error: (jqXHR, textStatus, errorThrown) => {
          reject('Error processing order: ' + (errorThrown || textStatus))
        },
      })
    })
  }

  handle3DSRedirect(data) {
    var form = document.createElement('form')
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

  showLoader() {
    const loader = $('#ecommpay-loader')
    loader.fadeIn(200)
  }

  hideLoader() {
    const loader = $('#ecommpay-loader')
    loader.fadeOut(200)
  }

  showError(message) {
    // Create error container if it doesn't exist
    if ($('#ecommpay-errors-container').length === 0) {
      $('body').prepend(
        '<div id="ecommpay-errors-container" class="alert alert-danger" style="display: none; position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; max-width: 500px;"></div>'
      )
    }

    $('#ecommpay-errors-container').html(message).show()

    // Auto-hide message after 10 seconds
    setTimeout(() => {
      $('#ecommpay-errors-container').fadeOut(500)
    }, 10000)
  }

  setupApplePay() {
    const applePayMethod = $('input[name=payment_method_code][value=applepay]')
    const applePayMethodIdAttr = applePayMethod.closest('div.additional-information').attr('id')
    if (!applePayMethodIdAttr) {
      console.info('Apple Pay method not found / wrong checkout step / wrong page')
      return
    }
    const applePayMethodIndex = applePayMethodIdAttr.split('-')[2]

    const container = $('div#payment-option-' + applePayMethodIndex + '-container')
    const additionalInfo = $('div#payment-option-' + applePayMethodIndex + '-additional-information')
    if (applePayMethod.length && !this.isApplePayAllowed()) {
      container.remove()
      additionalInfo.remove()
    }
  }

  isApplePayAllowed() {
    return Object.prototype.hasOwnProperty.call(window, 'ApplePaySession') && ApplePaySession.canMakePayments()
  }

  restoreCart() {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: window.ECOMMPAY_RESTORE_CART_URL,
        method: 'POST',
        success: async () => {
          resolve()
        },
        error: (jqXHR, textStatus, errorThrown) => {
          reject(new Error('Failed to restore cart: ' + textStatus))
        },
      })
    })
  }

  async restoreCartForPopup() {
    this.isProcessing = false
    try {
      await this.restoreCart()
      this.showError('Payment was cancelled. You can try another payment method.')
    } catch (error) {
      console.error(error)
      this.showError(error.message)
    }
  }
}

$(() => {
  if (window.ECOMMPAY_HOST) {
    window.ECPPaymentHandler = new EcommpayPaymentHandler()
  }
})

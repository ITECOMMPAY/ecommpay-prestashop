<fieldset>
  <legend>
    <h2>{l s='More Methods' mod='ecommpay'}</h2>
  </legend>

  <div class="form-group col-md-6">
    <label for="ECOMMPAY_MORE_METHODS_ENABLED">{l s='Enabled' mod='ecommpay'}</label>
    <input type="checkbox" id="ECOMMPAY_MORE_METHODS_ENABLED" name="ECOMMPAY_MORE_METHODS_ENABLED" value="1"
           {if $options.ECOMMPAY_MORE_METHODS_ENABLED}checked{/if} />
    <div class="text-muted">
      {l s='Before enabling the payment method please contact' mod='ecommpay'}
      <a href="mailto:support@ecommpay.com">support@ecommpay.com</a>
    </div>
  </div>

  <div class="form-group col-md-6">
    <label for="ECOMMPAY_MORE_METHODS_TITLE">{l s='Title' mod='ecommpay'}</label>
    <input type="text" id="ECOMMPAY_MORE_METHODS_TITLE" name="ECOMMPAY_MORE_METHODS_TITLE"
           value="{$options.ECOMMPAY_MORE_METHODS_TITLE|escape:'html':'UTF-8'}" />
  </div>

  <div class="form-group col-md-6">
    <label for="ECOMMPAY_MORE_METHODS_DESCRIPTION">{l s='Description' mod='ecommpay'}</label>
    <input type="text" id="ECOMMPAY_MORE_METHODS_DESCRIPTION" name="ECOMMPAY_MORE_METHODS_DESCRIPTION"
           value="{$options.ECOMMPAY_MORE_METHODS_DESCRIPTION|escape:'html':'UTF-8'}" />
    <div class="text-muted">
      {l s='This is the description the customer sees during checkout' mod='ecommpay'}
    </div>
  </div>

  <div class="form-group col-md-6">
    <label for="ECOMMPAY_MORE_METHODS_CODE">{l s='Payment method code' mod='ecommpay'}</label>
    <input type="text" id="ECOMMPAY_MORE_METHODS_CODE" name="ECOMMPAY_MORE_METHODS_CODE"
           value="{$options.ECOMMPAY_MORE_METHODS_CODE|escape:'html':'UTF-8'}" />
    <div class="text-muted">
      {l s='The identifier of the payment method that is opened to customers without an option to select another one. The list of codes is provided in' mod='ecommpay'}
      <a href="https://developers.ecommpay.com/en/en_pm_codes.html" target="_blank" rel="noopener noreferrer">Payment
        method codes</a>.
    </div>
  </div>
</fieldset>

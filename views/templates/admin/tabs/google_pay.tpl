  <fieldset>
    <legend>
      <h2>{l s='Google Pay' mod='ecommpay'}</h2>
    </legend>

    <div class="form-group col-md-6">
      <label for="ECOMMPAY_GOOGLE_PAY_ENABLED">{l s='Enabled' mod='ecommpay'}</label>
      <input type="checkbox" id="ECOMMPAY_GOOGLE_PAY_ENABLED" name="ECOMMPAY_GOOGLE_PAY_ENABLED" value="1"
             {if $options.ECOMMPAY_GOOGLE_PAY_ENABLED}checked{/if} />
      <div class="text-muted">
        {l s='Before enabling the payment method please contact' mod='ecommpay'}
        <a href="mailto:support@ecommpay.com">support@ecommpay.com</a>
      </div>
    </div>

    <div class="form-group col-md-6">
      <label for="ECOMMPAY_GOOGLE_PAY_TITLE">{l s='Title' mod='ecommpay'}</label>
      <input type="text" id="ECOMMPAY_GOOGLE_PAY_TITLE" name="ECOMMPAY_GOOGLE_PAY_TITLE"
             value="{$options.ECOMMPAY_GOOGLE_PAY_TITLE|default:''|escape:'html':'UTF-8'}" />
    </div>

    <div class="form-group col-md-6">
      <label for="ECOMMPAY_GOOGLE_PAY_DESCRIPTION">{l s='Description' mod='ecommpay'}</label>
      <input type="text" id="ECOMMPAY_GOOGLE_PAY_DESCRIPTION" name="ECOMMPAY_GOOGLE_PAY_DESCRIPTION"
             value="{$options.ECOMMPAY_GOOGLE_PAY_DESCRIPTION|default:''|escape:'html':'UTF-8'}" />
      <div class="text-muted">
        {l s='This is the description the customer sees during checkout' mod='ecommpay'}
      </div>
    </div>
  </fieldset>

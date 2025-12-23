  <fieldset>
    <legend>
      <h2>{l s='Card Settings' mod='ecommpay'}</h2>
    </legend>

    <div class="form-group col-md-6">
      <label for="ECOMMPAY_CARD_ENABLED">{l s='Enabled' mod='ecommpay'}</label>
      <input type="checkbox" id="ECOMMPAY_CARD_ENABLED" name="ECOMMPAY_CARD_ENABLED" value="1"
             {if $options.ECOMMPAY_CARD_ENABLED}checked{/if} />
      <div class="text-muted">
        {l s='Before enabling the payment method please contact' mod='ecommpay'}
        <a href="mailto:support@ecommpay.com">support@ecommpay.com</a>
      </div>
    </div>

    <div class="form-group col-md-6">
      <label for="ECOMMPAY_CARD_TITLE">{l s='Title' mod='ecommpay'}</label>
      <input type="text" id="ECOMMPAY_CARD_TITLE" name="ECOMMPAY_CARD_TITLE"
             value="{$options.ECOMMPAY_CARD_TITLE|default:''|escape:'html':'UTF-8'}" />
    </div>

    <div class="form-group col-md-6">
      <label for="ECOMMPAY_CARD_DESCRIPTION">{l s='Description' mod='ecommpay'}</label>
      <input type="text" id="ECOMMPAY_CARD_DESCRIPTION" name="ECOMMPAY_CARD_DESCRIPTION"
             value="{$options.ECOMMPAY_CARD_DESCRIPTION|default:''|escape:'html':'UTF-8'}" />
      <div class="text-muted">
        {l s='This is the description the customer sees during checkout' mod='ecommpay'}
      </div>
    </div>

    <div class="form-group col-md-6">
      <label for="ECOMMPAY_CARD_DISPLAY_MODE">{l s='Display mode' mod='ecommpay'}</label>
      <select id="ECOMMPAY_CARD_DISPLAY_MODE" name="ECOMMPAY_CARD_DISPLAY_MODE">
        <option value="popup"
                {if $options.ECOMMPAY_CARD_DISPLAY_MODE == 'popup'}selected{/if}>{l s='Popup' mod='ecommpay'}</option>
        <option value="redirect"
                {if $options.ECOMMPAY_CARD_DISPLAY_MODE == 'redirect'}selected{/if}>{l s='Redirect' mod='ecommpay'}</option>
        <option value="embedded"
                {if $options.ECOMMPAY_CARD_DISPLAY_MODE == 'embedded'}selected{/if}>{l s='Embedded' mod='ecommpay'}</option>
      </select>
      <div class="text-muted">
        {l s='Payment Page display mode' mod='ecommpay'}
      </div>
    </div>
  </fieldset>

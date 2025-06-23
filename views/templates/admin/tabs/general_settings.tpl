  <fieldset>
    <legend>
      <h2>{l s='General Settings' mod='ecommpay'}</h2>
    </legend>

    <div class="form-group col-md-6">
      <label for="ECOMMPAY_PROJECT_ID">{l s='Project ID' mod='ecommpay'}</label>
      <input type="text" class="form-control" id="ECOMMPAY_PROJECT_ID" name="ECOMMPAY_PROJECT_ID"
             value="{$options.ECOMMPAY_PROJECT_ID|escape:'html':'UTF-8'}" />
      <div class="text-muted">
        {l s='Your project ID you could get from Ecommpay helpdesk' mod='ecommpay'}
      </div>
    </div>

    <div class="form-group col-md-6">
      <label for="ECOMMPAY_SECRET_KEY">{l s='Secret key' mod='ecommpay'}</label>
      <input type="password" size="33" id="ECOMMPAY_SECRET_KEY" name="ECOMMPAY_SECRET_KEY"
             value="{$options.ECOMMPAY_SECRET_KEY|escape:'html':'UTF-8'}" />
      <div class="text-muted">
        {l s='Secret key which is used to sign payment request. You could get it from Ecommpay helpdesk' mod='ecommpay'}
      </div>
    </div>

    <div class="form-group col-md-6">
      <label for="ECOMMPAY_PAYMENT_PAGE_LANGUAGE">{l s='Language' mod='ecommpay'}</label>
      <select id="ECOMMPAY_PAYMENT_PAGE_LANGUAGE" name="ECOMMPAY_PAYMENT_PAGE_LANGUAGE">
        {foreach from=$availableLanguages|default:[] item=lang}
          <option value="{$lang['code']}"
                  {if $options.ECOMMPAY_PAYMENT_PAGE_LANGUAGE === $lang['code']}selected{/if}>
            {$lang['name']}
          </option>
        {/foreach}
      </select>
      <div class="text-muted">
        {l s='Language of payment page' mod='ecommpay'}
      </div>
    </div>

    <div class="form-group col-md-12">
      {l s='You should provide callback endpoint to Ecommpay helpdesk. It is required to get information about payment\'s status' mod='ecommpay'}
      :
      <a href="{$callbackUrl|escape:'html':'UTF-8'}">
        {$callbackUrl|escape:'html':'UTF-8'}
      </a>
    </div>
  </fieldset>

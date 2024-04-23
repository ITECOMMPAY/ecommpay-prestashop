<h2>{l s='Ecommpay payments' mod='ecommpay'}</h2>
<form action="#" method="post" style="clear: both; margin-top: 10px;" class="form-vertical">
<fieldset>
	<legend>{l s='Settings' mod='ecommpay'}</legend>
    <p>
        Please provide project id and secret key received from Ecommpay.
    </p>

    <div class="form-group col-md-6">
        <label for="ECOMMPAY_PROJECT_ID">{l s='Project ID' mod='ecommpay'}</label>
        <input type="text" class="form-control" id="ECOMMPAY_PROJECT_ID" name="ECOMMPAY_PROJECT_ID" value="{$ECOMMPAY_PROJECT_ID}" />
        <div class="text-muted">
            {l s='Your project ID you could get from Ecommpay helpdesk. Leave it blank if test mode' mod='ecommpay'}
        </div>
    </div>

    <div class="form-group col-md-6">
        <label for="ECOMMPAY_SECRET_KEY">{l s='Secret key' mod='ecommpay'}</label>
        <input type="text" size="33" id="ECOMMPAY_SECRET_KEY" name="ECOMMPAY_SECRET_KEY" value="{$ECOMMPAY_SECRET_KEY}" />
        <div class="text-muted">
            {l s='Secret key which is used to sign payment request. You could get it from Ecommpay helpdesk' mod='ecommpay'}
        </div>
    </div>

    <div class="form-group col-md-6">
        <label for="ECOMMPAY_TITLE">{l s='Title' mod='ecommpay'}</label>
        <input type="text" id="ECOMMPAY_TITLE" name="ECOMMPAY_TITLE" value="{$ECOMMPAY_TITLE}" />
    </div>

    <div class="form-group col-md-6">
        <label for="ECOMMPAY_DESCRIPTION">{l s='Description' mod='ecommpay'}</label>
        <input type="text" id="ECOMMPAY_DESCRIPTION" name="ECOMMPAY_DESCRIPTION" value="{$ECOMMPAY_DESCRIPTION}" />
    </div>

    <div class="form-group col-md-6">
        <label for="ECOMMPAY_PAYMENT_PAGE_CURRENCY">{l s='Currency' mod='ecommpay'}</label>
        <select id="ECOMMPAY_PAYMENT_PAGE_CURRENCY" name="ECOMMPAY_PAYMENT_PAGE_CURRENCY">
            {foreach from=$availableCurrencies item=currency}
                {if $ECOMMPAY_PAYMENT_PAGE_CURRENCY === $currency->iso_code}
                    <option value="{$currency->iso_code}" selected>{$currency->name}</option>
                {else}
                    <option value="{$currency->iso_code}">{$currency->name}</option>
                {/if}
            {/foreach}
        </select>
        <div class="text-muted">
            {l s='Payment currency' mod='ecommpay'}
        </div>
    </div>

    <div class="form-group col-md-6">
        <label for="ECOMMPAY_PAYMENT_PAGE_LANGUAGE">{l s='Language' mod='ecommpay'}</label>
        <select id="ECOMMPAY_PAYMENT_PAGE_LANGUAGE" name="ECOMMPAY_PAYMENT_PAGE_LANGUAGE">
            {foreach from=$availableLanguages item=lang}
                {if $ECOMMPAY_PAYMENT_PAGE_LANGUAGE === $lang['code']}
                    <option value="{$lang['code']}" selected>{$lang['name']}</option>
                {else}
                    <option value="{$lang['code']}" >{$lang['name']}</option>
                {/if}
            {/foreach}
        </select>
        <div class="text-muted">
            {l s='Language of payment page' mod='ecommpay'}
        </div>
    </div>

    <div class="form-group col-md-6">
        <label for="ECOMMPAY_IS_TEST">{l s='Test mode' mod='ecommpay'}</label>
        {if $ECOMMPAY_IS_TEST}
            <input type="checkbox" id="ECOMMPAY_IS_TEST" name="ECOMMPAY_IS_TEST" value="1" checked />
        {else}
            <input type="checkbox" id="ECOMMPAY_IS_TEST" name="ECOMMPAY_IS_TEST" value="1" />
        {/if}
    </div>

    <div class="form-group col-md-6">
        <label for="ECOMMPAY_IS_POPUP">{l s='Popup mode' mod='ecommpay'}</label>
        {if $ECOMMPAY_IS_POPUP}
            <input type="checkbox" id="ECOMMPAY_IS_POPUP" name="ECOMMPAY_IS_POPUP" value="1" checked />
        {else}
            <input type="checkbox" id="ECOMMPAY_IS_POPUP" name="ECOMMPAY_IS_POPUP" value="1" />
        {/if}
        <div class="text-muted">
            {l s='Show payment page in popup instead of redirect' mod='ecommpay'}
        </div>
    </div>

    <div class="form-group col-md-12">
        <label for="ECOMMPAY_ADDITIONAL_PARAMETERS">{l s='Additional parameters' mod='ecommpay'}</label>
        <input type="text" class="form-control" id="ECOMMPAY_ADDITIONAL_PARAMETERS" name="ECOMMPAY_ADDITIONAL_PARAMETERS" value="{$ECOMMPAY_ADDITIONAL_PARAMETERS}" />
        <div class="text-muted">
            {l s='It will be added to redirect link to Ecommpay payment page' mod='ecommpay'}
        </div>
    </div>

    <div class="col-md-6 text-right">
        <input type="submit"
           class="btn btn-default"
           name="ecommpay_updateSettings" value="{l s='Save Settings' mod='ecommpay'}" class="button" style="cursor: pointer; display:"/>
    </div>

    <div class="form-group col-md-12">
        {l s='You should provide callback endpoint to Ecommpay helpdesk. It is required to get information about payment\'s status' mod='ecommpay'}:
        <a href="{$callbackUrl}">
            {$callbackUrl}
        </a>
    </div>

</fieldset>
</form>

{if $psVersion < '1.6'}
    <style type="text/css">
        .form-group {
            padding: 18px 0;
            border-bottom: 1px solid lightgrey;
        }
        .form-group .text-muted {
            color: gray;
            text-align: right;
        }
    </style>
{/if}

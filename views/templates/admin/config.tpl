<h2>{l s='Ecommpay payments' mod='ecommpay'}</h2>
<form action="#" method="post" style="clear: both; margin-top: 10px;" class="form-vertical">
  <fieldset>
    <legend>{l s='Settings' mod='ecommpay'}</legend>
    <div class="row">
      <div class="col-md-3">
        <div class="panel">
          <div class="panel-heading">{l s='Ecommpay Settings' mod='ecommpay'}</div>
          <ul class="list-group">
            {assign var="tabs" value=[
            'general_settings' => {l s='General Settings' mod='ecommpay'},
            'card_settings' => {l s='Card Settings' mod='ecommpay'},
            'apple_pay' => {l s='Apple Pay' mod='ecommpay'},
            'google_pay' => {l s='Google Pay' mod='ecommpay'},
            'more_methods' => {l s='More Methods' mod='ecommpay'}
            ]}
            {foreach from=$tabs key=tab item=label}
              <li class="list-group-item {if $active_tab == $tab}active{/if}">
                <a
                  href="{$link->getAdminLink('AdminEcommpaySettings')|escape:'html':'UTF-8'}&tab={$tab}">{$label}</a>
              </li>
            {/foreach}
          </ul>
        </div>
      </div>
      <div class="col-md-9">
        {if $active_tab == 'general_settings'}
          {include file="module:ecommpay/views/templates/admin/tabs/general_settings.tpl"}
        {elseif $active_tab == 'card_settings'}
          {include file="module:ecommpay/views/templates/admin/tabs/card_settings.tpl"}
        {elseif $active_tab == 'apple_pay'}
          {include file="module:ecommpay/views/templates/admin/tabs/apple_pay.tpl"}
        {elseif $active_tab == 'google_pay'}
          {include file="module:ecommpay/views/templates/admin/tabs/google_pay.tpl"}
        {elseif $active_tab == 'more_methods'}
          {include file="module:ecommpay/views/templates/admin/tabs/more_methods.tpl"}
        {/if}
      </div>
    </div>
    <div class="col-md-6 text-right">
      <input type="submit"
             class="btn button btn-default"
             name="submitEcommpaySettings" value="{l s='Save Settings' mod='ecommpay'}"
             style="cursor: pointer; display:" />
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
{else}
  <style>
    .list-group-item.active a {
      color: white;
    }
  </style>
{/if}

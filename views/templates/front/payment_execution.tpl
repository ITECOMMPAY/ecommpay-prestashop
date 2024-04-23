<h2>{l s='Order summary' mod='ecommpay'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

 

<h3>{l s='Ecommpay payments.' mod='ecommpay'}</h3>

<p>
    {l s='Here is a short summary of your order:' mod='ecommpay'}
</p>
<p style="margin-top:20px;">
    - {l s='The total amount of your order is' mod='ecommpay'}
    <span id="amount" class="price">{displayPrice price=$total}</span>
    {if $use_taxes == 1}
        {l s='(tax incl.)' mod='ecommpay'}
    {/if}
</p>

<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='ecommpay'}</a>
<a id="confirm_order" href="{$paymentUrl}" class="exclusive_large" style="float: right;">
    {l s='I confirm my order' mod='ecommpay'}
</a>

<div class="row">
  <input type="hidden" name="payment_method_code" value="{$paymentMethodCode}" />
  {if $paymentMethodCode == 'card' && $paymentCardDisplayMode == 'embedded'}
    <div id="ecommpay-iframe"></div>
  {else}
    <p class="payment_module">
      {if $paymentMethodDescription}
        <span>{l s=$paymentMethodDescription mod='ecommpay'}</span>
      {/if}
    </p>
  {/if}
</div>

<div id="ecommpay-errors-container" class="alert alert-danger" style="display: none;"></div>

<div id="ecommpay-loader" style="display: none;">
  <div class="ecommpay-loader-overlay"></div>
  <div class="ecommpay-loader-content">
    <div class="ecommpay-loader-spinner"></div>
    <div class="ecommpay-loader-text">{l s='Processing payment...' mod='ecommpay'}</div>
  </div>
</div>

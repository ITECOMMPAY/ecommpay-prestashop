{if $ecommpay_payment_declined_message}
<div class="alert alert-warning" role="alert">
    <i class="material-icons">warning</i>
    {$ecommpay_payment_declined_message|escape:'html':'UTF-8'}
</div>
{/if}

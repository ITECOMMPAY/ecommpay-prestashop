<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a
                class="ecommpay"
                href="{$paymentUrl}"
                title="{l s=$paymentTitle mod='ecommpay'}">
                {l s=$paymentTitle mod='ecommpay'}
                {if $paymentDescription}
                    <span>({l s=$paymentDescription mod='ecommpay'})</span>
                {/if}
            </a>
        </p>
    </div>
</div>
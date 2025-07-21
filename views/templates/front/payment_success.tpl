<div style="text-align: center">
    <p>
    <h2>{$status}</h2>
    <br/>
    {if $order}
        <h3 style="font-size: 1.5em">
            <a href="/index.php?controller=order-detail&id_order={$order->id}">
                {l s='Show order' mod='ecommpay'}
            </a>
        </h3>
    {/if}
    <br/>
    <div style="padding: 10px; font-size: 1em">
        <a href="{$historyUrl}">
            {l s='Go to my orders' mod='ecommpay'}
        </a>
    </div>
    </p>
</div>

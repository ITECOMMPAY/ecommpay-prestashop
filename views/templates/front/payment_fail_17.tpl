{extends file='page.tpl'}

{block name='content'}
    <div style="text-align: center">
        <p>
        <h2>{l s='Your payment failed to be processed by Ecommpay' mod='ecommpay'}</h2>
        <br/>
        <br/>
        <div style="padding: 10px; font-size: 1em">
            <a href="{$historyUrl}">
                {l s='Go to my orders' mod='ecommpay'}
            </a>
        </div>
        </p>
    </div>
{/block}

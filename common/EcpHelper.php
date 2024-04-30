<?php

declare(strict_types=1);

require_once __DIR__ . '/../ecommpay.php';

class EcpHelper
{
    public static function getCallbackUrl(): string
    {
        return Context::getContext()->link->getModuleLink(ecommpay::PLUGIN_NAME, 'callback');
    }

    public static function getSuccessUrl(Cart $cart, array $config): string
    {
        $orderId = Order::getIdByCartId($cart->id);
                
        $query_args = ['order_id' => $orderId];
        
        if ($config['isTest']) {
            $query_args['test'] = 1;
        }
        
        return Context::getContext()->link->getModuleLink(
            ecommpay::PLUGIN_NAME, 
            'successpayment',
            $query_args
        );
    }

    public static function getFailUrl(): string
    {
        return Context::getContext()->link->getModuleLink(
            ecommpay::PLUGIN_NAME, 
            'failpayment'
        );
    }

    public static function getPaymentUrlWithConfirm(): string
    {
        return Context::getContext()->link->getModuleLink(
            ecommpay::PLUGIN_NAME, 
            'payment',
            ['confirm' => true]
        );
    }
}
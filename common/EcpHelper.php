<?php

require_once __DIR__ . '/../ecommpay.php';

class EcpHelper
{
    public static function getCallbackUrl() {
        return Context::getContext()->link->getModuleLink(ecommpay::PLUGIN_NAME, 'callback');
    }

    public static function getSuccessUrl($cart, array $config) {
        $orderId = Order::getOrderByCartId($cart->id);
                
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

    public static function getFailUrl() {
        return Context::getContext()->link->getModuleLink(
            ecommpay::PLUGIN_NAME, 
            'failpayment',
        );
    }

    public static function getPaymentUrlWithConfirm()
    {
        return Context::getContext()->link->getModuleLink(
            ecommpay::PLUGIN_NAME, 
            'payment',
            ['confirm' => true]
        );
    }
}
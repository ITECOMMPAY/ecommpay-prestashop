<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Cart;
use Context;
use Ecommpay;
use Order;

class EcpHelper
{
    public static function getCallbackUrl(): string
    {
        return Context::getContext()->link->getModuleLink(ecommpay::PLUGIN_NAME, 'callback');
    }

    public static function getSuccessUrl(Cart $cart): string
    {
        $orderId = Order::getIdByCartId($cart->id);

        $query_args = ['order_id' => $orderId];

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

    public static function getPaymentUrl(): string
    {
        return Context::getContext()->link->getModuleLink(ecommpay::PLUGIN_NAME, 'payment');
    }
}

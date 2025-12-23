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

    public static function getPaymentUrl(): string
    {
        return Context::getContext()->link->getModuleLink(ecommpay::PLUGIN_NAME, 'payment');
    }
}

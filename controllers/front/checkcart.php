<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use Ecommpay\EcpLogger;

class EcommpayCheckcartModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json');

        $cart = $this->context->cart;
        $amount = $cart->getOrderTotal();
        $requestAmount = Tools::getValue('amount');

        if ($amount !== $requestAmount) {
            $this->response = json_encode(['amount_is_equal' => false]);
            return;
        }

        $this->response = json_encode(['amount_is_equal' => true]);
    }
}

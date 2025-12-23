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

        $this->sendJsonResponse(['amount_is_equal' => $amount !== $requestAmount]);
    }

    protected function sendJsonResponse(array $data, int $httpStatusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($httpStatusCode);
        echo json_encode($data);
        exit;
    }
}

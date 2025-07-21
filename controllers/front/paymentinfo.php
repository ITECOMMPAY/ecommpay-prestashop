<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Ecommpay\EcommpayConfig;
use Ecommpay\EcpPaymentService;
use Ecommpay\EcpPaymentPageBuilder;

class EcommpayPaymentInfoModuleFrontController extends ModuleFrontController
{
    /**
     * This method is used to get the payment info for the payment page.
     * WARNING! Used only in embedded (iframe) mode.
     * @throws Exception
     * @return never
     */
    public function initContent(): void
    {
        parent::initContent();
        header('Content-Type: application/json');

        try {
            // Get basic cart info
            $cart = $this->context->cart;
            if (!$cart || !$cart->id) {
                throw new Exception('Cart is not available');
            }

            $currency = new Currency($cart->id_currency);
            if (!Validate::isLoadedObject($currency)) {
                throw new Exception('Currency is not valid');
            }

            $projectId = (int)Configuration::get(EcommpayConfig::ECOMMPAY_PROJECT_ID);
            if ($projectId <= 0) {
                throw new Exception('Project ID is not valid');
            }

            $secretKey = Configuration::get(EcommpayConfig::ECOMMPAY_SECRET_KEY);
            if (empty($secretKey)) {
                throw new Exception('Secret key is not configured');
            }

            $amount = (int)($cart->getOrderTotal() * 100);
            if ($amount <= 0) {
                throw new Exception('Amount is not valid');
            }

            $additionalParams = [
                'payment_id' => EcpPaymentService::generatePaymentID(),
                'force_payment_method' => 'card',
                'target_element' => 'ecommpay-iframe',
                'payment_methods_options' => json_encode(['additional_data' => ['embedded_mode' => true]]),
            ];

            $paymentPageBuilder = new EcpPaymentPageBuilder($this->module, $this->context);
            $params = $paymentPageBuilder->getCommonPaymentParameters($cart, $additionalParams);

            echo json_encode([
                'success' => true,
                'payment_config' => $params
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
}

<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Ecommpay\EcommpayConfig;
use Ecommpay\EcpLogger;
use Ecommpay\EcpOrder;
use Ecommpay\EcpOrderCreator;
use Ecommpay\EcpPayment;
use Ecommpay\EcpPaymentPageBuilder;
use Ecommpay\EcpPaymentService;
use Ecommpay\exceptions\EcpCartValidationException;

/**
 * @since 1.5.0
 */
class EcommpayPaymentModuleFrontController extends ModuleFrontController
{
    /**
     * @throws Exception
     */
    public function initContent(): void
    {
        parent::initContent();

        // Start output buffering to prevent PHP warnings from corrupting JSON response
        ob_start();

        EcpLogger::log('Payment request: ', [
            'method' => $_SERVER['REQUEST_METHOD'],
            'get' => $_GET,
            'post' => $_POST
        ]);

        try {
            $cart = $this->context->cart;

            $orderCreator = new EcpOrderCreator($this->module);
            $order = $orderCreator->createOrderIfRequired($cart);

            $order = new EcpOrder($order->id);
            $paymentMethod = $this->getPaymentMethod();
            $paymentId = $this->getOrCreatePaymentId($paymentMethod);
            $this->savePaymentIdToOrderPayment($order, $paymentId);

            $additionalParams = $this->collectAdditionalPaymentPageParams($paymentId, $paymentMethod);

            $paymentPageBuilder = new EcpPaymentPageBuilder($this->module, $this->context);
            $params = $paymentPageBuilder->getCommonPaymentParameters($cart, $additionalParams);
            $response = $this->buildResponse($order, $params);
            $this->sendAjax($response);
        } catch (EcpCartValidationException $e) {
            if (ob_get_level()) {
                ob_clean();
            }
            $this->sendAjaxError($e->getMessage());
        } catch (Exception $e) {
            EcpLogger::log('Unexpected error ' . $e->getMessage());
            if (ob_get_level()) {
                ob_clean();
            }
            $this->sendAjaxError($e->getMessage());
        }
    }

    private function collectAdditionalPaymentPageParams(string $paymentId, string $paymentMethod): array
    {
        $additionalParams = [
            'payment_id' => $paymentId,
            'merchant_return_url' => $this->context->link->getModuleLink('ecommpay', 'restorecart'),
        ];

        if ($forcePaymentMethod = $this->getForcePaymentMethod($paymentMethod)) {
            $additionalParams['force_payment_method'] = $forcePaymentMethod;
        }

        return $additionalParams;
    }

    private function getForcePaymentMethod(string $paymentMethod): ?string
    {
        $forcePaymentMethodMapping = [
            EcpPayment::CARD_PAYMENT_METHOD => 'card',
            EcpPayment::APPLEPAY_PAYMENT_METHOD => 'apple_pay_core',
            EcpPayment::GOOGLEPAY_PAYMENT_METHOD => 'google_pay_host',
            EcpPayment::MORE_METHODS_PAYMENT_METHOD => Configuration::get(EcommpayConfig::ECOMMPAY_MORE_METHODS_CODE) ?: null,
        ];
        return $forcePaymentMethodMapping[$paymentMethod] ?? null;
    }

    private function buildResponse(Order $order, array $paymentPageParams): array
    {
        return [
            'success' => true,
            'order_id' => $order->id,
            'order_reference' => $order->reference,
            'order_status' => $order->current_state,
            'redirect_host' => EcpPaymentService::getPaymentPageBaseUrl() . '/payment',
            'redirect_params' => $paymentPageParams,
        ];
    }

    /**
     * Send AJAX response
     * @param array $data
     */
    private function sendAjax(array $data): void
    {
        EcpLogger::log('Sending AJAX response: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        // Clean any output buffer to prevent corruption of JSON response
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        die(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Send AJAX error response
     * @param string $message
     */
    private function sendAjaxError(string $message): void
    {
        $errorResponse = [
            'success' => false,
            'error' => $message
        ];

        EcpLogger::log('Sending AJAX error response: ' . json_encode($errorResponse, JSON_UNESCAPED_UNICODE));

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        die(json_encode($errorResponse, JSON_UNESCAPED_UNICODE));
    }

    private function getPaymentMethod(): string
    {
        if (!$paymentMethod = Tools::getValue('payment_method')) {
            EcpLogger::log('Not selected payment method', ['severity' => 3]);
            throw new Exception('Selected payment method is null');
        }
        return $paymentMethod;
    }

    private function getOrCreatePaymentId(string $paymenMethod): string
    {
        if (
            $paymenMethod === EcpPayment::CARD_PAYMENT_METHOD
            && EcommpayConfig::getCardDisplayMode() === EcommpayConfig::EMBEDDED_DISPLAY_MODE
        ) {
            if (!$paymentId = Tools::getValue('payment_id')) {
                EcpLogger::log('Not provided payment id for embedded mode', ['severity' => 3]);
                throw new Exception('Not provided payment id for embedded mode');
            }
        }
        else {
            $paymentId = EcpPaymentService::generatePaymentID();
        }
        return $paymentId;
    }

    /**
     * Save payment ID to order_payment table
     * @param EcpOrder $order
     * @param string $paymentId
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function savePaymentIdToOrderPayment(EcpOrder $order, string $paymentId): void
    {
        if (!Validate::isLoadedObject($order)) {
            EcpLogger::log('Order is not loaded: ' . $order->id);
            return;
        }

        $payment = $order->getOrderPaymentCollection()->getLast();
        if (!$payment) {
            EcpLogger::log('Payment not found for order: ' . $order->id);
            return;
        }

        $payment->ecp_payment_id = $paymentId;
        $payment->save();
    }
}

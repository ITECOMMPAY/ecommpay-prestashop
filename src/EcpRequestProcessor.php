<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Db;
use Exception;
use PrestaShopException;

class EcpRequestProcessor
{
    private const ECOMMPAY_BASE_URL = 'https://api.ecommpay.com';
    private const GATE_REFUND_ENDPOINT = '/v2/payment/card/refund';

    /**
     * @var SignatureHandler
     */
    private $signer;
    /**
     * @var EcpPaymentService
     */
    private $paymentService;

    public function __construct($signer, $paymentService)
    {
        $this->signer = $signer;
        $this->paymentService = $paymentService;
    }

    /**
     * @param string $orderId
     * @param float $amount
     * @param string $currency
     * @param string $reason
     * @return EcpCallbackResult
     * @throws Exception
     */
    public function sendPostRequest(
        int  $orderId,
        float $amount,
        string $currency,
        string $reason = ''
    ): EcpCallbackResult {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::getGateEndpoint());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $order = new EcpOrder($orderId);

        $requestData = [
            'general' => [
                'project_id' => $this->paymentService->getProjectId(),
                'payment_id' => $order->ecp_payment_id,
                'merchant_callback_url' => EcpHelper::getCallbackUrl()
            ],
            'payment' => [
                'amount' => EcpCurrencyConverter::transformCurrency($amount, $currency),
                'currency' => $currency,
                'description' => $reason
            ],
            'interface_type' => EcpPaymentService::getInterfaceType(),
        ];

        $requestData['general']['signature'] = $this->signer->sign($requestData);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        $out = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (curl_errno($ch)) {
            throw new Exception('Ecommpay refund ended with error: ' . curl_error($ch));
        }

        $data = json_decode($out, true);
        if ($data === null) {
            throw new Exception('Malformed response');
        }

        if ($httpStatus != 200) {
            return (new EcpCallbackResult(null, $data['status'], $data['message']))
                ->setPaymentStatus($data['payment']['status']);
        }

        $this->updatePaymentMethod($orderId, $data['payment']['method']);

        return (new EcpCallbackResult($data['request_id'], $data['status']))
            ->setPaymentStatus($data['payment']['status']);
    }

    protected function getGateEndpoint(): string
    {
        $protocol = getenv('ECP_PROTO') ?: 'https';
        $envHost = getenv('ECOMMPAY_GATE_HOST');
        $baseUrl = $envHost ? $protocol . '://' . $envHost : self::ECOMMPAY_BASE_URL;

        return $baseUrl . self::GATE_REFUND_ENDPOINT;
    }

    /**
     * @throws PrestaShopException
     */
    private function updatePaymentMethod($orderId, $paymentCode): void
    {
        $db = Db::getInstance();
        $db->update(
            'orders',
            ['payment' => pSQL($paymentCode)],
            'id_order = ' . (int)$orderId
        );
    }
}

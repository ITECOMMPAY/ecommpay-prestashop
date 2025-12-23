<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use ecommpay\SignatureHandler;
use Ecommpay\exceptions\EcpGatewayException;
use PrestaShopDatabaseException;
use PrestaShopException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class EcpRequestProcessor
{
    private const ECOMMPAY_BASE_URL = 'api.ecommpay.com';
    private const GATE_REFUND_ENDPOINT = '/v2/payment/card/refund';

    /**
     * @var SignatureHandler
     */
    private $signer;
    /**
     * @var EcpPaymentService
     */
    private $paymentService;
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    public function __construct(SignatureHandler $signer, EcpPaymentService $paymentService, HttpClientInterface $httpClient)
    {
        $this->signer = $signer;
        $this->paymentService = $paymentService;
        $this->httpClient = $httpClient;
    }

    /**
     * @param int $orderId
     * @param float $amount
     * @param string $currency
     * @param string $reason
     * @return string
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     * @throws EcpGatewayException
     */
    public function sendPostRequest(
        int    $orderId,
        float  $amount,
        string $currency,
        string $reason = ''
    ): string
    {
        $order = new EcpOrder($orderId);

        $requestData = [
            'general' => [
                'project_id' => $this->paymentService->getProjectId(),
                'payment_id' => $order->getPaymentId(),
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

        try {
            $response = $this->httpClient->request('POST', self::getGateEndpoint(), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData,
                'verify_peer' => true,
            ]);

            $httpStatus = $response->getStatusCode();
            $content = $response->getContent(false);

            $data = json_decode($content, true);
            if ($data === null) {
                throw new EcpGatewayException('Malformed response');
            }

            if ($httpStatus !== 200) {
                $errorMessage = 'Refund request failed (HTTP ' . $httpStatus . ')';
                if (isset($data['message'])) {
                    $errorMessage .= ': ' . $data['message'];
                }
                throw new EcpGatewayException($errorMessage);
            }

            if (!isset($data['request_id'])) {
                throw new EcpGatewayException('Response missing request_id');
            }

            return $data['request_id'];

        } catch (TransportExceptionInterface $e) {
            throw new EcpGatewayException('Ecommpay refund ended with error: ' . $e->getMessage());
        } catch (HttpExceptionInterface $e) {
            throw new EcpGatewayException('Ecommpay refund ended with HTTP error: ' . $e->getMessage());
        }
    }

    protected function getGateEndpoint(): string
    {
        $protocol = getenv('ECP_PROTO') ?: 'https';
        $envHost = getenv('ECOMMPAY_GATE_HOST');
        $baseUrl = $envHost ? $protocol . '://' . $envHost : $protocol . '://' . self::ECOMMPAY_BASE_URL;

        return $baseUrl . self::GATE_REFUND_ENDPOINT;
    }
}

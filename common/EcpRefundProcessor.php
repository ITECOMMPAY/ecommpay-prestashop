<?php
declare(strict_types=1);

require_once __DIR__ . '/EcpSigner.php';
require_once __DIR__ . '/EcpRefundResult.php';
require_once __DIR__ . '/EcpCallback.php';
require_once __DIR__ . '/EcpOperationException.php';
require_once __DIR__ . '/EcpOrderIdFormatter.php';

class EcpRefundProcessor
{
    const ECOMMPAY_GATE_PROTO = 'https';
    const ECOMMPAY_GATE_HOST = 'api.ecommpay.com';
    const GATE_REFUND_ENDPOINT = '/v2/payment/card/refund';
    const REFUND_OPERATION_TYPE = 'refund';
    const REVERSAL_OPERATION_TYPE = 'reversal';
    /**
     * @var int
     */
    private $projectId;

    /**
     * @var EcpSigner
     */
    private $signer;

    /**
     * @var string
     */
    private $paymentPrefix;

    /**
     * @param $projectId
     * @param string $secretKey
     * @param string $paymentPrefix
     */
    public function __construct(int $projectId, string $secretKey, string $paymentPrefix = '')
    {
        $this->projectId = $projectId;
        $this->signer = new EcpSigner($secretKey);
        $this->paymentPrefix = $paymentPrefix;
    }


    /**
     * @param string $orderId
     * @param float $amount
     * @param string $currency
     * @param string $reason
     * @return EcpRefundResult
     * @throws Exception
     */
    public function processRefund(string $orderId, float $amount, string $currency, string $reason = ''): EcpRefundResult
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::getGateEndpoint());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $post = [
            'general' => [
                'project_id' => $this->projectId,
                'payment_id' => $this->paymentPrefix
                    ? EcpOrderIdFormatter::addOrderPrefix($orderId, $this->paymentPrefix)
                    : $orderId,
                'merchant_callback_url' => EcpHelper::getCallbackUrl()
            ],
            'payment' => [
                'amount' => round($amount * 100), //
                'currency' => $currency,
                'description' => $reason
            ],
            'interface_type' => Signer::getInterfaceType(),
        ];

        $post['general']['signature'] = $this->signer->getSignature($post);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        $out = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($out, true);
        if ($data === null) {
            throw new Exception('Malformed response');
        }

        if ($httpStatus != 200) {
            return (new EcpRefundResult($orderId, null, $data['status'], $data['message']))
                ->setPaymentStatus($data['payment']['status']);
        }

        return (new EcpRefundResult($orderId, $data['request_id'], $data['status']))
            ->setPaymentStatus($data['payment']['status']);
    }

    /**
     * @param string $rawData
     * @return EcpCallback
     * @throws Exception
     */
    public function processCallback(string $rawData): EcpCallback
    {
        $data = json_decode($rawData, true);
        if ($data === null) {
            throw new Exception('Malformed callback data.');
        }
        if (empty($data['operation']) || empty($data['operation']['type']) ||
            !in_array($data['operation']['type'], [self::REFUND_OPERATION_TYPE, self::REVERSAL_OPERATION_TYPE])
        ) {
            throw new EcpOperationException('Invalid or missed operation type, expected "refund"'.
                ' or "reversal".');
        }
        if (!$this->signer->checkSignature($data)) {
            throw new Exception('Wrong data signature.');
        }
        if (empty($data['operation']['status'])) {
            throw new Exception('Empty "status" field in callback data.');
        }
        $status = $data['operation']['status'];
        if (!in_array($status, ['success', 'decline'])) {
            throw new Exception('Received status is not final.');
        }

        if (empty($data['payment']) || empty($data['payment']['id'])) {
            throw new Exception('Missed "payment.id" field in callback data.');
        }
        if (empty($data['operation']['request_id'])) {
            throw new Exception('Empty "operation.request_id" field in callback data.');
        }

        $callback = new EcpCallback($data);
        $orderId = EcpOrderIdFormatter::removeOrderPrefix($callback->getPaymentId(), $this->paymentPrefix);
        $callback->setOrderId($orderId);
        return $callback;
    }

    protected function getGateEndpoint(): string
    {
        $proto = getenv('ECOMMPAY_GATE_PROTO') ?: self::ECOMMPAY_GATE_PROTO;
        $host = getenv('ECOMMPAY_GATE_HOST') ?: self::ECOMMPAY_GATE_HOST;

        return $proto.'://'.$host.self::GATE_REFUND_ENDPOINT;
    }
}

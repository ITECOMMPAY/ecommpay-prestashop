<?php

class EcpCallback
{
    /**
     * @var array
     */
    private $callback;

    /**
     * @var string|null
     */
    private $orderId;

    /**
     * @var string|null
     */
    private $requestId;

    /**
     * @var string|null
     */
    private $operationStatus;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $paymentStatus;

    /**
     * @var string
     */
    private $paymentId;

    /**
     * @var int
     */
    private $sumInitialAmount;

    /**
     * @var string
     */
    private $sumInitialCurrency;


    /**
     * EcpRefundCallbackResult constructor.
     * @param array $callbackData
     */
    public function __construct(array $callbackData)
    {
        $this->callback = $callbackData;
        $this->paymentStatus = $callbackData['payment']['status'];
        $this->paymentId = $callbackData['payment']['id'];
        $this->requestId = $callbackData['operation']['request_id'];
        $this->operationStatus = $callbackData['operation']['status'];
        $this->description = $callbackData['operation']['message'];
        $this->sumInitialAmount = $callbackData['operation']['sum_initial']['amount'];
        $this->sumInitialCurrency = $callbackData['operation']['sum_initial']['currency'];
    }

    /**
     * @return array
     */
    public function getCallback() {
        return $this->callback;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return strtolower($this->operationStatus) === 'success';
    }

    /**
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    public function setOrderId(string $orderId) {
        $this->orderId = $orderId;
    }

    /**
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return null|string
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * @return string
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @return float|int
     */
    public function getSumInitialAmount($minor=false)
    {
        if ($minor) {
            return $this->sumInitialAmount;
        }
        return (float)$this->sumInitialAmount/100;
    }
}

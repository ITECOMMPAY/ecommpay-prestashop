<?php

class EcpRefundResult
{
    /**
     * @var string|null
     */
    private $orderId;

    /**
     * @var string|null
     */
    private $refundExternalId;

    /**
     * @var string|null
     */
    private $status;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $paymentStatus;

    /**
     * EcpRefundCallbackResult constructor.
     * @param string $orderId
     * @param string $refundExternalId
     * @param string $status
     * @param string|null $description
     */
    public function __construct($orderId, $refundExternalId, $status, $description = null)
    {
        $this->orderId = $orderId;
        $this->refundExternalId = $refundExternalId;
        $this->status = $status;
        $this->description = $description;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return strtolower($this->status) === 'success';
    }

    /**
     * @return string|null
     */
    public function getRefundExternalId()
    {
        return $this->refundExternalId;
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
     * @param string $paymentStatus
     * @return EcpRefundResult
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }
}

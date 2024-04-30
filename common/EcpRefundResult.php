<?php
declare(strict_types=1);

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
    public function isSuccess(): bool
    {
        return strtolower($this->status) === 'success';
    }

    /**
     * @return string|null
     */
    public function getRefundExternalId(): ?string
    {
        return $this->refundExternalId;
    }

    /**
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return null|string
     */
    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): EcpRefundResult
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }
}

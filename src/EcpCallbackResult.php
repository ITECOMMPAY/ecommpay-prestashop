<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

class EcpCallbackResult
{
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

    public function __construct(string $externalId, string $status, string $description = null)
    {
        $this->refundExternalId = $externalId;
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

    public function setPaymentStatus(?string $paymentStatus): EcpCallbackResult
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }
}

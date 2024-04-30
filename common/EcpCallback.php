<?php
declare(strict_types=1);

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
     * @var int|null
     */
    private $sumInitialAmount;

    /**
     * @var string|null
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
        $this->requestId = $callbackData['operation']['request_id'] ?? null;
        $this->operationStatus = $callbackData['operation']['status'] ?? null;
        $this->description = $callbackData['operation']['message'] ?? null;
        $this->sumInitialAmount = $callbackData['operation']['sum_initial']['amount'] ?? null;
        $this->sumInitialCurrency = $callbackData['operation']['sum_initial']['currency'] ?? null;
    }

    /**
     * @return array
     */
    public function getCallback(): array
    {
        return $this->callback;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return strtolower($this->operationStatus) === 'success';
    }

    /**
     * @return string|null
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
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
     * @return string
     */
    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    /**
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * @return float|int
     */
    public function getSumInitialAmount(bool $minor = false): mixed
    {
        if ($minor) {
            return $this->sumInitialAmount;
        }
        return (float)$this->sumInitialAmount / 100;
    }
}

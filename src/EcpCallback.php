<?php

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use ecommpay\Callback;

class EcpCallback extends Callback
{
    public const OPERATION_TYPE_SUCCESS = 'success';

    public function getPaymentDescription(): ?string
    {
        return $this->getValue('payment.description');
    }

    public function getPaymentMethod(): ?string
    {
        return $this->getValue('payment.method');
    }

    public function getSumInitialAmountMinor(): ?int
    {
        if (!$paymentAmount = $this->getValue('operation.sum_initial.amount')) {
            return null;
        }
        return $paymentAmount;
    }

    public function getSumInitialAmount(): ?float
    {
        if (!$sumInitialMinor = $this->getSumInitialAmountMinor()) {
            return null;
        }
        return (float)$sumInitialMinor / 100;
    }

    public function getOperationStatus(): ?string
    {
        return $this->getValue('operation.status');
    }

    public function isOperationSuccess(): ?bool
    {
        return $this->getOperationStatus() ? strtolower($this->getOperationStatus()) === $this::OPERATION_TYPE_SUCCESS : null;
    }
    
    public function getOperationType(): ?string
    {
        return $this->getValue('operation.type');
    }

    public function getOperationRequestId(): ?string
    {
        return $this->getValue('operation.request_id');
    }

    public function getAccountNumber(): ?string
    {
        return $this->getValue('account.number');
    }

    public function getAccountCardHolder(): ?string
    {
        return $this->getValue('account.card_holder');
    }

    public function getAccountType(): ?string
    {
        return $this->getValue('account.type');
    }
}

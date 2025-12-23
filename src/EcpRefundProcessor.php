<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use Currency;
use DateTime;
use Ecommpay\exceptions\EcpGatewayException;
use Ecommpay\exceptions\EcpRefundException;
use PrestaShop\Decimal\DecimalNumber;
use PrestaShop\Decimal\Operation\Rounding;
use Tools;

class EcpRefundProcessor
{
    private $requestProcessor;
    private $orderManager;
    private $moduleName;

    public function __construct(EcpRequestProcessor $requestProcessor, EcpOrderManager $orderManager, string $moduleName)
    {
        $this->requestProcessor = $requestProcessor;
        $this->orderManager = $orderManager;
        $this->moduleName = $moduleName;
    }

    public function process(EcpOrder $order): void
    {
        if (!$this->isRequestedRefundViaEcommpay()) {
            return;
        }
        try {
            $this->validateRefund($order);
            $this->processRequest($order);
        } catch (EcpRefundException $error) {
            $this->orderManager->saveOrderMessage($order->id, $error->getMessage());
        } catch (EcpGatewayException $error) {
            $this->orderManager->saveOrderMessage($order->id, 'Request was not correctly processed by gateway. ' . $e->getMessage());
        }
    }

    private function validateRefund(EcpOrder $order): void
    {
        if (!$this->isOrderProcessedViaEcommpay($order)) {
            throw new EcpRefundException('Refund failed: Order is not in a valid state for refund.');
        }
        if (!$this->orderHasValidStatusForRefund($order)) {
            throw new EcpRefundException('Refund failed: Order is not in a valid state for refund.');
        }
        if (!$this->isTotalRefundedLessThanOrderTotal($order)) {
            throw new EcpRefundException('Refund failed: Refund amount exceeds the maximum allowed amount.');
        }
    }

    /**
     * @throws EcpGatewayException
     */
    private function processRequest(EcpOrder $order): void
    {
        $currency = new Currency($order->id_currency);

        $slip = $this->getSlip($order);
        $totalRefund = $this->getTotalRefund($slip);

        $requestId = $this->requestProcessor->sendPostRequest(
            $order->id,
            $totalRefund,
            $currency->iso_code
        );

        $this->saveRefundResult($order, $slip, $totalRefund, $currency, $requestId);
    }

    private function isRequestedRefundViaEcommpay(): bool
    {
        return Tools::isSubmit('doPartialRefundEcommpay');
    }

    private function isOrderProcessedViaEcommpay(EcpOrder $order): bool
    {
        return $order->module === $this->moduleName;
    }

    private function orderHasValidStatusForRefund(EcpOrder $order): bool
    {
        $validStates = [
            Configuration::get(EcpOrderStates::APPROVED_STATE),
            Configuration::get(EcpOrderStates::PARTIALLY_REFUNDED_STATE),
        ];

        return in_array($order->current_state, $validStates);
    }

    private function isTotalRefundedLessThanOrderTotal(EcpOrder $order): bool
    {
        $totalRefunded = new DecimalNumber('0');
        $orderSlips = $order->getOrderSlipsCollection();

        foreach ($orderSlips as $slip) {
            $totalRefunded->plus(
                new DecimalNumber($slip->total_products_tax_incl)
            )->plus(
                new DecimalNumber($slip->total_shipping_tax_incl)
            );
        }

        return $totalRefunded->isLowerOrEqualThan(new DecimalNumber($order->total_paid_tax_incl));
    }

    private function getSlip(EcpOrder $order): EcpOrderSlip
    {
        if (!$slip = $this->orderManager->getLastSlipForOrder($order)) {
            throw new EcpRefundException('Refund failed: Unable to get order slip.');
        }

        return $slip;
    }

    private function getTotalRefund(EcpOrderSlip $slip): float
    {
        $totalRefund = new DecimalNumber($slip->total_products_tax_incl);

        if (Tools::getValue('partialRefundShippingCost')) {
            $totalRefund->plus(new DecimalNumber($slip->total_shipping_tax_incl));
        }

        if ($totalRefund->isLowerOrEqualThanZero()) {
            throw new EcpRefundException('Refund failed: Invalid refund amount (must be greater than 0).');
        }

        return (float)$totalRefund->round(2, Rounding::ROUND_HALF_EVEN);
    }


    private function saveRefundResult(EcpOrder $order, EcpOrderSlip $slip, float $totalRefund, Currency $currency, string $requestId): void
    {
        $thread_id = $this->orderManager->createOrderThread($order->id);

        $now = (new DateTime())->format('d.m.Y H:i:s');
        $message = sprintf('Refund request #%d for amount %.2f %s was sent at %s', $slip->id, $totalRefund, $currency->iso_code, $now);
        $this->orderManager->saveOrderMessage($order->id, $message, $thread_id);

        $slip->external_id = $requestId;
        $slip->customer_thread_id = $thread_id;
        $slip->save();

        $order->setCurrentState(Configuration::get(EcpOrderStates::PENDING_STATE));
    }

}

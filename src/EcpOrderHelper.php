<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use PrestaShopCollection;

/**
 * Controller for restoring cart after payment failure or cancellation
 */
class EcpOrderHelper
{
    private const FINAL_STATUSES = [
        EcpOrderStates::APPROVED_STATE,
        EcpOrderStates::PARTIALLY_REFUNDED_STATE,
        EcpOrderStates::REFUNDED_STATE,
    ];

    static function cartCanBeRestoredForOrder(EcpOrder $order): bool
    {
        $avoidedStatusIds = array_map(function (string $status): string {
            return Configuration::get($status);
        }, self::FINAL_STATUSES);

        return !in_array($order->current_state, $avoidedStatusIds, true);
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrderByPaymentId(string $paymentId): ?EcpOrder
    {
        $order_payments = new PrestaShopCollection(EcpOrderPayment::class);
        $order_payment = $order_payments->where('ecp_payment_id', '=', $paymentId)->getLast();
        if (!$order_payment) {
            return null;
        }

        $orders = new PrestaShopCollection(EcpOrder::class);
        $orders->where('reference', '=', $order_payment->order_reference);
        return $orders->getLast() ?? null;
    }
}

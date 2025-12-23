<?php

declare(strict_types=1);

namespace Ecommpay;

use PrestaShopDatabaseException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class EcpHookManager
{
    /**
     * @throws PrestaShopDatabaseException
     */
    public function getPaymentDataForOrder(int $orderId): array
    {
        $order = new EcpOrder($orderId);
        $payments = $order->getOrderPaymentCollection();
        $paymentData = [];
        foreach ($payments as $payment) {
            $paymentRow = [
                'id_order_payment' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'payment_method' => $payment->payment_method,
                'amount' => $payment->amount,
                'date_add' => $payment->date_add,
                'ecp_payment_id' => $payment->ecp_payment_id
            ];
            $paymentData[] = array_filter($paymentRow);
        }
        return $paymentData;
    }
}

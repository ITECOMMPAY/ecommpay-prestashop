<?php

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use DbQuery;
use Order;
use PrestaShopCollection;

class EcpOrder extends Order
{
    public function updatePaymentMethod(string $paymentMethod): void
    {
        $this->payment = $paymentMethod;
        $this->update();
    }

    public function getOrderPaymentCollection()
    {
        $order_payments = new PrestaShopCollection(EcpOrderPayment::class);
        $order_payments->where('order_reference', '=', $this->reference);

        return $order_payments;
    }

    public function getPaymentId(): ?string
    {
        $payment = $this->getOrderPaymentCollection()->getLast();
        return $payment ? $payment->ecp_payment_id : null;
    }
}

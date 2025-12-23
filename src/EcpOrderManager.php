<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use CustomerMessage;
use CustomerThread;
use Order;
use PrestaShopException;
use Tools;
use Ecommpay\exceptions\EcpBadRequestException;

class EcpOrderManager
{
    private $context;

    public function __construct($context)
    {
        $this->context = $context;
    }

    public function saveOrderMessage(int $order_id, string $message, int $thread_id = null): ?CustomerMessage
    {
        try {
            if ($thread_id === null) {
                $thread_id = $this->createOrderThread($order_id);
            }
            $order_message = new CustomerMessage();
            $order_message->id_customer_thread = $thread_id;
            $order_message->private = 1;
            if ($this->context->employee) {
                $order_message->id_employee = $this->context->employee->id;
            }
            $order_message->message = $message;
            $order_message->save();
        } catch (PrestaShopException $e) {
            return null;
        }
        return $order_message;
    }

    public function createOrderThread(int $id_order): int
    {
        $orderThread = new CustomerThread();
        $orderThread->id_shop = $this->context->shop->id;
        $orderThread->id_lang = $this->context->language->id;
        $orderThread->id_contact = 0;
        $orderThread->id_order = $id_order;
        $orderThread->id_customer = $this->context->customer->id;
        $orderThread->status = 'open';
        $orderThread->email = $this->context->customer->email;
        $orderThread->token = Tools::passwdGen(12);
        $orderThread->add();

        return (int)$orderThread->id;
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getLastSlipForOrder(Order $order): ?EcpOrderSlip
    {
        $slip = $order->getOrderSlipsCollection()->orderBy('id_order_slip', 'desc')->getFirst();
        if (!$slip) {
            $this->saveOrderMessage(
                $order->id,
                'Unable to send refund request: an order slip was not created properly.'
            );
            return null;
        }
        return new EcpOrderSlip($slip->id);
    }

    /**
     * @throws PrestaShopException
     * @throws EcpBadRequestException
     */
    public function setNewOrderStatus(Order $order, EcpCallback $callback): void
    {
        if (!$paymentStatus = $callback->getPaymentStatus()) {
            throw new EcpBadRequestException('No payment status in callback');
        }

        $orderState = EcpPayment::getOrderStateByEcpStatus($paymentStatus);

        if (!$orderState) {
            return;
        }

        $stateId = Configuration::get($orderState);

        if ($order->current_state === $stateId) {
            return;
        }

        $order->setCurrentState($stateId);
        $order->save();
    }
}

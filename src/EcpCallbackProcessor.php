<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Cart;
use Configuration;
use DateTime;
use Db;
use Order;
use OrderSlip;
use PrestaShopException;
use Validate;
use ecommpay\SignatureHandler;
use ecommpay\exception\ProcessException;
use Ecommpay\exceptions\EcpBadRequestException;
use Ecommpay\exceptions\EcpDataNotFound;
use Ecommpay\exceptions\EcpSignatureInvalidException;

class EcpCallbackProcessor
{
    private const OPERATION_TYPE_REFUND = 'refund';
    private const OPERATION_TYPE_REVERSAL = 'reversal';
    private const OPERATION_TYPE_SALE = 'sale';
    private const PAYMENT_TYPE_REFUNDED = 'refunded';
    private const PAYMENT_TYPE_REVERSAL = 'reversal';
    private const PAYMENT_TYPE_PARTIALLY_REFUNDED = 'partially refunded';
    private const PAYMENT_TYPE_PARTIALLY_REVERSED = 'partially reversed';

    /**
     * @var SignatureHandler
     */
    private $signer;
    /**
     * @var EcpOrderManager
     */
    private $orderManager;

    public function __construct(SignatureHandler $signer, EcpOrderManager $orderManager)
    {
        $this->signer = $signer;
        $this->orderManager = $orderManager;
    }

    public function processCallback(array $data): void
    {
        try {
            $callback = new EcpCallback($data, $this->signer);
        } catch (ProcessException $e) {
            throw new EcpSignatureInvalidException('Wrong data signature.');
        }

        $order = $this->getOrder($callback);

        switch ($callback->getOperationType()){
            case self::OPERATION_TYPE_SALE:
                $this->handleSale($callback, $order);
                break;
            case self::OPERATION_TYPE_REFUND:
            case self::OPERATION_TYPE_REVERSAL:
                $this->handleRefund($callback, $order);
                break;
        }
    }

    private function getOrder(EcpCallback $callback): EcpOrder
    {
        if (!$paymentId = $callback->getPaymentId()) {
            throw new EcpBadRequestException('No payment id');
        }

        if (!$order = EcpOrder::findByPaymentId($paymentId)) {
            throw new EcpDataNotFound('Order not found with that payment id');
        }

        if (!Validate::isLoadedObject($order)) {
            throw new EcpDataNotFound('Order couldn\'t be loaded');
        }

        return $order;
    }

    private function handleSale(EcpCallback $callback, EcpOrder $order): void
    {
        $this->addTransactionsInfo($order, $callback);
        $this->orderManager->setNewOrderStatus($order, $callback);
    }

    private function handleRefund(EcpCallback $callback, EcpOrder $order): void
    {
        if (!$requestId = $callback->getOperationRequestId()) {
            throw new EcpDataNotFound('Request ID required');
        }
        if (!$slip = EcpOrderSlip::findByExternalId($requestId)) {
            $slip = $this->createSlip($callback, $order);
        }

        $nowDateTimeString = (new DateTime())->format('d.m.Y H:i:s');
        $customerThreadId = (int)$slip->customer_thread_id;

        if (!$callback->isOperationSuccess()) {
            $message = 'Money-back request #' . $slip->id . ' was declined at ' . $nowDateTimeString
                . '. Reason: ' . $callback->getPaymentDescription();
            $this->orderManager->saveOrderMessage($order->id, $message, $customerThreadId);
            return;
        }

        $message = 'Money-back request #' . $slip->id . ' was successfully processed at ' . $nowDateTimeString;
        $this->orderManager->saveOrderMessage($order->id, $message, $customerThreadId);

        if (in_array($callback->getPaymentStatus(), [self::PAYMENT_TYPE_PARTIALLY_REFUNDED, self::PAYMENT_TYPE_PARTIALLY_REVERSED])) {
            $order->setCurrentState(Configuration::get(EcpOrderStates::PARTIALLY_REFUNDED_STATE));
            $order->save();
        }

        if (in_array($callback->getPaymentStatus(), [self::PAYMENT_TYPE_REFUNDED, self::PAYMENT_TYPE_REVERSAL])) {
            $order->setCurrentState(Configuration::get(EcpOrderStates::REFUNDED_STATE));
            $order->save();
        }
        $slip->external_id = '+' . $slip->external_id;
        $slip->save();
    }

    private function createSlip(EcpCallback $callback, EcpOrder $order): EcpOrderSlip
    {
        OrderSlip::create($order, [], false, -$callback->getSumInitialAmount());
        $slip = $this->orderManager->getLastSlipForOrder($order);
        $slip->external_id = $callback->$callback->getOperationRequestId();
        $slip->customer_thread_id = $this->orderManager->createOrderThread($order->id);
        $slip->save();
        return $slip;
    }

    private function addTransactionsInfo(Order $order, EcpCallback $callback): void
    {
        if (_PS_VERSION_ < 1.5) {
            return;
        }

        $cartId = Cart::getCartIdByOrderId($order->id);

        $payment = $order->getOrderPaymentCollection()->where('transaction_id', '=', $cartId)->getFirst();

        if (!$payment) {
            return;
        }

        if ($paymentAmount = $callback->getSumInitialAmount()) {
            $payment->amount = number_format($paymentAmount, 2, '.', '');
        }
        $payment->card_number = $callback->getAccountNumber();
        $payment->card_holder = $callback->getAccountCardHolder();
        $payment->card_brand = $callback->getAccountType();
        $payment->payment_method = $callback->getPaymentMethod();

        $payment->save();
    }
}

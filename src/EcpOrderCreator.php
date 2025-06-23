<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Cart;
use Context;
use Configuration;
use Currency;
use Customer;
use Module;
use Order;
use Tools;
use Validate;
use Ecommpay\exceptions\EcpCartValidationException;

class EcpOrderCreator
{
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function createOrderIfRequired(Cart $cart): Order
    {
        $this->validateCart($cart);
        $this->validateOrderExists($cart);
        $this->validatePaymentMethodAuthorization();
        $this->validateProducts($cart);

        $currency = $this->getAndValidateCurrency($cart);
        $orderStatus = $this->getAndValidateOrderStatus();
        $customer = $this->getAndValidateCustomer($cart);

        $total = (float)$cart->getOrderTotal();

        EcpLogger::log('Creating order with params', [
            'cart_id' => $cart->id,
            'customer_id' => $customer->id,
            'total' => $total,
            'currency_id' => $currency->id,
            'payment_status' => $orderStatus
        ]);

        $this->module->validateOrder(
            $cart->id,
            $orderStatus,
            $total,
            $this->module->displayName,
            null,
            array('transaction_id' => $cart->id),
            $currency->id,
            false,
            $customer->secure_key
        );

        return $this->getAndValidateCreatedOrder($cart);
    }

    private function getAndValidateCurrency(Cart $cart): Currency
    {
        $currency = new Currency($cart->id_currency);
        if (!Validate::isLoadedObject($currency)) {
            $this->throwException('Invalid currency', ['currency_id' => $cart->id_currency]);
        }
        return $currency;
    }

    private function getAndValidateCustomer(Cart $cart): Customer
    {
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->throwException('Invalid customer', ['customer_id' => $cart->id_customer]);
        }
        return $customer;
    }

    private function getAndValidateOrderStatus(): int
    {
        $orderStatus = (int)Configuration::get('PS_OS_PAYMENT');
        if ($orderStatus === 0) {
            $this->throwException('Order status PS_OS_PAYMENT not found or invalid');
        }
        return $orderStatus;
    }

    private function validateCart(Cart $cart): void
    {
        if (!$cart || !Validate::isLoadedObject($cart)) {
            $this->throwException('Invalid cart');
        }

        if (
            $cart->id_customer === 0 || $cart->id_address_delivery === 0 || $cart->id_address_invoice === 0
            || !$this->module->active
        ) {
            $this->throwException('Invalid cart data. ', [
                'id_customer' => $cart->id_customer,
                'id_address_delivery' => $cart->id_address_delivery,
                'id_address_invoice' => $cart->id_address_invoice,
                'module_active' => $this->module->active
            ]);
        }
    }

    private function validatePaymentMethodAuthorization(): void
    {
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === $this->module->name) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            $this->throwException('Payment module is not available', ['module' => $this->module->name]);
        }
    }

    private function validateProducts(Cart $cart): void
    {
        $products = $cart->getProducts();
        if (empty($products)) {
            $this->throwException('Cart is empty');
        }
    }

    private function validateOrderExists(Cart $cart): void
    {
        if ($cart->OrderExists()) {
            $this->throwException('Order already exists for cart', ['cart_id' => $cart->id]);
        }
    }

    private function getAndValidateCreatedOrder(Cart $cart): Order
    {
        $orderId = Order::getIdByCartId($cart->id);
        if (!$orderId) {
            $this->throwException('Failed to get order ID after creation.', ['cart_id' => $cart->id]);
        }

        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            $this->throwException('Failed to load order after creation', ['order_id' => $orderId]);
        }

        EcpLogger::log('Order created successfully', [
            'order_id' => $orderId,
            'order_status' => $order->current_state,
            'order_status_name' => $order->getCurrentStateFull(Context::getContext()->language->id)
        ]);

        return $order;
    }

    private function throwException(string $message, array $context = []): void
    {
        EcpLogger::log($message, $context);
        throw new EcpCartValidationException($message);
    }
}

<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Ecommpay\EcpLogger;
use Ecommpay\EcpOrder;
use Ecommpay\EcpOrderHelper;
use Ecommpay\exceptions\EcpCartDuplicationException;

/**
 * Controller for restoring cart after payment failure or cancellation
 */
class EcommpayRestorecartModuleFrontController extends ModuleFrontController
{
    private const CART_LIST_KEY = 'cart';
    private const CART_DUPLICATION_SUCCESS_KEY = 'success';

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function postProcess(): void
    {
        if (!$this->context->customer || !$this->context->customer->id) {
            Tools::redirect($this->context->link->getPageLink('order', true));
            return;
        }

        $oldCart = $this->getLatestCartWithOrder($this->context->customer->id);

        if (!$oldCart) {
            EcpLogger::log('No cart found for restoration');
            Tools::redirect($this->context->link->getPageLink('order', true));
            return;
        }

        $orderId = EcpOrder::getIdByCartId($oldCart->id);
        $order = new EcpOrder($orderId);

        if (!EcpOrderHelper::cartCanBeRestoredForOrder($order)) {
            Tools::redirect($this->context->link->getPageLink('order', true));
            return;
        }

        $this->restoreCart($oldCart);
    }

    protected function redirectToCheckout(): void
    {
        $checkoutUrl = $this->context->link->getPageLink('order', true);

        if (isset($this->context->cookie)) {
            $message = $this->module->l('Payment was declined. You can try another payment method.', 'restorecart');
            $this->context->cookie->ecommpay_payment_declined = $message;
            $this->context->cookie->write();
        }

        Tools::redirect($checkoutUrl);
    }

    protected function getLatestCartWithOrder(int $customerId): ?Cart
    {
        $carts = Cart::getCustomerCarts($customerId);
        if (!$carts) {
            return null;
        }

        foreach (array_reverse($carts) as $cartData) {
            $cartId = (int)($cartData['id_cart'] ?? 0);
            if (!$cartId) {
                continue;
            }

            if (!EcpOrder::getIdByCartId($cartId)) {
                continue;
            }

            $cart = new Cart($cartId);
            if (Validate::isLoadedObject($cart)) {
                return $cart;
            }
        }

        return null;
    }

    private function restoreCart(Cart $oldCart): void
    {
        $duplication = $oldCart->duplicate();

        if (!is_array($duplication)) {
            $this->logCartRestorationFailure($duplication);
        }
        elseif (!$this->isNewCartValid($duplication)) {
            $this->logCartRestorationFailure($duplication);
        }
        else {
            try {
                $this->setNewCart($duplication);
            } catch (EcpCartDuplicationException $e) {
                EcpLogger::log('Failed to restore cart: ' . $e->getMessage());
            }
        }

        $this->redirectToCheckout();
    }

    private function isNewCartValid(array $duplication): bool
    {
        return $this->hasValidCart($duplication)
            && ($duplication[self::CART_DUPLICATION_SUCCESS_KEY] ?? false);
    }

    private function hasValidCart(array $duplication): bool
    {
        $cart = $duplication[self::CART_LIST_KEY] ?? null;
        return $cart instanceof Cart && Validate::isLoadedObject($cart);
    }

    /**
     * @throws EcpCartDuplicationException
     */
    private function setNewCart(array $duplication): void
    {
        if (!$this->hasValidCart($duplication)) {
            throw new EcpCartDuplicationException('Invalid cart duplication result.');
        }

        $newCart = $duplication[self::CART_LIST_KEY];
        $this->updateContextCart($newCart);
    }

    private function updateContextCart(Cart $cart): void
    {
        $this->context->cookie->id_cart = $cart->id;
        $this->context->cart = $cart;
        CartRule::autoAddToCart($this->context);
        $this->context->cookie->write();
    }

    /**
     * @param array|false $duplication
     */
    private function logCartRestorationFailure($duplication): void
    {
        if (!is_array($duplication) || !$this->hasValidCart($duplication)) {
            EcpLogger::log('Sorry. We cannot renew your order.');
            return;
        }

        if (!($duplication[self::CART_DUPLICATION_SUCCESS_KEY] ?? false)) {
            EcpLogger::log('Some items are no longer available, and we are unable to renew your order.');
        }
    }
}

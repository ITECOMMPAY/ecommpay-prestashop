<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Order;
use OrderState;
use PrestaShopDatabaseException;
use PrestaShopException;
use Validate;
use Ecommpay\EcpOrderStates;
use Exception;
use Ecommpay\EcpLogger;

/**
 * @since 1.5.0
 */
class EcommpaySuccesspaymentModuleFrontController extends ModuleFrontController
{
    private const HISTORY_URI = 'index.php?controller=history';

    /**
     * @var string
     */
    protected $status;

    /**
     * @var Order|null
     */
    protected $currentOrder = null;

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function initContent()
    {
        parent::initContent();

        $cartId = Tools::getValue('cart_id');
        $orderId = $cartId ? Order::getIdByCartId((int)$cartId) : null;

        if ($orderId) {
            $this->redirectToOrderConfirmation($orderId);
            return;
        }

        Tools::redirect($this::HISTORY_URI);
    }

    private function redirectToOrderConfirmation(int $orderId): void
    {
        try {
            $order = new Order($orderId);
            if (!Validate::isLoadedObject($order)) {
                Tools::redirect($this::HISTORY_URI);
                return;
            }

            Tools::redirect('index.php?' . http_build_query([
                    'controller' => 'order-confirmation',
                    'id_cart' => $order->id_cart,
                    'id_module' => $this->module->id,
                    'id_order' => $order->id,
                    'key' => $order->secure_key,
                ]));
        } catch (Exception $e) {
            Tools::redirect($this::HISTORY_URI);
        }
    }
}

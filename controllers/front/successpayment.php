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

        $orderId = Tools::getValue('order_id');
        if (!$orderId) {
            Tools::redirect($this::HISTORY_URI);
            return;
        }

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
                'id_order' =>  $order->id,
                'key' => $order->secure_key,
            ]));

        } catch (Exception $e) {
            Tools::redirect($this::HISTORY_URI);
        }
    }

    /**
     * @return $this
     */
    protected function findOrder(): EcommpaySuccesspaymentModuleFrontController
    {
        $orderId = (int)($_GET['order_id'] ?? 0);

        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            $this->status = $this->module->l('Unable to find order #' . $orderId);
            return $this;
        }

        $this->currentOrder = $order;
        return $this;
    }

    protected function getOrder(): ?Order
    {
        if (empty($this->currentOrder)) {
            return null;
        }

        return $this->currentOrder;
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function setStatusFromOrder(): void
    {
        $order = $this->getOrder();
        if (!$order) {
            return;
        }

        $state = new OrderState($order->current_state);
        $name = $state->name[1] ?? null;
        if (!$name) {
            $name = $state->name[0] ?? null;
        }
        if ($name) {
            $this->status = $this->module->l('Your order has status: ' . $name);
        }
    }
}

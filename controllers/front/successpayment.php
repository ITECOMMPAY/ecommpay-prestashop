<?php

/**
 * @since 1.5.0
 */
class EcommpaySuccessPaymentModuleFrontController extends ModuleFrontController
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @var Order
     */
    protected $currentOrder = null;

    public function initContent()
    {
        parent::initContent();

        $this->status = 'Once payment is processed by Ecommpay you order will appear in your history';
        $historyUrl = $this->module->getHistoryLink();

        if ($this->isTestMode()) {
            $this
                ->findOrder()
                ->changeTestOrderState();
        } else {
            $this
                ->findOrder()
                ->setStatusFromRealOrder();
        }

        $status = $this->status;
        $order = $this->currentOrder;

        $this->context->smarty->assign(compact('historyUrl', 'status', 'order'));

        if (_PS_VERSION_ >= '1.7') {
            $this->setTemplate('module:ecommpay/views/templates/front/payment_success_17.tpl');
            return;
        }

        $this->setTemplate('payment_success.tpl');
    }

    /**
     * @return bool
     */
    protected function isTestMode()
    {
        return
            isset($_GET['test'])
            &&
            $_GET['test'] === '1'
            &&
            $this->module->isInTestMode();
    }

    /**
     * @return $this
     */
    protected function findOrder()
    {
        $orderId = intval(isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0);

        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            $this->status = $this->module->l('Unable to find order #' . $orderId);
            return $this;
        }

        $this->currentOrder = $order;
        return $this;
    }

    protected function getOrder()
    {
        if (empty($this->currentOrder)) {
            return null;
        }

        return $this->currentOrder;
    }

    protected function changeTestOrderState()
    {
        $order = $this->getOrder();
        if (!$order) {
            return;
        }

        $stateApproved = Configuration::get('PS_OS_ECOMMPAY_APPROVED');

        if ($order->current_state != $stateApproved) {
            $this->addTestTransactionsInfo($order);
            $this->module->setOrderState($order, $stateApproved);
        }
        $this->status = $this->module->l('Your order has been processed by Ecommpay');
    }

    protected function setStatusFromRealOrder()
    {
        $order = $this->getOrder();
        if (!$order) {
            return;
        }

        $state = new OrderState($order->current_state);
        $name = isset($state->name[1]) ? $state->name[1] : null;
        if (!$name) {
            $name = isset($state->name[0]) ? $state->name[0] : null;
        }
        if ($name) {
            $this->status = $this->module->l('Your order has status: ' . $name);
        }
    }

    protected function addTestTransactionsInfo($order)
    {
        $this->module->addTransactionsInfo($order, array(
            'payment' => array(
                'id' => $order->id,
                'sum' => array(
                    'amount' => $order->getTotalPaid() * 100
                ),
            ),
            'account' => array(
                'card_holder' => 'Test Test',
                'number' => '5555555555554444'
            )
        ));
    }
}

<?php

/**
 * @since 1.5.0
 */
class EcommpayCallbackModuleFrontController extends ModuleFrontController
{
    protected $currentOrder;

    public function initContent()
    {
        parent::initContent();

        $body = file_get_contents('php://input');
        $bodyData = json_decode($body, true);

        // TODO catch exception when directory is not writable
        $this->log('body', $body);
        $this->log('bodyData', $bodyData);

        try {
            /** @var ecommpay $module */
            $module = $this->module;
            if ($module->processRefundCallback($body)) {
                die('Ok. Refund is processed');
            }
        } catch (\Exception $e) {
            http_response_code(400);
            die($e->getMessage());
        }

        /** @var ecommpay $module */
        $module = $this->module;
        if (is_array($bodyData) && $module->signer->checkSignature($bodyData)) {
            $this->log('signature valid', $bodyData['signature']);

            $orderId = $bodyData['payment']['id'];
            require_once(__DIR__ . '/../../common/EcpOrderIdFormatter.php');
            $orderId = EcpOrderIdFormatter::removeOrderPrefix($orderId, Signer::CMS_PREFIX);

            $paymentStatus = $bodyData['payment']['status'];

            $state = 'success' === $paymentStatus ? 'PS_OS_ECOMMPAY_APPROVED' : 'PS_OS_ECOMMPAY_DECLINED';
            $this
                ->findOrder($orderId)
                ->setOrderState($state, $bodyData);
            die('ok');
        }

        $this->log('signature invalid');
        http_response_code(400);
        die('signature invalid');
    }

    protected function findOrder($orderId)
    {
        $order = new Order($orderId);

        if (!Validate::isLoadedObject($order)) {
            $this->log(sprintf('Order with id %s NOT found', $orderId));
            return $this;
        }

        $this->log(sprintf('Order with id %s found', $orderId));
        $this->currentOrder = $order;
        return $this;
    }

    protected function setOrderState($state, array $data)
    {
        if (empty($this->currentOrder)) {
            $this->log('Skipping order creation, order does not exist');
            return;
        }

        $order = $this->currentOrder;
        $stateId = Configuration::get($state);

        if ($order->current_state != $stateId) {
            $this->module->addTransactionsInfo($order, $data);
            $this->module->setOrderState($order, $stateId);
            $this->log(sprintf('Order %s exists, setting state %s', $order->id, $state));
            return;
        }

        $this->log(sprintf('Order %s exists, state already is %s', $order->id, $state));
    }

    protected function log($name, $data = null)
    {
        $message = sprintf('CALLBACK: %s', $name);
        if ($data !== null) {
            $message .= sprintf(' = %s', print_r($data, true));
        }

        if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_ === true) {
            Logger::addLog($message, 1);
        }
    }
}

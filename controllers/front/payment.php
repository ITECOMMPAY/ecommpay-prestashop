<?php
declare(strict_types=1);

/**
 * @since 1.5.0
 */
class EcommpayPaymentModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {

            if ($this->isAjax()) {
                $this->sendAjax(array(
                    'success' => false,
                    'error' => 'Wrong data, try again from start'
                ));
            }

            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == $this->module->name) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {

            if ($this->isAjax()) {
                $this->sendAjax(array(
                    'success' => false,
                    'error' => 'No customer'
                ));
            }

            Tools::redirect('index.php?controller=order&step=1');
        }

        $products = $cart->getProducts();
        if (empty($products)) {

            if ($this->isAjax()) {
                $this->sendAjax(array(
                    'success' => false,
                    'error' => 'No products'
                ));
            }

            Tools::redirect('index.php?controller=order&step=1');
        }

        if (
            (isset($_GET['confirm']) && $_GET['confirm'] === '1')
            ||
            (isset($_POST['confirm']) && $_POST['confirm'] === '1')
        ) {
            $this->createOrderIfRequired($cart);
            $paymentUrl = $this->module->signer->getCardRedirectUrl($cart);

            if ($this->isAjax()) {
                $this->sendAjax(array(
                    'success' => true,
                    'cardRedirectUrl' => $paymentUrl
                ));
            }

            Tools::redirect($paymentUrl);
        }

        if (_PS_VERSION_ >= 1.7) {
            die('Only POST method available for 1.7');
        }

        $paymentUrl = $this->context->link->getModuleLink($this->module->name, 'payment', ['confirm' => true]);

        $this->context->smarty->assign(array(
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'paymentUrl' => $paymentUrl,
        ));

        $this->setTemplate('payment_execution.tpl');
    }

    protected function isAjax(): bool
    {
        return (bool) Tools::getValue('is_ajax');
    }

    protected function sendAjax(array $data): void
    {
        die(json_encode($data));
    }

    /**
     * Creates order for provided cart if it does not exist
     * @param $cart
     */
    protected function createOrderIfRequired(Cart $cart): void
    {
        $orderId = Order::getIdByCartId($cart->id);
        if ($orderId) {
            $order = new Order($orderId);
            if (Validate::isLoadedObject($order)) {
                return;
            }
        }

        $total = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
        $currency =  new Currency($cart->id_currency);

        $this->module->validateOrder(
            $cart->id,
            Configuration::get('PS_OS_ECOMMPAY_PENDING'),
            $total,
            $this->module->displayName,
            null,
            array('transaction_id' => $cart->id),
            $currency->id,
            false,
            $cart->secure_key
        );
    }
}

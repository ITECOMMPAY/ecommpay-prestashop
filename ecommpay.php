<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/Signer.php';
require_once __DIR__ . '/common/EcpHelper.php';
require_once __DIR__ . '/common/EcpOrderStates.php';


class ecommpay extends PaymentModule
{
    public const PLUGIN_NAME = 'ecommpay';

    const ECOMMPAY_PROJECT_ID = 'ECOMMPAY_PROJECT_ID';
    const ECOMMPAY_SECRET_KEY = 'ECOMMPAY_SECRET_KEY';
    const ECOMMPAY_IS_TEST = 'ECOMMPAY_IS_TEST';
    const ECOMMPAY_PAYMENT_PAGE_LANGUAGE = 'ECOMMPAY_PAYMENT_PAGE_LANGUAGE';
    const ECOMMPAY_PAYMENT_PAGE_CURRENCY = 'ECOMMPAY_PAYMENT_PAGE_CURRENCY';
    const ECOMMPAY_ADDITIONAL_PARAMETERS = 'ECOMMPAY_ADDITIONAL_PARAMETERS';
    const ECOMMPAY_IS_POPUP = 'ECOMMPAY_IS_POPUP';
    const ECOMMPAY_TITLE = 'ECOMMPAY_TITLE';
    const ECOMMPAY_DESCRIPTION = 'ECOMMPAY_DESCRIPTION';

    /**
     * @var array
     */
    protected $pluginOptions;

    /**
     * @var array
     */
    public $options;

    /**
     * @var Signer
     */
    public $signer;

    /**
     * @var EcpRefundProcessor
     */
    private $refund_processor;

    public function __construct()
    {
        $this->name = self::PLUGIN_NAME;
        $this->displayName = $this->l('Ecommpay payments');
        $this->description = $this->l('Online payment via Ecommpay');
        $this->author = 'Ecommpay';
        $this->version = '1.0.2';
        $this->tab = 'payments_gateways';
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        $this->_constructOptions();
        $this->_constructWarning();
        parent::__construct();

        /* Backward compatibility */
        if (_PS_VERSION_ < 1.5) {
            require(_PS_MODULE_DIR_ . $this->name . '/backward_compatibility/backward.php');
        }

        $this->signer = new Signer(
            $this->options[self::ECOMMPAY_IS_TEST],
            $this->options[self::ECOMMPAY_PROJECT_ID],
            $this->options[self::ECOMMPAY_SECRET_KEY],
            $this->options[self::ECOMMPAY_PAYMENT_PAGE_LANGUAGE],
            $this->options[self::ECOMMPAY_PAYMENT_PAGE_CURRENCY],
            $this->options[self::ECOMMPAY_ADDITIONAL_PARAMETERS],
            $this->context->link->getModuleLink($this->name, 'PLACEHOLDER')
        );

        require_once('common/EcpRefundProcessor.php');
        require_once('classes/EcpOrderSlip.php');
        $this->refund_processor = new EcpRefundProcessor(
            $this->options[self::ECOMMPAY_IS_TEST] ? Signer::ECOMMPAY_TEST_PROJECT_ID : (int)$this->options[self::ECOMMPAY_PROJECT_ID],
            $this->options[self::ECOMMPAY_IS_TEST] ? Signer::ECOMMPAY_TEST_SECRET_KEY : $this->options[self::ECOMMPAY_SECRET_KEY],
            $this->options[self::ECOMMPAY_IS_TEST] ? Signer::CMS_PREFIX : ''
        );
    }

    protected function _constructOptions()
    {
        $this->pluginOptions = array(
            self::ECOMMPAY_IS_TEST,
            self::ECOMMPAY_PAYMENT_PAGE_CURRENCY,
            self::ECOMMPAY_PAYMENT_PAGE_LANGUAGE,
            self::ECOMMPAY_PROJECT_ID,
            self::ECOMMPAY_SECRET_KEY,
            self::ECOMMPAY_ADDITIONAL_PARAMETERS,
            self::ECOMMPAY_IS_POPUP,
            self::ECOMMPAY_TITLE,
            self::ECOMMPAY_DESCRIPTION
        );

        $config = Configuration::getMultiple($this->pluginOptions);

        $this->options = array();
        foreach ($this->pluginOptions as $key) {
            $this->options[$key] = isset($config[$key]) ? $config[$key] : '';
        }
    }

    protected function _constructWarning()
    {
        if ($this->options[self::ECOMMPAY_IS_TEST]) {
            return;
        }

        $warnings = array();

        if (empty($this->options[self::ECOMMPAY_PROJECT_ID])) {
            $warnings[] = $this->l('Configure project ID');
        }

        if (empty($this->options[self::ECOMMPAY_SECRET_KEY])) {
            $warnings[] = $this->l('Configure secret key');
        }

        if (!empty($warnings)) {
            $this->warning = implode(', ', $warnings);
        }
    }

    public function install()
    {
        $ecpOrderState = new EcpOrderStates($this->name);
        $ecpOrderState->addOrderStates();

        Configuration::updateValue(self::ECOMMPAY_IS_TEST, 1);
        Configuration::updateValue(self::ECOMMPAY_IS_POPUP, 0);
        Configuration::updateValue(self::ECOMMPAY_PAYMENT_PAGE_CURRENCY, 'USD');
        Configuration::updateValue(self::ECOMMPAY_PAYMENT_PAGE_LANGUAGE, 'en');
        Configuration::updateValue(self::ECOMMPAY_TITLE, 'Payment via Ecommpay');
        Configuration::updateValue(
            self::ECOMMPAY_DESCRIPTION,
            'You will be redirected to Ecommpay payment page. All data you enter in that page are secured'
        );

        return parent::install() &&
            $this->installSQL() &&
            $this->registerHook('header') &&
            $this->registerHook('payment') &&
            $this->registerHook('actionOrderSlipAdd') &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->register17Hooks();
    }

    protected function register17Hooks()
    {
        if (_PS_VERSION_ >= '1.7') {
            return
                $this->registerHook('paymentOptions') &&
                $this->registerHook('displayHeader');
        }
        return true;
    }


    /**
     * @return boolean if install was successfull
     */
    private function installSQL()
    {
        $sql = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . _DB_NAME_ ."' AND TABLE_NAME = '" ._DB_PREFIX_ . "order_slip' AND column_name = 'external_id';";
        try {
            if (empty(DB::getInstance()->getRow($sql))) {
                $sql = "ALTER TABLE " . _DB_PREFIX_ . "order_slip ADD COLUMN external_id VARCHAR(255) DEFAULT NULL COMMENT 'Ecommpay refund id'";
                if (!DB::getInstance()->execute($sql)) {
                    return false;
                }
            }
        } catch (PrestaShopDatabaseException $e) {
            return false;
        }

        $sql = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . _DB_NAME_ ."' AND TABLE_NAME = '" ._DB_PREFIX_ . "order_slip' AND column_name = 'customer_thread_id';";
        try {
            if (empty(DB::getInstance()->getRow($sql))) {
                $sql = "ALTER TABLE " . _DB_PREFIX_ . "order_slip ADD COLUMN customer_thread_id INT UNSIGNED DEFAULT NULL";
                if (!DB::getInstance()->execute($sql)) {
                    return false;
                }
            }
        } catch (PrestaShopDatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return boolean if install was successfull
     */
    private function uninstallSQL()
    {
        $sql = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . _DB_NAME_ ."' AND TABLE_NAME = '" ._DB_PREFIX_ . "order_slip' AND column_name = 'external_id';";
        try {
            if (!empty(DB::getInstance()->getRow($sql))) {
                $sql = "ALTER TABLE " . _DB_PREFIX_ . "order_slip DROP external_id";
                if (!DB::getInstance()->execute($sql)) {
                    return false;
                }
            }
        } catch (PrestaShopDatabaseException $e) {
            return false;
        }

        $sql = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . _DB_NAME_ ."' AND TABLE_NAME = '" ._DB_PREFIX_ . "order_slip' AND column_name = 'customer_thread_id';";
        try {
            if (!empty(DB::getInstance()->getRow($sql))) {
                $sql = "ALTER TABLE " . _DB_PREFIX_ . "order_slip DROP customer_thread_id";
                if (!DB::getInstance()->execute($sql)) {
                    return false;
                }
            }
        } catch (PrestaShopDatabaseException $e) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::ECOMMPAY_IS_TEST);
        Configuration::deleteByName(self::ECOMMPAY_PROJECT_ID);
        Configuration::deleteByName(self::ECOMMPAY_SECRET_KEY);
        Configuration::deleteByName(self::ECOMMPAY_PAYMENT_PAGE_LANGUAGE);
        Configuration::deleteByName(self::ECOMMPAY_PAYMENT_PAGE_CURRENCY);
        Configuration::deleteByName(self::ECOMMPAY_ADDITIONAL_PARAMETERS);
        Configuration::deleteByName(self::ECOMMPAY_IS_POPUP);
        Configuration::deleteByName(self::ECOMMPAY_TITLE);
        Configuration::deleteByName(self::ECOMMPAY_DESCRIPTION);
        return $this->uninstallSQL() && parent::uninstall();
    }

    private function copyOrderStateImage($orderState, $image)
    {
        $imagePath = dirname(dirname(dirname(__FILE__))) . '/img/os/' . $image;
        if (file_exists($imagePath)) {
            @copy($imagePath, dirname(dirname(dirname(__FILE__))) . '/img/os/' . $orderState->id . '.gif');
        }
    }

    public function hookPayment($params)
    {
        $paymentUrl = $this->context->link->getModuleLink($this->name, 'payment');
        $paymentTitle = Configuration::get(self::ECOMMPAY_TITLE);
        $paymentDescription = Configuration::get(self::ECOMMPAY_DESCRIPTION);
        $this->context->smarty->assign(compact('paymentUrl', 'paymentTitle', 'paymentDescription'));
        return $this->display(__FILE__, 'views/templates/front/payment.tpl');
    }

    public function hookPaymentOptions()
    {
        if (!$this->active) {
            return;
        }

        $newOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $newOption->setCallToActionText(Configuration::get(self::ECOMMPAY_TITLE))
            ->setInputs(
                array(
                    array(
                        'name' => 'confirm',
                        'type' => 'hidden',
                        'value' => 1
                    )

                )
            )
            ->setAdditionalInformation(Configuration::get(self::ECOMMPAY_DESCRIPTION))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment'));
        return [$newOption];
    }

    public function hookHeader()
    {
        $basePath = __DIR__ . '/';
        $add15Css = false;
        if (_PS_VERSION_ <= 1.5) {
            $basePath = $this->_path;
            $add15Css = 'css/payment15.css';
        }

        $cssPath = $basePath . 'css/payment.css';
        $this->context->controller->addCSS($cssPath, 'all');

        if ($add15Css !== false) {
            $cssPath = $basePath . $add15Css;
            $this->context->controller->addCSS($cssPath, 'all');
        }

        if (Configuration::get(self::ECOMMPAY_IS_POPUP)) {
            $jsPath = $basePath . 'js/payment.js';
            $this->context->controller->addJS($jsPath);
        }

        Media::addJsDef([
            'ecpHost' => Signer::getPaymentPageHost(),
            'paymentUrlWithConfirm' => EcpHelper::getPaymentUrlWithConfirm(),
        ]);
    }

    public function hookDisplayHeader()
    {
        Media::addJsDef([
            'ecpHost' => Signer::getPaymentPageHost(),
            'paymentUrlWithConfirm' => EcpHelper::getPaymentUrlWithConfirm(),
        ]);
        if (Configuration::get(self::ECOMMPAY_IS_POPUP)) {
            $this->context->controller->addJS($this->_path . 'js/payment.js', false);
        }
    }

    public function hookActionOrderSlipAdd($params)
    {
        if (!Tools::isSubmit('doPartialRefundEcommpay')) {
            return;
        }

        /** @var Order $order */
        $order = $params['order'];

        $slip = $this->getLastSlipForOrder($order);

        $amount = 0;
        foreach ($params['productList'] as $product) {
            $amount += $product['amount'];
        }
        if (Tools::getValue('partialRefundShippingCost')) {
            $amount += Tools::getValue('partialRefundShippingCost');
        }

        $currency = new Currency($order->id_currency);

        try {
            $ecp_refund_result = $this->refund_processor->processRefund(
                (string)$params['order']->id,
                $amount,
                $currency->iso_code
            );
            if ($ecp_refund_result->getRefundExternalId() === null) {
                $this->saveOrderMessage($order->id, 'Request was declined by gateway.');
            }

            $thread_id = $this->createOrderThread($order->id);

            $now = new DateTime();
            $message = 'Money-back request #' . $slip->id . ' was sent at ' . $now->format('d.m.Y H:i:s');
            $this->saveOrderMessage($order->id, $message, $thread_id);

            $slip->external_id = $ecp_refund_result->getRefundExternalId();
            $slip->customer_thread_id = $thread_id;
            $slip->save();
        } catch (Exception $e) {
            $this->saveOrderMessage($order->id, 'Request was not correctly processed by gateway.');
        }
    }

    /**
     * @param int $order_id
     * @param string $message
     * @param int|null $thread_id
     * @return CustomerMessage
     */
    private function saveOrderMessage($order_id, $message, $thread_id = null)
    {
        try {
            if ($thread_id === null) {
                $thread_id = $this->createOrderThread($order_id);
            }
            $order_message = new CustomerMessage();
            $order_message->id_customer_thread = $thread_id;
            $order_message->private = 1;
            $order_message->id_employee = $this->context->employee->id;
            $order_message->message = $message;
            $order_message->save();
        } catch (PrestaShopException $e) {
            return null;
        }
        return $order_message;
    }

    /**
     * @param $id_order
     * @return int
     * @throws PrestaShopException
     */
    private function createOrderThread($id_order)
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
     * @param string $data
     * @return bool
     * @throws Exception
     */
    public function processRefundCallback($data)
    {
        try {
            $callback = $this->refund_processor->processCallback($data);
        } catch (EcpOperationException $ex) {
            return  false;
        }

        /** @var EcpOrderSlip|null $slip */
        $slip = EcpOrderSlip::findByExternalId($callback->getRequestId());
        if (!$slip) {
            $order = new Order((int)$callback->getOrderId());
            $slip = OrderSlip::create($order, [], false, -$callback->getSumInitialAmount());
            $slip = $this->getLastSlipForOrder($order);
            $slip->external_id = $callback->getRequestId();
            $slip->customer_thread_id = $this->createOrderThread($order->id);
            $slip->save();
        }

        $order = new Order($slip->id_order);

        $now = new \DateTime();

        if ($callback->isSuccess()) {
            $message = 'Money-back request #' . $slip->id . ' was successfully processed at ' .
                $now->format('d.m.Y H:i:s');
        } else {
            $message = 'Money-back request #' . $slip->id . ' was declined at ' .
                $now->format('d.m.Y H:i:s') .
                '. Reason: ' . $callback->getDescription();
            $this->saveOrderMessage($order->id, $message, $slip->customer_thread_id);
            die('Ok');
        }

        $this->saveOrderMessage($order->id, $message, $slip->customer_thread_id);

        if (in_array($callback->getPaymentStatus(), ['partially refunded', 'partially reversed'])) {
            $this->setOrderState($order, Configuration::get('PS_OS_ECOMMPAY_PT_REFUNDED'));
            $order->save();
        }

        if (in_array($callback->getPaymentStatus(), ['refunded', 'reversal'])) {
            $this->setOrderState($order, Configuration::get('PS_OS_ECOMMPAY_REFUNDED'));
            $order->save();
        }


        $slip->external_id = '+' . $slip->external_id;
        $slip->save();

        return true;
    }

    private function getLastSlipForOrder($order) {    
        $maxId = 0;
        foreach ($order->getOrderSlipsCollection() as $slip){
            if ($slip->id > $maxId) {
                $maxId = $slip->id;
            }
        }
        if ($maxId == 0) {
            $this->saveOrderMessage($order->id, 'Unable to send refund request: an order slip was not created properly.');
            return;
        }
        return new EcpOrderSlip($maxId);;
    }

    public function hookActionAdminControllerSetMedia()
    {
        if (Tools::getValue('controller') == "AdminOrders" && Tools::getValue('id_order')) {
            Media::addJsDefL('chb_ecommpay_refund', $this->l('Refund via Ecommpay'));
            $this->context->controller->addJS($this->_path.'/js/bo_order.js');
        }
    }


    public function getContent()
    {
        if (Tools::getValue('ecommpay_updateSettings')) {
            foreach ($this->pluginOptions as $key) {
                Configuration::updateValue($key, Tools::getValue($key));
            }
            $this->_constructOptions();
        }

        $availableCurrencies = Currency::getCurrencies(true);
        $availableLanguages = array(
            array('code' => 'default', 'name' => 'By customer browser'),
            array('code' => 'en', 'name' => 'English'),
            array('code' => 'fr', 'name' => 'France'),
            array('code' => 'it', 'name' => 'Italian'),
            array('code' => 'de', 'name' => 'Germany'),
            array('code' => 'es', 'name' => 'Spanish'),
            array('code' => 'ru', 'name' => 'Russian'),
            array('code' => 'zh', 'name' => 'Chinese'),
        );

        $callbackUrl = EcpHelper::getCallbackUrl();
        $psVersion = _PS_VERSION_;

        $this->context->smarty->assign(
            array_merge($this->options, compact('availableCurrencies', 'availableLanguages', 'callbackUrl', 'psVersion'))
        );
        return $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }

    public function isInTestMode()
    {
        return $this->options[self::ECOMMPAY_IS_TEST];
    }

    public function loadOrderFromCart(Cart $cart)
    {
        $orderId = Order::getIdByCartId($cart->id);
        if (!$orderId) {
            return null;
        }

        $order = new Order($orderId);

        if (!Validate::isLoadedObject($order)) {
            return null;
        }

        return $order;
    }

    public function addTransactionsInfo($order, array $transactionData)
    {
        if (_PS_VERSION_ >= 1.5) {
            $payments = $order->getOrderPaymentCollection();
            if ($payments->count() == 0) {
                return;
            }

            $cartId = Cart::getCartIdByOrderId($order->id);

            foreach ($payments as $payment) {
                if ($payment->transaction_id != $cartId) {
                    continue;
                }

                $fields = [
                    'card_number' => 'number',
                    'card_holder' => 'card_holder',
                    'card_brand' => 'type',
                    'payment_method' => 'payment_method',
                ];

                $payment->amount = number_format($transactionData['payment']['sum']['amount'] / 100, 2, '.', '');

                foreach ($fields as $localKey => $foreignKey) {
                    if (!isset($transactionData['account'][$foreignKey])) {
                        continue;
                    }
                    $payment->{$localKey} = $transactionData['account'][$foreignKey];
                }

                $payment->save();

                break;
            }
        }
    }

    public function setOrderState($order, $stateId)
    {
        $order->setCurrentState($stateId);
    }

    public function getHistoryLink()
    {
        return $this->context->link->getPageLink('history');
    }
}

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
 * obtain it through the world-wide-web, please email license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2015 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use Ecommpay\EcommpayConfig;
use Ecommpay\EcpCallbackProcessor;
use Ecommpay\EcpOrderManager;
use Ecommpay\EcpOrderStates;
use Ecommpay\EcpRequestProcessor;
use Ecommpay\EcpHelper;
use Ecommpay\EcpPayment;
use Ecommpay\EcpPaymentService;
use Ecommpay\EcpLogger;
use Ecommpay\exceptions\EcpSqlExecutionException;
use ecommpay\SignatureHandler;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Ecommpay extends PaymentModule
{
    public const PLUGIN_NAME = 'ecommpay';
    protected $pluginOptions;

    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $version;
    /**
     * @var array
     */
    public $options;
    /**
     * @var SignatureHandler
     */
    public $signer;
    /**
     * @var EcpPaymentService
     */
    public $paymentService;
    private $callbackProcessor;

    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = self::PLUGIN_NAME;
        $this->tab = 'payments_gateways';
        $this->version = '2.0.0';
        $this->author = 'Ecommpay';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '8.2.99'];

        $this->displayName = $this->l('Ecommpay payments');
        $this->description = $this->l('Online payment via Ecommpay');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->bootstrap = true;

        $this->_constructOptions();
        $this->_constructWarning();
        parent::__construct();

        /* Backward compatibility */
        if (_PS_VERSION_ < 1.5) {
            require(_PS_MODULE_DIR_ . $this->name . '/backward_compatibility/backward.php');
        }

        $this->signer = new SignatureHandler($this->options[EcommpayConfig::ECOMMPAY_SECRET_KEY] ?: '');
        $this->paymentService = new EcpPaymentService((int) $this->options[EcommpayConfig::ECOMMPAY_PROJECT_ID]);
        $this->orderManager = new EcpOrderManager($this->context);
        $this->callbackProcessor = new EcpCallbackProcessor($this->signer, $this->orderManager);
        $this->requestProcessor = new EcpRequestProcessor($this->signer, $this->paymentService);
    }

    /**
     * @throws PrestaShopException
     */
    protected function _constructOptions(): void
    {
        $this->pluginOptions = EcommpayConfig::SETTINGS_FIELDS;

        $config = Configuration::getMultiple(array_keys($this->pluginOptions));

        $this->options = [];

        foreach ($this->pluginOptions as $key => $defaultValue) {
            $this->options[$key] = $config[$key] ?? $defaultValue ?? '';
        }
    }

    protected function _constructWarning(): void
    {
        $warnings = array();

        if (empty($this->options[EcommpayConfig::ECOMMPAY_PROJECT_ID])) {
            $warnings[] = $this->l('Configure project ID');
        }

        if (empty($this->options[EcommpayConfig::ECOMMPAY_SECRET_KEY])) {
            $warnings[] = $this->l('Configure secret key');
        }

        if (!empty($warnings)) {
            $this->warning = implode(', ', $warnings);
        }
    }

    public function install(): bool
    {
        try {
            $ecpOrderState = new EcpOrderStates($this->name);
            $ecpOrderState->addOrderStates();

            foreach ($this->pluginOptions as $option => $defaultValue) {
                Configuration::updateValue($option, $defaultValue);
            }

            return parent::install()
                && $this->installSQL()
                && $this->registerHook('actionOrderSlipAdd')
                && $this->registerHook('actionAdminControllerSetMedia')
                && $this->register17Hooks()
                && $this->installTab();
        } catch (Throwable $e) {
            file_put_contents(__DIR__ . '/ecommpay-dev.log', $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * @throws PrestaShopException
     */
    private function installTab(): bool
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminEcommpaySettings';
        $tab->id_parent = $this->getTabId('AdminParentModules');
        $tab->module = $this->name;

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Ecommpay Settings';
        }

        return $tab->add();
    }

    /**
     * @throws PrestaShopException
     */
    private function getTabId($className): int
    {
        return (int)Db::getInstance()->getValue(
            'SELECT id_tab FROM ' . _DB_PREFIX_ . 'tab WHERE class_name = "' . pSQL($className) . '"'
        );
    }

    /**
     * @throws PrestaShopException
     */
    private function uninstallTab(): bool
    {
        $id_tab = $this->getTabId('AdminEcommpaySettings');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    protected function register17Hooks():   bool
    {
        if (_PS_VERSION_ >= '1.7') {
            return
                $this->registerHook('paymentOptions') &&
                $this->registerHook('displayHeader');
        }
        return true;
    }

    /**
     * @return boolean if install was successfully
     */
    private function installSQL(): bool
    {
        try {
            $this->addDatabaseColumn("order_slip", "customer_thread_id", "INT UNSIGNED DEFAULT NULL");
            $this->addDatabaseColumn("order_slip", "external_id", "VARCHAR(255) DEFAULT NULL COMMENT 'Ecommpay refund id'");
            $this->addDatabaseColumn("orders", "ecp_payment_id", "VARCHAR(255) DEFAULT NULL COMMENT 'Ecommpay payment id'");
        } catch (PrestaShopDatabaseException | EcpSqlExecutionException $e) {
            EcpLogger::log('Error occurred while inserting SQL columns. Message: ' . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @return boolean if install was successfully
     */
    private function uninstallSQL(): bool
    {
        try {
            $this->removeDatabaseColumn("order_slip", "external_id");
            $this->removeDatabaseColumn("order_slip", "customer_thread_id");
            $this->removeDatabaseColumn("orders", "ecp_payment_id");
        } catch (PrestaShopDatabaseException | EcpSqlExecutionException $e) {
            EcpLogger::log('Error occured while removing SQL columns. Message: ' . $e->getMessage());
            return false;
        }
        return true;
    }

    private function executeSqlQuery(string $sql): void
    {
        if (!$result = DB::getInstance()->execute($sql)) {
            throw new EcpSqlExecutionException(
                sprintf('Execution %s ended with result %s', $sql, $result)
            );
        }
    }
    private function addDatabaseColumn(string $tableName, string $columnName, string $columnType): void
    {
        $sql = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . _DB_NAME_
            . "' AND TABLE_NAME = '" . _DB_PREFIX_ . $tableName . "' AND column_name = '" . $columnName . "';";
        if (!empty(DB::getInstance()->getRow($sql))) {
            return;
        }
        $sql = "ALTER TABLE " . _DB_PREFIX_ . $tableName . " ADD COLUMN " . $columnName . " " . $columnType;
        $this->executeSqlQuery($sql);
    }

    private function removeDatabaseColumn(string $tableName, string $columnName): void
    {
        $sql = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . _DB_NAME_
            . "' AND TABLE_NAME = '" . _DB_PREFIX_ . $tableName . "' AND column_name = '" . $columnName . "';";
        if (empty(DB::getInstance()->getRow($sql))) {
            return;
        }
        /** @noinspection SqlResolve */
        $sql = "ALTER TABLE " . _DB_PREFIX_ . $tableName . " DROP " . $columnName;
        $this->executeSqlQuery($sql);
    }

    /**
     * @throws PrestaShopException
     */
    public function uninstall(): bool
    {
        foreach (array_keys($this->pluginOptions) as $option) {
            Configuration::deleteByName($option);
        }

        return $this->uninstallSQL() && $this->uninstallTab() && parent::uninstall();
    }

    private function copyOrderStateImage($orderState, $image): void
    {
        $imagePath = dirname(__FILE__, 3) . '/img/os/' . $image;
        if (file_exists($imagePath)) {
            @copy($imagePath, dirname(__FILE__, 3) . '/img/os/' . $orderState->id . '.gif');
        }
    }

    /**
     * Create payment option with common settings
     * @param string $title Configuration key for title
     * @param string $description Configuration key for description
     * @param string $paymentMethod Controller action name
     * @return PaymentOption
     * @throws SmartyException
     */
    private function createPaymentOption(string $title, string $description, string $paymentMethod): PaymentOption
    {
        $option = new PaymentOption();
        $option->setCallToActionText($title)
            ->setAction($this->context->link->getModuleLink($this->name, $paymentMethod));

        $this->context->smarty->assign([
            'paymentCardDisplayMode' => EcommpayConfig::getCardDisplayMode(),
            'paymentMethodTitle' => $title,
            'paymentMethodDescription' => $description,
            'paymentMethodCode' => $paymentMethod
        ]);

        $option->setAdditionalInformation(
            $this->context->smarty->fetch('module:ecommpay/views/templates/front/payment.tpl')
        );

        return $option;
    }

    public function hookPaymentOptions(): array
    {
        if (!$this->active) {
            EcpLogger::log('Module is not active');
            return [];
        }

        $paymentOptions = [];

        // Card payment option
        if (Configuration::get(EcommpayConfig::ECOMMPAY_CARD_ENABLED)) {
            $paymentOptions[] = $this->createPaymentOption(
                Configuration::get(EcommpayConfig::ECOMMPAY_CARD_TITLE),
                Configuration::get(EcommpayConfig::ECOMMPAY_CARD_DESCRIPTION),
                EcpPayment::CARD_PAYMENT_METHOD
            );
        }

        // Apple Pay option
        if (Configuration::get(EcommpayConfig::ECOMMPAY_APPLE_PAY_ENABLED)) {
            $paymentOptions[] = $this->createPaymentOption(
                Configuration::get(EcommpayConfig::ECOMMPAY_APPLE_PAY_TITLE),
                Configuration::get(EcommpayConfig::ECOMMPAY_APPLE_PAY_DESCRIPTION),
                EcpPayment::APPLEPAY_PAYMENT_METHOD
            );
        }

        // Google Pay option
        if (Configuration::get(EcommpayConfig::ECOMMPAY_GOOGLE_PAY_ENABLED)) {
            $paymentOptions[] = $this->createPaymentOption(
                Configuration::get(EcommpayConfig::ECOMMPAY_GOOGLE_PAY_TITLE),
                Configuration::get(EcommpayConfig::ECOMMPAY_GOOGLE_PAY_DESCRIPTION),
                EcpPayment::GOOGLEPAY_PAYMENT_METHOD
            );
        }

        // More payment methods option
        if (Configuration::get(EcommpayConfig::ECOMMPAY_MORE_METHODS_ENABLED)) {
            $paymentOptions[] = $this->createPaymentOption(
                Configuration::get(EcommpayConfig::ECOMMPAY_MORE_METHODS_TITLE),
                Configuration::get(EcommpayConfig::ECOMMPAY_MORE_METHODS_DESCRIPTION),
                EcpPayment::MORE_METHODS_PAYMENT_METHOD
            );
        }

        return $paymentOptions;
    }

    public function hookDisplayHeader()
    {
        if ($this->context->controller instanceof OrderController) {
            $this->context->controller->addCSS('modules/ecommpay/views/css/payment.css');
            $this->context->controller->addJS('modules/ecommpay/views/js/payment.js');

            Media::addJsDef([
                'ECOMMPAY_PAYMENT_URL' => EcpHelper::getPaymentUrl(),
                'ECOMMPAY_HOST' => EcpPaymentService::getPaymentPageBaseUrl(),
                'ECOMMPAY_PAYMENT_INFO_URL' => $this->context->link->getModuleLink('ecommpay', 'paymentinfo', [], true),
                'ECOMMPAY_SUCCESS_URL' => $this->context->link->getModuleLink('ecommpay', 'successpayment', [], true),
                'ECOMMPAY_FAIL_URL' => $this->context->link->getModuleLink('ecommpay', 'failpayment', [], true),
                'ECOMMPAY_CARD_DISPLAY_MODE' => EcommpayConfig::getCardDisplayMode(),
                'ECOMMPAY_CHECK_CART_URL' => $this->context->link->getModuleLink('ecommpay', 'checkcart', [], true),
            ]);
        }
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function hookActionOrderSlipAdd($params): void
    {
        if (!Tools::isSubmit('doPartialRefundEcommpay')) {
            return;
        }

        /** @var Order $order */
        $order = $params['order'];

        $slip = $this->orderManager->getLastSlipForOrder($order);

        $amount = 0;
        foreach ($params['productList'] as $product) {
            $amount += $product['amount'];
        }
        if (Tools::getValue('partialRefundShippingCost')) {
            $amount += Tools::getValue('partialRefundShippingCost');
        }

        $currency = new Currency($order->id_currency);

        try {
            $ecp_refund_result = $this->requestProcessor->sendPostRequest(
                $order->id,
                $amount,
                $currency->iso_code
            );
            if ($ecp_refund_result->getRefundExternalId() === null) {
                $this->orderManager->saveOrderMessage($order->id, 'Request was declined by gateway.');
            }

            $thread_id = $this->orderManager->createOrderThread($order->id);

            $now = new DateTime();
            $message = 'Money-back request #' . $slip->id . ' was sent at ' . $now->format('d.m.Y H:i:s');
            $this->orderManager->saveOrderMessage($order->id, $message, $thread_id);

            $slip->external_id = $ecp_refund_result->getRefundExternalId();
            $slip->customer_thread_id = $thread_id;
            $slip->save();

            //do not remove it to prevent an callback error Order->payment is empty
            $order->setCurrentState(Configuration::get(EcpOrderStates::PENDING_STATE));
        } catch (Exception $e) {
            $this->orderManager->saveOrderMessage($order->id, 'Request was not correctly processed by gateway. ' . $e->getMessage());
        }
    }

    public function processCallback(array $data): void
    {
        $this->callbackProcessor->processCallback($data);
    }

    public function hookActionAdminControllerSetMedia(): void
    {
        if (Tools::getValue('controller') == "AdminOrders" && Tools::getValue('id_order')) {
            Media::addJsDefL('chb_ecommpay_refund', $this->l('Refund via Ecommpay'));
            $this->context->controller->addJS($this->_path . '/views/js/bo_order.js');
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function loadOrderFromCart(Cart $cart): ?Order
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

    public function getHistoryLink(): string
    {
        return $this->context->link->getPageLink('history');
    }

    public function getContent(): string
    {
        return Tools::redirectAdmin($this->context->link->getAdminLink('AdminEcommpaySettings'));
    }
}

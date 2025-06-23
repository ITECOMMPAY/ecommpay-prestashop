<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use OrderState;
use PrestaShopDatabaseException;
use PrestaShopException;

class EcpOrderStates
{
    public const PENDING_STATE = 'PS_OS_ECOMMPAY_PENDING';
    public const APPROVED_STATE = 'PS_OS_ECOMMPAY_APPROVED';
    public const DECLINED_STATE = 'PS_OS_ECOMMPAY_DECLINED';
    public const REFUNDED_STATE = 'PS_OS_ECOMMPAY_REFUNDED';
    public const PARTIALLY_REFUNDED_STATE = 'PS_OS_ECOMMPAY_PT_REFUNDED';

    private const ORDER_STATE_NAME_PENDING = 'Ecommpay: Pending';
    private const ORDER_STATE_NAME_APPROVED = 'Ecommpay: Approved';
    private const ORDER_STATE_NAME_DECLINED = 'Ecommpay: Declined';
    private const ORDER_STATE_NAME_REFUND = 'Ecommpay: Refund';
    private const ORDER_STATE_NAME_PARTIALLY_REFUND = 'Ecommpay: Partially refunded';

    private const PS_LANG_DEFAULT = 'PS_LANG_DEFAULT';

    private const COLOR_LIGHT_BLUE = '#4169E1';
    private const COLOR_GREEN = '#32CD32';
    private const COLOR_DARK_RED = '#DC143C';
    private const COLOR_RED = '#ec2e15';

    private const TEMPLATE_PREPARATION = 'preparation';
    private const TEMPLATE_PAYMENT = 'payment';
    private const TEMPLATE_PAYMENT_ERROR = 'payment_error';
    private const TEMPLATE_REFUND = 'refund';

    private const IMAGE_NAME_CROSS_CANCEL = '8.gif';
    private const IMAGE_NAME_PENDING = '9.gif';
    private const IMAGE_NAME_COINS = '10.gif';

    private const IMAGES_RELATIVE_PATH = '/img/os/';
    private const IMAGE_FORMAT_FILE = '.gif';

    /**
     * @var string
     */
    private $moduleName;

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function addOrderStates(): void
    {
        $this->createPendingState();
        $this->createApprovedState();
        $this->createDeclinedState();
        $this->createRefundedState();
        $this->createPartiallyRefundedState();
    }

    private function copyOrderStateImage(OrderState $orderState, string $imageName): void
    {
        $imageDirPath = dirname(__FILE__, 3) . self::IMAGES_RELATIVE_PATH;
        $imagePath = $imageDirPath . $imageName;
        if (file_exists($imagePath)) {
            @copy($imagePath, $imageDirPath . $orderState->id . self::IMAGE_FORMAT_FILE);
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createPendingState(): void
    {
        if (Configuration::get(self::PENDING_STATE) > 0) {
            return;
        }

        $OrderState = new OrderState(null, Configuration::get(self::PS_LANG_DEFAULT));
        $OrderState->name = self::ORDER_STATE_NAME_PENDING;
        $OrderState->invoice = false;
        $OrderState->send_email = true;
        $OrderState->module_name = $this->moduleName;
        $OrderState->color = self::COLOR_LIGHT_BLUE;
        $OrderState->unremovable = true;
        $OrderState->hidden = false;
        $OrderState->logable = true;
        $OrderState->delivery = false;
        $OrderState->shipped = false;
        $OrderState->paid = false;
        $OrderState->deleted = false;
        $OrderState->template = self::TEMPLATE_PREPARATION;
        $OrderState->add();

        Configuration::updateValue(self::PENDING_STATE, $OrderState->id);
        $this->copyOrderStateImage($OrderState, self::IMAGE_NAME_PENDING);
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private function createApprovedState(): void
    {
        if (Configuration::get(self::APPROVED_STATE) > 0) {
            return;
        }

        $OrderState = new OrderState(null, Configuration::get(self::PS_LANG_DEFAULT));
        $OrderState->name = self::ORDER_STATE_NAME_APPROVED;
        $OrderState->invoice = true;
        $OrderState->send_email = true;
        $OrderState->module_name = $this->moduleName;
        $OrderState->color = self::COLOR_GREEN;
        $OrderState->unremovable = true;
        $OrderState->hidden = false;
        $OrderState->logable = true;
        $OrderState->delivery = false;
        $OrderState->shipped = false;
        $OrderState->paid = false; // avoid duplicate payments
        $OrderState->deleted = false;
        $OrderState->template = self::TEMPLATE_PAYMENT;
        $OrderState->add();

        Configuration::updateValue(self::APPROVED_STATE, $OrderState->id);
        $this->copyOrderStateImage($OrderState, self::IMAGE_NAME_COINS);
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private function createDeclinedState(): void
    {
        if (Configuration::get(self::DECLINED_STATE) > 0) {
            return;
        }

        $OrderState = new OrderState(null, Configuration::get(self::PS_LANG_DEFAULT));
        $OrderState->name = self::ORDER_STATE_NAME_DECLINED;
        $OrderState->invoice = false;
        $OrderState->send_email = true;
        $OrderState->module_name = $this->moduleName;
        $OrderState->color = self::COLOR_DARK_RED;
        $OrderState->unremovable = true;
        $OrderState->hidden = false;
        $OrderState->logable = true;
        $OrderState->delivery = false;
        $OrderState->shipped = false;
        $OrderState->paid = false;
        $OrderState->deleted = false;
        $OrderState->template = self::TEMPLATE_PAYMENT_ERROR;
        $OrderState->add();

        Configuration::updateValue(self::DECLINED_STATE, $OrderState->id);
        $this->copyOrderStateImage($OrderState, self::IMAGE_NAME_CROSS_CANCEL);
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private function createRefundedState(): void
    {
        if (Configuration::get(self::REFUNDED_STATE) > 0) {
            return;
        }

        $OrderState = new OrderState(null, Configuration::get(self::PS_LANG_DEFAULT));
        $OrderState->name = self::ORDER_STATE_NAME_REFUND;
        $OrderState->invoice = false;
        $OrderState->send_email = true;
        $OrderState->module_name = $this->moduleName;
        $OrderState->color = self::COLOR_RED;
        $OrderState->unremovable = true;
        $OrderState->hidden = false;
        $OrderState->logable = true;
        $OrderState->delivery = false;
        $OrderState->shipped = false;
        $OrderState->paid = false;
        $OrderState->deleted = false;
        $OrderState->template = self::TEMPLATE_REFUND;
        $OrderState->add();

        Configuration::updateValue(self::REFUNDED_STATE, $OrderState->id);
        $this->copyOrderStateImage($OrderState, self::IMAGE_NAME_CROSS_CANCEL);
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private function createPartiallyRefundedState(): void
    {
        if (Configuration::get(self::PARTIALLY_REFUNDED_STATE) > 0) {
            return;
        }

        $OrderState = new OrderState(null, Configuration::get(self::PS_LANG_DEFAULT));
        $OrderState->name = self::ORDER_STATE_NAME_PARTIALLY_REFUND;
        $OrderState->invoice = false;
        $OrderState->send_email = true;
        $OrderState->module_name = $this->moduleName;
        $OrderState->color = self::COLOR_RED;
        $OrderState->unremovable = true;
        $OrderState->hidden = false;
        $OrderState->logable = true;
        $OrderState->delivery = false;
        $OrderState->shipped = false;
        $OrderState->paid = false;
        $OrderState->deleted = false;
        $OrderState->template = self::TEMPLATE_REFUND;
        $OrderState->add();

        Configuration::updateValue(self::PARTIALLY_REFUNDED_STATE, $OrderState->id);
        $this->copyOrderStateImage($OrderState, self::IMAGE_NAME_CROSS_CANCEL);
    }
}

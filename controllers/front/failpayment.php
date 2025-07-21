<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Ecommpay\EcpLogger;
use PrestaShopException;

/**
 * @since 1.5.0
 */
class EcommpayFailPaymentModuleFrontController extends ModuleFrontController
{
    /**
     * @throws PrestaShopException
     */
    public function initContent()
    {
        parent::initContent();

        $historyUrl = $this->module->getHistoryLink();

        $this->context->smarty->assign(compact('historyUrl'));

        if (_PS_VERSION_ >= '1.7') {
            $this->setTemplate('module:ecommpay/views/templates/front/payment_fail_17.tpl');
            return;
        }

        $this->setTemplate('payment_fail.tpl');
    }
}

<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use Ecommpay\EcommpayConfig;
use Ecommpay\EcpHelper;
use ModuleAdminController;
use PrestaShopException;
use Tools;

class AdminEcommpaySettingsController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function postProcess(): void
    {
        if (Tools::isSubmit('submitEcommpaySettings')) {
            EcommpayConfig::saveSettings();
            $this->confirmations[] = $this->trans('Settings updated successfully.', [], 'Admin.Notifications.Success');
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function initContent(): void
    {
        parent::initContent();

        $this->context->smarty->assign([
            'link' => $this->context->link,
            'active_tab' => Tools::getValue('tab', 'general_settings'),
            'options' => EcommpayConfig::getSettings(),
            'availableLanguages' => EcommpayConfig::AVAILABLE_LANGUAGES,
            'callbackUrl' => EcpHelper::getCallbackUrl(),
            'psVersion' => _PS_VERSION_,
        ]);

        $this->setTemplate('config.tpl');
    }
}

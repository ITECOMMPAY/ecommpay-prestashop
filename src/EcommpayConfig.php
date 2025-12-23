<?php

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use PrestaShopException;
use Tools;

class EcommpayConfig
{
    public const ECOMMPAY_PROJECT_ID = 'ECOMMPAY_PROJECT_ID';
    public const ECOMMPAY_SECRET_KEY = 'ECOMMPAY_SECRET_KEY';
    public const ECOMMPAY_PAYMENT_PAGE_LANGUAGE = 'ECOMMPAY_PAYMENT_PAGE_LANGUAGE';

    public const ECOMMPAY_CARD_ENABLED = 'ECOMMPAY_CARD_ENABLED';
    public const ECOMMPAY_CARD_TITLE = 'ECOMMPAY_CARD_TITLE';
    public const ECOMMPAY_CARD_DESCRIPTION = 'ECOMMPAY_CARD_DESCRIPTION';
    public const ECOMMPAY_CARD_DISPLAY_MODE = 'ECOMMPAY_CARD_DISPLAY_MODE';

    public const ECOMMPAY_APPLE_PAY_ENABLED = 'ECOMMPAY_APPLE_PAY_ENABLED';
    public const ECOMMPAY_APPLE_PAY_TITLE = 'ECOMMPAY_APPLE_PAY_TITLE';
    public const ECOMMPAY_APPLE_PAY_DESCRIPTION = 'ECOMMPAY_APPLE_PAY_DESCRIPTION';

    public const ECOMMPAY_GOOGLE_PAY_ENABLED = 'ECOMMPAY_GOOGLE_PAY_ENABLED';
    public const ECOMMPAY_GOOGLE_PAY_TITLE = 'ECOMMPAY_GOOGLE_PAY_TITLE';
    public const ECOMMPAY_GOOGLE_PAY_DESCRIPTION = 'ECOMMPAY_GOOGLE_PAY_DESCRIPTION';

    public const ECOMMPAY_MORE_METHODS_ENABLED = 'ECOMMPAY_MORE_METHODS_ENABLED';
    public const ECOMMPAY_MORE_METHODS_TITLE = 'ECOMMPAY_MORE_METHODS_TITLE';
    public const ECOMMPAY_MORE_METHODS_DESCRIPTION = 'ECOMMPAY_MORE_METHODS_DESCRIPTION';
    public const ECOMMPAY_MORE_METHODS_CODE = 'ECOMMPAY_MORE_METHODS_CODE';

    public const LANGUAGE_BY_CUSTOMER_BROWSER = 'browser';

    public const
        EMBEDDED_DISPLAY_MODE = 'embedded',
        POPUP_DISPLAY_MODE = 'popup',
        REDIRECT_DISPLAY_MODE = 'redirect';

    public const SETTINGS_FIELDS = [
        self::ECOMMPAY_PROJECT_ID => '',
        self::ECOMMPAY_SECRET_KEY => '',
        self::ECOMMPAY_PAYMENT_PAGE_LANGUAGE => self::LANGUAGE_BY_CUSTOMER_BROWSER,

        self::ECOMMPAY_CARD_ENABLED => false,
        self::ECOMMPAY_CARD_TITLE => 'Card payments',
        self::ECOMMPAY_CARD_DESCRIPTION => 'Pay securely with your credit or debit card',
        self::ECOMMPAY_CARD_DISPLAY_MODE => 'embedded',

        self::ECOMMPAY_APPLE_PAY_ENABLED => false,
        self::ECOMMPAY_APPLE_PAY_TITLE => 'Apple Pay',
        self::ECOMMPAY_APPLE_PAY_DESCRIPTION => 'Pay quickly and securely with Apple Pay',

        self::ECOMMPAY_GOOGLE_PAY_ENABLED => false,
        self::ECOMMPAY_GOOGLE_PAY_TITLE => 'Google Pay',
        self::ECOMMPAY_GOOGLE_PAY_DESCRIPTION => 'Pay quickly and securely with Google Pay',

        self::ECOMMPAY_MORE_METHODS_ENABLED => false,
        self::ECOMMPAY_MORE_METHODS_TITLE => 'More payment methods',
        self::ECOMMPAY_MORE_METHODS_DESCRIPTION => 'Additional payment methods available',
        self::ECOMMPAY_MORE_METHODS_CODE => '',
    ];

    public const AVAILABLE_LANGUAGES = [
        ['code' => self::LANGUAGE_BY_CUSTOMER_BROWSER, 'name' => 'By customer browser'],
        ['code' => 'en', 'name' => 'English'],
        ['code' => 'fr', 'name' => 'French'],
        ['code' => 'it', 'name' => 'Italian'],
        ['code' => 'de', 'name' => 'German'],
        ['code' => 'es', 'name' => 'Spanish'],
        ['code' => 'ru', 'name' => 'Russian'],
        ['code' => 'zh', 'name' => 'Chinese'],
    ];

    /**
     * @throws PrestaShopException
     */
    public static function getSettings(): array
    {
        $settings = Configuration::getMultiple(array_keys(self::SETTINGS_FIELDS));

        foreach (self::SETTINGS_FIELDS as $field => $defaultValue) {
            if (!isset($settings[$field])) {
                $settings[$field] = $defaultValue;
            }
        }

        return $settings;
    }

    private static function getTabFields(string $tab): array
    {
        switch ($tab) {
            case 'general_settings':
                return [
                    self::ECOMMPAY_PROJECT_ID,
                    self::ECOMMPAY_SECRET_KEY,
                    self::ECOMMPAY_PAYMENT_PAGE_LANGUAGE,
                ];
            case 'card_settings':
                return [
                    self::ECOMMPAY_CARD_ENABLED,
                    self::ECOMMPAY_CARD_TITLE,
                    self::ECOMMPAY_CARD_DESCRIPTION,
                    self::ECOMMPAY_CARD_DISPLAY_MODE,
                ];
            case 'apple_pay':
                return [
                    self::ECOMMPAY_APPLE_PAY_ENABLED,
                    self::ECOMMPAY_APPLE_PAY_TITLE,
                    self::ECOMMPAY_APPLE_PAY_DESCRIPTION,
                ];
            case 'google_pay':
                return [
                    self::ECOMMPAY_GOOGLE_PAY_ENABLED,
                    self::ECOMMPAY_GOOGLE_PAY_TITLE,
                    self::ECOMMPAY_GOOGLE_PAY_DESCRIPTION,
                ];
            case 'more_methods':
                return [
                    self::ECOMMPAY_MORE_METHODS_ENABLED,
                    self::ECOMMPAY_MORE_METHODS_TITLE,
                    self::ECOMMPAY_MORE_METHODS_DESCRIPTION,
                    self::ECOMMPAY_MORE_METHODS_CODE,
                ];
            default:
                return [];
        }
    }

    public static function saveSettings(): void
    {
        $activeTab = Tools::getValue('tab') ?: 'general_settings';
        $tabFields = self::getTabFields($activeTab);

        foreach ($tabFields as $field) {
            $defaultValue = self::SETTINGS_FIELDS[$field];

            if ($field === self::ECOMMPAY_SECRET_KEY && Tools::getValue($field) === '') {
                continue;
            }

            // Special handling for boolean/checkbox fields
            if (is_bool($defaultValue)) {
                $value = (bool)Tools::getValue($field);
            }
            else {
                $value = Tools::getValue($field);
            }

            Configuration::updateValue($field, is_numeric($value) ? (int)$value : $value);
        }
    }

    public static function getCardDisplayMode(): string
    {
        return Configuration::get(self::ECOMMPAY_CARD_DISPLAY_MODE);
    }
}

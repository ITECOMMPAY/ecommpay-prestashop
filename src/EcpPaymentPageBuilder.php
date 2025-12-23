<?php

declare(strict_types=1);

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Address;
use Cart;
use Context;
use Configuration;
use Country;
use Currency;
use Exception;
use Module;
use State;

class EcpPaymentPageBuilder
{
    private const
        REDIRECT_AVAILABLE_ALWAYS = 2,
        REDIRECT_PARENT_WINDOW = 'parent_page';

    private const TARGET_ELEMENT = 'ecommpay-iframe';

    private $module;
    private $context;

    public function __construct(Module $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * @throws Exception
     */
    public function getCommonPaymentParameters(Cart $cart, array $additionalParams = []): array
    {
        $currency = new Currency($cart->id_currency);
        $projectId = (int)Configuration::get(EcommpayConfig::ECOMMPAY_PROJECT_ID);

        $params = [
            'project_id' => $projectId,
            'payment_currency' => $currency->iso_code,
            'payment_amount' => EcpCurrencyConverter::transformCurrency($cart->getOrderTotal(), $currency->iso_code),
            'merchant_callback_url' => EcpHelper::getCallbackUrl(),
            'interface_type' => EcpPaymentService::getInterfaceTypeString(),
            'merchant_success_url' => $this->buildOrderConfirmationUrl($cart),
            'merchant_success_enabled' => $this::REDIRECT_AVAILABLE_ALWAYS,
            'merchant_success_redirect_mode' => $this::REDIRECT_PARENT_WINDOW,
            'merchant_fail_url' => $this->context->link->getModuleLink('ecommpay', 'restorecart'),
            'merchant_fail_enabled' => $this::REDIRECT_AVAILABLE_ALWAYS,
            'merchant_fail_redirect_mode' => $this::REDIRECT_PARENT_WINDOW,
            'merchant_return_enabled' => $this::REDIRECT_AVAILABLE_ALWAYS,
            'merchant_return_redirect_mode' => $this::REDIRECT_PARENT_WINDOW,
        ];

        $params = $this->addBillingAndCustomerInfo($params, $cart);
        $params = $this->addLanguageCode($params);

        $params = array_merge($params, $additionalParams);

        if (!$signer = $this->module->signer) {
            throw new Exception('Signature handler is not configured');
        }

        $params['signature'] = $signer->sign($params);
        $params['_plugin_version'] = $this->module->version;
        $params['_prestashop_version'] = _PS_VERSION_;

        return $params;
    }

    /**
     * Build order confirmation URL
     * For new payments, we don't have order yet, so we'll use successpayment controller
     * which will handle the redirect to order-confirmation after order is created
     * @param Cart $cart
     * @return string
     */
    private function buildOrderConfirmationUrl(Cart $cart): string
    {
        return $this->context->link->getModuleLink(
            'ecommpay',
            'successpayment',
            ['cart_id' => $cart->id]
        );
    }

    private function addBillingAndCustomerInfo(array $params, Cart $cart): array
    {
        $customer = $this->context->customer;
        $address = new Address($cart->id_address_invoice);
        $fullAddress = implode(' ', [$address->address1 ?: '', $address->address2 ?: '']);
        $fullAddress = $fullAddress !== ' ' ? $fullAddress : null;


        $customerAndBillingParams = [
            'customer_id' => $customer->id ?? null,
            'customer_first_name' => $customer->firstname ?? null,
            'customer_last_name' => $customer->lastname ?? null,
            'customer_email' => $customer->email ?? null,
            'customer_phone' => $address->phone ?? null,
            'billing_address' => $fullAddress,
            'billing_postal' => $address->postcode ?? null,
            'billing_city' => $address->city ?? null,
            'billing_country' => Country::getIsoById($address->id_country) ?: null,
            'billing_region' => State::getNameById($address->id_state) ?: null,
            'avs_street_address' => $fullAddress,
            'avs_post_code' => $address->postcode ?? null
        ];

        $filteredCustomerAndBillingParams = array_filter($customerAndBillingParams, function ($value) {
            return $value !== null;
        });

        return array_merge($params, $filteredCustomerAndBillingParams);
    }

    private function addLanguageCode(array $params): array
    {
        $language = Configuration::get(EcommpayConfig::ECOMMPAY_PAYMENT_PAGE_LANGUAGE);
        if ($language !== EcommpayConfig::LANGUAGE_BY_CUSTOMER_BROWSER) {
            $params['language_code'] = $language;
        }
        return $params;
    }

    /**
     * Get embedded mode parameters including redirect URLs
     * @param Cart $cart
     * @return array
     * @throws Exception
     */
    public function getEmbeddedModeParameters(Cart $cart): array
    {
        $additionalParams = [
            'payment_id' => EcpPaymentService::generatePaymentID(),
            'force_payment_method' => EcpPayment::CARD_PAYMENT_METHOD,
            'target_element' => self::TARGET_ELEMENT,
            'payment_methods_options' => json_encode(['additional_data' => ['embedded_mode' => true]]),
            'redirect_success_url' => $this->context->link->getModuleLink(
                'ecommpay',
                'successpayment',
                ['cart_id' => $cart->id]
            ),
            'redirect_success_enabled' => self::REDIRECT_AVAILABLE_ALWAYS,
            'redirect_success_mode' => self::REDIRECT_PARENT_WINDOW,
            'redirect_fail_url' => $this->context->link->getModuleLink('ecommpay', 'restorecart'),
            'redirect_fail_enabled' => self::REDIRECT_AVAILABLE_ALWAYS,
            'redirect_fail_mode' => self::REDIRECT_PARENT_WINDOW
        ];

        return $this->getCommonPaymentParameters($cart, $additionalParams);
    }
}

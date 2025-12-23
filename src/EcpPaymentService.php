<?php

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Address;
use Cart;
use Configuration;
use Currency;
use Customer;
use Tools;

class EcpPaymentService
{
    public const CMS_PREFIX = 'presta';
    private const INTERFACE_TYPE = 17;
    private const PAYMENTPAGE_HOST = 'paymentpage.ecommpay.com';

    private $projectId;

    public function __construct(int $projectId)
    {
        $this->projectId = $projectId;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public static function generatePaymentID(): string
    {
        return 'pt_' . substr(Tools::passwdGen(10), 0, 8);
    }

    /**
     * Return PaymentPage host
     *
     * @return string
     */
    public static function getPaymentPageBaseUrl(): string
    {
        $protocol = getenv('ECP_PROTO') ?: 'https';
        $hostFromEnv = getenv('PAYMENTPAGE_HOST');
        $host = is_string($hostFromEnv) ? $hostFromEnv : self::PAYMENTPAGE_HOST;
        return $protocol . '://' . $host;
    }

    public static function getInterfaceType(): array
    {
        return ['id' => self::INTERFACE_TYPE];
    }

    public static function getInterfaceTypeString(): string
    {
        return json_encode(self::getInterfaceType());
    }
}

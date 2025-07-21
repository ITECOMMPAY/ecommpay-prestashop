<?php

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Db;
use DbQuery;
use Order;

class EcpOrder extends Order
{
    private const LIMIT_MEDIUMTEXT_UTF8_MB4 = 4194303;
    /**
     * @var string
     */
    public $ecp_payment_id;
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'orders',
        'primary' => 'id_order',
        'fields' => [
            'id_address_delivery' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_address_invoice' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_cart' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_carrier' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'current_state' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'secure_key' => ['type' => self::TYPE_STRING, 'validate' => 'isMd5', 'size' => 32],
            'payment' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'module' => ['type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'required' => true, 'size' => 255],
            'recyclable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'gift' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'gift_message' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => self::LIMIT_MEDIUMTEXT_UTF8_MB4],
            'mobile_theme' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'total_discounts' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_discounts_tax_incl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_discounts_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_paid' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'total_paid_tax_incl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_paid_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_paid_real' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'total_products' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'total_products_wt' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'total_shipping' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_shipping_tax_incl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_shipping_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'carrier_tax_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'total_wrapping' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_wrapping_tax_incl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_wrapping_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'round_mode' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'round_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'conversion_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'invoice_number' => ['type' => self::TYPE_INT],
            'delivery_number' => ['type' => self::TYPE_INT],
            'invoice_date' => ['type' => self::TYPE_DATE],
            'delivery_date' => ['type' => self::TYPE_DATE],
            'valid' => ['type' => self::TYPE_BOOL],
            'reference' => ['type' => self::TYPE_STRING, 'size' => 255],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'note' => ['type' => self::TYPE_HTML, 'size' => self::LIMIT_MEDIUMTEXT_UTF8_MB4],
            'ecp_payment_id' => ['type' => self::TYPE_STRING],
        ],
    ];

    public function savePaymentId(string $paymentId): void
    {
        $this->ecp_payment_id = $paymentId;
        $this->update();
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function findByPaymentId(string $paymentId): ?EcpOrder
    {
        $query = (new DbQuery())
            ->select('id_order')
            ->from(self::$definition['table'])
            ->where("ecp_payment_id = '" . pSQL($paymentId) . "'");
        if (!$id = Db::getInstance()->getValue($query)) {
            return null;
        }
        return new EcpOrder($id);
    }
}

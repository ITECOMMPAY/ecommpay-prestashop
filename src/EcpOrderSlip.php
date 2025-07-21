<?php

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Db;
use DbQuery;
use OrderSlip;

class EcpOrderSlip extends OrderSlip
{
    /**
     * @var string
     */
    public $external_id;

    /**
     * @var int
     */
    public $customer_thread_id;

    public static $definition = array(
        'table' => 'order_slip',
        'primary' => 'id_order_slip',
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'conversion_rate' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'total_products_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'total_products_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'total_shipping_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'total_shipping_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'amount' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'shipping_cost' => array('type' => self::TYPE_INT),
            'shipping_cost_amount' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'partial' => array('type' => self::TYPE_INT),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'order_slip_type' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'external_id' => array('type' => self::TYPE_STRING),
            'customer_thread_id' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
        ),
    );

    /**
     * @param string $externalId
     *
     * @return EcpOrderSlip|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function findByExternalId(string $externalId): ?EcpOrderSlip
    {
        $query = (new DbQuery())
            ->select('id_order_slip')
            ->from(self::$definition['table'])
            ->where("external_id = '" . pSQL($externalId) . "'");
        if (!$id = Db::getInstance()->getValue($query)) {
            return null;
        }
        return new EcpOrderSlip($id);
    }
}

<?php

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
            'id_customer'             => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_order'                => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'conversion_rate'         => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'total_products_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'total_products_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'total_shipping_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'total_shipping_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'amount'                  => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'shipping_cost'           => array('type' => self::TYPE_INT),
            'shipping_cost_amount'    => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'partial'                 => array('type' => self::TYPE_INT),
            'date_add'                => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd'                => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'order_slip_type'         => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'external_id'             => array('type' => self::TYPE_STRING),
            'customer_thread_id'      => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
        ),
    );

    /**
     * @param string $external_id
     * @return EcpOrderSlip|null
     */
    public static function findByExternalId($external_id)
    {
        $espaced_external_id = pSQL($external_id);
        $table_name = _DB_PREFIX_ . self::$definition['table'];
        $id = Db::getInstance()->getValue("
            SELECT id_order_slip
            FROM $table_name
            WHERE external_id = '$espaced_external_id'");
        if (!$id) {
            return null;
        }

        return new EcpOrderSlip($id);
    }
}
<?php

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use OrderPayment;

class EcpOrderPayment extends OrderPayment
{
    /**
     * @var ?string
     */
    public $ecp_payment_id;
    public static $definition = [
        'table' => 'order_payment',
        'primary' => 'id_order_payment',
        'fields' => [
            'order_reference' => ['type' => self::TYPE_STRING, 'size' => 255],
            'id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'payment_method' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'conversion_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'transaction_id' => ['type' => self::TYPE_STRING, 'size' => 254],
            'card_number' => ['type' => self::TYPE_STRING, 'size' => 254],
            'card_brand' => ['type' => self::TYPE_STRING, 'size' => 254],
            'card_expiration' => ['type' => self::TYPE_STRING, 'size' => 254],
            'card_holder' => ['type' => self::TYPE_STRING, 'size' => 254],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'allow_null' => true],
            'ecp_payment_id' => ['type' => self::TYPE_STRING, 'allow_null' => true]
        ],
    ];
}

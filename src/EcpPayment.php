<?php

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

class EcpPayment
{
    public const
        PAYMENT_STATUS_SUCCESS = 'success',
        PAYMENT_STATUS_DECLINE = 'decline',
        PAYMENT_STATUS_AWAITING_3DS_RESULT = 'awaiting 3ds result',
        PAYMENT_STATUS_EXTERNAL_ERROR = 'external error',
        PAYMENT_STATUS_INTERNAL_ERROR = 'internal error',
        PAYMENT_STATUS_AWAITING_CUSTOMER = 'awaiting customer',
        PAYMENT_STATUS_EXPIRED = 'expired';

    public const
        CARD_PAYMENT_METHOD = 'card',
        APPLEPAY_PAYMENT_METHOD = 'applepay',
        GOOGLEPAY_PAYMENT_METHOD = 'googlepay',
        MORE_METHODS_PAYMENT_METHOD = 'moremethods';

    public static function getOrderStateByEcpStatus(string $ecpStatus): ?string
    {
        return self::getStatusMap()[$ecpStatus] ?? null;
    }

    private static function getStatusMap(): array
    {
        return [
            self::PAYMENT_STATUS_SUCCESS => EcpOrderStates::APPROVED_STATE,
            self::PAYMENT_STATUS_DECLINE => EcpOrderStates::DECLINED_STATE,
            self::PAYMENT_STATUS_AWAITING_3DS_RESULT => EcpOrderStates::PENDING_STATE,
            self::PAYMENT_STATUS_EXTERNAL_ERROR => EcpOrderStates::DECLINED_STATE,
            self::PAYMENT_STATUS_INTERNAL_ERROR => EcpOrderStates::DECLINED_STATE,
            self::PAYMENT_STATUS_AWAITING_CUSTOMER => EcpOrderStates::DECLINED_STATE,
            self::PAYMENT_STATUS_EXPIRED => EcpOrderStates::DECLINED_STATE
        ];
    }

    public static function getEcpStatusByOrderStatus(string $orderStatus): ?string
    {
        $status = array_search($orderStatus, self::getStatusMap());
        return !empty($status) ? $status : null;
    }
}

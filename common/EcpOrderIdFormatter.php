<?php
declare(strict_types=1);

class EcpOrderIdFormatter
{
    /**
     * @param string $orderId
     * @param string $prefix
     * @return string
     */
    public static function addOrderPrefix(string $orderId, string $prefix): string
    {
        return $prefix . '&' . $_SERVER['SERVER_NAME'] . '&' . $orderId;
    }

    /**
     * @param string $orderId
     * @param string $prefix
     * @return mixed
     */
    public static function removeOrderPrefix(string $orderId, string $prefix): string
    {
        return preg_replace('/^' . $prefix . '&' . preg_quote($_SERVER['SERVER_NAME']) . '&/', '', $orderId);
    }
}
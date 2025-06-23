<?php

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShopLogger;

class EcpLogger
{
    /**
     * Log message to dev log file and PrestaShop logger
     * @param string $message
     * @param array $context
     */
    public static function log(string $message, array $context = []): void
    {
        $logFile = _PS_ROOT_DIR_ . '/logs/dev-' . date('Y-m-d') . '.log';
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;

        if (!empty($context)) {
            $logMessage .= ' - Context: ' . json_encode($context);
        }

        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);

        PrestaShopLogger::addLog($message . (!empty($context) ? ' - ' . json_encode($context) : ''));
    }
}

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
        $logDir = _PS_ROOT_DIR_ . '/logs';
        $logFile = $logDir . '/dev-' . date('Y-m-d') . '.log';
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;

        if (!empty($context)) {
            // Sanitize context to remove sensitive data
            $sanitizedContext = self::sanitizeContext($context);
            $logMessage .= ' - Context: ' . json_encode($sanitizedContext);
        }

        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Suppress warnings for file operations to prevent JSON corruption
        @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);

        $logMessageForPS = $message;
        if (!empty($context)) {
            $logMessageForPS .= ' - ' . json_encode(self::sanitizeContext($context));
        }
        PrestaShopLogger::addLog($logMessageForPS);
    }

    /**
     * Sanitize context data to remove sensitive information
     * @param array $context
     * @return array
     */
    private static function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password', 'token', 'key', 'secret', 'auth', 'session_id',
            'cookie', 'credit_card', 'cvv', 'ssn', 'email', 'phone',
            'address', 'ip', 'user_agent', 'referer'
        ];

        $sanitized = [];
        foreach ($context as $key => $value) {
            $keyLower = strtolower($key);
            $isSensitive = false;

            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains($keyLower, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            }
            elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeContext($value);
            }
            else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}

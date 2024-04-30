<?php
declare(strict_types=1);

class EcpSigner
{
    /**
     * @var string
     */
    private $secretKey;

    /**
     * @param $secretKey
     */
    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @param array $params
     * @param array $ignoreParamKeys
     * @param int $currentLevel
     * @param string $prefix
     * @return array
     */
    private function getParamsToSign(array $params, array $ignoreParamKeys = [], int $currentLevel = 1, string $prefix = ''): array
    {
        $paramsToSign = [];
        foreach ($params as $key => $value) {
            if ((in_array($key, $ignoreParamKeys) && $currentLevel == 1)) {
                continue;
            }
            $paramKey = ($prefix ? $prefix . ':' : '') . $key;
            if (is_array($value)) {
                if ($currentLevel >= 3) {
                    $paramsToSign[$paramKey] = (string)$paramKey.':';
                } else {
                    $subArray = $this->getParamsToSign($value, $ignoreParamKeys, $currentLevel + 1, $paramKey);
                    $paramsToSign = array_merge($paramsToSign, $subArray);
                }
            } else {
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                } else {
                    $value = (string)$value;
                }
                $paramsToSign[$paramKey] = (string)$paramKey.':'.$value;
            }
        }
        if ($currentLevel == 1) {
            ksort($paramsToSign, SORT_NATURAL);
        }
        return $paramsToSign;
    }

    /**
     * @param array $data
     * @return string
     */
    public function getSignature(array $data): string
    {
        $paramsToSign = $this->getParamsToSign($data);
        $stringToSign = $this->getStringToSign($paramsToSign);

        return base64_encode(hash_hmac('sha512', $stringToSign, $this->secretKey, true));
    }

    /**
     * @param array $paramsToSign
     * @return string
     */
    private function getStringToSign(array $paramsToSign): string
    {
        return implode(';', $paramsToSign);
    }

    /**
     * @param array $data
     * @return bool
     */
    public function checkSignature(array $data): bool
    {
        if (!array_key_exists('signature', $data)) {
            return false;
        }
        $signature = $data['signature'];
        unset($data['signature']);

        return $this->getSignature($data) === $signature;
    }
}
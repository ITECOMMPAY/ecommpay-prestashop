<?php

require_once __DIR__ . '/common/EcpHelper.php';

class Signer
{
    /**
     * @var bool
     */
    protected $isTest;

    /**
     * @var string|int
     */
    protected $projectId;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var string
     */
    protected $ppLanguage;

    /**
     * @var string
     */
    protected $ppCurrency;

    /**
     * @var string
     */
    protected $additionalParameters;

    /**
     * @var string
     */
    protected $baseUrl;

    const ECOMMPAY_TEST_PROJECT_ID = 112;
    const ECOMMPAY_TEST_SECRET_KEY = 'kHRhsQHHhOUHeD+rt4kgH7OZiwE=';

    const INTERFACE_TYPE = 17;

    const CMS_PREFIX = 'presta';

    /**
     * @return array
     */
    public static function getInterfaceType()
    {
        return [
            'id' => self::INTERFACE_TYPE,
        ];
    }

    /**
     * Signer constructor.
     * @param $isTest
     * @param $projectId
     * @param $secretKey
     * @param $ppLanguage
     * @param $ppCurrency
     * @param $additionalParameters
     * @param $baseUrl
     */
    public function __construct($isTest, $projectId, $secretKey, $ppLanguage, $ppCurrency, $additionalParameters, $baseUrl)
    {
        $this->isTest = $isTest;
        $this->projectId = $projectId;
        $this->secretKey = $secretKey;
        $this->ppLanguage = $ppLanguage;
        $this->ppCurrency = $ppCurrency;
        $this->additionalParameters = $additionalParameters;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Return PaymentPage host
     *
     * @return string
     */
    public static function getPaymentPageHost()
    {
        $host = getenv('PAYMENTPAGE_HOST');

        return \is_string($host) ? $host : 'paymentpage.ecommpay.com';
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $isTest = $this->isTest;
        $projectId = $this->projectId;
        $salt = $this->secretKey;
        $ppCurrency = $this->ppCurrency;
        $ppLanguage = $this->ppLanguage;
        $additionalParameters = $this->additionalParameters;

        return compact(
            'isTest',
            'projectId',
            'salt',
            'ppCurrency',
            'ppLanguage',
            'additionalParameters'
        );
    }

    /**
     * @return string
     */
    public function getCardRedirectUrl($cart)
    {
        $config = $this->getConfig();

        $urlData = $this->createUrlData($cart, $config);

        if (!empty($config['additionalParameters'])) {
            $additionalData = [];
            parse_str($config['additionalParameters'], $additionalData);
            $urlData = array_merge($urlData, $additionalData);
        }

        $urlData['signature'] = $this->signData($urlData, []);
        $urlArgs = http_build_query($urlData, '', '&');

        return sprintf('https://%s/payment?%s', self::getPaymentPageHost(), $urlArgs);
    }

    /**
     * @param $cart
     * @param array $config
     * @return array
     */
    protected function createUrlData($cart, array $config)
    {
        $paymentAmount = $cart->getOrderTotal() * 100;
        $orderId = Order::getOrderByCartId($cart->id);
        $paymentId = $orderId;

        if ($config['isTest']) {
            require_once(__DIR__ . '/common/EcpOrderIdFormatter.php');
            $paymentId = EcpOrderIdFormatter::addOrderPrefix($paymentId, self::CMS_PREFIX);
        }

        $urlParams = [
            'project_id' => $config['isTest'] ? self::ECOMMPAY_TEST_PROJECT_ID : $config['projectId'],
            'payment_amount' => $paymentAmount,
            'payment_id' => $paymentId,
            'payment_currency' => $config['ppCurrency'],
            'merchant_success_url' => EcpHelper::getSuccessUrl($cart, $config),
            'merchant_success_enabled' => 2,
            'merchant_fail_url' => EcpHelper::getFailUrl(),
            'merchant_success_enabled' => 2,
            'merchant_callback_url' => EcpHelper::getCallbackUrl(),
            'interface_type' => json_encode(self::getInterfaceType()),
        ];

        if (strtolower($config['ppLanguage']) !== 'default') {
            $urlParams['language_code'] = strtolower($config['ppLanguage']);
        }

        if($cart->id_customer){
            $customer = new Customer((int)$cart->id_customer);
            if ($customer && !$customer->isGuest()) {
                $urlParams['customer_id'] = $cart->id_customer;
            }
        }

        return $urlParams;
    }

    /**
     * Get parameters to sign
     * @param array $params
     * @param array $ignoreParamKeys
     * @param int $currentLevel
     * @param string $prefix
     * @return array
     */
    private function getParamsToSign(
        array $params,
        array $ignoreParamKeys = [],
        $currentLevel = 1,
        $prefix = ''
    )
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
                    $subArray = self::getParamsToSign($value, $ignoreParamKeys, $currentLevel + 1, $paramKey);
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

    private function signData(array $data, $skipParams) {
        $config = $this->getConfig();
        $paramsToSign = $this->getParamsToSign($data, $skipParams);
        $stringToSign = $this->getStringToSign($paramsToSign);
        $secretKey = $config['isTest'] ? self::ECOMMPAY_TEST_SECRET_KEY : $config['salt'];
        return base64_encode(hash_hmac('sha512', $stringToSign, $secretKey, true));
    }

    private function getStringToSign(array $paramsToSign)
    {
        return implode(';', $paramsToSign);
    }

    public function checkSignature(array $data) {
        $signature = $data['signature'];
        unset($data['signature']);
        return $this->signData($data, []) === $signature;
    }
}

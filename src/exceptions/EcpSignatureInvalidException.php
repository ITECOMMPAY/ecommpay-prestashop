<?php

declare(strict_types=1);

namespace Ecommpay\exceptions;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Exception;

class EcpSignatureInvalidException extends EcpBadRequestException
{
}

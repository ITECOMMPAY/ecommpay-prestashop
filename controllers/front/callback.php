<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Ecommpay\exceptions\EcpBadRequestException;
use Ecommpay\exceptions\EcpDataNotFound;
use Ecommpay\exceptions\EcpSignatureInvalidException;

/**
 * @since 1.5.0
 */
class EcommpayCallbackModuleFrontController extends ModuleFrontController
{
    public function postProcess(): void
    {
        $body = file_get_contents('php://input');
        $bodyJson = json_decode($body, true);

        if ($bodyJson === null) {
            $this->sendJsonResponse(['error' => 'Malformed callback data.'], 400);
        }

        try {
            $this->module->processCallback($bodyJson);
            $this->sendJsonResponse(['message' => 'OK']);
        } catch (EcpSignatureInvalidException $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 403);
        } catch (EcpDataNotFound $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 404);
        } catch (EcpBadRequestException $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method to send JSON response and exit.
     *
     * @param array $data The data to encode as JSON.
     * @param int $httpStatusCode The HTTP status code to send.
     */
    protected function sendJsonResponse(array $data, int $httpStatusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($httpStatusCode);
        echo json_encode($data);
        exit;
    }
}

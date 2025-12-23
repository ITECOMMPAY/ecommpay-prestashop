<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Controller for clearing payment declined message
 */
class EcommpayClearmessageModuleFrontController extends ModuleFrontController
{
    public function initContent(): void
    {
        parent::initContent();
        header('Content-Type: application/json');

        try {
            // Clear the payment declined message cookie
            if (isset($this->context->cookie->ecommpay_payment_declined)) {
                unset($this->context->cookie->ecommpay_payment_declined);
                $this->context->cookie->write();
                echo json_encode(['success' => true, 'message' => 'Message cleared']);
            } else {
                echo json_encode(['success' => true, 'message' => 'No message to clear']);
            }
            exit;
        } catch (Exception $e) {
            error_log('Error clearing payment declined message: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

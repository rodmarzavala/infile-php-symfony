<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\Studio\Http\Controllers\Api;

use InfilePhp\Core\FelConfig;
use InfilePhp\Core\Sat\Rtu;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;

final class HealthController
{
    public function __construct(
        private ?FelConfig $config = null
    ) {
    }

    public function index(): JsonResponse
    {
        $start = microtime(true);
        $success = false;
        $error = null;

        if (!$this->config) {
            return new JsonResponse(['success' => false, 'error' => 'Infile SDK not configured']);
        }

        $hasSignUser = $this->config->signUser !== '';
        $hasSignKey = $this->config->signKey !== '';
        $hasApiUser = $this->config->apiUser !== '';
        $hasApiKey = $this->config->apiKey !== '';
        $hasNit = $this->config->nit !== '';

        $credentialsValid = $hasSignUser && $hasSignKey && $hasApiUser && $hasApiKey && $hasNit;

        if ($credentialsValid) {
            try {
                Rtu::lookupNit($this->config->nit !== '' ? $this->config->nit : 'CF');
                $success = true;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Credenciales incompletas en la configuración';
        }

        $latency = round((microtime(true) - $start) * 1000);

        return new JsonResponse([
            'success' => true,
            'env' => [
                'sign_user' => $hasSignUser,
                'sign_key' => $hasSignKey,
                'api_user' => $hasApiUser,
                'api_key' => $hasApiKey,
                'nit' => $hasNit,
            ],
            'connection' => [
                'success' => $success,
                'latency_ms' => $latency,
                'error' => $error,
                'environment' => $this->config->environment->value,
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\Studio\Http\Middleware;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class StudioSecurityListener implements EventSubscriberInterface
{
    public function __construct(
        private ?AuthorizationCheckerInterface $authChecker = null,
        private ?CsrfTokenManagerInterface $csrfTokenManager = null,
        private string $environment = 'dev'
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/fel-studio')) {
            return;
        }

        // 1. Check Authorization (Role)
        if ($this->authChecker) {
            if (!$this->authChecker->isGranted('ROLE_FEL_STUDIO') && $this->environment !== 'dev') {
                $event->setResponse(new JsonResponse(['error' => 'Forbidden. Requires ROLE_FEL_STUDIO.'], 403));
                return;
            }
        } elseif ($this->environment !== 'dev') {
            // If security is not installed but we are not in dev, deny by default.
            $event->setResponse(new JsonResponse(['error' => 'Forbidden. Symfony Security component is required for production usage of FEL Studio.'], 403));
            return;
        }

        // 2. Check CSRF token for state-changing API requests
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            if ($this->csrfTokenManager) {
                $token = $request->headers->get('X-CSRF-TOKEN', '');
                if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('fel_studio', $token))) {
                    $event->setResponse(new JsonResponse(['error' => 'Invalid CSRF token'], 419));
                    return;
                }
            }
        }
    }
}

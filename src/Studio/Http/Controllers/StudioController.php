<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\Studio\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class StudioController
{
    public function __construct(
        private ?CsrfTokenManagerInterface $csrfTokenManager = null
    ) {
    }

    public function index(): Response
    {
        $indexPath = __DIR__ . '/../../../../Resources/public/studio-ui/index.html';

        if (!file_exists($indexPath)) {
            return new Response('FEL Studio UI not built. Please run "npm run build" in packages/studio-ui.', 500);
        }

        $html = file_get_contents($indexPath);

        $tokenHtml = '';
        if ($this->csrfTokenManager) {
            $token = $this->csrfTokenManager->getToken('fel_studio')->getValue();
            $tokenHtml = '<meta name="csrf-token" content="' . $token . '">' . "\n";
        }

        $html = str_replace(
            ['</head>', '/__FEL_STUDIO_BASE__/'],
            [$tokenHtml . '</head>', '/bundles/infilephp/studio-ui/'],
            $html
        );

        return new Response((string) $html);
    }
}

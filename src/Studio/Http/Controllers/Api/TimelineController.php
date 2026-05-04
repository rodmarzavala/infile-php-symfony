<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\Studio\Http\Controllers\Api;

use InfilePhp\Symfony\Studio\Storage\StudioRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

final class TimelineController
{
    public function index(StudioRepository $repository): JsonResponse
    {
        return new JsonResponse([
            'data' => $repository->getTimeline(),
        ]);
    }

    public function clear(StudioRepository $repository): JsonResponse
    {
        $repository->clear();

        return new JsonResponse(['status' => 'cleared']);
    }
}

<?php

namespace App\EventListener;

use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[Autoconfigure(tags: [['name' => 'kernel.event_listener', 'event' => 'kernel.exception']])]
class ValidationExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ValidationException) {
            $response = new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => $exception->getErrors(),
            ], Response::HTTP_BAD_REQUEST);

            $event->setResponse($response);
            return;
        }

        // You can add more general error handling here if needed,
        // or just let Symfony's default error handler take over.
        // For example, to return JSON for other HttpExceptions:
        if ($exception instanceof HttpExceptionInterface) {
            $response = new JsonResponse([
                'message' => $exception->getMessage(),
                'code' => $exception->getStatusCode(),
            ], $exception->getStatusCode());
            $event->setResponse($response);
        }
    }
}

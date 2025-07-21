<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // This is called when an unauthenticated user tries to access a protected resource.
        // For an API, we want to return a JSON 401 Unauthorized.
        return new JsonResponse(
            ['message' => 'Authentication required.'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}

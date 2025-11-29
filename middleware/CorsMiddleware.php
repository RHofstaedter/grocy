<?php

namespace Grocy\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CorsMiddleware
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function __invoke(Request $request, RequestHandler $requestHandler): Response
    {
        if ($request->getMethod() == 'OPTIONS') {
            $response = $this->responseFactory->createResponse(200);
        } else {
            $response = $requestHandler->handle($request);
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        $response = $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

        return $response->withHeader('Access-Control-Allow-Headers', '*');
    }
}

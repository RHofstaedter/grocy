<?php

namespace Grocy\Middleware;

use Grocy\Services\SessionService;
use DI\Container;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class SessionAuthMiddleware extends AuthMiddleware
{
    public function __construct(Container $container, ResponseFactoryInterface $responseFactory)
    {
        parent::__construct($container, $responseFactory);
    }

    public function authenticate(Request $request)
    {
        $sessionService = SessionService::getInstance();

        if (!isset($_COOKIE[SessionService::SESSION_COOKIE_NAME]) || !$sessionService->isValidSession($_COOKIE[SessionService::SESSION_COOKIE_NAME])) {
            return null;
        } else {
            return $sessionService->getUserBySessionKey($_COOKIE[SessionService::SESSION_COOKIE_NAME]);
        }
    }

    public static function processLogin(array $postParams): never
    {
        throw new Exception('Not implemented');
    }
}

<?php

namespace Grocy\Controllers;

use DI\Container;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Throwable;

class ExceptionController extends BaseApiController
{
    public function __construct(private readonly \Slim\App $app, Container $container)
    {
        parent::__construct($container);
    }

    public function __invoke(ServerRequestInterface $serverRequest, Throwable $throwable, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails, ?LoggerInterface $logger = null)
    {
        $response = $this->app->getResponseFactory()->createResponse();
        $isApiRoute = str_starts_with($serverRequest->getUri()->getPath(), '/api/');

        if (!defined('GROCY_AUTHENTICATED')) {
            define('GROCY_AUTHENTICATED', false);
        }

        if ($isApiRoute) {
            $status = 500;

            if ($throwable instanceof HttpException) {
                $status = $throwable->getCode();
            }

            $data = [
                'error_message' => $throwable->getMessage()
            ];

            if ($displayErrorDetails) {
                $data['error_details'] = [
                    'stack_trace' => $throwable->getTraceAsString(),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine()
                ];
            }

            return $this->apiResponse($response->withStatus($status)->withHeader('Content-Type', 'application/json'), $data);
        }

        if ($throwable instanceof HttpNotFoundException) {
            if (!defined('GROCY_AUTHENTICATED')) {
                define('GROCY_AUTHENTICATED', false);
            }

            return $this->renderPage($response->withStatus(404), 'errors/404', [
                'exception' => $throwable
            ]);
        }

        if ($throwable instanceof HttpForbiddenException) {
            return $this->renderPage($response->withStatus(403), 'errors/403', [
                'exception' => $throwable
            ]);
        }

        return $this->renderPage($response->withStatus(500), 'errors/500', [
            'exception' => $throwable,
            'systemInfo' => $this->getApplicationService()->getSystemInfo()
        ]);
    }
}

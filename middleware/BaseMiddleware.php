<?php

namespace Grocy\Middleware;

use Grocy\Services\ApplicationService;
use DI\Container;

class BaseMiddleware
{
    protected $ApplicationService;

    public function __construct(protected \DI\Container $AppContainer)
    {
        $this->ApplicationService = ApplicationService::getInstance();
    }
}

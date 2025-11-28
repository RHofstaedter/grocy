<?php

namespace Grocy\Controllers;

use Grocy\Services\SessionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController extends BaseController
{
    public function loginPage(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'login');
    }

    public function logout(Request $request, Response $response, array $args)
    {
        $this->getSessionService()->removeSession($_COOKIE[SessionService::SESSION_COOKIE_NAME]);
        return $response->withRedirect($this->AppContainer->get('UrlManager')->ConstructUrl('/'));
    }

    public function processLogin(Request $request, Response $response, array $args)
    {
        $authMiddlewareClass = GROCY_AUTH_CLASS;
        if ($authMiddlewareClass::processLogin($request->getParsedBody())) {
            return $response->withRedirect($this->AppContainer->get('UrlManager')->ConstructUrl('/'));
        }

        return $response->withRedirect($this->AppContainer->get('UrlManager')->ConstructUrl('/login?invalid=true'));
    }
}
